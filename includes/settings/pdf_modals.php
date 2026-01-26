<!-- PDF 模板管理 Modal -->
<div id="pdfTemplatesManagerModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width: 700px;">
        <div class="edit-modal-header">
            <span>📄 PDF 模板管理</span>
            <button class="close-btn" onclick="closePdfTemplatesManager()">✕</button>
        </div>
        <div style="padding: 20px;">
            <div id="pdfTemplatesList"></div>
        </div>
    </div>
</div>

<!-- 編輯 PDF 模板 Modal -->
<div id="editPdfTemplateModal" class="edit-modal">
    <div class="edit-modal-content edit-modal-scrollable" style="max-width: 600px;">
        <div class="edit-modal-header">
            <span>✏️ 編輯模板</span>
            <button class="close-btn" onclick="closeEditPdfTemplateModal()">✕</button>
        </div>
        <form id="editPdfTemplateForm" class="edit-modal-body">
            <input type="hidden" id="editTemplateId">

            <!-- 模板名稱 -->
            <div class="form-group">
                <label for="editTemplateName">模板名稱</label>
                <input type="text" id="editTemplateName" required maxlength="100">
            </div>

            <!-- 設為預設 -->
            <div class="form-group">
                <label>
                    <input type="checkbox" id="editTemplateIsDefault">
                    設為預設模板（開啟匯出時自動套用）
                </label>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

            <?php include __DIR__ . '/../shared/export/pdf_form.php'; ?>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditPdfTemplateModal()">取消</button>
                <button type="submit" class="btn btn-success">儲存</button>
            </div>
        </form>
    </div>
</div>

<!-- 刪除 PDF 模板確認 Modal -->
<div id="deletePdfTemplateModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認刪除</span>
            <button class="close-btn" onclick="closeDeletePdfTemplateModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p>確定要刪除模板「<span id="deletePdfTemplateName"></span>」嗎？</p>
                <p style="color:#999;font-size:13px;">此操作無法復原。</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeletePdfTemplateModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePdfTemplateBtn">刪除</button>
            </div>
        </div>
    </div>
</div>