<?php
// Tags CRUD API (支援批量建立和排序更新)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf_check.php';
require_once __DIR__ . '/../includes/api_response.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // 取得用戶所有 tags（按排序順序）
            $stmt = $pdo->prepare("
                SELECT id, name, color, sort_order, created_at
                FROM tags
                WHERE user_id = ?
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([$userId]);
            $tags = $stmt->fetchAll();

            ApiResponse::success(['tags' => $tags]);
            break;

        case 'POST':
            // 建立新 tag（支援批量）
            $data = json_decode(file_get_contents('php://input'), true);
            $color = $data['color'] ?? '#6c757d';

            // 批量建立模式
            if (isset($data['names']) && is_array($data['names'])) {
                $names = array_filter(array_map('trim', $data['names']));
                $created = 0;
                $errors = [];

                // 取得目前最大排序值
                $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM tags WHERE user_id = ?");
                $stmt->execute([$userId]);
                $maxOrder = (int) ($stmt->fetch()['max_order'] ?? 0);

                foreach ($names as $name) {
                    if (empty($name) || mb_strlen($name) > 50)
                        continue;

                    // 檢查是否已存在
                    $stmt = $pdo->prepare("SELECT id FROM tags WHERE user_id = ? AND name = ?");
                    $stmt->execute([$userId, $name]);
                    if ($stmt->fetch()) {
                        $errors[] = "「{$name}」已存在";
                        continue;
                    }

                    $maxOrder++;
                    $stmt = $pdo->prepare("INSERT INTO tags (user_id, name, color, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $name, $color, $maxOrder]);
                    $created++;
                }

                ApiResponse::success([
                    'created' => $created,
                    'errors' => $errors
                ]);
                break;
            }

            // 單一建立模式
            $name = trim($data['name'] ?? '');
            if (empty($name)) {
                ApiResponse::error('標籤名稱不可為空', 400);
            }

            if (mb_strlen($name) > 50) {
                ApiResponse::error('標籤名稱最多 50 個字元', 400);
            }

            // 檢查是否已存在
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE user_id = ? AND name = ?");
            $stmt->execute([$userId, $name]);
            if ($stmt->fetch()) {
                ApiResponse::error('此標籤已存在', 400);
            }

            // 取得目前最大排序值
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM tags WHERE user_id = ?");
            $stmt->execute([$userId]);
            $maxOrder = (int) ($stmt->fetch()['max_order'] ?? 0) + 1;

            // 插入新 tag
            $stmt = $pdo->prepare("INSERT INTO tags (user_id, name, color, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $color, $maxOrder]);
            $newId = $pdo->lastInsertId();

            ApiResponse::success([
                'tag' => [
                    'id' => (int) $newId,
                    'name' => $name,
                    'color' => $color
                ]
            ]);
            break;

        case 'PUT':
            // 更新 tag
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int) ($data['id'] ?? 0);
            $name = trim($data['name'] ?? '');
            $color = $data['color'] ?? '#6c757d';

            if ($id <= 0 || empty($name)) {
                ApiResponse::error('參數錯誤', 400);
            }

            // 確認 tag 屬於該用戶
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if (!$stmt->fetch()) {
                ApiResponse::error('標籤不存在', 404);
            }

            // 檢查名稱是否重複（排除自己）
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE user_id = ? AND name = ? AND id != ?");
            $stmt->execute([$userId, $name, $id]);
            if ($stmt->fetch()) {
                ApiResponse::error('此標籤名稱已存在', 400);
            }

            $stmt = $pdo->prepare("UPDATE tags SET name = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $color, $id, $userId]);

            ApiResponse::success();
            break;

        case 'PATCH':
            // 批量更新排序
            $data = json_decode(file_get_contents('php://input'), true);
            $order = $data['order'] ?? [];

            if (!is_array($order)) {
                ApiResponse::error('參數錯誤', 400);
            }

            $pdo->beginTransaction();
            try {
                foreach ($order as $item) {
                    $id = (int) ($item['id'] ?? 0);
                    $sortOrder = (int) ($item['sort_order'] ?? 0);
                    if ($id <= 0)
                        continue;

                    $stmt = $pdo->prepare("UPDATE tags SET sort_order = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$sortOrder, $id, $userId]);
                }
                $pdo->commit();
                ApiResponse::success();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            // 刪除 tag
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                ApiResponse::error('參數錯誤', 400);
            }

            // 確認 tag 屬於該用戶並刪除
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            if ($stmt->rowCount() === 0) {
                ApiResponse::error('標籤不存在', 404);
            }

            ApiResponse::success();
            break;

        default:
            ApiResponse::error('不支援的請求方法', 405);
    }

} catch (PDOException $e) {
    ApiResponse::error('操作失敗', 500);
}
