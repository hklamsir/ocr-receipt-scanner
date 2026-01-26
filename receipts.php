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

    <!-- 底部選取工具列 -->
    <div class="select-toolbar bottom-select-toolbar">
        <label class="select-all-label">
            <input type="checkbox" id="bottomSelectAllCheckbox">
            <span>全選</span>
        </label>
        <span id="bottomFilteredCount" class="filtered-count" style="display:none;"></span>
        <span id="bottomSelectedCount" class="selected-count"></span>
        <div class="toolbar-actions" id="bottomToolbarActions" style="display:none;">
            <button id="bottomCancelSelectBtn" class="btn btn-outline btn-sm">✖ 取消選取</button>
            <!-- Desktop buttons - hidden on mobile -->
            <div class="desktop-actions">
                <button id="bottomBulkAddTagBtn" class="btn btn-secondary btn-sm">🏷️ 加入標籤</button>
                <button id="bottomBulkRemoveTagBtn" class="btn btn-warning btn-sm">🏷️ 移除標籤</button>
                <button id="bottomBulkDeleteBtn" class="btn btn-danger btn-sm">🗑️ 刪除選取</button>
                <button id="bottomBulkExportPdfBtn" class="btn btn-success btn-sm">📄 批量匯出PDF</button>
            </div>
            <!-- Mobile button - hidden on desktop -->
            <button id="bottomMobileActionsBtn" class="btn btn-primary btn-sm mobile-actions-btn">📱 操作選單</button>
        </div>
    </div>

    <!-- 底部匯出按鈕 -->
    <div class="bottom-export-bar">
        <button id="bottomExportBtn" class="btn btn-success">📥 匯出 Excel</button>
    </div>
</div>

<?php
// Main Modals (Preview, Edit, Delete, Mobile)
include __DIR__ . '/includes/receipts/main_modals.php';

// Tag Management Modals
include __DIR__ . '/includes/receipts/tag_modals.php';

// Export & Bulk Modals
include __DIR__ . '/includes/receipts/export_modals.php';
?>

<script>
    // Pre-define global functions that may be called by inline onclick handlers
    // before ES module finishes loading. These will be overwritten by the module.
    window.openPdfExportModal = function (id) {
        console.warn('[Pre-module] openPdfExportModal called before module loaded, queuing...');
        window._pendingPdfExportId = id;
    };
    window.openEditModal = function (id) {
        console.warn('[Pre-module] openEditModal called before module loaded');
    };
    window.openDeleteModal = function (id) {
        console.warn('[Pre-module] openDeleteModal called before module loaded');
    };
    window.openModal = function (src) {
        console.warn('[Pre-module] openModal called before module loaded');
    };
</script>
<script type="module" src="js/receipts.js"></script>

<!-- PDF 變數說明 Modal (Moved from include for reliability) -->
<div id="pdfHintModal" class="edit-modal" style="z-index: 1100;">
    <div class="edit-modal-content" style="max-width: 500px;">
        <div class="edit-modal-header">
            <span>💡 PDF 變數說明</span>
            <button class="close-btn" onclick="closePdfHintModal()">✕</button>
        </div>
        <div class="edit-modal-body" style="padding: 20px;">
            <p style="margin-bottom: 15px; font-size: 14px; color: #666;">您可以在頁首或頁尾文字中使用以下變數，系統將在產生 PDF 時自動替換為實際內容：</p>
            <table class="hint-table">
                <thead>
                    <tr>
                        <th>變數</th>
                        <th>說明</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="code-var">{PAGENO}</td><td>當前頁碼</td></tr>
                    <tr><td class="code-var">{PAGES}</td><td>總頁數</td></tr>
                    <tr><td class="code-var">{TODAY}</td><td>今日日期 (YYYY-MM-DD)</td></tr>
                    <tr><td class="code-var">{NOW}</td><td>當前時間 (HH:MM)</td></tr>
                    <tr><td class="code-var">{USER}</td><td>使用者名稱</td></tr>
                    <tr><td class="code-var">{COMPANY}</td><td>單據公司名稱</td></tr>
                    <tr><td class="code-var">{DATE}</td><td>單據日期</td></tr>
                    <tr><td class="code-var">{AMOUNT}</td><td>單據金額</td></tr>
                    <tr><td class="code-var">{PAYMENT}</td><td>支付方式</td></tr>
                    <tr><td class="code-var">{SUMMARY}</td><td>單據總結</td></tr>
                    <tr><td class="code-var">{ITEMS}</td><td>項目摘要</td></tr>
                </tbody>
            </table>
            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="closePdfHintModal()">關閉</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>