-- ==========================================
-- 收據浣熊 UI 提升：users 表新增 theme 欄位
-- 執行前請先備份 users 表
-- 適用環境：MySQL / MariaDB（InfinityFree 生產）
-- ==========================================

-- 新增使用者 UI 主題偏好欄位（預設 teal = 活潑浣熊森林青綠）
ALTER TABLE users
  ADD COLUMN theme VARCHAR(20) NOT NULL DEFAULT 'teal'
  COMMENT 'UI 主題：teal|elegant|minimal|dark';

-- 可回滾 SQL（如需還原）：
-- ALTER TABLE users DROP COLUMN theme;
