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

        <!-- Excel 模板管理卡片 -->
        <div class="settings-card" id="excelTemplatesCard">
            <div class="settings-card-header">
                <span class="settings-card-icon">📊</span>
                <h3>Excel 模板管理</h3>
            </div>
            <p>管理您的 Excel 匯出設定模板</p>
            <button class="btn btn-primary" onclick="openExcelTemplatesManager()">管理模板</button>
        </div>

    </div>
</div>

<!-- Tags 管理 Modal -->
<div id="tagsManagerModal" class="edit-modal">
    <div class="edit-modal-content tags-manager-content">
        <div class="edit-modal-header">
            <span>🏷️ 管理標籤</span>
            <button class="close-btn" onclick="closeTagsManager()">✕</button>
        </div>
        <div class="tags-manager-body">
            <!-- 批量新增區 -->
            <div class="form-group">
                <label>批量新增標籤</label>
                <div class="batch-add-row">
                    <input type="text" id="batchTagInput" placeholder="輸入標籤名稱，用逗號分隔（如：餐飲, 交通, 辦公）">
                    <button class="btn btn-primary" id="batchAddBtn">新增</button>
                </div>
            </div>

            <!-- 選擇顏色 -->
            <div class="form-group">
                <label>選擇顏色</label>
                <div class="color-palette" id="batchColorPalette"></div>
                <input type="hidden" id="selectedBatchColor" value="#3b82f6">
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">

            <!-- 現有標籤列表 -->
            <div class="form-group">
                <label>現有標籤 <span style="color:#999;font-weight:normal;">(拖拽排序)</span></label>
                <div id="tagsList" class="tags-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- 編輯單個 Tag Modal -->
<div id="editTagModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:400px;">
        <div class="edit-modal-header">
            <span>✏️ 編輯標籤</span>
            <button class="close-btn" onclick="closeEditTagModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <input type="hidden" id="editTagId">
            <div class="form-group">
                <label for="editTagName">標籤名稱</label>
                <input type="text" id="editTagName" maxlength="50">
            </div>
            <div class="form-group">
                <label>顏色</label>
                <div class="color-palette" id="editColorPalette"></div>
                <input type="hidden" id="selectedEditColor">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditTagModal()">取消</button>
                <button type="button" class="btn btn-primary" id="saveEditTagBtn">儲存</button>
            </div>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div id="deleteTagModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認刪除</span>
            <button class="close-btn" onclick="closeDeleteTagModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p>確定要刪除標籤 「<span id="deleteTagName"></span>」嗎？</p>
                <p style="color:#999;font-size:13px;">此標籤將從所有單據中移除。</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteTagModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTagBtn">刪除</button>
            </div>
        </div>
    </div>
</div>

<!-- 變更密碼 Modal -->
<div id="passwordModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:400px;">
        <div class="edit-modal-header">
            <span>🔐 變更密碼</span>
            <button class="close-btn" onclick="closePasswordModal()">✕</button>
        </div>
        <form id="passwordForm" style="padding:20px;">
            <div class="form-group">
                <label for="currentPassword">目前密碼</label>
                <input type="password" id="currentPassword" required>
            </div>
            <div class="form-group">
                <label for="newPassword">新密碼</label>
                <input type="password" id="newPassword" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirmPassword">確認新密碼</label>
                <input type="password" id="confirmPassword" required minlength="6">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">取消</button>
                <button type="submit" class="btn btn-primary">變更密碼</button>
            </div>
        </form>
    </div>
</div>

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

<!-- Excel 模板管理 Modal -->
<div id="excelTemplatesManagerModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width: 700px;">
        <div class="edit-modal-header">
            <span>📊 Excel 模板管理</span>
            <button class="close-btn" onclick="closeExcelTemplatesManager()">✕</button>
        </div>
        <div style="padding: 20px;">
            <div id="excelTemplatesList"></div>
        </div>
    </div>
</div>

<!-- 編輯 Excel 模板 Modal -->
<div id="editExcelTemplateModal" class="edit-modal">
    <div class="edit-modal-content edit-modal-scrollable" style="max-width: 600px;">
        <div class="edit-modal-header">
            <span>✏️ 編輯模板</span>
            <button class="close-btn" onclick="closeEditExcelTemplateModal()">✕</button>
        </div>
        <form id="editExcelTemplateForm" class="edit-modal-body">
            <input type="hidden" id="editExcelTemplateId">

            <!-- 模板名稱 -->
            <div class="form-group">
                <label for="editExcelTemplateName">模板名稱</label>
                <input type="text" id="editExcelTemplateName" required maxlength="100">
            </div>

            <!-- 設為預設 -->
            <div class="form-group">
                <label>
                    <input type="checkbox" id="editExcelTemplateIsDefault">
                    設為預設模板（開啟匯出時自動套用）
                </label>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

            <!-- 欄位配置 -->
            <div class="form-group">
                <label style="font-weight: 600;">📌 選擇並排序欄位（拖拉調整順序）：</label>
                <div id="editExcelFieldsList" class="export-fields-list"></div>
                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-outline btn-sm" id="editExcelAddEmptyColumnBtn">+
                        新增空欄位</button>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditExcelTemplateModal()">取消</button>
                <button type="submit" class="btn btn-success">儲存</button>
            </div>
        </form>
    </div>
</div>

<!-- 編輯 Excel 模板時新增空欄位 Modal -->
<div id="editExcelAddColumnModal" class="edit-modal">
    <div class="edit-modal-content" style="max-width:350px;">
        <div class="edit-modal-header">
            <span>➕ 新增空欄位</span>
            <button class="close-btn" onclick="closeEditExcelAddColumnModal()">✕</button>
        </div>
        <div style="padding:20px;">
            <div class="form-group">
                <label for="editExcelEmptyColumnName">欄位名稱</label>
                <input type="text" id="editExcelEmptyColumnName" maxlength="20" placeholder="例如：備註">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditExcelAddColumnModal()">取消</button>
                <button type="button" class="btn btn-primary" id="confirmEditExcelAddColumnBtn">新增</button>
            </div>
        </div>
    </div>
</div>

<!-- 刪除 Excel 模板確認 Modal -->
<div id="deleteExcelTemplateModal" class="edit-modal">
    <div class="edit-modal-content delete-confirm">
        <div class="edit-modal-header">
            <span>⚠️ 確認刪除</span>
            <button class="close-btn" onclick="closeDeleteExcelTemplateModal()">✕</button>
        </div>
        <div class="delete-body">
            <div class="delete-message">
                <p>確定要刪除模板「<span id="deleteExcelTemplateName"></span>」嗎？</p>
                <p style="color:#999;font-size:13px;">此操作無法復原。</p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteExcelTemplateModal()">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteExcelTemplateBtn">刪除</button>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import { Toast } from './js/modules/toast.js';

    // CSRF Token 輔助函數
    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function getCSRFHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken()
        };
    }

    // 30 色調色盤 (6 hues x 5 shades)
    const PRESET_COLORS = [
        // Red, Orange, Green, Blue, Purple, Pink (shades 300 to 700)
        '#fca5a5', '#fdba74', '#86efac', '#93c5fd', '#d8b4fe', '#f9a8d4', // 300
        '#f87171', '#fb923c', '#4ade80', '#60a5fa', '#a78bfa', '#f472b6', // 400
        '#ef4444', '#f97316', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', // 500
        '#dc2626', '#ea580c', '#16a34a', '#2563eb', '#7c3aed', '#db2777', // 600
        '#b91c1c', '#c2410c', '#15803d', '#1d4ed8', '#6d28d9', '#be185d'  // 700
    ];

    let allTags = [];
    let deleteTagId = null;
    let draggedItem = null;

    // 初始化調色盤
    function renderColorPalette(containerId, selectedColor, onSelect) {
        const container = document.getElementById(containerId);
        container.innerHTML = PRESET_COLORS.map(color => `
                <div class="color-swatch ${color === selectedColor ? 'selected' : ''}" 
                     data-color="${color}" 
                     style="background:${color};">
                </div>
            `).join('');

        container.querySelectorAll('.color-swatch').forEach(swatch => {
            swatch.addEventListener('click', () => {
                container.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
                swatch.classList.add('selected');
                onSelect(swatch.dataset.color);
            });
        });
    }

    // 載入所有 tags
    async function loadTags() {
        try {
            const res = await fetch('api/tags.php');
            const data = await res.json();
            if (data.success) {
                allTags = data.tags;
                renderTagsList();
            }
        } catch (err) {
            console.error('載入標籤失敗:', err);
        }
    }

    // 渲染 tags 列表
    function renderTagsList() {
        const list = document.getElementById('tagsList');
        if (allTags.length === 0) {
            list.innerHTML = '<p style="color:#999;text-align:center;padding:20px;">尚無標籤</p>';
            return;
        }

        list.innerHTML = allTags.map((tag, index) => `
                <div class="tag-list-item" draggable="true" data-id="${tag.id}" data-index="${index}">
                    <span class="drag-handle">⋮⋮</span>
                    <span class="tag" style="background:${tag.color};">${tag.name}</span>
                    <div class="tag-actions">
                        <button class="btn btn-sm btn-secondary" onclick="openEditTagModal(${tag.id})">✏️</button>
                        <button class="btn btn-sm btn-danger" onclick="openDeleteTagModal(${tag.id})">🗑️</button>
                    </div>
                </div>
            `).join('');

        // 拖拽排序
        setupDragAndDrop();
    }

    function setupDragAndDrop() {
        const items = document.querySelectorAll('.tag-list-item');
        items.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('drop', handleDrop);
        });
    }

    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        draggedItem = null;
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDrop(e) {
        e.preventDefault();
        if (draggedItem === this) return;

        const list = document.getElementById('tagsList');
        const items = [...list.querySelectorAll('.tag-list-item')];
        const fromIndex = items.indexOf(draggedItem);
        const toIndex = items.indexOf(this);

        if (fromIndex < toIndex) {
            this.after(draggedItem);
        } else {
            this.before(draggedItem);
        }

        // 更新排序
        saveTagsOrder();
    }

    async function saveTagsOrder() {
        const items = document.querySelectorAll('.tag-list-item');
        const order = [...items].map((item, index) => ({
            id: parseInt(item.dataset.id),
            sort_order: index
        }));

        try {
            const res = await fetch('api/tags.php', {
                method: 'PATCH',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ order })
            });
            const result = await res.json();
            if (result.success) {
                Toast.success('排序已更新');
            }
        } catch (err) {
            Toast.error('排序更新失敗');
        }
    }

    // Tags Manager Modal
    window.openTagsManager = function () {
        loadTags();
        renderColorPalette('batchColorPalette', '#3b82f6', (color) => {
            document.getElementById('selectedBatchColor').value = color;
        });
        document.getElementById('tagsManagerModal').style.display = 'flex';
    };

    window.closeTagsManager = function () {
        document.getElementById('tagsManagerModal').style.display = 'none';
    };

    // 批量新增
    document.getElementById('batchAddBtn').addEventListener('click', async () => {
        const input = document.getElementById('batchTagInput').value.trim();
        if (!input) {
            Toast.warning('請輸入標籤名稱');
            return;
        }

        const names = input.split(/[,，]/).map(n => n.trim()).filter(n => n);
        if (names.length === 0) {
            Toast.warning('請輸入有效的標籤名稱');
            return;
        }

        const color = document.getElementById('selectedBatchColor').value;

        try {
            const res = await fetch('api/tags.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ names, color })
            });
            const result = await res.json();
            if (result.success) {
                Toast.success(`成功新增 ${result.created} 個標籤`);
                document.getElementById('batchTagInput').value = '';
                loadTags();
            } else {
                Toast.error(result.error || '新增失敗');
            }
        } catch (err) {
            Toast.error('新增標籤失敗');
        }
    });

    // 編輯 Tag
    window.openEditTagModal = function (id) {
        const tag = allTags.find(t => t.id === id);
        if (!tag) return;

        document.getElementById('editTagId').value = id;
        document.getElementById('editTagName').value = tag.name;
        document.getElementById('selectedEditColor').value = tag.color;

        renderColorPalette('editColorPalette', tag.color, (color) => {
            document.getElementById('selectedEditColor').value = color;
        });

        document.getElementById('editTagModal').style.display = 'flex';
    };

    window.closeEditTagModal = function () {
        document.getElementById('editTagModal').style.display = 'none';
    };

    document.getElementById('saveEditTagBtn').addEventListener('click', async () => {
        const id = parseInt(document.getElementById('editTagId').value);
        const name = document.getElementById('editTagName').value.trim();
        const color = document.getElementById('selectedEditColor').value;

        if (!name) {
            Toast.warning('請輸入標籤名稱');
            return;
        }

        try {
            const res = await fetch('api/tags.php', {
                method: 'PUT',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ id, name, color })
            });
            const result = await res.json();
            if (result.success) {
                Toast.success('標籤已更新');
                closeEditTagModal();
                loadTags();
            } else {
                Toast.error(result.error || '更新失敗');
            }
        } catch (err) {
            Toast.error('更新標籤失敗');
        }
    });

    // 刪除 Tag
    window.openDeleteTagModal = function (id) {
        const tag = allTags.find(t => t.id === id);
        if (!tag) return;

        deleteTagId = id;
        document.getElementById('deleteTagName').textContent = tag.name;
        document.getElementById('deleteTagModal').style.display = 'flex';
    };

    window.closeDeleteTagModal = function () {
        deleteTagId = null;
        document.getElementById('deleteTagModal').style.display = 'none';
    };

    document.getElementById('confirmDeleteTagBtn').addEventListener('click', async () => {
        if (!deleteTagId) return;

        try {
            const res = await fetch('api/tags.php', {
                method: 'DELETE',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ id: deleteTagId })
            });
            const result = await res.json();
            if (result.success) {
                Toast.success('標籤已刪除');
                closeDeleteTagModal();
                loadTags();
            } else {
                Toast.error(result.error || '刪除失敗');
            }
        } catch (err) {
            Toast.error('刪除標籤失敗');
        }
    });

    // 變更密碼
    window.openPasswordModal = function () {
        document.getElementById('passwordForm').reset();
        document.getElementById('passwordModal').style.display = 'flex';
    };

    window.closePasswordModal = function () {
        document.getElementById('passwordModal').style.display = 'none';
    };

    document.getElementById('passwordForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            Toast.error('新密碼與確認密碼不符');
            return;
        }

        if (newPassword.length < 6) {
            Toast.error('新密碼至少需要 6 個字元');
            return;
        }

        try {
            const res = await fetch('api/change_password.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
            });
            const result = await res.json();
            if (result.success) {
                Toast.success('密碼已更新');
                closePasswordModal();
            } else {
                Toast.error(result.error || '變更密碼失敗');
            }
        } catch (err) {
            Toast.error('變更密碼失敗');
        }
    });

    // ========================================
    // PDF 模板管理
    // ========================================
    let pdfTemplates = [];
    let deletePdfTemplateId = null;

    // 開啟 PDF 模板管理
    window.openPdfTemplatesManager = async function () {
        await loadPdfTemplates();
        document.getElementById('pdfTemplatesManagerModal').style.display = 'flex';
    };

    window.closePdfTemplatesManager = function () {
        document.getElementById('pdfTemplatesManagerModal').style.display = 'none';
    };

    // 載入 PDF 模板
    async function loadPdfTemplates() {
        try {
            const res = await fetch('api/get_pdf_templates.php');
            const data = await res.json();

            if (data.success) {
                // 只顯示用戶自己的模板（排除系統模板）
                pdfTemplates = data.templates.filter(t => !t.is_system);
                renderPdfTemplatesList();
            }
        } catch (err) {
            console.error('載入模板失敗:', err);
            Toast.error('載入模板失敗');
        }
    }

    // 渲染模板列表
    function renderPdfTemplatesList() {
        const list = document.getElementById('pdfTemplatesList');

        if (pdfTemplates.length === 0) {
            list.innerHTML = '<p style="color:#999;text-align:center;padding:40px;">尚無自訂模板<br><small>您可以在 PDF 匯出時點擊「另存為模板」來建立模板</small></p>';
            return;
        }

        list.innerHTML = pdfTemplates.map(t => `
            <div class="template-item" style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 5px;">
                        ${t.template_name}
                        ${t.is_default ? '<span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">預設</span>' : ''}
                    </div>
                    <div style="font-size: 13px; color: #666;">
                        ${t.page_size} | 邊界 ${t.margin_top}mm | 頁首/頁尾 ${t.header_font_size}pt/${t.footer_font_size}pt
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-sm btn-secondary" onclick="openEditPdfTemplateModal(${t.id})">✏️ 編輯</button>
                    <button class="btn btn-sm btn-danger" onclick="openDeletePdfTemplateModal(${t.id})">🗑️ 刪除</button>
                </div>
            </div>
        `).join('');
    }

    // 編輯模板
    window.openEditPdfTemplateModal = function (id) {
        const template = pdfTemplates.find(t => t.id === id);
        if (!template) return;

        // 基本資訊
        document.getElementById('editTemplateId').value = id;
        document.getElementById('editTemplateName').value = template.template_name;
        document.getElementById('editTemplateIsDefault').checked = template.is_default;

        // 頁面設定
        document.getElementById('editPageSize').value = template.page_size;
        document.getElementById('editMarginTop').value = template.margin_top;
        document.getElementById('editMarginBottom').value = template.margin_bottom;
        document.getElementById('editMarginLeft').value = template.margin_left;
        document.getElementById('editMarginRight').value = template.margin_right;

        // 頁首設定
        document.getElementById('editHeader').value = template.header_text || '';
        const headerAlignRadio = document.querySelector(`input[name="editHeaderAlign"][value="${template.header_align}"]`);
        if (headerAlignRadio) headerAlignRadio.checked = true;
        document.getElementById('editHeaderFontSize').value = template.header_font_size;

        // 頁尾設定
        document.getElementById('editFooter').value = template.footer_text || '';
        const footerAlignRadio = document.querySelector(`input[name="editFooterAlign"][value="${template.footer_align}"]`);
        if (footerAlignRadio) footerAlignRadio.checked = true;
        document.getElementById('editFooterFontSize').value = template.footer_font_size;

        // 圖片設定
        const imageAlignRadio = document.querySelector(`input[name="editImageAlign"][value="${template.image_align}"]`);
        if (imageAlignRadio) imageAlignRadio.checked = true;
        document.getElementById('editImageHeightScale').value = template.image_height_scale;
        document.getElementById('editImageHeightScaleValue').textContent = template.image_height_scale;
        document.getElementById('editImageWidthScale').value = template.image_width_scale;
        document.getElementById('editImageWidthScaleValue').textContent = template.image_width_scale;

        document.getElementById('editPdfTemplateModal').style.display = 'flex';
    };

    window.closeEditPdfTemplateModal = function () {
        document.getElementById('editPdfTemplateModal').style.display = 'none';
    };

    // 滑桿事件
    document.getElementById('editImageHeightScale').addEventListener('input', function () {
        document.getElementById('editImageHeightScaleValue').textContent = this.value;
    });

    document.getElementById('editImageWidthScale').addEventListener('input', function () {
        document.getElementById('editImageWidthScaleValue').textContent = this.value;
    });

    document.getElementById('editPdfTemplateForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = parseInt(document.getElementById('editTemplateId').value);

        const templateData = {
            template_id: id,
            template_name: document.getElementById('editTemplateName').value.trim(),
            is_default: document.getElementById('editTemplateIsDefault').checked,
            page_size: document.getElementById('editPageSize').value,
            margin_top: document.getElementById('editMarginTop').value,
            margin_bottom: document.getElementById('editMarginBottom').value,
            margin_left: document.getElementById('editMarginLeft').value,
            margin_right: document.getElementById('editMarginRight').value,
            header_text: document.getElementById('editHeader').value,
            header_align: document.querySelector('input[name="editHeaderAlign"]:checked')?.value || 'C',
            header_font_size: document.getElementById('editHeaderFontSize').value,
            footer_text: document.getElementById('editFooter').value,
            footer_align: document.querySelector('input[name="editFooterAlign"]:checked')?.value || 'C',
            footer_font_size: document.getElementById('editFooterFontSize').value,
            image_align: document.querySelector('input[name="editImageAlign"]:checked')?.value || 'C',
            image_height_scale: document.getElementById('editImageHeightScale').value,
            image_width_scale: document.getElementById('editImageWidthScale').value
        };

        try {
            const res = await fetch('api/update_pdf_template.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify(templateData)
            });
            const data = await res.json();

            if (data.success) {
                Toast.success('模板更新成功');
                closeEditPdfTemplateModal();
                loadPdfTemplates();
            } else {
                Toast.error(data.error || '更新失敗');
            }
        } catch (err) {
            console.error('更新模板失敗:', err);
            Toast.error('更新模板失敗');
        }
    });

    // 刪除模板
    window.openDeletePdfTemplateModal = function (id) {
        const template = pdfTemplates.find(t => t.id === id);
        if (!template) return;

        deletePdfTemplateId = id;
        document.getElementById('deletePdfTemplateName').textContent = template.template_name;
        document.getElementById('deletePdfTemplateModal').style.display = 'flex';
    };

    window.closeDeletePdfTemplateModal = function () {
        deletePdfTemplateId = null;
        document.getElementById('deletePdfTemplateModal').style.display = 'none';
    };

    document.getElementById('confirmDeletePdfTemplateBtn').addEventListener('click', async () => {
        if (!deletePdfTemplateId) return;

        try {
            const res = await fetch('api/delete_pdf_template.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ template_id: deletePdfTemplateId })
            });
            const data = await res.json();

            if (data.success) {
                Toast.success('模板刪除成功');
                closeDeletePdfTemplateModal();
                loadPdfTemplates();
            } else {
                Toast.error(data.error || '刪除失敗');
            }
        } catch (err) {
            console.error('刪除模板失敗:', err);
            Toast.error('刪除模板失敗');
        }
    });

    // ========================================
    // Excel 模板管理
    // ========================================
    let excelTemplates = [];
    let deleteExcelTemplateId = null;
    let editExcelTemplateData = null;

    // 開啟 Excel 模板管理
    window.openExcelTemplatesManager = async function () {
        await loadExcelTemplates();
        document.getElementById('excelTemplatesManagerModal').style.display = 'flex';
    };

    window.closeExcelTemplatesManager = function () {
        document.getElementById('excelTemplatesManagerModal').style.display = 'none';
    };

    // 載入 Excel 模板
    async function loadExcelTemplates() {
        try {
            const res = await fetch('api/get_excel_templates.php');
            const data = await res.json();

            if (data.success) {
                // 只顯示用戶自己的模板（排除系統模板）
                excelTemplates = data.templates.filter(t => !t.is_system);
                renderExcelTemplatesList();
            }
        } catch (err) {
            console.error('載入模板失敗:', err);
            Toast.error('載入模板失敗');
        }
    }

    // 渲染模板列表
    function renderExcelTemplatesList() {
        const list = document.getElementById('excelTemplatesList');

        if (excelTemplates.length === 0) {
            list.innerHTML = '<p style="color:#999;text-align:center;padding:40px;">尚無自訂模板<br><small>您可以在 Excel 匯出時點擊「另存為模板」來建立模板</small></p>';
            return;
        }

        list.innerHTML = excelTemplates.map(t => {
            const enabledFields = t.fields_config.filter(f => f.enabled).map(f => f.label).join(', ');
            return `
            <div class="template-item" style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 5px;">
                        ${t.template_name}
                        ${t.is_default ? '<span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">預設</span>' : ''}
                    </div>
                    <div style="font-size: 13px; color: #666;">
                        欄位: ${enabledFields || '(無)'}
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-sm btn-secondary" onclick="openEditExcelTemplateModal(${t.id})">✏️ 編輯</button>
                    <button class="btn btn-sm btn-danger" onclick="openDeleteExcelTemplateModal(${t.id})">🗑️ 刪除</button>
                </div>
            </div>
        `;
        }).join('');
    }

    // 編輯模板
    let excelEditDraggedItem = null;

    window.openEditExcelTemplateModal = function (id) {
        const template = excelTemplates.find(t => t.id === id);
        if (!template) return;

        // Deep copy the fields config so we can edit it
        editExcelTemplateData = {
            ...template,
            fields_config: template.fields_config.map(f => ({ ...f }))
        };

        // 基本資訊
        document.getElementById('editExcelTemplateId').value = id;
        document.getElementById('editExcelTemplateName').value = template.template_name;
        document.getElementById('editExcelTemplateIsDefault').checked = template.is_default;

        // 渲染欄位配置列表
        renderEditExcelFieldsList();

        document.getElementById('editExcelTemplateModal').style.display = 'flex';
    };

    // 渲染欄位列表
    function renderEditExcelFieldsList() {
        const container = document.getElementById('editExcelFieldsList');
        container.innerHTML = editExcelTemplateData.fields_config.map((field, index) => `
            <div class="export-field-item ${field.enabled ? 'enabled' : ''}" 
                 data-index="${index}" 
                 draggable="true">
                <span class="drag-handle">☰</span>
                <label class="export-field-label">
                    <input type="checkbox" class="edit-excel-field-checkbox" 
                           data-index="${index}" 
                           ${field.enabled ? 'checked' : ''}>
                    <span>${field.label}</span>
                </label>
                ${field.key.startsWith('empty_') ?
                `<button type="button" class="remove-empty-column" data-index="${index}">✕</button>` :
                ''}
            </div>
        `).join('');

        // 綁定拖拉事件
        container.querySelectorAll('.export-field-item').forEach(item => {
            item.addEventListener('dragstart', handleExcelEditDragStart);
            item.addEventListener('dragend', handleExcelEditDragEnd);
            item.addEventListener('dragover', handleExcelEditDragOver);
            item.addEventListener('drop', handleExcelEditDrop);
            item.addEventListener('dragenter', handleExcelEditDragEnter);
            item.addEventListener('dragleave', handleExcelEditDragLeave);
        });

        // 綁定 checkbox 事件
        container.querySelectorAll('.edit-excel-field-checkbox').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                editExcelTemplateData.fields_config[index].enabled = e.target.checked;
                e.target.closest('.export-field-item').classList.toggle('enabled', e.target.checked);
            });
        });

        // 綁定移除空欄位事件
        container.querySelectorAll('.remove-empty-column').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(e.target.dataset.index);
                editExcelTemplateData.fields_config.splice(index, 1);
                renderEditExcelFieldsList();
            });
        });
    }

    // 拖拉排序處理函數
    function handleExcelEditDragStart(e) {
        excelEditDraggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.dataset.index);
    }

    function handleExcelEditDragEnd() {
        this.classList.remove('dragging');
        document.querySelectorAll('#editExcelFieldsList .export-field-item').forEach(item => {
            item.classList.remove('drag-over');
        });
        excelEditDraggedItem = null;
    }

    function handleExcelEditDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleExcelEditDragEnter(e) {
        e.preventDefault();
        if (this !== excelEditDraggedItem) {
            this.classList.add('drag-over');
        }
    }

    function handleExcelEditDragLeave() {
        this.classList.remove('drag-over');
    }

    function handleExcelEditDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        if (excelEditDraggedItem === this) return;

        const fromIndex = parseInt(excelEditDraggedItem.dataset.index);
        const toIndex = parseInt(this.dataset.index);

        const [movedItem] = editExcelTemplateData.fields_config.splice(fromIndex, 1);
        editExcelTemplateData.fields_config.splice(toIndex, 0, movedItem);

        renderEditExcelFieldsList();
    }

    // 新增空欄位
    document.getElementById('editExcelAddEmptyColumnBtn').addEventListener('click', () => {
        document.getElementById('editExcelEmptyColumnName').value = '';
        document.getElementById('editExcelAddColumnModal').style.display = 'flex';
    });

    window.closeEditExcelAddColumnModal = function () {
        document.getElementById('editExcelAddColumnModal').style.display = 'none';
    };

    document.getElementById('confirmEditExcelAddColumnBtn').addEventListener('click', () => {
        const name = document.getElementById('editExcelEmptyColumnName').value.trim();
        if (!name) {
            Toast.warning('請輸入欄位名稱');
            return;
        }

        const uniqueKey = 'empty_' + Date.now();
        editExcelTemplateData.fields_config.push({
            key: uniqueKey,
            label: name,
            enabled: true
        });

        renderEditExcelFieldsList();
        closeEditExcelAddColumnModal();
        Toast.success('已新增欄位');
    });

    window.closeEditExcelTemplateModal = function () {
        document.getElementById('editExcelTemplateModal').style.display = 'none';
        editExcelTemplateData = null;
    };

    document.getElementById('editExcelTemplateForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = parseInt(document.getElementById('editExcelTemplateId').value);

        const templateData = {
            template_id: id,
            template_name: document.getElementById('editExcelTemplateName').value.trim(),
            is_default: document.getElementById('editExcelTemplateIsDefault').checked,
            fields_config: editExcelTemplateData.fields_config
        };

        try {
            const res = await fetch('api/update_excel_template.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify(templateData)
            });
            const data = await res.json();

            if (data.success) {
                Toast.success('模板更新成功');
                closeEditExcelTemplateModal();
                loadExcelTemplates();
            } else {
                Toast.error(data.error || '更新失敗');
            }
        } catch (err) {
            console.error('更新模板失敗:', err);
            Toast.error('更新模板失敗');
        }
    });


    // 刪除模板
    window.openDeleteExcelTemplateModal = function (id) {
        const template = excelTemplates.find(t => t.id === id);
        if (!template) return;

        deleteExcelTemplateId = id;
        document.getElementById('deleteExcelTemplateName').textContent = template.template_name;
        document.getElementById('deleteExcelTemplateModal').style.display = 'flex';
    };

    window.closeDeleteExcelTemplateModal = function () {
        deleteExcelTemplateId = null;
        document.getElementById('deleteExcelTemplateModal').style.display = 'none';
    };

    document.getElementById('confirmDeleteExcelTemplateBtn').addEventListener('click', async () => {
        if (!deleteExcelTemplateId) return;

        try {
            const res = await fetch('api/delete_excel_template.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ template_id: deleteExcelTemplateId })
            });
            const data = await res.json();

            if (data.success) {
                Toast.success('模板刪除成功');
                closeDeleteExcelTemplateModal();
                loadExcelTemplates();
            } else {
                Toast.error(data.error || '刪除失敗');
            }
        } catch (err) {
            console.error('刪除模板失敗:', err);
            Toast.error('刪除模板失敗');
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>