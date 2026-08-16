<?php
// vision_proxy.php - Gemini 視覺端到端代理（含安全檢查）
// 直接吃收據圖片 → Gemini 視覺模型 → 結構化 JSON。
// 僅在系統設定 gemini_vision_enabled = 1 時由前端呼叫；開啟後不走 OCR.space / DeepSeek。
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/llm_gemini.php';

header('Content-Type: application/json; charset=utf-8');

// Referer 檢查
if (!Security::validateReferer()) {
    http_response_code(403);
    logError('Vision Proxy - Invalid referer from: ' . ($_SERVER['HTTP_REFERER'] ?? 'unknown'));
    echo json_encode(['success' => false, 'error' => '無效的請求來源']);
    exit;
}

// Rate Limiting (10 requests per minute)
if (!Security::checkRateLimit(10, 60)) {
    http_response_code(429);
    logError('Vision Proxy - Rate limit exceeded from IP: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(['success' => false, 'error' => '請求過於頻繁']);
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '僅支援 POST 請求']);
    exit;
}

// =============== 用戶配額檢查 ===============
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDB();
        $userId = $_SESSION['user_id'];

        $quotaStmt = $pdo->prepare("SELECT quota_limit FROM users WHERE id = ?");
        $quotaStmt->execute([$userId]);
        $user = $quotaStmt->fetch();
        $quotaLimit = $user['quota_limit'] ?? 0;

        if ($quotaLimit > 0) {
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM receipts 
                WHERE user_id = ? 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
                AND MONTH(created_at) = MONTH(CURRENT_DATE())
            ");
            $countStmt->execute([$userId]);
            $result = $countStmt->fetch();
            $currentCount = $result['count'] ?? 0;

            if ($currentCount >= $quotaLimit) {
                http_response_code(429);
                logInfo("Vision Proxy - User quota exceeded: user_id=$userId, current=$currentCount, limit=$quotaLimit");
                echo json_encode([
                    'success' => false,
                    'error' => "已達本月配額上限（$quotaLimit 張）。本月已儲存 $currentCount 張，無法繼續處理。"
                ]);
                exit;
            }
        }
    } catch (Exception $e) {
        logError("Vision Proxy - Quota check error: " . $e->getMessage());
        // 配額檢查失敗不阻止處理
    }
}
// =============== 配額檢查結束 ===============

// 讀取請求 body
$request_body = file_get_contents('php://input');

if (empty($request_body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '缺少請求資料']);
    exit;
}

$request_data = json_decode($request_body, true);

if (!isset($request_data['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '缺少圖片資料']);
    exit;
}

$imageDataUrl = $request_data['image'];

// 解析 data URL：data:image/jpeg;base64,XXXX
if (!preg_match('/^data:([^;]+);base64,(.+)$/', $imageDataUrl, $m)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '圖片格式無效']);
    exit;
}
$mimeType = $m[1];
$imageBase64 = $m[2];

// 檢查 API Key 是否已設定
if (empty(GEMINI_API_KEY)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => '系統尚未設定 Gemini API 金鑰，請聯繫管理員']);
    exit;
}

$vision_prompt = <<<PROMPT
你是一個專業的收據解析器。請直接從提供的收據圖片中識別並提取：
- 日期（格式：YYYY-MM-DD）
- 時間（格式：HH:MM:SS，若無秒數可補 00）
- 公司名稱
- 購買物品摘要（最多 20 個繁體中文字，用逗號分隔主要品項）
- 支付方式（如：現金、信用卡、Visa、Master、支付寶、微信支付、Payme、八達通等）
- 總金額（純數字，保留小數，如 123.00）
- 總結（用小於 15 個繁體中文字總結購買內容）

## 總結欄位分類規則（重要）：
請根據圖片中購買物品進行**精準分類**，使用最具體的類別而非籠統描述。

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
1. **優先使用最具體的類別**：如圖片中為「香印提子、紅心奇異果」應總結為「水果」而非「超市購物」或「食品」
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
1. 如圖片無明確日期/時間，可嘗試從「參考號」(Ref. No.)、交易號或印表機時間推斷。
2. 所有輸出（包括公司名稱、摘要）必須為「繁體中文」。
3. 若某欄位無法推斷，留空字串 ""。
4. 嚴格按照以下 JSON Schema 輸出陣列（即使只有一張收據也包在陣列中）。

請直接輸出 JSON 陣列，不要有任何其他說明文字。
PROMPT;

try {
    $text = generateGeminiVision($vision_prompt, $mimeType, $imageBase64);
} catch (Exception $e) {
    http_response_code(502);
    logError('Vision Proxy - Gemini error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Gemini 視覺 API 錯誤：' . $e->getMessage(),
        'engine' => 'gemini-vision'
    ]);
    exit;
}

if (preg_match('/\[[\s\S]*\]/', $text, $matches)) {
    $json_str = $matches[0];
    $parsed = json_decode($json_str, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
        logInfo('Vision Proxy - Success from IP: ' . $_SERVER['REMOTE_ADDR']);
        echo json_encode(['success' => true, 'result' => $parsed, 'engine' => 'gemini-vision']);
        exit;
    }
}

http_response_code(502);
logError('Vision Proxy - Failed to parse Gemini response');
echo json_encode([
    'success' => false,
    'error' => '無法解析 Gemini 視覺回應為有效 JSON',
    'raw' => $text,
    'engine' => 'gemini-vision'
]);
