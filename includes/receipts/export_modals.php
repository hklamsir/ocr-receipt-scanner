<?php
/**
 * export_modals.php - Export related dialogs for receipts page
 * Uses shared form components from includes/shared/export/
 */
?>
<!-- DEBUG: export_modals.php included -->

<!-- 匯出 Excel 設定 Modal -->
<div id="exportModal" class="edit-modal">
    <div class="edit-modal-content export-modal-content">
        <div class="edit-modal-header">
            <span>📥 匯出設定</span>
            <button class="close-btn" onclick="closeExportModal()">✕</button>
        </div>
        <div class="export-modal-body">
            <!-- 模板選擇區 -->
            <div class="excel-template-section"
                style="padding: 15px 0; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="excelTemplateSelect"
                            style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">快速套用模板</label>
                        <select id="excelTemplateSelect"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">不使用模板</option>
                        </select>
                    </div>
                    <button type="button" id="applyExcelTemplateBtn" class="btn btn-secondary btn-sm"
                        style="margin-top: 22px;">套用</button>
                    <button type="button" id="saveExcelTemplateBtn" class="btn btn-primary btn-sm"
                        style="margin-top: 22px;">💾 另存為模板</button>
                </div>
            </div>

            <p id="exportInfo" style="margin-bottom:15px;color:#666;"></p>

            <?php include __DIR__ . '/../shared/export/excel_form.php'; ?>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">取消</button>
                <button type="button" class="btn btn-success" id="confirmExportBtn">📥 匯出</button>
            </div>
        </div>
    </div>
</div>

<!-- 新增空欄位 Modal (Excel) -->
<div id="addEmptyColumnModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:350px;">
        <div class="edit-modal-header">
            <span>➕ 新增空欄位</span>
            <button class="close-btn" onclick="closeAddEmptyColumnModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group">
                <label for="emptyColumnName">欄位名稱</label>
                <input type="text" id="emptyColumnName" maxlength="20" placeholder="例如：備註">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddEmptyColumnModal()">取消</button>
                <button type="button" class="btn btn-primary" id="confirmAddEmptyColumnBtn">新增</button>
            </div>
        </div>
    </div>
</div>

<!-- PDF 匯出設定 Modal -->
<div id="pdfExportModal" class="edit-modal">
    <div class="edit-modal-content edit-modal-scrollable">
        <div class="edit-modal-header">
            <span>📄 PDF 匯出設定</span>
            <button class="close-btn" onclick="closePdfExportModal()">✕</button>
        </div>

        <!-- 批次資訊顯示 -->
        <div id="pdfBatchInfo"
            style="display:none; padding: 10px 20px; background: #e3f2fd; border-bottom: 1px solid #90caf9; color: #1976d2; font-size: 14px;">
            <strong>批量匯出：</strong><span id="pdfBatchCount"></span>
        </div>

        <!-- 模板選擇區 -->
        <div class="pdf-template-section"
            style="padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="pdfTemplateSelect"
                        style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">快速套用模板</label>
                    <select id="pdfTemplateSelect"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">不使用模板</option>
                    </select>
                </div>
                <button type="button" id="applyTemplateBtn" class="btn btn-secondary btn-sm"
                    style="margin-top: 22px;">套用</button>
                <button type="button" id="saveTemplateBtn" class="btn btn-primary btn-sm" style="margin-top: 22px;">💾
                    另存為模板</button>
            </div>
        </div>

        <form id="pdfExportForm" class="edit-modal-body">

            <?php include __DIR__ . '/../shared/export/pdf_form.php'; ?>

            <!-- 自訂檔案名稱 (Receipts Specific) -->
            <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <label for="pdfCustomFilename">檔案名稱（選填）</label>
                <input type="text" id="pdfCustomFilename" placeholder="留空使用系統預設檔名" maxlength="100">
                <small style="display: block; margin-top: 5px; color: #666;">預設：<span id="pdfDefaultFilename"
                        style="font-family: monospace;"></span></small>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closePdfExportModal()">取消</button>
                <button type="submit" id="pdfExportBtn" class="btn btn-success">📄 匯出 PDF</button>
            </div>
        </form>
    </div>
</div>

<!-- 批量刪除確認 Modal -->
<div id="bulkDeleteModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認批量刪除</span>
            <button class="close-btn" onclick="closeBulkDeleteModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p id="bulkDeleteInfo"></p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBulkDeleteModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">🗑️ 確定刪除</button>
            </div>
        </div>
    </div>
</div>