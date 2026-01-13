-- ==========================================
-- OCR 專案資料庫結構
-- 適用於 InfinityFree MySQL
-- ==========================================

-- 1. 用戶表
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL COMMENT '用戶名（唯一）',
  password_hash VARCHAR(255) NOT NULL COMMENT '密碼 hash',
  is_admin TINYINT(1) DEFAULT 0 COMMENT '是否為管理員',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  last_login TIMESTAMP NULL COMMENT '最後登入時間',
  INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用戶表';

-- 2. 單據記錄表
CREATE TABLE IF NOT EXISTS receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '所屬用戶 ID',
  receipt_date DATE COMMENT '單據日期',
  receipt_time TIME COMMENT '單據時間',
  company_name VARCHAR(200) COMMENT '公司名稱',
  items_summary TEXT COMMENT '購買物品摘要',
  summary VARCHAR(50) COMMENT '購買總結（小於 15 字）',
  payment_method VARCHAR(50) COMMENT '支付方式',
  total_amount DECIMAL(10,2) COMMENT '總金額',
  ocr_engine TINYINT COMMENT 'OCR 引擎（1 或 2）',
  image_filename VARCHAR(255) COMMENT '圖片檔名（存於 receipts/username/ 目錄）',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_date (user_id, receipt_date),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='單據記錄表';

-- 3. 建立預設管理員帳號
-- 用戶名: admin
-- 密碼: admin123
-- ⚠️ 部署後請立即透過管理頁面修改密碼！
INSERT INTO users (username, password_hash, is_admin) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- 4. 建立測試用戶（可選）
-- 用戶名: testuser
-- 密碼: test123
INSERT INTO users (username, password_hash, is_admin) VALUES 
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- ==========================================
-- 驗證安裝
-- ==========================================
-- 執行以下查詢確認表格建立成功：
-- SELECT * FROM users;
-- SELECT * FROM receipts;
