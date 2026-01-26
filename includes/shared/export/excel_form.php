<!-- Shared Excel Configuration Form -->
<div class="excel-config-fields">
    <p style="margin-bottom:10px;font-weight:600;color:#333;">📌 選擇並排序欄位（拖拉調整順序）：</p>
    <div id="excel_fieldsList" class="export-fields-list"></div>
    <div style="margin-top:15px; margin-bottom: 25px;">
        <button type="button" class="btn btn-outline btn-sm" id="excel_addEmptyColumnBtn">+ 新增空欄位</button>
    </div>

    <!-- 排序設定 -->
    <div class="sort-settings-section" style="padding-top: 20px; border-top: 1px solid #eee; margin-top: 10px;">
        <p style="margin-bottom:10px;font-weight:600;color:#333;">🔃 排序設定：</p>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 140px;">
                <label for="excel_sortBy"
                    style="display:block; font-size: 13px; color: #666; margin-bottom: 5px;">排序依據</label>
                <select id="excel_sortBy" class="form-control" style="width: 100%; padding: 8px;">
                    <option value="date">日期</option>
                    <option value="company">公司名稱</option>
                    <option value="payment">支付方式</option>
                    <option value="amount">總金額</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 140px;">
                <label for="excel_sortOrder"
                    style="display:block; font-size: 13px; color: #666; margin-bottom: 5px;">排序順序</label>
                <select id="excel_sortOrder" class="form-control" style="width: 100%; padding: 8px;">
                    <option value="desc">由大至小 / 由新至舊 (DESC)</option>
                    <option value="asc">由小至大 / 由舊至新 (ASC)</option>
                </select>
            </div>
        </div>
    </div>
</div>