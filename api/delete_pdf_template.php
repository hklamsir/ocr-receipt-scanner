<?php
/**
 * PDF 模板 - 刪除模板 API
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['template_id'])) {
        throw new Exception('缺少模板ID');
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];
    $templateId = (int) $data['template_id'];

    // 檢查模板是否存在且屬於當前用戶
    $stmt = $pdo->prepare("SELECT is_system FROM pdf_templates WHERE id = ? AND user_id = ?");
    $stmt->execute([$templateId, $userId]);
    $template = $stmt->fetch();

    if (!$template) {
        throw new Exception('模板不存在或無權限刪除');
    }

    if ($template['is_system']) {
        throw new Exception('系統模板無法刪除');
    }

    // 刪除模板
    $stmt = $pdo->prepare("DELETE FROM pdf_templates WHERE id = ? AND user_id = ?");
    $stmt->execute([$templateId, $userId]);

    ApiResponse::success(['message' => '模板刪除成功']);

} catch (Exception $e) {
    error_log('刪除 PDF 模板失敗: ' . $e->getMessage());
    ApiResponse::error($e->getMessage());
}
