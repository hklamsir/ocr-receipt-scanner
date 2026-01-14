<?php
// proxy.php - DeepSeek API 代理（含安全檢查）
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/logger.php';

// 從設定檔載入 API Key
$DEEPSEEK_API_KEY = DEEPSEEK_API_KEY;

// Referer 檢查
if (!Security::validateReferer()) {
    http_response_code(403);
    logError('Invalid referer from: ' . ($_SERVER['HTTP_REFERER'] ?? 'unknown') . ' | IP: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(['success' => false, 'error' => '無效的請求來源']);
    exit;
}

// Rate Limiting (10 requests per minute)
if (!Security::checkRateLimit(10, 60)) {
    http_response_code(429);
    logError('Rate limit exceeded from IP: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(['success' => false, 'error' => '請求過於頻繁，請稍後再試']);
    exit;
}

logInfo('Request from IP: ' . $_SERVER['REMOTE_ADDR']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'extract') {
    echo json_encode(['success' => false, 'error' => '無效請求']);
    exit;
}

$ocr_text = $_POST['ocr_text'] ?? '';
if (empty(trim($ocr_text))) {
    echo json_encode(['success' => false, 'error' => '無 OCR 文字內容']);
    exit;
}

$prompt = <<<PROMPT
你是一個專業的收據解析器。從以下OCR文字中提取：
- 日期（格式：YYYY-MM-DD）
- 時間（格式：HH:MM:SS，若無秒數可補 00）
- 公司名稱
- 購買物品摘要（最多 20 個繁體中文字，用逗號分隔主要品項）
- 支付方式（如：現金、信用卡、Visa、Master、支付寶、微信支付、Payme、八達通等）
- 總金額（純數字，保留小數，如 123.00）
- 總結（用小於 15 個繁體中文字總結購買內容）

## 總結欄位分類規則（重要）：
請根據購買物品進行**精準分類**，使用最具體的類別而非籠統描述。

### 常見類別參考：
- **食材類**：水果、蔬菜、肉類、海鮮、蛋奶類、米麵糧油、調味料
- **飲食類**：餐飲消費、飲料、咖啡茶飲、麵包糕點、零食甜點
- **日用品**：清潔用品、衛浴用品、紙品、洗衣用品
- **個人護理**：化妝品、保養品、個人衛生用品
- **醫療保健**：藥品、保健食品、醫療用品
- **交通**：交通費用、加油、停車費
- **娛樂**：娛樂消費、電影、遊戲
- **服飾**：服裝、鞋類、配件
- **其他**：文具、書籍、家居用品、電子產品、寵物用品

### 分類原則：
1. **優先使用最具體的類別**：如購買「香印提子、紅心奇異果」應總結為「水果」而非「超市購物」或「食品」
2. **單一主類**：若商品屬同一類別（如都是水果），直接使用該類別
3. **多類別處理**：
   - 若有明顯主類（佔比>60%），使用主類別（如「水果為主」）
   - 若多類且無明顯主類，使用「混合購物」或「日用品購物」
4. **特殊場景**：
   - 便利店早餐→「餐飲消費」
   - 超市買菜→根據主要品項（如「蔬菜」、「肉類」等）
   - 藥房購買→「藥品」或「保健品」
   - 加油站→「加油」
   - 交通卡增值→「交通費用」

## 其他規則：
1. 如無明確日期/時間，可嘗試從「參考號」(Ref. No.)、交易號或印表機時間推斷。
2. 所有輸出（包括公司名稱、摘要）必須為「繁體中文」。
3. 若某欄位無法推斷，留空字串 ""。
4. 嚴格按照以下 JSON Schema 輸出陣列（即使只有一張收據也包在陣列中）。

OCR 文字內容：
{$ocr_text}

請直接輸出 JSON 陣列，不要有任何其他說明文字。
PROMPT;

$payload = [
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => '你是一個嚴格遵守指示的助手，只輸出純粹的 JSON。'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.3,
    'max_tokens' => 2048
];

$ch = curl_init('https://api.deepseek.com/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $DEEPSEEK_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 90);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode([
        'success' => false,
        'error' => 'DeepSeek API 錯誤 (HTTP ' . $http_code . ')',
        'raw' => $response
    ]);
    exit;
}

$resp = json_decode($response, true);
$content = $resp['choices'][0]['message']['content'] ?? '';

if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
    $json_str = $matches[0];
    $parsed = json_decode($json_str, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
        echo json_encode(['success' => true, 'result' => $parsed]);
        exit;
    }
}

echo json_encode([
    'success' => false,
    'error' => '無法解析為有效 JSON',
    'raw' => $content
]);
?>