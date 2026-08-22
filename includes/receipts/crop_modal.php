<!-- Crop Modal (裁剪單據圖片) -->
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
