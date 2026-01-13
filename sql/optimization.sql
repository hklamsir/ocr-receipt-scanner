-- ==========================================
-- 資料庫優化腳本
-- OCR 單據辨識系統
-- ==========================================
-- 
-- ⚠️ 注意事項：
-- 1. 執行前請先備份資料庫
-- 2. 請在 phpMyAdmin 或其他資料庫管理工具中執行
-- 3. 若索引已存在會報錯，可忽略該錯誤
--
-- ==========================================

-- 1. 新增公司名稱索引（加速搜尋）
-- 使用前綴索引避免過長的索引
ALTER TABLE receipts ADD INDEX idx_company (company_name(50));

-- 2. 新增支付方式索引（加速篩選排序）
ALTER TABLE receipts ADD INDEX idx_payment (payment_method);

-- 3. 新增 updated_at 欄位（支援未來增量同步）
-- 自動記錄最後修改時間
ALTER TABLE receipts ADD COLUMN updated_at TIMESTAMP 
    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER created_at;

-- 4. 為現有資料設定初始值
-- 將 updated_at 設為建立時間
UPDATE receipts SET updated_at = created_at WHERE updated_at IS NULL;

-- ==========================================
-- 驗證安裝
-- ==========================================
-- 執行以下查詢確認索引建立成功：
-- SHOW INDEX FROM receipts;
-- 
-- 確認 updated_at 欄位存在：
-- DESCRIBE receipts;
