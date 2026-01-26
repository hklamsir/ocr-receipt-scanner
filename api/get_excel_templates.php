<?php
/**
 * Excel 模板 - 獲取模板列表 API
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/api_response.php';

try {
    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 獲取用戶自己的模板 + 系統模板
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            template_name,
            is_default,
            is_system,
            fields_config,
            sort_by,
            sort_order,
            created_at,
            updated_at
        FROM excel_templates
        WHERE user_id = ? OR is_system = 1
        ORDER BY is_system DESC, is_default DESC, template_name ASC
    ");
    $stmt->execute([$userId]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 解析 JSON 欄位
    foreach ($templates as &$template) {
        $template['is_default'] = (bool) $template['is_default'];
        $template['is_system'] = (bool) $template['is_system'];
        $template['fields_config'] = json_decode($template['fields_config'], true);
    }

    ApiResponse::success(['templates' => $templates]);

} catch (Exception $e) {
    error_log('獲取 Excel 模板失敗: ' . $e->getMessage());
    ApiResponse::error($e->getMessage());
}
