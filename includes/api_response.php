<?php
/**
 * api_response.php - 統一 API 回應格式
 */

class ApiResponse
{
    /**
     * 發送成功回應
     * 
     * @param array $data 回應資料（會合併到根層級）
     * @param string|null $message 成功訊息（可選）
     */
    public static function success($data = [], $message = null)
    {
        self::send(true, $data, $message);
    }

    /**
     * 發送錯誤回應
     * 
     * @param string $errorMessage 錯誤訊息
     * @param int $statusCode HTTP 狀態碼 (預設 400)
     * @param array $data 額外除錯資料（可選）
     */
    public static function error($errorMessage, $statusCode = 400, $data = [])
    {
        http_response_code($statusCode);
        self::send(false, $data, $errorMessage);
    }

    /**
     * 內部發送方法
     */
    private static function send($success, $data, $messageOrError)
    {
        // 確保 Content-Type 正確
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $response = ['success' => $success];

        // 設定訊息或錯誤欄位
        if (!empty($messageOrError)) {
            $key = $success ? 'message' : 'error';
            $response[$key] = $messageOrError;
        }

        // 合併資料到根層級 (如果是陣列)
        if (is_array($data) && !empty($data)) {
            $response = array_merge($response, $data);
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
