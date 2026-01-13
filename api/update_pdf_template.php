<?php
/**
 * PDF 模板 - 更新模板 API
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
        throw new Exception('模板不存在或無權限修改');
    }

    if ($template['is_system']) {
        throw new Exception('系統模板無法修改');
    }

    // 如果設為預設，取消其他模板的預設狀態
    if (!empty($data['is_default'])) {
        $stmt = $pdo->prepare("UPDATE pdf_templates SET is_default = 0 WHERE user_id = ? AND id != ?");
        $stmt->execute([$userId, $templateId]);
    }

    // 更新模板
    $stmt = $pdo->prepare("
        UPDATE pdf_templates SET
            template_name = ?,
            is_default = ?,
            page_size = ?,
            margin_top = ?,
            margin_bottom = ?,
            margin_left = ?,
            margin_right = ?,
            header_text = ?,
            header_align = ?,
            header_font_size = ?,
            footer_text = ?,
            footer_align = ?,
            footer_font_size = ?,
            image_align = ?,
            image_height_scale = ?,
            image_width_scale = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $data['template_name'] ?? '',
        !empty($data['is_default']) ? 1 : 0,
        $data['page_size'] ?? 'A4',
        $data['margin_top'] ?? 10,
        $data['margin_bottom'] ?? 10,
        $data['margin_left'] ?? 10,
        $data['margin_right'] ?? 10,
        $data['header_text'] ?? '',
        $data['header_align'] ?? 'C',
        $data['header_font_size'] ?? 12,
        $data['footer_text'] ?? '',
        $data['footer_align'] ?? 'C',
        $data['footer_font_size'] ?? 12,
        $data['image_align'] ?? 'C',
        $data['image_height_scale'] ?? 80,
        $data['image_width_scale'] ?? 40,
        $templateId,
        $userId
    ]);

    ApiResponse::success(['message' => '模板更新成功']);

} catch (Exception $e) {
    error_log('更新 PDF 模板失敗: ' . $e->getMessage());
    ApiResponse::error($e->getMessage());
}
