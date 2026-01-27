<?php
// IP 封鎖管理 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/csrf_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // 取得封鎖清單
            $showExpired = isset($_GET['show_expired']) && $_GET['show_expired'] === '1';

            $sql = "SELECT b.*, u.username as blocked_by_name
                    FROM ip_blocklist b
                    LEFT JOIN users u ON b.created_by = u.id";

            if (!$showExpired) {
                $sql .= " WHERE blocked_until IS NULL OR blocked_until > NOW()";
            }

            $sql .= " ORDER BY b.created_at DESC";

            $stmt = $pdo->query($sql);
            $blocklist = $stmt->fetchAll();

            // 統計
            $activeCount = 0;
            $expiredCount = 0;
            $now = new DateTime();
            foreach ($blocklist as $item) {
                if ($item['blocked_until'] === null || new DateTime($item['blocked_until']) > $now) {
                    $activeCount++;
                } else {
                    $expiredCount++;
                }
            }

            ApiResponse::success([
                'blocklist' => $blocklist,
                'active_count' => $activeCount,
                'expired_count' => $expiredCount
            ]);
            break;

        case 'POST':
            // 新增封鎖
            $input = json_decode(file_get_contents('php://input'), true);

            $ipAddress = trim($input['ip_address'] ?? '');
            $reason = trim($input['reason'] ?? '');
            $duration = (int) ($input['duration_hours'] ?? 0); // 0 = 永久

            // 驗證 IP 格式
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                ApiResponse::error('無效的 IP 位址');
            }

            // 不能封鎖自己
            if ($ipAddress === ($_SERVER['REMOTE_ADDR'] ?? '')) {
                ApiResponse::error('無法封鎖自己的 IP');
            }

            $blockedUntil = null;
            if ($duration > 0) {
                $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$duration} hours"));
            }

            // 檢查是否已存在
            $stmt = $pdo->prepare("SELECT id FROM ip_blocklist WHERE ip_address = ?");
            $stmt->execute([$ipAddress]);
            if ($stmt->fetch()) {
                // 更新現有記錄
                $stmt = $pdo->prepare("
                    UPDATE ip_blocklist 
                    SET reason = ?, blocked_until = ?, created_by = ?, created_at = NOW()
                    WHERE ip_address = ?
                ");
                $stmt->execute([$reason, $blockedUntil, $_SESSION['user_id'], $ipAddress]);
                $message = 'IP 封鎖已更新';
            } else {
                // 新增記錄
                $stmt = $pdo->prepare("
                    INSERT INTO ip_blocklist (ip_address, reason, blocked_until, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$ipAddress, $reason, $blockedUntil, $_SESSION['user_id']]);
                $message = 'IP 已加入封鎖清單';
            }

            // 記錄活動日誌
            $logStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, action, details, ip_address)
                VALUES (?, 'ip_blocked', ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                json_encode([
                    'blocked_ip' => $ipAddress,
                    'reason' => $reason,
                    'duration_hours' => $duration
                ], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            ApiResponse::success([], $message);
            break;

        case 'DELETE':
            // 解除封鎖
            $id = (int) ($_GET['id'] ?? 0);
            $ipAddress = $_GET['ip'] ?? '';

            if ($id) {
                $stmt = $pdo->prepare("SELECT ip_address FROM ip_blocklist WHERE id = ?");
                $stmt->execute([$id]);
                $record = $stmt->fetch();

                if (!$record) {
                    ApiResponse::error('記錄不存在');
                }

                $ipAddress = $record['ip_address'];
                $stmt = $pdo->prepare("DELETE FROM ip_blocklist WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($ipAddress) {
                $stmt = $pdo->prepare("DELETE FROM ip_blocklist WHERE ip_address = ?");
                $stmt->execute([$ipAddress]);
            } else {
                ApiResponse::error('缺少 ID 或 IP 位址');
            }

            // 記錄活動日誌
            $logStmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, action, details, ip_address)
                VALUES (?, 'ip_unblocked', ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                json_encode(['unblocked_ip' => $ipAddress], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            ApiResponse::success([], 'IP 封鎖已解除');
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
    }

} catch (PDOException $e) {
    error_log('IP blocklist error: ' . $e->getMessage());
    ApiResponse::error('操作失敗', 500);
}
