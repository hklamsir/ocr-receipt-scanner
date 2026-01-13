-- ==========================================
-- Tags 排序欄位 Migration
-- ==========================================

ALTER TABLE tags ADD COLUMN sort_order INT DEFAULT 0 AFTER color;

-- 初始化排序順序
SET @row_number = 0;
UPDATE tags SET sort_order = (@row_number := @row_number + 1) ORDER BY id;
