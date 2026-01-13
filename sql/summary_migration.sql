-- ==========================================
-- 加入 summary 欄位到 receipts 表
-- 用於儲存購買摘要（小於 15 字的總結）
-- ==========================================

ALTER TABLE receipts 
ADD COLUMN summary VARCHAR(50) COMMENT '購買總結（小於 15 字）' 
AFTER items_summary;
