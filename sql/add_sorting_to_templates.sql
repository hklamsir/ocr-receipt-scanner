-- ==========================================
-- Add Sorting Columns to Excel Templates
-- Date: 2026-01-26
-- ==========================================

ALTER TABLE excel_templates 
ADD COLUMN sort_by VARCHAR(50) DEFAULT 'date' AFTER fields_config,
ADD COLUMN sort_order VARCHAR(10) DEFAULT 'desc' AFTER sort_by;

-- Update existing templates to have default sorting
UPDATE excel_templates SET sort_by = 'date', sort_order = 'desc' WHERE sort_by IS NULL;
