<?php
// setup.php - 初始化管理員帳號
// ⚠️ 執行後請立即刪除此檔案！

require_once __DIR__ . '/includes/db.php';

echo "<h2>初始化管理員帳號</h2>";

try {
    $pdo = getDB();

    // 檢查 admin 是否已存在
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $existing = $stmt->fetch();

    if ($existing) {
        echo "<p>❌ admin 帳號已存在，正在更新密碼...</p>";

        // 更新密碼
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, is_admin = 1 WHERE username = 'admin'");
        $updateStmt->execute([$password_hash]);

        echo "<p>✅ 已重設 admin 密碼為：<strong>admin123</strong></p>";
    } else {
        echo "<p>建立新的 admin 帳號...</p>";

        // 建立管理員
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', ?, 1)");
        $insertStmt->execute([$password_hash]);

        echo "<p>✅ 已建立 admin 帳號</p>";
        echo "<p>用戶名：<strong>admin</strong></p>";
        echo "<p>密碼：<strong>admin123</strong></p>";
    }

    echo "<hr>";
    echo "<p><a href='login.php'>前往登入頁面</a></p>";
    echo "<p style='color:red;'><strong>⚠️ 登入成功後，請立即刪除此 setup.php 檔案！</strong></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>錯誤：" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>