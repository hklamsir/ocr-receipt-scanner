-- 修復 PDF 模板資料表結構
-- 將 user_id 改為允許 NULL

-- 步驟 1: 修改 user_id 欄位為允許 NULL
ALTER TABLE pdf_templates 
MODIFY COLUMN user_id INT NULL;

-- 步驟 2: 插入系統模板
INSERT INTO pdf_templates (
    user_id, template_name, is_system, is_default,
    page_size, margin_top, margin_bottom, margin_left, margin_right,
    header_text, header_align, header_font_size,
    footer_text, footer_align, footer_font_size,
    image_align, image_height_scale, image_width_scale
) VALUES (
    NULL, '標準格式', 1, 0,
    'A4', 10.00, 10.00, 10.00, 10.00,
    '', 'C', 12,
    '第 {PAGENO} 頁', 'C', 12,
    'C', 80, 40
);

-- 驗證
SELECT * FROM pdf_templates WHERE user_id IS NULL;
