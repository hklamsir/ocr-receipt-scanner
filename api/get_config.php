<?php
// 前端設定 API - 提供不含敏感資訊的設定
require_once __DIR__ . '/../includes/config.php';

// 啟動 session 以獲取用戶 ID
session_start();

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'maxFiles' => MAX_FILES,
    'imageQuality' => IMAGE_QUALITY,
    'maxImageSizeKb' => MAX_IMAGE_SIZE_KB,
    'ocrProxyUrl' => 'ocr_proxy.php', // 使用本地 proxy
    'userId' => $_SESSION['user_id'] ?? null, // 提供用戶 ID 用於 localStorage 區分
    'geminiVisionEnabled' => defined('GEMINI_VISION_ENABLED') ? (GEMINI_VISION_ENABLED ? true : false) : false,
    'llmProvider' => defined('LLM_PROVIDER') ? LLM_PROVIDER : 'deepseek'
], JSON_UNESCAPED_SLASHES);

