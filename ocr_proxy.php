<?php
// ocr_proxy.php - OCR.space API 直接實現（不依賴 Google Apps Script）
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/logger.php';

header('Content-Type: application/json; charset=utf-8');

// Referer 檢查
if (!Security::validateReferer()) {
    http_response_code(403);
    logError('OCR Proxy - Invalid referer from: ' . ($_SERVER['HTTP_REFERER'] ?? 'unknown'));
    echo json_encode(['success' => false, 'error' => '無效的請求來源']);
    exit;
}

// Rate Limiting (10 requests per minute for OCR)
if (!Security::checkRateLimit(10, 60)) {
    http_response_code(429);
    logError('OCR Proxy - Rate limit exceeded from IP: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(['success' => false, 'error' => '請求過於頻繁']);
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '僅支援 POST 請求']);
    exit;
}

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

$imageBase64 = $request_data['image'];

// 檢查 API Key 是否已設定
if (!defined('OCR_API_KEY') || empty(OCR_API_KEY)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => '系統尚未設定 OCR.space API 金鑰，請聯繫管理員']);
    exit;
}

// 取得設定的引擎（預設為 2）
$preferredEngine = defined('OCR_ENGINE') ? OCR_ENGINE : '2';

// ===== 第一次嘗試: 使用設定的引擎 =====
$result = callOcrSpace($imageBase64, $preferredEngine, $preferredEngine === '2' ? 'auto' : 'cht');

if ($result['success']) {
    logInfo('OCR Proxy - Success with Engine ' . $preferredEngine . ' from IP: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(['text' => $result['text'], 'engine' => (int) $preferredEngine]);
    exit;
}

// ===== 降級方案: 使用另一個引擎 =====
$fallbackEngine = $preferredEngine === '2' ? '1' : '2';
$result = callOcrSpace($imageBase64, $fallbackEngine, $fallbackEngine === '2' ? 'auto' : 'cht');

if ($result['success']) {
    logInfo('OCR Proxy - Success with Engine ' . $fallbackEngine . ' (fallback) from IP: ' . $_SERVER['REMOTE_ADDR']);
    echo json_encode(['text' => $result['text'], 'engine' => (int) $fallbackEngine]);
    exit;
}

// ===== 兩個引擎都失敗 =====
http_response_code(500);
logError('OCR Proxy - Both engines failed: ' . ($result['error'] ?? 'unknown'));
echo json_encode([
    'error' => 'OCR 引擎無法使用',
    'detail' => $result['error'] ?? 'unknown'
]);

/**
 * 調用 OCR.space API
 */
function callOcrSpace($base64Image, $engine, $language)
{
    $apiKey = OCR_API_KEY;
    $apiUrl = 'https://api.ocr.space/parse/image';

    // 準備 POST 資料
    $postData = [
        'apikey' => $apiKey,
        'base64Image' => $base64Image,
        'language' => $language,
        'OCREngine' => $engine,
        'detectOrientation' => 'true',
        'scale' => 'true',
        'isTable' => 'true'
    ];

    // 使用 cURL 調用 API
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'cURL error: ' . $curl_error];
    }

    $json = json_decode($response, true);

    if (!$json) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }

    if (isset($json['IsErroredOnProcessing']) && $json['IsErroredOnProcessing']) {
        return ['success' => false, 'error' => $json['ErrorMessage'][0] ?? 'OCR processing error'];
    }

    // 組合所有頁面的文字
    $text = '';
    if (isset($json['ParsedResults']) && is_array($json['ParsedResults'])) {
        foreach ($json['ParsedResults'] as $result) {
            $text .= $result['ParsedText'] . "\n";
        }
    }

    $text = trim($text);

    if (empty($text)) {
        return ['success' => false, 'error' => 'No text extracted'];
    }

    return ['success' => true, 'text' => $text];
}
