<?php
// Receipt-Tags 關聯 API（支援批量設定）
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
            // 取得單據的 tags
            $receiptId = (int) ($_GET['receipt_id'] ?? 0);

            if ($receiptId <= 0) {
                ApiResponse::error('參數錯誤', 400);
            }

            // 確認單據屬於該用戶
            $stmt = $pdo->prepare("SELECT id FROM receipts WHERE id = ? AND user_id = ?");
            $stmt->execute([$receiptId, $userId]);
            if (!$stmt->fetch()) {
                ApiResponse::error('單據不存在', 404);
            }

            // 取得關聯的 tags
            $stmt = $pdo->prepare("
                SELECT t.id, t.name, t.color
                FROM tags t
                INNER JOIN receipt_tags rt ON t.id = rt.tag_id
                WHERE rt.receipt_id = ?
                ORDER BY t.sort_order ASC, t.name ASC
            ");
            $stmt->execute([$receiptId]);
            $tags = $stmt->fetchAll();

            ApiResponse::success(['tags' => $tags]);
            break;

        case 'POST':
            // 新增 tag 到單據
            $data = json_decode(file_get_contents('php://input'), true);
            $receiptId = (int) ($data['receipt_id'] ?? 0);
            $tagId = (int) ($data['tag_id'] ?? 0);

            if ($receiptId <= 0 || $tagId <= 0) {
                ApiResponse::error('參數錯誤', 400);
            }

            // 確認單據屬於該用戶
            $stmt = $pdo->prepare("SELECT id FROM receipts WHERE id = ? AND user_id = ?");
            $stmt->execute([$receiptId, $userId]);
            if (!$stmt->fetch()) {
                ApiResponse::error('單據不存在', 404);
            }

            // 確認 tag 屬於該用戶
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE id = ? AND user_id = ?");
            $stmt->execute([$tagId, $userId]);
            if (!$stmt->fetch()) {
                ApiResponse::error('標籤不存在', 404);
            }

            // 檢查單據已有多少個 tags（上限 5 個）
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM receipt_tags WHERE receipt_id = ?");
            $stmt->execute([$receiptId]);
            $count = $stmt->fetch()['count'];

            if ($count >= 5) {
                ApiResponse::error('每張單據最多只能有 5 個標籤', 400);
            }

            // 檢查是否已關聯
            $stmt = $pdo->prepare("SELECT 1 FROM receipt_tags WHERE receipt_id = ? AND tag_id = ?");
            $stmt->execute([$receiptId, $tagId]);
            if ($stmt->fetch()) {
                ApiResponse::error('此標籤已加入', 400);
            }

            // 新增關聯
            $stmt = $pdo->prepare("INSERT INTO receipt_tags (receipt_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$receiptId, $tagId]);

            ApiResponse::success();
            break;

        case 'PUT':
            // 批量設定單據的 tags（取代現有關聯）
            $data = json_decode(file_get_contents('php://input'), true);
            $receiptId = (int) ($data['receipt_id'] ?? 0);
            $tagIds = $data['tag_ids'] ?? [];

            if ($receiptId <= 0) {
                ApiResponse::error('參數錯誤', 400);
            }

            if (!is_array($tagIds)) {
                ApiResponse::error('標籤參數錯誤', 400);
            }

            // 限制最多 5 個
            if (count($tagIds) > 5) {
                ApiResponse::error('每張單據最多只能有 5 個標籤', 400);
            }

            // 確認單據屬於該用戶
            $stmt = $pdo->prepare("SELECT id FROM receipts WHERE id = ? AND user_id = ?");
            $stmt->execute([$receiptId, $userId]);
            if (!$stmt->fetch()) {
                ApiResponse::error('單據不存在', 404);
            }

            // 驗證所有 tag 都屬於該用戶
            if (!empty($tagIds)) {
                $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE id IN ($placeholders) AND user_id = ?");
                $stmt->execute([...array_map('intval', $tagIds), $userId]);
                $validTags = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($validTags) !== count($tagIds)) {
                    ApiResponse::error('部分標籤無效', 400);
                }
            }

            $pdo->beginTransaction();
            try {
                // 刪除現有關聯
                $stmt = $pdo->prepare("DELETE FROM receipt_tags WHERE receipt_id = ?");
                $stmt->execute([$receiptId]);

                // 新增新關聯
                if (!empty($tagIds)) {
                    $stmt = $pdo->prepare("INSERT INTO receipt_tags (receipt_id, tag_id) VALUES (?, ?)");
                    foreach ($tagIds as $tagId) {
                        $stmt->execute([$receiptId, (int) $tagId]);
                    }
                }

                $pdo->commit();
                ApiResponse::success();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            // 從單據移除 tag
            $data = json_decode(file_get_contents('php://input'), true);
            $receiptId = (int) ($data['receipt_id'] ?? 0);
            $tagId = (int) ($data['tag_id'] ?? 0);

            if ($receiptId <= 0 || $tagId <= 0) {
                ApiResponse::error('參數錯誤', 400);
            }

            // 確認單據屬於該用戶
            $stmt = $pdo->prepare("SELECT id FROM receipts WHERE id = ? AND user_id = ?");
            $stmt->execute([$receiptId, $userId]);
            if (!$stmt->fetch()) {
                ApiResponse::error('單據不存在', 404);
            }

            // 刪除關聯
            $stmt = $pdo->prepare("DELETE FROM receipt_tags WHERE receipt_id = ? AND tag_id = ?");
            $stmt->execute([$receiptId, $tagId]);

            ApiResponse::success();
            break;

        default:
            ApiResponse::error('不支援的請求方法', 405);
    }

} catch (PDOException $e) {
    ApiResponse::error('操作失敗', 500);
}
