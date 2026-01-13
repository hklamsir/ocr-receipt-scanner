<?php
/**
 * PDF 模板 - 儲存新模板 API
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['template_name'])) {
        throw new Exception('缺少模板名稱');
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 檢查用戶模板數量是否已達上限（10個）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pdf_templates WHERE user_id = ? AND is_system = 0");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    if ($count >= 10) {
        throw new Exception('模板數量已達上限（10個），請先刪除部分模板');
    }

    // 檢查模板名稱是否重複
    $stmt = $pdo->prepare("SELECT id FROM pdf_templates WHERE user_id = ? AND template_name = ?");
    $stmt->execute([$userId, $data['template_name']]);
    if ($stmt->fetch()) {
        throw new Exception('模板名稱已存在');
    }

    // 如果設為預設，取消其他模板的預設狀態
    if (!empty($data['is_default'])) {
        $stmt = $pdo->prepare("UPDATE pdf_templates SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    // 插入新模板
    $stmt = $pdo->prepare("
        INSERT INTO pdf_templates (
            user_id, template_name, is_default,
            page_size, margin_top, margin_bottom, margin_left, margin_right,
            header_text, header_align, header_font_size,
            footer_text, footer_align, footer_font_size,
            image_align, image_height_scale, image_width_scale
        ) VALUES (
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?
        )
    ");

    $stmt->execute([
        $userId,
        $data['template_name'],
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
        $data['image_width_scale'] ?? 40
    ]);

    ApiResponse::success([
        'message' => '模板儲存成功',
        'template_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log('儲存 PDF 模板失敗: ' . $e->getMessage());
    ApiResponse::error($e->getMessage());
}
