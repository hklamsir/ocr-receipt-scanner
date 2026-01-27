<?php
// 系統設定管理 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/csrf_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    if ($method === 'GET') {
        // 取得所有設定
        $stmt = $pdo->query("SELECT setting_key, setting_value, description, updated_at FROM system_settings ORDER BY setting_key");
        $settings = $stmt->fetchAll();

        // 將設定轉換為易用的格式
        $settingsMap = [];
        foreach ($settings as $s) {
            $settingsMap[$s['setting_key']] = [
                'value' => $s['setting_value'],
                'description' => $s['description'],
                'updated_at' => $s['updated_at']
            ];
        }

        ApiResponse::success(['settings' => $settingsMap]);

    } elseif ($method === 'POST') {
        // 更新設定
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input) || !is_array($input)) {
            ApiResponse::error('無效的請求格式');
        }

        $updated = [];
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($input as $key => $data) {
                $value = is_array($data) ? ($data['value'] ?? '') : $data;
                $description = is_array($data) ? ($data['description'] ?? null) : null;

                // 驗證 key
                if (!preg_match('/^[a-z_]+$/', $key)) {
                    continue;
                }

                $stmt->execute([$key, $value, $description]);
                $updated[] = $key;
            }

            // 記錄活動日誌
            $logStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, action, details, ip_address)
                VALUES (?, 'settings_updated', ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                json_encode(['updated_keys' => $updated], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            $pdo->commit();
            ApiResponse::success(['updated' => $updated], '設定已更新');

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } else {
        ApiResponse::error('Method not allowed', 405);
    }

} catch (PDOException $e) {
    error_log('Settings API error: ' . $e->getMessage());
    ApiResponse::error('操作失敗', 500);
}
