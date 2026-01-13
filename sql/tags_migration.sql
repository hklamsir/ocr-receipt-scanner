-- ==========================================
-- Tags 系統資料表 Migration
-- 執行此 SQL 新增 tags 功能
-- ==========================================

-- 1. Tags 主表（存放所有 tag 名稱）
CREATE TABLE IF NOT EXISTS tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '所屬用戶 ID',
  name VARCHAR(50) NOT NULL COMMENT 'Tag 名稱',
  color VARCHAR(7) DEFAULT '#6c757d' COMMENT 'Tag 顏色（HEX）',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tag_per_user (user_id, name),
  INDEX idx_user_tags (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='標籤表';

-- 2. 單據-Tag 關聯表（多對多）
CREATE TABLE IF NOT EXISTS receipt_tags (
  receipt_id INT NOT NULL COMMENT '單據 ID',
  tag_id INT NOT NULL COMMENT 'Tag ID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  PRIMARY KEY (receipt_id, tag_id),
  FOREIGN KEY (receipt_id) REFERENCES receipts(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='單據標籤關聯表';

-- ==========================================
-- 驗證安裝
-- ==========================================
-- 執行以下查詢確認表格建立成功：
-- SHOW TABLES LIKE 'tags';
-- SHOW TABLES LIKE 'receipt_tags';
-- DESCRIBE tags;
-- DESCRIBE receipt_tags;
