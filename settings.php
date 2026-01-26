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

<script type="module" src="js/pages/settings.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>