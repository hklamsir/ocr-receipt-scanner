-- ==========================================
-- 管理員功能資料庫擴充腳本
-- 執行時間: 2026-01
-- ==========================================

-- 1. 系統每日統計表
CREATE TABLE IF NOT EXISTS system_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stat_date DATE UNIQUE NOT NULL COMMENT '統計日期',
  total_ocr_requests INT DEFAULT 0 COMMENT 'OCR 請求總數',
  successful_ocr INT DEFAULT 0 COMMENT '成功的 OCR 數',
  failed_ocr INT DEFAULT 0 COMMENT '失敗的 OCR 數',
  total_receipts_saved INT DEFAULT 0 COMMENT '儲存的單據數',
  storage_used_bytes BIGINT DEFAULT 0 COMMENT '使用的儲存空間 (bytes)',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統每日統計';

-- 2. 使用者活動日誌
CREATE TABLE IF NOT EXISTS user_activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '用戶 ID',
  action VARCHAR(50) NOT NULL COMMENT '操作類型',
  details TEXT COMMENT '操作詳情',
  ip_address VARCHAR(45) COMMENT 'IP 位址',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_action (user_id, action),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='使用者活動日誌';

-- 3. 擴充 users 表 - 新增配額和狀態欄位
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS quota_limit INT DEFAULT 0 COMMENT '月配額限制 (0=無限制)',
  ADD COLUMN IF NOT EXISTS status ENUM('active', 'suspended') DEFAULT 'active' COMMENT '帳號狀態';

-- 4. 系統設定表
CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT COMMENT '設定值',
  description VARCHAR(200) COMMENT '設定說明',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統設定';

-- 插入預設設定
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('max_files_per_upload', '20', '每次上傳最大檔案數'),
('image_quality', '60', '圖片壓縮品質 (1-100)'),
('max_image_size_kb', '200', '圖片最大大小 (KB)'),
('login_max_attempts', '5', '登入失敗最大嘗試次數'),
('login_lockout_minutes', '15', '登入鎖定時間 (分鐘)');

-- 5. 系統公告表
CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL COMMENT '公告標題',
  content TEXT COMMENT '公告內容',
  is_active TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
  start_date DATETIME COMMENT '開始顯示時間',
  end_date DATETIME COMMENT '結束顯示時間',
  created_by INT COMMENT '建立者 ID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active (is_active, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統公告';

-- 6. 登入嘗試記錄
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) COMMENT '嘗試的用戶名',
  ip_address VARCHAR(45) NOT NULL COMMENT 'IP 位址',
  user_agent VARCHAR(255) COMMENT '瀏覽器資訊',
  success TINYINT(1) DEFAULT 0 COMMENT '是否成功',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip_address),
  INDEX idx_username (username),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登入嘗試記錄';

-- 7. IP 封鎖清單
CREATE TABLE IF NOT EXISTS ip_blocklist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) UNIQUE NOT NULL COMMENT 'IP 位址',
  reason VARCHAR(200) COMMENT '封鎖原因',
  blocked_until DATETIME COMMENT '封鎖到期時間 (NULL=永久)',
  created_by INT COMMENT '建立者 ID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip_address),
  INDEX idx_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP 封鎖清單';

-- 8. 活動 Session 記錄
CREATE TABLE IF NOT EXISTS active_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '用戶 ID',
  session_id VARCHAR(128) NOT NULL COMMENT 'Session ID',
  ip_address VARCHAR(45) COMMENT 'IP 位址',
  user_agent VARCHAR(255) COMMENT '瀏覽器資訊',
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_session (session_id),
  INDEX idx_user (user_id),
  INDEX idx_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='活動 Session';

-- ==========================================
-- 驗證安裝
-- ==========================================
-- 執行以下查詢確認表格建立成功：
-- SHOW TABLES LIKE 'system_%';
-- SHOW TABLES LIKE 'login_%';
-- SHOW TABLES LIKE 'ip_%';
-- SHOW TABLES LIKE 'active_%';
-- SHOW TABLES LIKE 'announcements';
-- DESCRIBE users;
