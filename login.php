<?php
// 如果已登入，直接跳轉
// 設定 Session Cookie 安全屬性（HTTPS 時啟用 Secure）
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 頁面設定
$pageTitle = '登入';
$headerTitle = '收據浣熊';
$showNav = false;  // 登入頁不顯示導航列
$extraStyles = '
    <style>
        /* 登入頁面 Header 置中 */
        header {
            justify-content: center;
        }
        .header-branding {
            flex: none;
            margin-left: 0;
            justify-content: center;
        }

        /* 登入頁面居中佈局 */
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 180px);
            padding: 20px;
        }

        .login-box {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .login-box h1 {
            text-align: center;
            color: var(--color-gray-800);
            margin: 0 0 30px 0;
            font-size: 24px;
        }

        .login-box .btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
        }
    </style>
';
include __DIR__ . '/includes/header.php';
?>

<div class="container login-wrapper">
    <div class="login-box">
        <h1>🔐 登入</h1>

        <div class="login-error" id="error"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">用戶名</label>
                <input type="text" id="username" name="username" class="form-control" required autocomplete="username"
                    autofocus>
            </div>

            <div class="form-group">
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" class="form-control" required
                    autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-success">登入</button>
        </form>
    </div>
</div>



<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('error');

        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = 'index.php';
            } else {
                errorDiv.textContent = data.error || '登入失敗';
                errorDiv.style.display = 'block';
                // 清除密碼欄並 focus
                const passwordInput = document.getElementById('password');
                passwordInput.value = '';
                passwordInput.focus();
            }
        } catch (err) {
            errorDiv.textContent = '連線錯誤，請稍後再試';
            errorDiv.style.display = 'block';
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>