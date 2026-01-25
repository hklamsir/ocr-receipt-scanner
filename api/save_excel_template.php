<?php
/**
 * Excel 模板 - 儲存新模板 API
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

    if (!isset($data['fields_config']) || !is_array($data['fields_config'])) {
        throw new Exception('缺少欄位配置');
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 檢查用戶模板數量是否已達上限（10個）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM excel_templates WHERE user_id = ? AND is_system = 0");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    if ($count >= 10) {
        throw new Exception('模板數量已達上限（10個），請先刪除部分模板');
    }

    // 檢查模板名稱是否重複
    $stmt = $pdo->prepare("SELECT id FROM excel_templates WHERE user_id = ? AND template_name = ?");
    $stmt->execute([$userId, $data['template_name']]);
    if ($stmt->fetch()) {
        throw new Exception('模板名稱已存在');
    }

    // 如果設為預設，取消其他模板的預設狀態
    if (!empty($data['is_default'])) {
        $stmt = $pdo->prepare("UPDATE excel_templates SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    // 插入新模板
    $stmt = $pdo->prepare("
        INSERT INTO excel_templates (
            user_id, template_name, is_default, fields_config
        ) VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $data['template_name'],
        !empty($data['is_default']) ? 1 : 0,
        json_encode($data['fields_config'], JSON_UNESCAPED_UNICODE)
    ]);

    ApiResponse::success([
        'message' => '模板儲存成功',
        'template_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log('儲存 Excel 模板失敗: ' . $e->getMessage());
    ApiResponse::error($e->getMessage());
}
