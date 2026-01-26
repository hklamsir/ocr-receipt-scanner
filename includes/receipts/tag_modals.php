<!-- Tag 篩選 Modal -->
<div id="tagSelectModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>🏷️ 選擇標籤篩選</span>
            <button class="close-btn" onclick="closeTagSelectModal()">✕</button>
        </div>
        <div class="tag-modal-body">
            <div id="tagGrid" class="tag-grid"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeTagSelectModal()">取消</button>
                <button type="button" class="btn btn-primary" id="applyTagFilterBtn">套用篩選</button>
            </div>
        </div>
    </div>
</div>

<!-- 編輯單據時的 Tags 多選 Modal -->
<div id="editTagsModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>🏷️ 選擇標籤 <span class="tag-limit-hint">(最多 5 個)</span></span>
            <button class="close-btn" onclick="closeEditTagsModal()">✕</button>
        </div>
        <div class="tag-modal-body">
            <div id="editTagsGrid" class="tag-grid"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditTagsModal()">取消</button>
                <button type="button" class="btn btn-primary" id="saveEditTagsBtn">確定</button>
            </div>
        </div>
    </div>
</div>

<!-- 新建 Tag Modal -->
<div id="createTagModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:400px;">
        <div class="edit-modal-header">
            <span>🏷️ 新建標籤</span>
            <button class="close-btn" onclick="closeCreateTagModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group">
                <label for="newTagName">標籤名稱</label>
                <input type="text" id="newTagName" maxlength="50" placeholder="輸入標籤名稱">
            </div>
            <div class="form-group">
                <label>顏色</label>
                <div class="color-palette" id="newTagColorPalette"></div>
                <input type="hidden" id="newTagColor" value="#3b82f6">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCreateTagModal()">取消</button>
                <button type="button" class="btn btn-primary" id="saveNewTagBtn">建立</button>
            </div>
        </div>
    </div>
</div>

<!-- 批量加入標籤 Modal -->
<div id="bulkTagsModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>🏷️ 為選取單據加入標籤</span>
            <button class="close-btn" onclick="closeBulkTagsModal()">✕</button>
        </div>
        <div class="tag-modal-body">
            <p id="bulkTagsInfo" style="margin-bottom:15px;color:#666;"></p>
            <div id="bulkTagsGrid" class="tag-grid"></div>
            <div style="margin-top: 15px;">
                <button type="button" class="btn btn-outline btn-sm" id="bulkCreateNewTagBtn">+ 新建標籤</button>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBulkTagsModal()">取消</button>
                <button type="button" class="btn btn-primary" id="saveBulkTagsBtn">加入標籤</button>
            </div>
        </div>
    </div>
</div>

<!-- 批量移除標籤 Modal -->
<div id="bulkRemoveTagsModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>🏷️ 從選取單據移除標籤</span>
            <button class="close-btn" onclick="closeBulkRemoveTagsModal()">✕</button>
        </div>
        <div class="tag-modal-body">
            <p id="bulkRemoveTagsInfo" style="margin-bottom:15px;color:#666;"></p>
            <div id="bulkRemoveTagsGrid" class="tag-grid"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBulkRemoveTagsModal()">取消</button>
                <button type="button" class="btn btn-warning" id="saveBulkRemoveTagsBtn">移除標籤</button>
            </div>
        </div>
    </div>
</div>