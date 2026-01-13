/**
 * receipts-export.js - Excel export functionality for receipts page
 */
import * as State from './receipts-state.js';
import { Toast } from './toast.js';

// ========================================
// Export Fields Configuration
// ========================================
const DEFAULT_EXPORT_FIELDS = [
    { key: 'date', label: '日期', enabled: true },
    { key: 'time', label: '時間', enabled: true },
    { key: 'company', label: '公司名稱', enabled: true },
    { key: 'items', label: '項目摘要', enabled: true },
    { key: 'summary', label: '總結', enabled: true },
    { key: 'payment', label: '支付方式', enabled: true },
    { key: 'amount', label: '總金額', enabled: true },
    { key: 'tags', label: '標籤', enabled: false }
];

let exportFields = [];
let exportIdsToExport = [];
let draggedItem = null;

export function getExportFields() { return exportFields; }
export function getExportIdsToExport() { return exportIdsToExport; }

// ========================================
// Initialize Export Fields
// ========================================
export function initExportFields() {
    const saved = localStorage.getItem('exportFieldsConfig');
    if (saved) {
        try {
            exportFields = JSON.parse(saved);
            DEFAULT_EXPORT_FIELDS.forEach(df => {
                if (!exportFields.find(f => f.key === df.key)) {
                    exportFields.push({ ...df });
                }
            });
        } catch (e) {
            exportFields = DEFAULT_EXPORT_FIELDS.map(f => ({ ...f }));
        }
    } else {
        exportFields = DEFAULT_EXPORT_FIELDS.map(f => ({ ...f }));
    }
}

export function saveExportFieldsConfig() {
    localStorage.setItem('exportFieldsConfig', JSON.stringify(exportFields));
}

// ========================================
// Render Export Fields List
// ========================================
export function renderExportFieldsList() {
    const container = document.getElementById('exportFieldsList');
    container.innerHTML = exportFields.map((field, index) => `
        <div class="export-field-item ${field.enabled ? 'enabled' : ''}" 
             data-index="${index}" 
             draggable="true">
            <span class="drag-handle">☰</span>
            <label class="export-field-label">
                <input type="checkbox" class="export-field-checkbox" 
                       data-index="${index}" 
                       ${field.enabled ? 'checked' : ''}>
                <span>${field.label}</span>
            </label>
            ${field.key.startsWith('empty_') ?
            `<button type="button" class="remove-empty-column" data-index="${index}">✕</button>` :
            ''}
        </div>
    `).join('');

    container.querySelectorAll('.export-field-item').forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
    });

    container.querySelectorAll('.export-field-checkbox').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.index);
            exportFields[index].enabled = e.target.checked;
            e.target.closest('.export-field-item').classList.toggle('enabled', e.target.checked);
        });
    });

    container.querySelectorAll('.remove-empty-column').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const index = parseInt(e.target.dataset.index);
            exportFields.splice(index, 1);
            renderExportFieldsList();
        });
    });
}

// ========================================
// Drag and Drop Handlers
// ========================================
function handleDragStart(e) {
    draggedItem = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.index);
}

function handleDragEnd() {
    this.classList.remove('dragging');
    document.querySelectorAll('.export-field-item').forEach(item => {
        item.classList.remove('drag-over');
    });
    draggedItem = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    if (this !== draggedItem) {
        this.classList.add('drag-over');
    }
}

function handleDragLeave() {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    if (draggedItem === this) return;

    const fromIndex = parseInt(draggedItem.dataset.index);
    const toIndex = parseInt(this.dataset.index);

    const [movedItem] = exportFields.splice(fromIndex, 1);
    exportFields.splice(toIndex, 0, movedItem);

    renderExportFieldsList();
}

// ========================================
// Open Export Modal
// ========================================
export function openExportModal() {
    const selectedReceiptIds = State.getSelectedReceiptIds();
    const receiptsData = State.getReceiptsData();

    if (selectedReceiptIds.size > 0) {
        exportIdsToExport = Array.from(selectedReceiptIds);
    } else {
        exportIdsToExport = receiptsData.map(r => r.id);
    }

    if (exportIdsToExport.length === 0) {
        Toast.warning('沒有可匯出的單據');
        return;
    }

    document.getElementById('exportInfo').textContent = `將匯出 ${exportIdsToExport.length} 筆單據`;
    initExportFields();
    renderExportFieldsList();
    document.getElementById('exportModal').style.display = 'flex';
}

// ========================================
// Add Empty Column
// ========================================
export function addEmptyColumn(name) {
    if (!name) {
        Toast.warning('請輸入欄位名稱');
        return false;
    }

    const uniqueKey = 'empty_' + Date.now();
    exportFields.push({
        key: uniqueKey,
        label: name,
        enabled: true
    });

    renderExportFieldsList();
    return true;
}

// ========================================
// Export to Excel
// ========================================
export function executeExport() {
    const enabledFields = exportFields.filter(f => f.enabled);
    if (enabledFields.length === 0) {
        Toast.warning('請至少選擇一個欄位');
        return false;
    }

    saveExportFieldsConfig();

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/export_excel.php';
    form.style.display = 'none';

    const idsInput = document.createElement('input');
    idsInput.type = 'hidden';
    idsInput.name = 'ids';
    idsInput.value = JSON.stringify(exportIdsToExport);
    form.appendChild(idsInput);

    const columnsInput = document.createElement('input');
    columnsInput.type = 'hidden';
    columnsInput.name = 'columns';
    columnsInput.value = JSON.stringify(enabledFields.map(f => ({ key: f.key, label: f.label })));
    form.appendChild(columnsInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    return true;
}
