<!-- 圖片預覽 Modal -->
<div id="modal" onclick="closeModal()">
    <span onclick="closeModal()">✕</span>
    <img id="modalImg">
</div>

<!-- 編輯 Modal -->
<div id="editModal" class="edit-modal">
    <div class="edit-modal-content edit-modal-scrollable">
        <div class="edit-modal-header">
            <span>✏️ 編輯單據</span>
            <button class="close-btn" onclick="closeEditModal()">✕</button>
        </div>
        <form id="editForm" class="edit-modal-body">
            <input type="hidden" id="editId">
            <!-- 日期 + 時間 同一列 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="editDate">日期</label>
                    <input type="date" id="editDate" name="date">
                </div>
                <div class="form-group">
                    <label for="editTime">時間</label>
                    <input type="time" id="editTime" name="time" step="1">
                </div>
            </div>
            <div class="form-group">
                <label for="editCompany">公司名稱</label>
                <input type="text" id="editCompany" name="company" maxlength="50">
            </div>
            <div class="form-group">
                <label for="editSummary">總結</label>
                <input type="text" id="editSummary" name="summary" maxlength="15" placeholder="少於 15 字">
            </div>
            <div class="form-group">
                <label for="editItems">項目摘要</label>
                <textarea id="editItems" name="items" rows="3" maxlength="200"></textarea>
            </div>
            <!-- 支付方式 + 總金額 同一列 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="editPayment">支付方式</label>
                    <input type="text" id="editPayment" name="payment" maxlength="12">
                </div>
                <div class="form-group">
                    <label for="editAmount">總金額</label>
                    <input type="number" id="editAmount" name="amount" step="0.01">
                </div>
            </div>
            <!-- Tags 編輯區 -->
            <div class="form-group">
                <label>標籤 <span class="tag-limit-hint">(最多 5 個)</span></label>
                <div id="editTagsContainer" class="edit-tags-container"></div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button type="button" id="selectTagsBtn" class="btn btn-secondary">🏷️ 選擇標籤</button>
                    <button type="button" id="createNewTagBtn" class="btn btn-outline">+ 新建</button>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">取消</button>
                <button type="submit" class="btn btn-primary">💾 儲存</button>
            </div>
        </form>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div id="deleteModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認刪除</span>
            <button class="close-btn" onclick="closeDeleteModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p>確定要刪除這筆單據嗎？此操作無法復原。</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">🗑️ 確定刪除</button>
            </div>
        </div>
    </div>
</div>

<!-- 手機版操作按鈕 Modal -->
<div id="mobileActionsModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width: 90%; width: 400px;">
        <div class="edit-modal-header">
            <span>🔧 批次操作</span>
            <button class="close-btn" onclick="closeMobileActionsModal()">✕</button>
        </div>
        <div style="padding: 20px;">
            <p id="mobileActionsInfo" style="margin-bottom: 15px; color: #666;"></p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button id="mobileAddTagBtn" class="btn btn-secondary">🏷️ 加入標籤</button>
                <button id="mobileRemoveTagBtn" class="btn btn-warning">🏷️ 移除標籤</button>
                <button id="mobileDeleteBtn" class="btn btn-danger">🗑️ 刪除選取</button>
                <button id="mobileExportPdfBtn" class="btn btn-success">📄 批量匯出PDF</button>
            </div>
            <div class="form-actions" style="margin-top: 15px;">
                <button type="button" class="btn btn-secondary" onclick="closeMobileActionsModal()">關閉</button>
            </div>
        </div>
    </div>
</div>