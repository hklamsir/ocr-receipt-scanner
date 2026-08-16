-- ==========================================
-- 加入 Gemini LLM 相關設定到 system_settings
-- 供已部署環境使用（system_settings 以 setting_key 為主鍵）
-- ==========================================

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('gemini_api_key', '', 'Gemini API 金鑰'),
('llm_provider', 'deepseek', '文字 LLM 提供者 (deepseek|gemini)'),
('gemini_vision_enabled', '0', '啟用 Gemini 視覺端到端 (0=關, 1=開)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description);
