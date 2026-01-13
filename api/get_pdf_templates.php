<?php
/**
 * PDF 模板 - 取得模板列表 API
 * 回傳當前用戶的所有模板（包括系統模板）
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 查詢用戶自己的模板 + 系統模板
    $stmt = $pdo->prepare("
        SELECT 
            id,
            template_name,
            is_default,
            is_system,
            page_size,
            margin_top,
            margin_bottom,
            margin_left,
            margin_right,
            header_text,
            header_align,
            header_font_size,
            footer_text,
            footer_align,
            footer_font_size,
            image_align,
            image_height_scale,
            image_width_scale,
            created_at,
            updated_at
        FROM pdf_templates
        WHERE user_id = ? OR user_id IS NULL
        ORDER BY is_system DESC, is_default DESC, created_at DESC
    ");

    $stmt->execute([$userId]);
    $templates = $stmt->fetchAll();

    ApiResponse::success([
        'templates' => $templates
    ]);

} catch (Exception $e) {
    error_log('取得 PDF 模板失敗: ' . $e->getMessage());
    ApiResponse::error('取得模板列表失敗');
}
