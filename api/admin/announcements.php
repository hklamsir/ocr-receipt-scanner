<?php
// 公告管理 API（Admin）

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../includes/api_response.php';

// 設定香港時區
date_default_timezone_set('Asia/Hong_Kong');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();
    $nowHK = date('Y-m-d H:i:s');

    switch ($method) {
        case 'GET':
            // 取得公告列表
            $showAll = isset($_GET['all']) && $_GET['all'] === '1';

            if ($showAll) {
                // 管理員查看所有公告
                $stmt = $pdo->query("
                    SELECT a.*, u.username as created_by_name
                    FROM announcements a
                    LEFT JOIN users u ON a.created_by = u.id
                    ORDER BY a.created_at DESC
                ");
            } else {
                // 只取得當前有效的公告（使用香港時間）
                $stmt = $pdo->prepare("
                    SELECT id, title, content, start_date, end_date
                    FROM announcements
                    WHERE is_active = 1
                      AND (start_date IS NULL OR start_date <= ?)
                      AND (end_date IS NULL OR end_date >= ?)
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$nowHK, $nowHK]);
            }

            $announcements = $showAll ? $stmt->fetchAll() : $stmt->fetchAll();
            ApiResponse::success(['announcements' => $announcements]);
            break;

        case 'POST':
            // 新增公告
            $input = json_decode(file_get_contents('php://input'), true);

            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');
            $isActive = isset($input['is_active']) ? (int) $input['is_active'] : 1;
            $startDate = !empty($input['start_date']) ? $input['start_date'] : null;
            $endDate = !empty($input['end_date']) ? $input['end_date'] : null;

            if (empty($title)) {
                ApiResponse::error('標題不能為空');
            }

            if (mb_strlen($title) > 100) {
                ApiResponse::error('標題不能超過 100 字');
            }

            $stmt = $pdo->prepare("
                INSERT INTO announcements (title, content, is_active, start_date, end_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $content, $isActive, $startDate, $endDate, $_SESSION['user_id']]);

            $id = $pdo->lastInsertId();
            ApiResponse::success(['id' => $id], '公告已新增');
            break;

        case 'PUT':
            // 更新公告
            $input = json_decode(file_get_contents('php://input'), true);

            $id = (int) ($input['id'] ?? 0);
            if (!$id) {
                ApiResponse::error('缺少公告 ID');
            }

            $updates = [];
            $params = [];

            if (isset($input['title'])) {
                $title = trim($input['title']);
                if (empty($title)) {
                    ApiResponse::error('標題不能為空');
                }
                $updates[] = 'title = ?';
                $params[] = $title;
            }

            if (isset($input['content'])) {
                $updates[] = 'content = ?';
                $params[] = trim($input['content']);
            }

            if (isset($input['is_active'])) {
                $updates[] = 'is_active = ?';
                $params[] = (int) $input['is_active'];
            }

            if (array_key_exists('start_date', $input)) {
                $updates[] = 'start_date = ?';
                $params[] = !empty($input['start_date']) ? $input['start_date'] : null;
            }

            if (array_key_exists('end_date', $input)) {
                $updates[] = 'end_date = ?';
                $params[] = !empty($input['end_date']) ? $input['end_date'] : null;
            }

            if (empty($updates)) {
                ApiResponse::error('沒有要更新的欄位');
            }

            $params[] = $id;
            $sql = "UPDATE announcements SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            ApiResponse::success([], '公告已更新');
            break;

        case 'DELETE':
            // 刪除公告
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                ApiResponse::error('缺少公告 ID');
            }

            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                ApiResponse::error('公告不存在');
            }

            ApiResponse::success([], '公告已刪除');
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
    }

} catch (PDOException $e) {
    error_log('Announcements API error: ' . $e->getMessage());
    ApiResponse::error('操作失敗', 500);
}
