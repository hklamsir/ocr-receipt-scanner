<?php
// includes/llm_gemini.php
//
// Gemini LLM 封裝層：移植 live-translator 的 FALLBACK_MODELS 模型級降級狀態機，
// 供 proxy.php（文字 LLM）與 vision_proxy.php（視覺端到端）共用。
//
// 設計要點（與計劃書一致）：
// - 「降級」= Gemini 模型級降級（主力 → 備援清單），不是 deepseek↔gemini 互備。
// - 文字與視覺模式共用同一份 FALLBACK_MODELS 降級清單。
// - 外部呼叫只需使用 generateGeminiText() / generateGeminiVision()，失敗時拋 Exception。

if (!defined('GEMINI_API_KEY')) {
    // 防 require 順序問題：config 尚未載入時補載（會一併定義 LLM_PROVIDER 等常數）。
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    }
}

// ============================================================
// 模型 Fallback 配置
// ============================================================

/** 模型優先級清單（依使用順序），均為現有模型（GA / Preview） */
define('FALLBACK_MODELS', [
    'gemini-3.5-flash-lite',   // 主力
    'gemini-3.1-flash-lite',   // 備援 1
    'gemini-3.5-flash',        // 備援 2
    'gemini-3.6-flash'         // 備援 3（GA 2026/7/21）
]);

/** 每層模型的 cURL 超時時間（秒），避免總執行時間過長 */
define('FALLBACK_TIMEOUTS', [15, 8, 4, 1]);

/** Fallback 狀態文件路徑（置於 includes/ 下，需可寫） */
define('FALLBACK_STATE_FILE', __DIR__ . '/gemini_fallback_state.json');

/** 429 RPM/TPM 冷卻時間（秒） */
define('FALLBACK_COOLDOWN', 120);

/** 429 RPD（每日配額）冷卻時間（秒） */
define('FALLBACK_COOLDOWN_RPD', 3600);

/** 最大重試次數（含首次嘗試） */
define('FALLBACK_MAX_RETRIES', 4);

/** 指數退避基礎等待（秒） */
define('FALLBACK_BASE_BACKOFF', 2);

/** 總請求時間上限（秒），需低於主機 PHP 執行限制 */
define('FALLBACK_TOTAL_TIMEOUT', 28);

/** 非主力模型恢復主力模型的等待時間（秒） */
define('FALLBACK_RESTORE_INTERVAL', 300);

/**
 * 構建 Gemini API URL（動態模型名）
 */
function build_gemini_url(string $model): string
{
    return 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
}

// ============================================================
// Fallback 狀態管理函數
// ============================================================

/**
 * 讀取 fallback 狀態文件
 * @return array 狀態數組
 */
function read_fallback_state(): array
{
    $default = [
        'active_model_index' => 0,
        'blocked_until' => [],
        'primary_restore_at' => null,
        'last_request_at' => 0,
        'consecutive_failures' => 0
    ];

    foreach (FALLBACK_MODELS as $model) {
        $default['blocked_until'][$model] = null;
    }

    if (!file_exists(FALLBACK_STATE_FILE)) {
        return $default;
    }

    $fp = @fopen(FALLBACK_STATE_FILE, 'r');
    if (!$fp) {
        return $default;
    }

    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        return $default;
    }

    $content = '';
    while (!feof($fp)) {
        $content .= fread($fp, 8192);
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    if (empty(trim($content))) {
        return $default;
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        @unlink(FALLBACK_STATE_FILE);
        return $default;
    }

    return array_merge($default, $data);
}

/**
 * 寫入 fallback 狀態文件
 * @param array $state 狀態數組
 * @return bool 是否寫入成功
 */
function write_fallback_state(array $state): bool
{
    $fp = @fopen(FALLBACK_STATE_FILE, 'c+');
    if (!$fp) {
        return false;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    ftruncate($fp, 0);
    rewind($fp);
    $written = fwrite($fp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);

    return $written !== false;
}

/**
 * 獲取下一個可用模型的索引
 * @param array $state 當前狀態
 * @return int|null 可用模型索引，null 表示全部不可用
 */
function get_available_model_index(array $state): ?int
{
    $now = time();
    $startIndex = $state['active_model_index'];
    $modelCount = count(FALLBACK_MODELS);

    // 檢查是否需要恢復到主力模型
    if ($state['primary_restore_at'] !== null && $now >= $state['primary_restore_at']) {
        return 0;
    }

    // 從當前索引開始查找
    for ($i = $startIndex; $i < $modelCount; $i++) {
        $model = FALLBACK_MODELS[$i];
        $blockedUntil = $state['blocked_until'][$model] ?? null;
        if ($blockedUntil === null || $now >= $blockedUntil) {
            return $i;
        }
    }

    // 從開頭繼續查找
    for ($i = 0; $i < $startIndex; $i++) {
        $model = FALLBACK_MODELS[$i];
        $blockedUntil = $state['blocked_until'][$model] ?? null;
        if ($blockedUntil === null || $now >= $blockedUntil) {
            return $i;
        }
    }

    return null;
}

/**
 * 判斷 429 錯誤是否爲每日配額（RPD）耗盡
 */
function is_rpd_error(string $responseBody): bool
{
    $data = json_decode($responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    $message = $data['error']['message'] ?? '';
    return stripos($message, 'daily') !== false
        || stripos($message, 'RPD') !== false
        || stripos($message, 'per day') !== false;
}

/**
 * 退避等待（指數退避 + 隨機抖動）
 *
 * 在每次重試前加入隨機抖動，可避免多個並發請求在失敗後於同一時刻同步重試
 * （「驚羣效應」），降低對 Gemini 配額的瞬時衝擊並減少彼此覆寫 fallback 狀態。
 *
 * @param int $seconds 基礎等待秒數（建議已為指數增長值）
 */
function backoff_sleep(int $seconds): void
{
    $seconds = max(0, (int)$seconds);
    if ($seconds <= 0) {
        return;
    }
    $jitter = random_int(0, max(1, (int)($seconds / 2)));
    @sleep($seconds + $jitter);
}

/**
 * 調用 Gemini API
 * @param string $model 模型名
 * @param array $requestBody 請求體
 * @param int $timeout cURL 超時秒數
 * @return array { http_code, body, curl_error, curl_errno }
 */
function call_gemini_api(string $model, array $requestBody, int $timeout): array
{
    $url = build_gemini_url($model);

    $ch = curl_init($url);
    if ($ch === false) {
        return ['http_code' => 0, 'body' => null, 'curl_error' => 'Failed to initialize cURL.', 'curl_errno' => -1];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($requestBody, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'body' => $response,
        'curl_error' => $curlError,
        'curl_errno' => $curlErrno
    ];
}

// ============================================================
// 高階封裝
// ============================================================

/**
 * 執行 Gemini 呼叫並跑 FALLBACK_MODELS 模型級降級。
 *
 * @param array $requestBody 已組好的 Gemini 請求體
 * @return string 解析出的文字內容（candidates[0].content.parts[0].text）
 * @throws Exception 所有模型皆不可用、API Key 未設定、回應無法解析或為空時拋出
 */
function callGeminiWithFallback(array $requestBody): string
{
    if (empty(GEMINI_API_KEY)) {
        throw new Exception('系統尚未設定 Gemini API 金鑰，請聯繫管理員');
    }

    $startTime = time();
    $maxEndTime = $startTime + FALLBACK_TOTAL_TIMEOUT;
    $state = read_fallback_state();
    $state['last_request_at'] = $startTime;

    $lastError = null;
    $lastErrorModel = '';

    for ($attempt = 0; $attempt < FALLBACK_MAX_RETRIES; $attempt++) {
        $elapsed = time() - $startTime;
        if ($elapsed >= FALLBACK_TOTAL_TIMEOUT) {
            $lastError = 'Total execution time (' . $elapsed . 's) exceeded limit (' . FALLBACK_TOTAL_TIMEOUT . 's).';
            break;
        }

        $modelIndex = get_available_model_index($state);
        if ($modelIndex === null) {
            // 全部模型被封鎖，計算退避時間
            $backoff = FALLBACK_BASE_BACKOFF * pow(2, $state['consecutive_failures']);
            $backoff = min($backoff, 30);
            if (($elapsed + $backoff) < FALLBACK_TOTAL_TIMEOUT) {
                backoff_sleep($backoff);
                continue;
            }
            $lastError = 'All models are rate-limited. Please wait for cooldown.';
            break;
        }

        $model = FALLBACK_MODELS[$modelIndex];
        $timeout = FALLBACK_TIMEOUTS[$modelIndex] ?? 15;
        $remaining = $maxEndTime - time();
        if ($timeout > $remaining) {
            $timeout = max(1, (int)$remaining);
        }

        $result = call_gemini_api($model, $requestBody, $timeout);
        $lastErrorModel = $model;

        // --- cURL 錯誤處理 ---
        if ($result['curl_errno'] !== 0) {
            $state['blocked_until'][$model] = time() + 60;
            $state['consecutive_failures']++;
            $state['active_model_index'] = ($modelIndex + 1) % count(FALLBACK_MODELS);
            write_fallback_state($state);
            $lastError = 'cURL error on ' . $model . ': ' . $result['curl_error'];
            backoff_sleep(FALLBACK_BASE_BACKOFF);
            continue;
        }

        // --- 成功響應 ---
        if ($result['http_code'] === 200) {
            $state['consecutive_failures'] = 0;
            $state['blocked_until'][$model] = null;

            if ($modelIndex === 0) {
                // 主力模型成功 → 完全重置
                $state['active_model_index'] = 0;
                $state['primary_restore_at'] = null;
                foreach (FALLBACK_MODELS as $m) {
                    $state['blocked_until'][$m] = null;
                }
            } else {
                // 備援模型成功 → 標記恢復計時器
                $state['active_model_index'] = $modelIndex;
                $state['primary_restore_at'] = time() + FALLBACK_RESTORE_INTERVAL;
            }
            write_fallback_state($state);

            $data = json_decode($result['body'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('無法解析 Gemini API 回應（模型 ' . $model . '）');
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (empty(trim($text))) {
                throw new Exception('Gemini 回應為空（模型 ' . $model . '）');
            }
            return $text;
        }

        // --- 429 速率限制 ---
        if ($result['http_code'] === 429) {
            $cooldown = is_rpd_error($result['body']) ? FALLBACK_COOLDOWN_RPD : FALLBACK_COOLDOWN;
            $state['blocked_until'][$model] = time() + $cooldown;
            $state['consecutive_failures']++;
            $state['active_model_index'] = ($modelIndex + 1) % count(FALLBACK_MODELS);
            write_fallback_state($state);
            $lastError = 'Rate limited on ' . $model . ' (HTTP 429)';

            $backoff = FALLBACK_BASE_BACKOFF * pow(2, $state['consecutive_failures'] - 1);
            if ($backoff > 0) {
                backoff_sleep(min($backoff, 10));
            }
            continue;
        }

        // --- 4xx 客戶端錯誤（除 429 外均不重試） ---
        // 400/401/403/422 等表示請求本身有問題，或憑證/權限不足，切換模型無法解決。
        if ($result['http_code'] >= 400 && $result['http_code'] < 500) {
            $errorData = json_decode($result['body'], true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown client error';
            throw new Exception('Gemini 拒絕請求 (' . $result['http_code'] . '): ' . $errorMsg . ' [model=' . $model . ']');
        }

        // --- 5xx 服務端錯誤（短暫冷卻後重試下一個） ---
        if ($result['http_code'] >= 500) {
            $state['blocked_until'][$model] = time() + 60;
            $state['consecutive_failures']++;
            $state['active_model_index'] = ($modelIndex + 1) % count(FALLBACK_MODELS);
            write_fallback_state($state);
        }

        $errorData = json_decode($result['body'], true);
        $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
        $lastError = 'HTTP ' . $result['http_code'] . ' on ' . $model . ': ' . $errorMsg;
    }

    throw new Exception('所有 Gemini 模型皆不可用：' . ($lastError ?? 'No available model.') . '（最後嘗試模型：' . ($lastErrorModel ?: '無') . '）');
}

/**
 * 文字 LLM 呼叫（吃 OCR 文字 → 出結構化 JSON）。
 *
 * @param string $systemPrompt 系統指令
 * @param string $userPrompt 使用者內容（含收據 OCR 文字）
 * @return string 模型回傳文字（應為 JSON 陣列字串）
 * @throws Exception
 */
function generateGeminiText(string $systemPrompt, string $userPrompt): string
{
    $requestBody = [
        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 2048,
            'responseMimeType' => 'application/json'
        ]
    ];
    return callGeminiWithFallback($requestBody);
}

/**
 * 視覺端到端呼叫（直接吃圖片 → 出結構化 JSON）。
 *
 * @param string $promptText 視覺版收據解析提示詞（直接讀圖、輸出相同 JSON Schema）
 * @param string $mimeType 圖片 MIME（如 image/jpeg）
 * @param string $base64 圖片 Base64（不含 data: 前綴）
 * @return string 模型回傳文字（應為 JSON 陣列字串）
 * @throws Exception
 */
function generateGeminiVision(string $promptText, string $mimeType, string $base64): string
{
    $requestBody = [
        'contents' => [['parts' => [
            ['text' => $promptText],
            ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]]
        ]]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 2048,
            'responseMimeType' => 'application/json'
        ]
    ];
    return callGeminiWithFallback($requestBody);
}
