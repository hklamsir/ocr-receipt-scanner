<?php
require_once __DIR__ . '/includes/auth_check.php';

// 頁面設定
$pageTitle = '單據記錄';
$headerTitle = '單據記錄';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="history-header">
        <h2>我的單據記錄 <span id="receiptCount" style="color: #666; font-size: 0.8em;"></span></h2>
        <div class="btn-group">
            <button id="exportExcelBtn" class="btn btn-success">📥 匯出 Excel</button>
            <a href="index.php" class="btn btn-warning">+ 新增單據</a>
        </div>
    </div>

    <!-- 搜尋列 -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="🔍 搜尋公司、項目...">
        <select id="yearFilter" title="按年份篩選">
            <option value="">所有年份</option>
        </select>
        <select id="monthFilter" title="按月份篩選">
            <option value="">所有月份</option>
            <option value="01">1月</option>
            <option value="02">2月</option>
            <option value="03">3月</option>
            <option value="04">4月</option>
            <option value="05">5月</option>
            <option value="06">6月</option>
            <option value="07">7月</option>
            <option value="08">8月</option>
            <option value="09">9月</option>
            <option value="10">10月</option>
            <option value="11">11月</option>
            <option value="12">12月</option>
        </select>
        <button id="tagFilterBtn" class="btn btn-secondary">🏷️ 標籤</button>
        <select id="sortSelect" title="排序方式">
            <option value="date_desc">日期 ↓</option>
            <option value="date_asc">日期 ↑</option>
            <option value="company_asc">公司 A-Z</option>
            <option value="company_desc">公司 Z-A</option>
            <option value="payment_asc">支付方式 A-Z</option>
            <option value="payment_desc">支付方式 Z-A</option>
            <option value="amount_desc">金額 ↓</option>
            <option value="amount_asc">金額 ↑</option>
        </select>
        <button id="clearFilterBtn" class="btn btn-outline">清除</button>
    </div>

    <!-- 選取工具列 -->
    <div class="select-toolbar">
        <label class="select-all-label">
            <input type="checkbox" id="selectAllCheckbox">
            <span>全選</span>
        </label>
        <span id="filteredCount" class="filtered-count" style="display:none;"></span>
        <span id="selectedCount" class="selected-count"></span>
        <div class="toolbar-actions" id="toolbarActions" style="display:none;">
            <button id="cancelSelectBtn" class="btn btn-outline btn-sm">✖ 取消選取</button>
            <!-- Desktop buttons - hidden on mobile -->
            <div class="desktop-actions">
                <button id="bulkAddTagBtn" class="btn btn-secondary btn-sm">🏷️ 加入標籤</button>
                <button id="bulkRemoveTagBtn" class="btn btn-warning btn-sm">🏷️ 移除標籤</button>
                <button id="bulkDeleteBtn" class="btn btn-danger btn-sm">🗑️ 刪除選取</button>
                <button id="bulkExportPdfBtn" class="btn btn-success btn-sm">📄 批量匯出PDF</button>
            </div>
            <!-- Mobile button - hidden on desktop -->
            <button id="mobileActionsBtn" class="btn btn-primary btn-sm mobile-actions-btn">📱 操作選單</button>
        </div>
    </div>

    <!-- 已選標籤顯示 -->
    <div id="selectedTagsBar" class="selected-tags-bar" style="display:none;">
        <span>已選標籤：</span>
        <div id="selectedTagsList"></div>
    </div>

    <div id="receipts-container" class="receipt-grid"></div>
    <div id="no-filter-results" class="empty-state" style="display:none;">
        <h3>🔍 查無結果</h3>
        <p>沒有符合篩選條件的單據</p>
        <button id="clearFilterInEmptyBtn" class="btn btn-secondary">清除篩選條件</button>
    </div>
    <div id="empty-state" class="empty-state" style="display:none;">
        <h3>📭 尚無記錄</h3>
        <p>您還沒有儲存任何單據</p>
        <a href="index.php" class="btn btn-primary">開始辨識單據</a>
    </div>

    <!-- 底部匯出按鈕 -->
    <div class="bottom-export-bar">
        <button id="bottomExportBtn" class="btn btn-success">📥 匯出 Excel</button>
    </div>
</div>

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

<!-- 批量刪除確認 Modal -->
<div id="bulkDeleteModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認批量刪除</span>
            <button class="close-btn" onclick="closeBulkDeleteModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p id="bulkDeleteInfo"></p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBulkDeleteModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">🗑️ 確定刪除</button>
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


<!-- 匯出設定 Modal -->
<div id="exportModal" class="edit-modal">
    <div class="edit-modal-content export-modal-content">
        <div class="edit-modal-header">
            <span>📥 匯出設定</span>
            <button class="close-btn" onclick="closeExportModal()">✕</button>
        </div>
        <div class="export-modal-body">
            <!-- 模板選擇區 -->
            <div class="excel-template-section"
                style="padding: 15px 0; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="excelTemplateSelect"
                            style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">快速套用模板</label>
                        <select id="excelTemplateSelect"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">不使用模板</option>
                        </select>
                    </div>
                    <button type="button" id="applyExcelTemplateBtn" class="btn btn-secondary btn-sm"
                        style="margin-top: 22px;">套用</button>
                    <button type="button" id="saveExcelTemplateBtn" class="btn btn-primary btn-sm"
                        style="margin-top: 22px;">💾 另存為模板</button>
                </div>
            </div>

            <p id="exportInfo" style="margin-bottom:15px;color:#666;"></p>
            <p style="margin-bottom:10px;font-weight:600;color:#333;">📌 選擇並排序欄位（拖拉調整順序）：</p>
            <div id="exportFieldsList" class="export-fields-list"></div>
            <div style="margin-top:15px;">
                <button type="button" class="btn btn-outline btn-sm" id="addEmptyColumnBtn">+ 新增空欄位</button>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">取消</button>
                <button type="button" class="btn btn-success" id="confirmExportBtn">📥 匯出</button>
            </div>
        </div>
    </div>
</div>

<!-- 新增空欄位 Modal -->
<div id="addEmptyColumnModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:350px;">
        <div class="edit-modal-header">
            <span>➕ 新增空欄位</span>
            <button class="close-btn" onclick="closeAddEmptyColumnModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group">
                <label for="emptyColumnName">欄位名稱</label>
                <input type="text" id="emptyColumnName" maxlength="20" placeholder="例如：備註">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddEmptyColumnModal()">取消</button>
                <button type="button" class="btn btn-primary" id="confirmAddEmptyColumnBtn">新增</button>
            </div>
        </div>
    </div>
</div>

<!-- PDF 匯出設定 Modal -->
<div id="pdfExportModal" class="edit-modal">
    <div class="edit-modal-content edit-modal-scrollable">
        <div class="edit-modal-header">
            <span>📄 PDF 匯出設定</span>
            <button class="close-btn" onclick="closePdfExportModal()">✕</button>
        </div>

        <!-- 批次資訊顯示 -->
        <div id="pdfBatchInfo"
            style="display:none; padding: 10px 20px; background: #e3f2fd; border-bottom: 1px solid #90caf9; color: #1976d2; font-size: 14px;">
            <strong>批量匯出：</strong><span id="pdfBatchCount"></span>
        </div>

        <!-- 模板選擇區 -->
        <div class="pdf-template-section"
            style="padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="pdfTemplateSelect"
                        style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;">快速套用模板</label>
                    <select id="pdfTemplateSelect"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">不使用模板</option>
                        <!-- 動態載入模板列表 -->
                    </select>
                </div>
                <button type="button" id="applyTemplateBtn" class="btn btn-secondary btn-sm"
                    style="margin-top: 22px;">套用</button>
                <button type="button" id="saveTemplateBtn" class="btn btn-primary btn-sm" style="margin-top: 22px;">💾
                    另存為模板</button>
            </div>
        </div>

        <form id="pdfExportForm" class="edit-modal-body">
            <!-- 頁面大小 -->
            <div class="form-group">
                <label for="pdfPageSize">頁面大小</label>
                <select id="pdfPageSize">
                    <option value="A4" selected>A4 (210 × 297 mm)</option>
                    <option value="A5">A5 (148 × 210 mm)</option>
                    <option value="LETTER">Letter (216 × 279 mm)</option>
                </select>
            </div>

            <!-- 頁面邊界 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="pdfMarginTop">上邊界 (mm)</label>
                    <input type="number" id="pdfMarginTop" value="10" min="0" max="50">
                </div>
                <div class="form-group">
                    <label for="pdfMarginBottom">下邊界 (mm)</label>
                    <input type="number" id="pdfMarginBottom" value="10" min="0" max="50">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="pdfMarginLeft">左邊界 (mm)</label>
                    <input type="number" id="pdfMarginLeft" value="10" min="0" max="50">
                </div>
                <div class="form-group">
                    <label for="pdfMarginRight">右邊界 (mm)</label>
                    <input type="number" id="pdfMarginRight" value="10" min="0" max="50">
                </div>
            </div>

            <!-- 頁首設定 -->
            <div class="form-group">
                <label for="pdfHeader">頁首文字（選填，最多5行）</label>
                <textarea id="pdfHeader" rows="3" maxlength="500" placeholder="例如：我的單據\n2026年度"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>頁首對齊</label>
                    <div class="radio-group">
                        <label><input type="radio" name="pdfHeaderAlign" value="L"> 靠左</label>
                        <label><input type="radio" name="pdfHeaderAlign" value="C" checked> 置中</label>
                        <label><input type="radio" name="pdfHeaderAlign" value="R"> 靠右</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="pdfHeaderFontSize">頁首文字大小 (pt)</label>
                    <input type="number" id="pdfHeaderFontSize" value="12" min="8" max="24" step="1">
                </div>
            </div>

            <!-- 圖片對齊 -->
            <div class="form-group" style="text-align: center;">
                <label>單據圖片對齊</label>
                <div class="radio-group" style="align-items: center; justify-content: center;">
                    <label><input type="radio" name="pdfImageAlign" value="L"> 靠左</label>
                    <label><input type="radio" name="pdfImageAlign" value="C" checked> 置中</label>
                    <label><input type="radio" name="pdfImageAlign" value="R"> 靠右</label>
                </div>
            </div>

            <!-- 頁尾設定 -->
            <div class="form-group">
                <label for="pdfFooter">頁尾文字（選填，最多5行）</label>
                <textarea id="pdfFooter" rows="3" maxlength="500" placeholder="例如：第 {PAGENO} 頁\n版權所有"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>頁尾對齊</label>
                    <div class="radio-group">
                        <label><input type="radio" name="pdfFooterAlign" value="L"> 靠左</label>
                        <label><input type="radio" name="pdfFooterAlign" value="C" checked> 置中</label>
                        <label><input type="radio" name="pdfFooterAlign" value="R"> 靠右</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="pdfFooterFontSize">頁尾文字大小 (pt)</label>
                    <input type="number" id="pdfFooterFontSize" value="12" min="8" max="24" step="1">
                </div>
            </div>

            <!-- 圖片高度比例 -->
            <div class="form-group">
                <label for="pdfImageHeightScale">圖片高度比例 (頁面高度的 <span id="pdfImageHeightScaleValue">80</span>%)</label>
                <input type="range" id="pdfImageHeightScale" min="10" max="100" value="80" step="5">
                <div
                    style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 5px;">
                    <span>10%</span>
                    <span>100%</span>
                </div>
            </div>

            <!-- 圖片寬度比例上限 -->
            <div class="form-group">
                <label for="pdfImageWidthScale">圖片寬度比例上限 (頁面寬度的 <span id="pdfImageWidthScaleValue">40</span>%)</label>
                <input type="range" id="pdfImageWidthScale" min="20" max="100" value="40" step="5">
                <div
                    style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 5px;">
                    <span>20%</span>
                    <span>100%</span>
                </div>
                <small style="display: block; margin-top: 5px; color: #666;">圖片會先按高度縮放，如果寬度超過此比例則以寬度為準</small>
            </div>

            <!-- 自訂檔案名稱 -->
            <div class="form-group">
                <label for="pdfCustomFilename">檔案名稱（選填）</label>
                <input type="text" id="pdfCustomFilename" placeholder="留空使用系統預設檔名" maxlength="100">
                <small style="display: block; margin-top: 5px; color: #666;">預設：<span id="pdfDefaultFilename"
                        style="font-family: monospace;"></span></small>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closePdfExportModal()">取消</button>
                <button type="submit" id="pdfExportBtn" class="btn btn-success">📄 匯出 PDF</button>
            </div>
        </form>
    </div>
</div>

<script type="module" src="js/receipts.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>