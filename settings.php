<?php
require_once __DIR__ . '/includes/auth_check.php';

// 頁面設定
$pageTitle = '設定';
$headerTitle = '設定';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h2>設定</h2>

    <div class="settings-grid">
        <!-- 管理標籤卡片 -->
        <div class="settings-card" id="tagsCard">
            <div class="settings-card-header">
                <span class="settings-card-icon">🏷️</span>
                <h3>管理標籤</h3>
            </div>
            <p>新增、編輯、刪除和排序您的標籤</p>
            <button class="btn btn-primary" onclick="openTagsManager()">管理標籤</button>
        </div>

        <!-- Excel 模板管理卡片 -->
        <div class="settings-card" id="excelTemplatesCard">
            <div class="settings-card-header">
                <span class="settings-card-icon">📊</span>
                <h3>Excel 模板管理</h3>
            </div>
            <p>管理您的 Excel 匯出設定模板</p>
            <button class="btn btn-primary" onclick="openExcelTemplatesManager()">管理模板</button>
        </div>

        <!-- PDF 模板管理卡片 -->
        <div class="settings-card" id="pdfTemplatesCard">
            <div class="settings-card-header">
                <span class="settings-card-icon">📄</span>
                <h3>PDF 模板管理</h3>
            </div>
            <p>管理您的 PDF 匯出設定模板</p>
            <button class="btn btn-primary" onclick="openPdfTemplatesManager()">管理模板</button>
        </div>

        <!-- 變更密碼卡片 -->
        <div class="settings-card" id="passwordCard">
            <div class="settings-card-header">
                <span class="settings-card-icon">🔐</span>
                <h3>變更密碼</h3>
            </div>
            <p>更改您的登入密碼</p>
            <button class="btn btn-primary" onclick="openPasswordModal()">變更密碼</button>
        </div>

    </div>
</div>

<?php
// Tags 管理 Modals
include __DIR__ . '/includes/settings/tags_modals.php';

// 變更密碼 Modal
include __DIR__ . '/includes/settings/password_modal.php';

// PDF 模板管理 Modals
include __DIR__ . '/includes/settings/pdf_modals.php';

// Excel 模板管理 Modals
include __DIR__ . '/includes/settings/excel_modals.php';
?>

<script type="module" src="js/settings.js"></script>


<!-- PDF 變數說明 Modal (Copied from receipts.php for consistency) -->
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
                    <tr>
                        <td class="code-var">{PAGENO}</td>
                        <td>當前頁碼</td>
                    </tr>
                    <tr>
                        <td class="code-var">{PAGES}</td>
                        <td>總頁數</td>
                    </tr>
                    <tr>
                        <td class="code-var">{TODAY}</td>
                        <td>今日日期 (YYYY-MM-DD)</td>
                    </tr>
                    <tr>
                        <td class="code-var">{NOW}</td>
                        <td>當前時間 (HH:MM)</td>
                    </tr>
                    <tr>
                        <td class="code-var">{USER}</td>
                        <td>使用者名稱</td>
                    </tr>
                    <tr>
                        <td class="code-var">{COMPANY}</td>
                        <td>單據公司名稱</td>
                    </tr>
                    <tr>
                        <td class="code-var">{DATE}</td>
                        <td>單據日期</td>
                    </tr>
                    <tr>
                        <td class="code-var">{AMOUNT}</td>
                        <td>單據金額</td>
                    </tr>
                    <tr>
                        <td class="code-var">{PAYMENT}</td>
                        <td>支付方式</td>
                    </tr>
                    <tr>
                        <td class="code-var">{SUMMARY}</td>
                        <td>單據總結</td>
                    </tr>
                    <tr>
                        <td class="code-var">{ITEMS}</td>
                        <td>項目摘要</td>
                    </tr>
                </tbody>
            </table>
            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-secondary" style="width: 100%;"
                    onclick="closePdfHintModal()">關閉</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>