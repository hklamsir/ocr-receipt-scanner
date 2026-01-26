<!-- Tags 管理 Modal -->
<div id="tagsManagerModal" class="edit-modal">
    <div class="edit-modal-content tags-manager-content">
        <div class="edit-modal-header">
            <span>🏷️ 管理標籤</span>
            <button class="close-btn" onclick="closeTagsManager()">✕</button>
        </div>
        <div class="tags-manager-body">
            <!-- 批量新增區 -->
            <div class="form-group">
                <label>批量新增標籤</label>
                <div class="batch-add-row">
                    <input type="text" id="batchTagInput" placeholder="輸入標籤名稱，用逗號分隔（如：餐飲, 交通, 辦公）">
                    <button class="btn btn-primary" id="batchAddBtn">新增</button>
                </div>
            </div>

            <!-- 選擇顏色 -->
            <div class="form-group">
                <label>選擇顏色</label>
                <div class="color-palette" id="batchColorPalette"></div>
                <input type="hidden" id="selectedBatchColor" value="#3b82f6">
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

            <!-- 現有標籤列表 -->
            <div class="form-group">
                <label>現有標籤 <span style="color:#999;font-weight:normal;">(拖拽排序)</span></label>
                <div id="tagsList" class="tags-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- 編輯單個 Tag Modal -->
<div id="editTagModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:400px;">
        <div class="edit-modal-header">
            <span>✏️ 編輯標籤</span>
            <button class="close-btn" onclick="closeEditTagModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <input type="hidden" id="editTagId">
            <div class="form-group">
                <label for="editTagName">標籤名稱</label>
                <input type="text" id="editTagName" maxlength="50">
            </div>
            <div class="form-group">
                <label>顏色</label>
                <div class="color-palette" id="editColorPalette"></div>
                <input type="hidden" id="selectedEditColor">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditTagModal()">取消</button>
                <button type="button" class="btn btn-primary" id="saveEditTagBtn">儲存</button>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div id="deleteTagModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認刪除</span>
            <button class="close-btn" onclick="closeDeleteTagModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p>確定要刪除標籤 「<span id="deleteTagName"></span>」嗎？</p>
                <p style="color:#999;font-size:13px;">此標籤將從所有單據中移除。</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteTagModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTagBtn">刪除</button>
            </div>
        </div>
    </div>
</div>