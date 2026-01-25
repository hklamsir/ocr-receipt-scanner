<?php
// 登出 API
session_start();

// 刪除資料庫中的 session 紀錄
try {
    require_once __DIR__ . '/../includes/db.php';
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM active_sessions WHERE session_id = ?");
    $stmt->execute([session_id()]);
} catch (Exception $e) {
    // 靜默失敗，不影響登出流程
}

session_destroy();

header('Location: ../login.php');
exit;
