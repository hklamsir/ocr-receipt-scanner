import { Toast } from './modules/toast.js';
import { escapeHtml, safeColor } from './modules/utils.js';

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
    if (!container) return;
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
    if (!list) return;
    if (allTags.length === 0) {
        list.innerHTML = '<p style="color:#999;text-align:center;padding:20px;">尚無標籤</p>';
        return;
    }

    list.innerHTML = allTags.map((tag, index) => `
            <div class="tag-list-item" draggable="true" data-id="${tag.id}" data-index="${index}">
                <span class="drag-handle">⋮⋮</span>
                <span class="tag" style="background:${safeColor(tag.color)};">${escapeHtml(tag.name)}</span>
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
document.getElementById('batchAddBtn')?.addEventListener('click', async () => {
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

document.getElementById('saveEditTagBtn')?.addEventListener('click', async () => {
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

document.getElementById('confirmDeleteTagBtn')?.addEventListener('click', async () => {
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

document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
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
    if (!list) return;

    if (pdfTemplates.length === 0) {
        list.innerHTML = '<p style="color:#999;text-align:center;padding:40px;">尚無自訂模板<br><small>您可以在 PDF 匯出時點擊「另存為模板」來建立模板</small></p>';
        return;
    }

    list.innerHTML = pdfTemplates.map(t => `
        <div class="template-item" style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 5px;">
                    ${escapeHtml(t.template_name)}
                    ${t.is_default ? '<span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">預設</span>' : ''}
                </div>
                <div style="font-size: 13px; color: #666;">
                    ${escapeHtml(t.page_size)} | 邊界 ${t.margin_top}mm | 頁首/頁尾 ${t.header_font_size}pt/${t.footer_font_size}pt
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
    document.getElementById('pdf_pageSize').value = template.page_size;
    document.getElementById('pdf_marginTop').value = template.margin_top;
    document.getElementById('pdf_marginBottom').value = template.margin_bottom;
    document.getElementById('pdf_marginLeft').value = template.margin_left;
    document.getElementById('pdf_marginRight').value = template.margin_right;

    // 頁首設定
    document.getElementById('pdf_headerText').value = template.header_text || '';
    const headerAlignRadio = document.querySelector(`input[name="pdf_headerAlign"][value="${template.header_align}"]`);
    if (headerAlignRadio) headerAlignRadio.checked = true;
    document.getElementById('pdf_headerFontSize').value = template.header_font_size;

    // 頁尾設定
    document.getElementById('pdf_footerText').value = template.footer_text || '';
    const footerAlignRadio = document.querySelector(`input[name="pdf_footerAlign"][value="${template.footer_align}"]`);
    if (footerAlignRadio) footerAlignRadio.checked = true;
    document.getElementById('pdf_footerFontSize').value = template.footer_font_size;

    // 圖片設定
    const imageAlignRadio = document.querySelector(`input[name="pdf_imageAlign"][value="${template.image_align}"]`);
    if (imageAlignRadio) imageAlignRadio.checked = true;
    document.getElementById('pdf_imageHeightScale').value = template.image_height_scale;
    document.getElementById('pdf_imageHeightScaleValue').textContent = template.image_height_scale;
    document.getElementById('pdf_imageWidthScale').value = template.image_width_scale;
    document.getElementById('pdf_imageWidthScaleValue').textContent = template.image_width_scale;

    document.getElementById('editPdfTemplateModal').style.display = 'flex';
};

window.closeEditPdfTemplateModal = function () {
    document.getElementById('editPdfTemplateModal').style.display = 'none';
};

// 滑桿事件
document.getElementById('pdf_imageHeightScale')?.addEventListener('input', function () {
    document.getElementById('pdf_imageHeightScaleValue').textContent = this.value;
});

document.getElementById('pdf_imageWidthScale')?.addEventListener('input', function () {
    document.getElementById('pdf_imageWidthScaleValue').textContent = this.value;
});

document.getElementById('editPdfTemplateForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = parseInt(document.getElementById('editTemplateId').value);

    const templateData = {
        template_id: id,
        template_name: document.getElementById('editTemplateName').value.trim(),
        is_default: document.getElementById('editTemplateIsDefault').checked,
        page_size: document.getElementById('pdf_pageSize').value,
        margin_top: document.getElementById('pdf_marginTop').value,
        margin_bottom: document.getElementById('pdf_marginBottom').value,
        margin_left: document.getElementById('pdf_marginLeft').value,
        margin_right: document.getElementById('pdf_marginRight').value,
        header_text: document.getElementById('pdf_headerText').value,
        header_align: document.querySelector('input[name="pdf_headerAlign"]:checked')?.value || 'C',
        header_font_size: document.getElementById('pdf_headerFontSize').value,
        footer_text: document.getElementById('pdf_footerText').value,
        footer_align: document.querySelector('input[name="pdf_footerAlign"]:checked')?.value || 'C',
        footer_font_size: document.getElementById('pdf_footerFontSize').value,
        image_align: document.querySelector('input[name="pdf_imageAlign"]:checked')?.value || 'C',
        image_height_scale: document.getElementById('pdf_imageHeightScale').value,
        image_width_scale: document.getElementById('pdf_imageWidthScale').value
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

document.getElementById('confirmDeletePdfTemplateBtn')?.addEventListener('click', async () => {
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
    if (!list) return;

    if (excelTemplates.length === 0) {
        list.innerHTML = '<p style="color:#999;text-align:center;padding:40px;">尚無自訂模板<br><small>您可以在 Excel 匯出時點擊「另存為模板」來建立模板</small></p>';
        return;
    }

    list.innerHTML = excelTemplates.map(t => {
        const enabledFields = t.fields_config.filter(f => f.enabled).map(f => escapeHtml(f.label)).join(', ');
        return `
        <div class="template-item" style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 5px;">
                    ${escapeHtml(t.template_name)}
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

    console.log('[Debug] Opening template:', template);

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

    // 填充排序設定
    const sortBySelect = document.getElementById('excel_sortBy');
    const sortOrderSelect = document.getElementById('excel_sortOrder');
    if (sortBySelect) sortBySelect.value = template.sort_by || 'date';
    if (sortOrderSelect) sortOrderSelect.value = template.sort_order || 'desc';

    document.getElementById('editExcelTemplateModal').style.display = 'flex';
};

// 渲染欄位列表
function renderEditExcelFieldsList() {
    const container = document.getElementById('excel_fieldsList');
    if (!container) return;
    container.innerHTML = editExcelTemplateData.fields_config.map((field, index) => `
        <div class="export-field-item ${field.enabled ? 'enabled' : ''}" 
             data-index="${index}" 
             draggable="true">
            <span class="drag-handle">☰</span>
            <label class="export-field-label">
                <input type="checkbox" class="edit-excel-field-checkbox" 
                       data-index="${index}" 
                       ${field.enabled ? 'checked' : ''}>
                <span>${escapeHtml(field.label)}</span>
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
    document.querySelectorAll('#excel_fieldsList .export-field-item').forEach(item => {
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
document.getElementById('excel_addEmptyColumnBtn')?.addEventListener('click', () => {
    document.getElementById('editExcelEmptyColumnName').value = '';
    document.getElementById('editExcelAddColumnModal').style.display = 'flex';
});

window.closeEditExcelAddColumnModal = function () {
    document.getElementById('editExcelAddColumnModal').style.display = 'none';
};

document.getElementById('confirmEditExcelAddColumnBtn')?.addEventListener('click', () => {
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

document.getElementById('editExcelTemplateForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = parseInt(document.getElementById('editExcelTemplateId').value);

    const templateData = {
        template_id: id,
        template_name: document.getElementById('editExcelTemplateName').value.trim(),
        is_default: document.getElementById('editExcelTemplateIsDefault').checked,
        fields_config: editExcelTemplateData.fields_config,
        sort_by: document.getElementById('excel_sortBy')?.value || 'date',
        sort_order: document.getElementById('excel_sortOrder')?.value || 'desc'
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

document.getElementById('confirmDeleteExcelTemplateBtn')?.addEventListener('click', async () => {
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

// ========================================
// PDF Hint Modal (Shared)
// ========================================
window.openPdfHintModal = function () {
    const pdfHintModal = document.getElementById('pdfHintModal');
    if (pdfHintModal) {
        pdfHintModal.style.setProperty('display', 'flex', 'important');
    } else {
        console.error('[Settings] PDF Hint Modal not found');
    }
};

window.closePdfHintModal = function () {
    const pdfHintModal = document.getElementById('pdfHintModal');
    if (pdfHintModal) pdfHintModal.style.display = 'none';
};

// Event delegation for PDF hint triggers
document.addEventListener('click', (e) => {
    const trigger = e.target.closest('.pdf-hint-trigger');
    if (trigger) {
        e.preventDefault();
        window.openPdfHintModal();
    }
});

// ========================================
// 外觀主題選擇器
// ========================================
(function initThemeManager() {
    const options = document.querySelectorAll('#themeOptions .theme-option');
    const saveBtn = document.getElementById('saveThemeBtn');
    const hint = document.getElementById('themeSavedHint');
    if (!options.length || !saveBtn) return;

    let selectedTheme = document.documentElement.getAttribute('data-theme') || 'teal';

    function applyPreview(key) {
        document.documentElement.setAttribute('data-theme', key);
        options.forEach(o => o.classList.toggle('is-selected', o.dataset.themeKey === key));
    }

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            selectedTheme = opt.dataset.themeKey;
            applyPreview(selectedTheme);
            if (hint) hint.textContent = '';
        });
    });

    // 初始化預覽為目前主題
    applyPreview(selectedTheme);

    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        try {
            const res = await fetch('api/update_theme.php', {
                method: 'POST',
                headers: getCSRFHeaders(),
                body: JSON.stringify({ theme: selectedTheme })
            });
            const data = await res.json();
            if (data.success) {
                options.forEach(o => o.classList.toggle('is-current', o.dataset.themeKey === selectedTheme));
                if (hint) {
                    hint.textContent = '✓ 已儲存';
                    hint.style.color = 'var(--success)';
                }
                Toast.success('主題已更新');
            } else {
                Toast.error(data.error || '儲存失敗');
            }
        } catch (err) {
            Toast.error('儲存主題失敗');
        } finally {
            saveBtn.disabled = false;
        }
    });
})();
