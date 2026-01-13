<?php
/**
 * nav.php - 獨立導航列組件
 * 
 * 特點：
 * - 桌面版：右側水平連結
 * - 手機版：漢堡選單，點擊展開下拉
 * - 根據登入狀態顯示不同連結
 */

// 取得當前頁面以高亮
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>

<!-- 漢堡按鈕（手機版） -->
<button class="hamburger" id="hamburgerBtn" aria-label="開啟選單">☰</button>

<!-- 導航連結 -->
<nav class="nav-links" id="navLinks">
    <?php if ($isLoggedIn): ?>
        <span class="nav-user">👤
            <?php echo htmlspecialchars($username); ?>
        </span>
        <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">🏠 辨識單據</a>
        <a href="receipts.php" class="<?php echo $currentPage === 'receipts.php' ? 'active' : ''; ?>">📚 我的單據</a>
        <a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">⚙️ 設定</a>
        <?php if ($isAdmin): ?>
            <a href="admin.php" class="nav-admin <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>">📊 管理</a>
        <?php endif; ?>
        <a href="api/logout.php" class="nav-logout">登出</a>
    <?php endif; ?>
</nav>

<script>
    (function () {
        const hamburger = document.getElementById('hamburgerBtn');
        const navLinks = document.getElementById('navLinks');

        if (hamburger && navLinks) {
            hamburger.addEventListener('click', function (e) {
                e.stopPropagation();
                navLinks.classList.toggle('open');
                hamburger.textContent = navLinks.classList.contains('open') ? '✕' : '☰';
            });

            // 點擊其他地方關閉選單
            document.addEventListener('click', function (e) {
                if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) {
                    navLinks.classList.remove('open');
                    hamburger.textContent = '☰';
                }
            });
        }
    })();
</script>