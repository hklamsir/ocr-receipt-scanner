<?php
// 系統健康檢查 API（Admin）

require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

// 將所有邏輯包裝在 try-catch 中
try {
    $checks = [];

    // 1. 資料庫連線檢查
    $pdo = null;
    try {
        require_once __DIR__ . '/../../includes/db.php';
        $pdo = getDB();
        $stmt = $pdo->query("SELECT 1");
        $checks['database'] = [
            'name' => '資料庫連線',
            'status' => 'ok',
            'message' => '連線正常'
        ];
    } catch (Exception $e) {
        $checks['database'] = [
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
        try {
            $exists = @is_dir($path);
            $writable = $exists && @is_writable($path);

            $checks['dir_' . $name] = [
                'name' => "目錄: {$name}",
                'status' => $writable ? 'ok' : ($exists ? 'warning' : 'error'),
                'message' => $writable ? '存在且可寫入' : ($exists ? '存在但不可寫入' : '目錄不存在')
            ];
        } catch (Exception $e) {
            $checks['dir_' . $name] = [
                'name' => "目錄: {$name}",
                'status' => 'unknown',
                'message' => '無法檢查'
            ];
        }
    }

    // 3. 設定檔檢查
    $configFile = __DIR__ . '/../../config/secret.php';
    try {
        if (file_exists($configFile)) {
            $config = @include $configFile;
            $checks['config'] = [
                'name' => '設定檔',
                'status' => is_array($config) ? 'ok' : 'error',
                'message' => is_array($config) ? '設定檔載入正常' : '設定檔格式錯誤'
            ];

            // 檢查必要的設定項
            if (is_array($config)) {
                $requiredKeys = ['db_host', 'db_name', 'db_user', 'deepseek_api_key'];
                $missingKeys = [];
                foreach ($requiredKeys as $key) {
                    if (empty($config[$key])) {
                        $missingKeys[] = $key;
                    }
                }
                if (!empty($missingKeys)) {
                    $checks['config_keys'] = [
                        'name' => '必要設定項',
                        'status' => 'warning',
                        'message' => '缺少設定: ' . implode(', ', $missingKeys)
                    ];
                }
            }
        } else {
            $checks['config'] = [
                'name' => '設定檔',
                'status' => 'error',
                'message' => 'secret.php 不存在'
            ];
        }
    } catch (Exception $e) {
        $checks['config'] = [
            'name' => '設定檔',
            'status' => 'unknown',
            'message' => '無法檢查設定檔'
        ];
    }

    // 4. PHP 版本檢查
    $phpVersion = PHP_VERSION;
    $phpOk = version_compare($phpVersion, '7.4.0', '>=');
    $checks['php_version'] = [
        'name' => 'PHP 版本',
        'status' => $phpOk ? 'ok' : 'warning',
        'message' => "PHP {$phpVersion}" . ($phpOk ? '' : ' (建議 7.4+)')
    ];

    // 5. PHP 擴展檢查
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    $missingExtensions = [];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    // GD 是可選的
    if (!extension_loaded('gd')) {
        $checks['gd_extension'] = [
            'name' => 'GD 擴展',
            'status' => 'warning',
            'message' => 'GD 擴展未載入（圖片處理可能受影響）'
        ];
    }
    $checks['php_extensions'] = [
        'name' => 'PHP 擴展',
        'status' => empty($missingExtensions) ? 'ok' : 'error',
        'message' => empty($missingExtensions)
            ? '必要擴展已載入'
            : '缺少擴展: ' . implode(', ', $missingExtensions)
    ];

    // 6. 磁碟空間檢查（InfinityFree 可能無法取得）
    try {
        $diskFree = @disk_free_space(__DIR__ . '/../../');
        $diskTotal = @disk_total_space(__DIR__ . '/../../');
        if ($diskFree !== false && $diskTotal !== false && $diskTotal > 0) {
            $diskUsedPercent = round((1 - $diskFree / $diskTotal) * 100, 1);
            $diskFreeGB = round($diskFree / 1024 / 1024 / 1024, 2);

            $checks['disk_space'] = [
                'name' => '磁碟空間',
                'status' => $diskUsedPercent < 90 ? 'ok' : 'warning',
                'message' => "剩餘 {$diskFreeGB} GB ({$diskUsedPercent}% 已使用)"
            ];
        } else {
            $checks['disk_space'] = [
                'name' => '磁碟空間',
                'status' => 'unknown',
                'message' => '無法取得磁碟資訊（主機限制）'
            ];
        }
    } catch (Exception $e) {
        $checks['disk_space'] = [
            'name' => '磁碟空間',
            'status' => 'unknown',
            'message' => '無法取得磁碟資訊'
        ];
    }

    // 7. 資料庫表檢查
    if ($pdo) {
        try {
            $requiredTables = ['users', 'receipts', 'tags', 'receipt_tags'];
            $stmt = $pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $missingTables = array_diff($requiredTables, $existingTables);
            $checks['db_tables'] = [
                'name' => '資料庫表',
                'status' => empty($missingTables) ? 'ok' : 'error',
                'message' => empty($missingTables)
                    ? '核心表存在'
                    : '缺少表: ' . implode(', ', $missingTables)
            ];

            // 檢查新增的管理表
            $adminTables = ['system_stats', 'user_activity_logs', 'system_settings', 'login_attempts', 'ip_blocklist', 'active_sessions', 'announcements'];
            $missingAdminTables = array_diff($adminTables, $existingTables);
            $checks['admin_tables'] = [
                'name' => '管理功能表',
                'status' => empty($missingAdminTables) ? 'ok' : 'warning',
                'message' => empty($missingAdminTables)
                    ? '所有管理表存在'
                    : '缺少表: ' . implode(', ', $missingAdminTables) . '（請執行 admin_features.sql）'
            ];
        } catch (Exception $e) {
            $checks['db_tables'] = [
                'name' => '資料庫表',
                'status' => 'unknown',
                'message' => '無法檢查資料庫表'
            ];
        }
    }

    // 計算整體狀態
    $hasError = false;
    $hasWarning = false;
    foreach ($checks as $check) {
        if (isset($check['status'])) {
            if ($check['status'] === 'error')
                $hasError = true;
            if ($check['status'] === 'warning')
                $hasWarning = true;
        }
    }

    $overallStatus = $hasError ? 'error' : ($hasWarning ? 'warning' : 'ok');
    $overallMessage = $hasError ? '有錯誤需要處理' : ($hasWarning ? '有警告需要注意' : '系統運作正常');

    ApiResponse::success([
        'overall' => [
            'status' => $overallStatus,
            'message' => $overallMessage
        ],
        'checks' => array_values($checks),
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ]);

} catch (Exception $e) {
    // 最外層錯誤處理
    ApiResponse::error('健康檢查執行失敗: ' . $e->getMessage(), 500);
}

