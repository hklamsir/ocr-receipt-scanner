<?php
// Session 驗證模組
// 檢查用戶是否已登入，未登入則重定向至登入頁

session_start();

// 檢查 Session 是否存在
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// 更新最後活動時間（可選：用於 Session 自動過期）
$_SESSION['last_activity'] = time();
