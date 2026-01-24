-- ==========================================
-- OCR Receipt Scanner Init Script
-- Combined verification and migration scripts
-- Date: 2026-01-25
-- ==========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. Core Tables
-- ==========================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL COMMENT '用戶名（唯一）',
  password_hash VARCHAR(255) NOT NULL COMMENT '密碼 hash',
  is_admin TINYINT(1) DEFAULT 0 COMMENT '是否為管理員',
  quota_limit INT DEFAULT 0 COMMENT '月配額限制 (0=無限制)',
  status ENUM('active', 'suspended') DEFAULT 'active' COMMENT '帳號狀態',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  last_login TIMESTAMP NULL COMMENT '最後登入時間',
  INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用戶表';

-- Tags Table
CREATE TABLE IF NOT EXISTS tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '所屬用戶 ID',
  name VARCHAR(50) NOT NULL COMMENT 'Tag 名稱',
  color VARCHAR(7) DEFAULT '#6c757d' COMMENT 'Tag 顏色（HEX）',
  sort_order INT DEFAULT 0 COMMENT '排序順序',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tag_per_user (user_id, name),
  INDEX idx_user_tags (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='標籤表';

-- Receipts Table
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

-- Receipt Tags Relation Table
CREATE TABLE IF NOT EXISTS receipt_tags (
  receipt_id INT NOT NULL COMMENT '單據 ID',
  tag_id INT NOT NULL COMMENT 'Tag ID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  PRIMARY KEY (receipt_id, tag_id),
  FOREIGN KEY (receipt_id) REFERENCES receipts(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='單據標籤關聯表';

-- PDF Templates Table
CREATE TABLE IF NOT EXISTS pdf_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT '用戶ID，NULL表示系統模板',
    template_name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_system TINYINT(1) DEFAULT 0,
    
    -- PDF Settings
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

-- ==========================================
-- 2. Admin & System Tables
-- ==========================================

-- System Stats
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

-- User Activity Logs
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

-- System Settings
CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT COMMENT '設定值',
  description VARCHAR(200) COMMENT '設定說明',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系統設定';

-- Announcements
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

-- Login Attempts
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

-- IP Blocklist
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

-- Active Sessions
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
-- 3. Initial Data
-- ==========================================

-- Admin User (admin / admin123)
-- WARN: Change password immediately after deployment!
INSERT IGNORE INTO users (username, password_hash, is_admin, status) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active');

-- Test User (testuser / test123)
INSERT IGNORE INTO users (username, password_hash, is_admin, status) VALUES 
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'active');

-- System Settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('max_files_per_upload', '20', '每次上傳最大檔案數'),
('image_quality', '60', '圖片壓縮品質 (1-100)'),
('max_image_size_kb', '200', '圖片最大大小 (KB)'),
('login_max_attempts', '5', '登入失敗最大嘗試次數'),
('login_lockout_minutes', '15', '登入鎖定時間 (分鐘)'),
('deepseek_api_key', '', 'DeepSeek API 金鑰'),
('ocrspace_api_key', '', 'OCR.space API 金鑰'),
('ocr_engine', '2', 'OCR.space 引擎 (1=較穩定, 2=較準確)');

-- Default System PDF Template
INSERT INTO pdf_templates (
    user_id, template_name, is_system, is_default,
    page_size, margin_top, margin_bottom, margin_left, margin_right,
    header_text, header_align, header_font_size,
    footer_text, footer_align, footer_font_size,
    image_align, image_height_scale, image_width_scale
) VALUES (
    NULL, '標準格式 (系統預設)', 1, 0,
    'A4', 10, 10, 10, 10,
    '', 'C', 12,
    '第 {PAGENO} 頁', 'C', 12,
    'C', 80, 40
);

SET FOREIGN_KEY_CHECKS = 1;
