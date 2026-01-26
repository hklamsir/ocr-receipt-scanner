<?php
/**
 * Excel 模板 - 更新模板 API
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['template_id'])) {
        throw new Exception('缺少模板 ID');
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];
    $templateId = $data['template_id'];

    // 檢查模板是否存在且為用戶所有（且非系統模板）
    $stmt = $pdo->prepare("SELECT is_system FROM excel_templates WHERE id = ? AND user_id = ?");
    $stmt->execute([$templateId, $userId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        throw new Exception('模板不存在或無權限修改');
    }

    if ($template['is_system']) {
        throw new Exception('無法修改系統模板');
    }

    // 如果設為預設，取消其他模板的預設狀態
    if (!empty($data['is_default'])) {
        $stmt = $pdo->prepare("UPDATE excel_templates SET is_default = 0 WHERE user_id = ? AND id != ?");
        $stmt->execute([$userId, $templateId]);
    }

    // 更新模板
    $stmt = $pdo->prepare("
        UPDATE excel_templates SET
            template_name = ?,
            is_default = ?,
            fields_config = ?,
            sort_by = ?,
            sort_order = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $data['template_name'] ?? '',
        !empty($data['is_default']) ? 1 : 0,
        json_encode($data['fields_config'] ?? [], JSON_UNESCAPED_UNICODE),
        $data['sort_by'] ?? 'date',
        $data['sort_order'] ?? 'desc',
        $templateId,
        $userId
    ]);

    ApiResponse::success(['message' => '模板更新成功']);

} catch (Exception $e) {
    error_log('更新 Excel 模板失敗: ' . $e->getMessage());
    ApiResponse::error($e->getMessage());
}
