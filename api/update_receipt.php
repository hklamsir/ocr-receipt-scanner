<?php
// 更新單據 API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf_check.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/api_response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('僅支援 POST 請求', 405);
}

// 讀取 JSON 資料
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    ApiResponse::error('無效的請求資料', 400);
}

$receiptId = (int) $data['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = getDB();

    // 驗證該單據屬於當前用戶
    $stmt = $pdo->prepare("SELECT id FROM receipts WHERE id = ? AND user_id = ?");
    $stmt->execute([$receiptId, $userId]);
    if (!$stmt->fetch()) {
        ApiResponse::error('無權編輯此單據', 403);
    }

    // 允許更新的欄位
    $allowedFields = [
        'receipt_date' => 'date',
        'receipt_time' => 'time',
        'company_name' => 'company',
        'items_summary' => 'items',
        'payment_method' => 'payment',
        'total_amount' => 'amount',
        'summary' => 'summary'
    ];

    $updates = [];
    $params = [];

    // 欄位長度限制
    $fieldLimits = [
        'company' => 50,
        'payment' => 12,
        'summary' => 15,
        'items' => 200
    ];

    foreach ($allowedFields as $dbField => $inputField) {
        if (array_key_exists($inputField, $data)) {
            $value = $data[$inputField];

            // 長度驗證
            if (isset($fieldLimits[$inputField]) && mb_strlen($value, 'UTF-8') > $fieldLimits[$inputField]) {
                ApiResponse::error("欄位 {$inputField} 超過最大長度 {$fieldLimits[$inputField]} 字", 400);
            }

            $updates[] = "$dbField = ?";
            // 空字串轉為 null
            $params[] = ($value === '' || $value === null) ? null : $value;
        }
    }

    if (empty($updates)) {
        ApiResponse::success([], '無欄位需要更新');
    }

    $params[] = $receiptId;
    $sql = "UPDATE receipts SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    logInfo("User {$_SESSION['username']} updated receipt $receiptId");

    ApiResponse::success();

} catch (PDOException $e) {
    logError("Update receipt error: " . $e->getMessage());
    ApiResponse::error('更新失敗', 500);
}
