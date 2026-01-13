-- PDF 模板系統資料表
-- 創建日期: 2026-01-10
-- 修正：允許 user_id 為 NULL 來表示系統模板

-- 創建 pdf_templates 資料表
CREATE TABLE IF NOT EXISTS pdf_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT '用戶ID，NULL表示系統模板',
    template_name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_system TINYINT(1) DEFAULT 0,
    
    -- PDF 設定
    page_size VARCHAR(10) DEFAULT 'A4',
    margin_top DECIMAL(5,2) DEFAULT 10.00,
    margin_bottom DECIMAL(5,2) DEFAULT 10.00,
    margin_left DECIMAL(5,2) DEFAULT 10.00,
    margin_right DECIMAL(5,2) DEFAULT 10.00,
    
    header_text TEXT,
    header_align VARCHAR(1) DEFAULT 'C',
    header_font_size INT DEFAULT 12,
    
    footer_text TEXT,
    footer_align VARCHAR(1) DEFAULT 'C',
    footer_font_size INT DEFAULT 12,
    
    image_align VARCHAR(1) DEFAULT 'C',
    image_height_scale INT DEFAULT 80,
    image_width_scale INT DEFAULT 40,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_default (user_id, is_default),
    INDEX idx_system (is_system)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入系統內建模板（user_id = NULL 表示系統模板）
INSERT INTO pdf_templates (
    user_id, template_name, is_system, is_default,
    page_size, margin_top, margin_bottom, margin_left, margin_right,
    header_text, header_align, header_font_size,
    footer_text, footer_align, footer_font_size,
    image_align, image_height_scale, image_width_scale
) VALUES (
    1, '標準格式', 1, 0,
    'A4', 10, 10, 10, 10,
    '', 'C', 12,
    '第 {PAGENO} 頁', 'C', 12,
    'C', 80, 40
);
