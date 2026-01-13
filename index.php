<?php
// 檢查登入狀態
require_once __DIR__ . '/includes/auth_check.php';

// 頁面設定
$pageTitle = '辨識單據';
$headerTitle = '辨識單據';
include __DIR__ . '/includes/header.php';
?>

<div class="container">

    <div id="dropzone">拖放圖片或點擊選擇（最多 20 張），也可以用相機拍照</div>
    <input type="file" id="fileInput" accept="image/*,android/allowCamera" multiple hidden>

    <div class="actions">
        <button id="startBtn">開始 OCR</button>
        <button id="clearBtn">清除所有單據</button>
    </div>

    <div class="preview" id="preview"></div>

    <p id="globalStatus"></p>

    <div id="ocrButtonContainer"></div>

    <div id="tableContainer">
        <div class="table-actions" style="display:none; gap:10px; margin-bottom:15px; justify-content: flex-end;">
            <button id="saveBtn" class="btn btn-warning">💾 儲存記錄</button>
            <button id="copyBtn" class="btn btn-success">📋 複製內容</button>
        </div>
        <div id="structuredTable"></div>
    </div>

</div>

<!-- OCR Modal -->
<div id="ocrModal">
    <div class="modal-content">
        <div class="modal-header">
            <span>完整 OCR 結果</span>
            <button class="close-btn" onclick="closeOcrModal()">×</button>
        </div>
        <textarea id="result" readonly></textarea>
    </div>
</div>

<!-- Image Modal -->
<div id="modal" onclick="closeModal()">
    <span onclick="closeModal()">✕</span>
    <img id="modalImg">
</div>

<!-- Tag 選擇 Modal (儲存時使用) -->
<div id="saveTagsModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <span>🏷️ 選擇標籤 <span class="tag-limit-hint">(最多 5 個)</span></span>
            <button class="close-btn" onclick="closeSaveTagsModal()">✕</button>
        </div>
        <div class="tag-modal-body">
            <div id="saveTagsGrid" class="tag-grid"></div>
            <div style="margin-top:15px;">
                <button type="button" class="btn btn-outline" id="createTagInSaveBtn">+ 新建標籤</button>
            </div>
            <div id="saveLoadingOverlay" class="pdf-loading-overlay" style="display: none;">
                <div class="pdf-loading-content">
                    <div class="pdf-loading-spinner"></div>
                    <div class="pdf-loading-text">正在儲存單據...</div>
                    <div class="pdf-loading-subtext">請稍候，正在處理圖片與資料</div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeSaveTagsModal()">取消</button>
                <button type="button" class="btn btn-primary" id="confirmSaveWithTagsBtn">💾 儲存</button>
            </div>
        </div>
    </div>
</div>

<!-- 新建 Tag Modal (index.php) -->
<div id="createTagModalIndex" class="edit-modal">
    <div class="edit-modal-content" style="max-width:400px;">
        <div class="edit-modal-header">
            <span>🏷️ 新建標籤</span>
            <button class="close-btn" onclick="closeCreateTagModalIndex()">✕</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group">
                <label for="newTagNameIndex">標籤名稱</label>
                <input type="text" id="newTagNameIndex" maxlength="50" placeholder="輸入標籤名稱">
            </div>
            <div class="form-group">
                <label>顏色</label>
                <div class="color-palette" id="newTagColorPaletteIndex"></div>
                <input type="hidden" id="newTagColorIndex" value="#3b82f6">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCreateTagModalIndex()">取消</button>
                <button type="button" class="btn btn-primary" id="saveNewTagBtnIndex">建立</button>
            </div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div id="cropModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:90%; max-height:90vh;">
        <div class="edit-modal-header">
            <span>✂️ 裁剪圖片</span>
            <button class="close-btn" onclick="closeCropModal()">✕</button>
        </div>
        <div class="crop-modal-body"
            style="padding:15px; display:flex; flex-direction:column; max-height:calc(90vh - 120px);">
            <div class="crop-container"
                style="flex:1; min-height:300px; max-height:60vh; background:#f5f5f5; display:flex; justify-content:center; align-items:center; overflow:hidden;">
                <img id="cropImage" style="max-width:100%; max-height:100%;">
            </div>
            <div class="crop-toolbar"
                style="display:flex; gap:10px; padding-top:15px; justify-content:center; flex-wrap:wrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="cropperRotate(-90)">↺ 左轉</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cropperRotate(90)">↻ 右轉</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cropperFlipH()">↔ 水平翻轉</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cropperFlipV()">↕ 垂直翻轉</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cropperReset()">🔄 重置</button>
            </div>
            <div class="form-actions" style="border-top:1px solid #eee; margin-top:15px; padding-top:15px;">
                <button type="button" class="btn btn-secondary" onclick="closeCropModal()">取消</button>
                <button type="button" class="btn btn-primary" onclick="applyCrop()">✂️ 套用裁剪</button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="js/app.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>