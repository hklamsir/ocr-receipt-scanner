<?php
// 讀取圖片 API（需驗證權限）
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$filename = $_GET['filename'] ?? '';
$username = $_SESSION['username'];

if (empty($filename)) {
    http_response_code(400);
    die('缺少檔名');
}

// 檢查檔名安全性（防止目錄穿越攻擊）
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    http_response_code(403);
    die('無效的檔名');
}

// 構建圖片路徑
$imagePath = __DIR__ . '/../receipts/' . $username . '/' . $filename;

if (!file_exists($imagePath)) {
    http_response_code(404);
    die('圖片不存在');
}

// 輸出圖片
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($imagePath));
readfile($imagePath);
