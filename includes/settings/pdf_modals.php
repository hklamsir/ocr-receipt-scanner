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

            <!-- 頁面大小 -->
            <div class="form-group">
                <label for="editPageSize">頁面大小</label>
                <select id="editPageSize">
                    <option value="A4">A4 (210 × 297 mm)</option>
                    <option value="A5">A5 (148 × 210 mm)</option>
                    <option value="LETTER">Letter (216 × 279 mm)</option>
                </select>
            </div>

            <!-- 頁面邊界 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="editMarginTop">上邊界 (mm)</label>
                    <input type="number" id="editMarginTop" min="0" max="50">
                </div>
                <div class="form-group">
                    <label for="editMarginBottom">下邊界 (mm)</label>
                    <input type="number" id="editMarginBottom" min="0" max="50">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="editMarginLeft">左邊界 (mm)</label>
                    <input type="number" id="editMarginLeft" min="0" max="50">
                </div>
                <div class="form-group">
                    <label for="editMarginRight">右邊界 (mm)</label>
                    <input type="number" id="editMarginRight" min="0" max="50">
                </div>
            </div>

            <!-- 頁首設定 -->
            <div class="form-group">
                <label for="editHeader">頁首文字（選填，最多5行）</label>
                <textarea id="editHeader" rows="3" maxlength="500" placeholder="例如：我的單據\n2026年度"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>頁首對齊</label>
                    <div class="radio-group">
                        <label><input type="radio" name="editHeaderAlign" value="L"> 靠左</label>
                        <label><input type="radio" name="editHeaderAlign" value="C"> 置中</label>
                        <label><input type="radio" name="editHeaderAlign" value="R"> 靠右</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editHeaderFontSize">頁首文字大小 (pt)</label>
                    <input type="number" id="editHeaderFontSize" min="8" max="24" step="1">
                </div>
            </div>

            <!-- 頁尾設定 -->
            <div class="form-group">
                <label for="editFooter">頁尾文字（選填，最多5行）</label>
                <textarea id="editFooter" rows="3" maxlength="500" placeholder="例如：第 {PAGENO} 頁\n版權所有"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>頁尾對齊</label>
                    <div class="radio-group">
                        <label><input type="radio" name="editFooterAlign" value="L"> 靠左</label>
                        <label><input type="radio" name="editFooterAlign" value="C"> 置中</label>
                        <label><input type="radio" name="editFooterAlign" value="R"> 靠右</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editFooterFontSize">頁尾文字大小 (pt)</label>
                    <input type="number" id="editFooterFontSize" min="8" max="24" step="1">
                </div>
            </div>

            <!-- 圖片對齊 -->
            <div class="form-group">
                <label>單據圖片對齊</label>
                <div class="radio-group">
                    <label><input type="radio" name="editImageAlign" value="L"> 靠左</label>
                    <label><input type="radio" name="editImageAlign" value="C"> 置中</label>
                    <label><input type="radio" name="editImageAlign" value="R"> 靠右</label>
                </div>
            </div>

            <!-- 圖片高度比例 -->
            <div class="form-group">
                <label for="editImageHeightScale">圖片高度比例 (頁面高度的 <span id="editImageHeightScaleValue">80</span>%)</label>
                <input type="range" id="editImageHeightScale" min="10" max="100" step="5">
                <div
                    style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 5px;">
                    <span>10%</span>
                    <span>100%</span>
                </div>
            </div>

            <!-- 圖片寬度比例上限 -->
            <div class="form-group">
                <label for="editImageWidthScale">圖片寬度比例上限 (頁面寬度的 <span id="editImageWidthScaleValue">40</span>%)</label>
                <input type="range" id="editImageWidthScale" min="20" max="100" step="5">
                <div
                    style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 5px;">
                    <span>20%</span>
                    <span>100%</span>
                </div>
                <small style="display: block; margin-top: 5px; color: #666;">圖片會先按高度縮放，如果寬度超過此比例則以寬度為準</small>
            </div>

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