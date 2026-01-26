<!-- Shared PDF Configuration Form -->
<div class="pdf-config-fields">
    <!-- 頁面大小 -->
    <div class="form-group">
        <label for="pdf_pageSize">頁面大小</label>
        <select id="pdf_pageSize">
            <option value="A4" selected>A4 (210 × 297 mm)</option>
            <option value="A5">A5 (148 × 210 mm)</option>
            <option value="LETTER">Letter (216 × 279 mm)</option>
        </select>
    </div>

    <!-- 頁面邊界 -->
    <div class="form-row">
        <div class="form-group">
            <label for="pdf_marginTop">上邊界 (mm)</label>
            <input type="number" id="pdf_marginTop" value="10" min="0" max="50">
        </div>
        <div class="form-group">
            <label for="pdf_marginBottom">下邊界 (mm)</label>
            <input type="number" id="pdf_marginBottom" value="10" min="0" max="50">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="pdf_marginLeft">左邊界 (mm)</label>
            <input type="number" id="pdf_marginLeft" value="10" min="0" max="50">
        </div>
        <div class="form-group">
            <label for="pdf_marginRight">右邊界 (mm)</label>
            <input type="number" id="pdf_marginRight" value="10" min="0" max="50">
        </div>
    </div>

    <!-- 頁首設定 -->
    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label for="pdf_headerText" style="margin-bottom: 0;">頁首文字（選填，最多5行）</label>
            <a href="javascript:void(0)" class="pdf-hint-trigger"
                style="font-size: 12px; color: #3b82f6; text-decoration: none;">💡 變數說明</a>
        </div>
        <textarea id="pdf_headerText" rows="3" maxlength="500" placeholder="例如：我的單據\n2026年度"></textarea>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>頁首對齊</label>
            <div class="radio-group">
                <label><input type="radio" name="pdf_headerAlign" value="L"> 靠左</label>
                <label><input type="radio" name="pdf_headerAlign" value="C" checked> 置中</label>
                <label><input type="radio" name="pdf_headerAlign" value="R"> 靠右</label>
            </div>
        </div>
        <div class="form-group">
            <label for="pdf_headerFontSize">頁首文字大小 (pt)</label>
            <input type="number" id="pdf_headerFontSize" value="12" min="8" max="24" step="1">
        </div>
    </div>

    <!-- 圖片對齊 -->
    <div class="form-group" style="text-align: center;">
        <label>單據圖片對齊</label>
        <div class="radio-group" style="align-items: center; justify-content: center;">
            <label><input type="radio" name="pdf_imageAlign" value="L"> 靠左</label>
            <label><input type="radio" name="pdf_imageAlign" value="C" checked> 置中</label>
            <label><input type="radio" name="pdf_imageAlign" value="R"> 靠右</label>
        </div>
    </div>

    <!-- 頁尾設定 -->
    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <label for="pdf_footerText" style="margin-bottom: 0;">頁尾文字（選填，最多5行）</label>
            <a href="javascript:void(0)" class="pdf-hint-trigger"
                style="font-size: 12px; color: #3b82f6; text-decoration: none;">💡 變數說明</a>
        </div>
        <textarea id="pdf_footerText" rows="3" maxlength="500" placeholder="例如：第 {PAGENO} 頁\n版權所有"></textarea>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>頁尾對齊</label>
            <div class="radio-group">
                <label><input type="radio" name="pdf_footerAlign" value="L"> 靠左</label>
                <label><input type="radio" name="pdf_footerAlign" value="C" checked> 置中</label>
                <label><input type="radio" name="pdf_footerAlign" value="R"> 靠右</label>
            </div>
        </div>
        <div class="form-group">
            <label for="pdf_footerFontSize">頁尾文字大小 (pt)</label>
            <input type="number" id="pdf_footerFontSize" value="12" min="8" max="24" step="1">
        </div>
    </div>

    <!-- 圖片高度比例 -->
    <div class="form-group">
        <label for="pdf_imageHeightScale">圖片高度比例 (頁面高度的 <span id="pdf_imageHeightScaleValue">80</span>%)</label>
        <input type="range" id="pdf_imageHeightScale" min="10" max="100" value="80" step="5">
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 5px;">
            <span>10%</span>
            <span>100%</span>
        </div>
    </div>

    <!-- 圖片寬度比例上限 -->
    <div class="form-group">
        <label for="pdf_imageWidthScale">圖片寬度比例上限 (頁面寬度的 <span id="pdf_imageWidthScaleValue">40</span>%)</label>
        <input type="range" id="pdf_imageWidthScale" min="20" max="100" value="40" step="5">
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 5px;">
            <span>20%</span>
            <span>100%</span>
        </div>
        <small style="display: block; margin-top: 5px; color: #666;">圖片會先按高度縮放，如果寬度超過此比例則以寬度為準</small>
    </div>
</div>