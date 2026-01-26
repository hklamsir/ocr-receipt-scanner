<!-- Excel 模板管理 Modal -->
<div id="excelTemplatesManagerModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width: 700px;">
        <div class="edit-modal-header">
            <span>📊 Excel 模板管理</span>
            <button class="close-btn" onclick="closeExcelTemplatesManager()">✕</button>
        </div>
        <div style="padding: 20px;">
            <div id="excelTemplatesList"></div>
        </div>
    </div>
</div>

<!-- 編輯 Excel 模板 Modal -->
<div id="editExcelTemplateModal" class="edit-modal">
    <div class="edit-modal-content edit-modal-scrollable" style="max-width: 600px;">
        <div class="edit-modal-header">
            <span>✏️ 編輯模板</span>
            <button class="close-btn" onclick="closeEditExcelTemplateModal()">✕</button>
        </div>
        <form id="editExcelTemplateForm" class="edit-modal-body">
            <input type="hidden" id="editExcelTemplateId">

            <!-- 模板名稱 -->
            <div class="form-group">
                <label for="editExcelTemplateName">模板名稱</label>
                <input type="text" id="editExcelTemplateName" required maxlength="100">
            </div>

            <!-- 設為預設 -->
            <div class="form-group">
                <label>
                    <input type="checkbox" id="editExcelTemplateIsDefault">
                    設為預設模板（開啟匯出時自動套用）
                </label>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

            <!-- 欄位配置 -->
            <div class="form-group">
                <label style="font-weight: 600;">📌 選擇並排序欄位（拖拉調整順序）：</label>
                <div id="editExcelFieldsList" class="export-fields-list"></div>
                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-outline btn-sm" id="editExcelAddEmptyColumnBtn">+
                        新增空欄位</button>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditExcelTemplateModal()">取消</button>
                <button type="submit" class="btn btn-success">儲存</button>
            </div>
        </form>
    </div>
</div>

<!-- 編輯 Excel 模板時新增空欄位 Modal -->
<div id="editExcelAddColumnModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:350px;">
        <div class="edit-modal-header">
            <span>➕ 新增空欄位</span>
            <button class="close-btn" onclick="closeEditExcelAddColumnModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group">
                <label for="editExcelEmptyColumnName">欄位名稱</label>
                <input type="text" id="editExcelEmptyColumnName" maxlength="20" placeholder="例如：備註">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditExcelAddColumnModal()">取消</button>
                <button type="button" class="btn btn-primary" id="confirmEditExcelAddColumnBtn">新增</button>
            </div>
        </div>
    </div>
</div>

<!-- 刪除 Excel 模板確認 Modal -->
<div id="deleteExcelTemplateModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認刪除</span>
            <button class="close-btn" onclick="closeDeleteExcelTemplateModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p>確定要刪除模板「<span id="deleteExcelTemplateName"></span>」嗎？</p>
                <p style="color:#999;font-size:13px;">此操作無法復原。</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteExcelTemplateModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteExcelTemplateBtn">刪除</button>
            </div>
        </div>
    </div>
</div>