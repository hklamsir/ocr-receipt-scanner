<?php
// 安全檢查模組（InfinityFree 友好）

class Security
{
    // Referer 檢查（防禦縱深，不可作為授權依據；授權請依賴 auth_check 的 Session 驗證）
    public static function validateReferer()
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocalhost = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;

        // 缺少 Referer：僅本機開發環境放行，生產環境一律拒絕（避免跨站呼叫）
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return $isLocalhost;
        }

        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $current_host = parse_url(($host !== '' ? 'http://' . $host : ''), PHP_URL_HOST);

        // 必須同源
        if ($referer_host !== $current_host) {
            return false;
        }

        // 生產環境若使用 HTTPS，Referer 也必須為 HTTPS，防中間人降級攻擊
        if (!$isLocalhost && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $referer_scheme = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
            if ($referer_scheme !== 'https') {
                return false;
            }
        }

        return true;
    }

    // Session-based Rate Limiting（避免檔案寫入問題）
    public static function checkRateLimit($limit = 10, $window = 60)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $now = time();
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }

        // 清理過期記錄
        $_SESSION['rate_limit'] = array_filter(
            $_SESSION['rate_limit'],
            function ($t) use ($now, $window) {
                return $now - $t < $window;
            }
        );

        if (count($_SESSION['rate_limit']) >= $limit) {
            return false;
        }

        $_SESSION['rate_limit'][] = $now;
        return true;
    }

    // 生成或取得 CSRF Token
    public static function getCSRFToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    // 驗證 CSRF Token
    public static function validateCSRFToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
