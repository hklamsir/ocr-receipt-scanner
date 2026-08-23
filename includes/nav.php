<?php
/**
 * nav.php - 獨立導航列組件
 * 
 * 特點：
 * - 桌面版：右側水平連結
 * - 手機版：漢堡選單，點擊展開下拉
 * - 根據登入狀態顯示不同連結
 * - 用戶名點擊後顯示下拉面板
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
        <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">📑 辨識單據</a>
        <a href="receipts.php" class="<?php echo $currentPage === 'receipts.php' ? 'active' : ''; ?>">📚 我的單據</a>
        <?php if ($isAdmin): ?>
            <a href="admin.php" class="nav-admin <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>">📊 管理</a>
        <?php endif; ?>

        <!-- 用戶下拉選單 -->
        <div class="nav-user-dropdown-container">
            <button class="nav-user-btn" id="userDropdownBtn" aria-expanded="false">
                👤 <?php echo htmlspecialchars($username ?: '用戶'); ?> <span class="dropdown-arrow">▼</span>
            </button>
            <div class="nav-user-dropdown" id="userDropdownMenu">
                <div class="dropdown-loading">載入中...</div>
            </div>
        </div>

        <a href="api/logout.php" class="nav-logout">登出</a>
    <?php endif; ?>
</nav>

<script>
    (function () {
        const hamburger = document.getElementById('hamburgerBtn');
        const navLinks = document.getElementById('navLinks');
        const dropdownBtn = document.getElementById('userDropdownBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');
        let isProfileLoaded = false;

        // 漢堡選單邏輯
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', function (e) {
                e.stopPropagation();
                const willOpen = !navLinks.classList.contains('open');
                navLinks.classList.toggle('open');
                hamburger.textContent = navLinks.classList.contains('open') ? '✕' : '☰';
                // 手機選單展開時預先載入用戶資料，避免還要再按人頭
                if (willOpen && !isProfileLoaded) {
                    loadUserProfile();
                }
            });
        }

        // 用戶下拉選單邏輯
        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();

                const isOpen = dropdownMenu.classList.contains('show');
                dropdownMenu.classList.toggle('show');
                dropdownBtn.classList.toggle('active');
                dropdownBtn.setAttribute('aria-expanded', !isOpen);

                if (!isOpen && !isProfileLoaded) {
                    loadUserProfile();
                }
            });
        }

        // 點擊外部關閉選單
        document.addEventListener('click', function (e) {
            // 關閉手機版導航
            if (navLinks && navLinks.classList.contains('open') &&
                !navLinks.contains(e.target) && !hamburger.contains(e.target)) {
                navLinks.classList.remove('open');
                hamburger.textContent = '☰';
            }
            // 關閉用戶下拉
            if (dropdownMenu && dropdownMenu.classList.contains('show') &&
                !dropdownMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownBtn.classList.remove('active');
                dropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // 載入用戶資料
        async function loadUserProfile() {
            try {
                const res = await fetch('api/get_user_profile.php');
                const result = await res.json();

                if (result.success) {
                    renderDropdown(result);
                    isProfileLoaded = true;
                } else {
                    dropdownMenu.innerHTML = '<div class="dropdown-section" style="color:red;text-align:center;">載入失敗</div>';
                }
            } catch (err) {
                console.error('Failed to load profile:', err);
                dropdownMenu.innerHTML = '<div class="dropdown-section" style="color:red;text-align:center;">網路錯誤</div>';
            }
        }

        // 渲染下拉內容
        function renderDropdown(data) {
            const { username, is_admin, joined_at, last_login_relative, quota, stats } = data;

            const roleBadge = is_admin
                ? '<span class="dropdown-role admin">管理員</span>'
                : '<span class="dropdown-role">一般用戶</span>';

            let quotaHtml = '';
            if (quota.limit > 0) {
                quotaHtml = `
                <div class="quota-box">
                    <div class="quota-label">
                        <span>本月配額</span>
                        <span>${quota.used} / ${quota.limit} 張</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: ${quota.percent}%"></div>
                    </div>
                </div>
            `;
            } else {
                quotaHtml = `
                <div class="quota-box">
                    <div class="quota-label">
                        <span>本月配額</span>
                        <span style="color:#16a34a;">無限量</span>
                    </div>
                </div>
            `;
            }

            const html = `
            <div class="dropdown-header">
                <span class="dropdown-username">${username} ${roleBadge}</span>
                <div class="dropdown-sub">加入於 ${joined_at}</div>
                <div class="dropdown-sub">上次登入：${last_login_relative}</div>
            </div>
            <div class="dropdown-section">
                ${quotaHtml}
                <div class="dropdown-stats-grid">
                    <div class="stat-item">
                        <span class="stat-value">${stats.total_receipts}</span>
                        <span class="stat-label">總單據</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">${stats.total_tags}</span>
                        <span class="stat-label">標籤</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" style="font-size:12px;">${stats.storage_used}</span>
                        <span class="stat-label">儲存</span>
                    </div>
                </div>
            </div>
            <div class="dropdown-footer">
                <a href="settings.php" class="dropdown-link">⚙️ 設定</a>
                <a href="#" class="dropdown-link" onclick="openPasswordModalFromNav(event)">🔐 變更密碼</a>
            </div>
        `;

            dropdownMenu.innerHTML = html;
        }

        // 變更密碼跳轉
        window.openPasswordModalFromNav = function (e) {
            e.preventDefault();
            if (typeof openPasswordModal === 'function') {
                openPasswordModal();
                dropdownMenu.classList.remove('show');
                dropdownBtn.classList.remove('active');
            } else {
                window.location.href = 'settings.php?action=change_password';
            }
        };

        // URL 參數自動開啟密碼 modal
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'change_password') {
            window.addEventListener('load', function () {
                if (typeof openPasswordModal === 'function') {
                    openPasswordModal();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        }
    })();
</script>