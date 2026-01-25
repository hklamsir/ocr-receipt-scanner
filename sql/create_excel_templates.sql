-- ==========================================
-- Excel Templates Table Migration
-- Run this script to add excel_templates support
-- Date: 2026-01-25
-- ==========================================

SET NAMES utf8mb4;

-- Excel Templates Table
CREATE TABLE IF NOT EXISTS excel_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT '用戶ID，NULL表示系統模板',
    template_name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_system TINYINT(1) DEFAULT 0,
    fields_config JSON NOT NULL COMMENT '欄位配置 JSON 陣列',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_default (user_id, is_default),
    INDEX idx_system (is_system)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default System Excel Template
INSERT INTO excel_templates (
    user_id, template_name, is_system, is_default, fields_config
) VALUES (
    NULL, '標準格式 (系統預設)', 1, 0,
    '[{"key":"date","label":"日期","enabled":true},{"key":"time","label":"時間","enabled":true},{"key":"company","label":"公司名稱","enabled":true},{"key":"items","label":"項目摘要","enabled":true},{"key":"summary","label":"總結","enabled":true},{"key":"payment","label":"支付方式","enabled":true},{"key":"amount","label":"總金額","enabled":true},{"key":"tags","label":"標籤","enabled":false}]'
);

-- Completed
SELECT 'Excel templates table created successfully' AS result;
