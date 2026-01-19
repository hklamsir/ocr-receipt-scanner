<?php
// 系統健康檢查 API（Admin）
// 完全移除可能被 InfinityFree 禁用的函數

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

$checks = [];

// 1. 資料庫連線檢查
try {
    require_once __DIR__ . '/../../includes/db.php';
    $pdo = getDB();
    $stmt = $pdo->query("SELECT 1");
    $checks[] = [
        'name' => '資料庫連線',
        'status' => 'ok',
        'message' => '連線正常'
    ];
} catch (Exception $e) {
    $pdo = null;
    $checks[] = [
        'name' => '資料庫連線',
        'status' => 'error',
        'message' => '連線失敗'
    ];
}

// 2. 必要目錄檢查
$directories = [
    'receipts' => __DIR__ . '/../../receipts',
    'tmp' => __DIR__ . '/../../tmp',
    'config' => __DIR__ . '/../../config'
];

foreach ($directories as $name => $path) {
    $exists = @is_dir($path);
    $writable = $exists && @is_writable($path);

    $checks[] = [
        'name' => "目錄: {$name}",
        'status' => $writable ? 'ok' : ($exists ? 'warning' : 'error'),
        'message' => $writable ? '存在且可寫入' : ($exists ? '存在但不可寫入' : '目錄不存在')
    ];
}

// 3. 設定檔檢查
$configFile = __DIR__ . '/../../config/secret.php';
if (@file_exists($configFile)) {
    $checks[] = [
        'name' => '設定檔',
        'status' => 'ok',
        'message' => '設定檔存在'
    ];
} else {
    $checks[] = [
        'name' => '設定檔',
        'status' => 'error',
        'message' => 'secret.php 不存在'
    ];
}

// 4. PHP 版本檢查
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$checks[] = [
    'name' => 'PHP 版本',
    'status' => $phpOk ? 'ok' : 'warning',
    'message' => "PHP {$phpVersion}"
];

// 5. PHP 擴展檢查
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}
$checks[] = [
    'name' => 'PHP 擴展',
    'status' => empty($missingExtensions) ? 'ok' : 'error',
    'message' => empty($missingExtensions)
        ? '必要擴展已載入'
        : '缺少擴展: ' . implode(', ', $missingExtensions)
];

// 6. 磁碟空間 - 跳過（InfinityFree 可能禁用）
$checks[] = [
    'name' => '磁碟空間',
    'status' => 'unknown',
    'message' => '無法取得（主機限制）'
];

// 7. 資料庫表檢查
if ($pdo) {
    try {
        $requiredTables = ['users', 'receipts', 'tags', 'receipt_tags'];
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $missingTables = array_diff($requiredTables, $existingTables);
        $checks[] = [
            'name' => '資料庫表',
            'status' => empty($missingTables) ? 'ok' : 'error',
            'message' => empty($missingTables)
                ? '核心表存在'
                : '缺少表: ' . implode(', ', $missingTables)
        ];

        // 檢查新增的管理表
        $adminTables = ['system_stats', 'user_activity_logs', 'system_settings', 'login_attempts', 'ip_blocklist', 'active_sessions', 'announcements'];
        $missingAdminTables = array_diff($adminTables, $existingTables);
        $checks[] = [
            'name' => '管理功能表',
            'status' => empty($missingAdminTables) ? 'ok' : 'warning',
            'message' => empty($missingAdminTables)
                ? '所有管理表存在'
                : '缺少表: ' . implode(', ', $missingAdminTables)
        ];
    } catch (Exception $e) {
        $checks[] = [
            'name' => '資料庫表',
            'status' => 'unknown',
            'message' => '無法檢查'
        ];
    }
}

// 計算整體狀態
$hasError = false;
$hasWarning = false;
foreach ($checks as $check) {
    if ($check['status'] === 'error')
        $hasError = true;
    if ($check['status'] === 'warning')
        $hasWarning = true;
}

$overallStatus = $hasError ? 'error' : ($hasWarning ? 'warning' : 'ok');
$overallMessage = $hasError ? '有錯誤需要處理' : ($hasWarning ? '有警告需要注意' : '系統運作正常');

ApiResponse::success([
    'overall' => [
        'status' => $overallStatus,
        'message' => $overallMessage
    ],
    'checks' => $checks,
    'server_time' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
