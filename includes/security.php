<?php
// 安全檢查模組（InfinityFree 友好）

class Security
{
    // Referer 檢查（簡易但有效）
    public static function validateReferer()
    {
        // 允許本機測試
        if (!isset($_SERVER['HTTP_REFERER'])) {
            // 如果沒有 Referer，檢查是否為本機
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
                return true;
            }
            return false;
        }

        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $current_host = $_SERVER['HTTP_HOST'];
        return $referer_host === $current_host;
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
