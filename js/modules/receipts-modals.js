/**
 * receipts-modals.js - Modal handlers for receipts page
 */
import * as State from './receipts-state.js';
import * as UI from './receipts-ui.js';

// ========================================
// PDF Export State
// ========================================
let pdfExportReceiptId = null;
let pdfExportReceiptIds = [];
let pdfTemplates = [];

export function getPdfExportReceiptId() { return pdfExportReceiptId; }
export function setPdfExportReceiptId(id) { pdfExportReceiptId = id; }
export function getPdfExportReceiptIds() { return pdfExportReceiptIds; }
export function setPdfExportReceiptIds(ids) { pdfExportReceiptIds = ids; }
export function getPdfTemplates() { return pdfTemplates; }
export function setPdfTemplates(templates) { pdfTemplates = templates; }

// ========================================
// Image Modal
// ========================================
export function openModal(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('modal').style.display = 'flex';
}

export function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

// ========================================
// Edit Modal
// ========================================
export function openEditModal(id) {
    const receiptsData = State.getReceiptsData();
    const receipt = receiptsData.find(r => r.id == id);
    if (!receipt) return;

    State.setEditReceiptId(id);
    State.setEditReceiptTags(receipt.tags ? [...receipt.tags] : []);

    document.getElementById('editId').value = id;
    document.getElementById('editDate').value = receipt.receipt_date || '';
    document.getElementById('editTime').value = receipt.receipt_time || '';
    document.getElementById('editCompany').value = receipt.company_name || '';
    document.getElementById('editItems').value = receipt.items_summary || '';
    document.getElementById('editPayment').value = receipt.payment_method || '';
    document.getElementById('editAmount').value = receipt.total_amount || '';
    document.getElementById('editSummary').value = receipt.summary || '';

    UI.renderEditTags();
    document.getElementById('editModal').style.display = 'flex';
}

export function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    State.setEditReceiptId(null);
    State.setEditReceiptTags([]);
}

// ========================================
// Delete Modal
// ========================================
export function openDeleteModal(id) {
    State.setDeleteTargetId(id);
    document.getElementById('deleteModal').style.display = 'flex';
}

export function closeDeleteModal() {
    State.setDeleteTargetId(null);
    document.getElementById('deleteModal').style.display = 'none';
}

// ========================================
// Bulk Tags Modal
// ========================================
export function openBulkTagsModal() {
    const selectedReceiptIds = State.getSelectedReceiptIds();
    if (selectedReceiptIds.size === 0) {
        document.dispatchEvent(new CustomEvent('receipts:toast', {
            detail: { type: 'warning', message: '請先選取單據' }
        }));
        return;
    }
    UI.setBulkSelectedTags([]);
    document.getElementById('bulkTagsInfo').textContent = `將為 ${selectedReceiptIds.size} 張單據加入標籤`;
    UI.renderBulkTagsGrid();
    document.getElementById('bulkTagsModal').style.display = 'flex';
}

export function closeBulkTagsModal() {
    document.getElementById('bulkTagsModal').style.display = 'none';
}

// ========================================
// Bulk Remove Tags Modal
// ========================================
export function openBulkRemoveTagsModal() {
    const selectedReceiptIds = State.getSelectedReceiptIds();
    if (selectedReceiptIds.size === 0) {
        document.dispatchEvent(new CustomEvent('receipts:toast', {
            detail: { type: 'warning', message: '請先選取單據' }
        }));
        return;
    }
    UI.setBulkRemoveTags([]);
    document.getElementById('bulkRemoveTagsInfo').textContent = `將從 ${selectedReceiptIds.size} 張單據移除標籤`;
    UI.renderBulkRemoveTagsGrid();
    document.getElementById('bulkRemoveTagsModal').style.display = 'flex';
}

export function closeBulkRemoveTagsModal() {
    document.getElementById('bulkRemoveTagsModal').style.display = 'none';
}

// ========================================
// Bulk Delete Modal
// ========================================
export function openBulkDeleteModal() {
    const selectedReceiptIds = State.getSelectedReceiptIds();
    if (selectedReceiptIds.size === 0) {
        document.dispatchEvent(new CustomEvent('receipts:toast', {
            detail: { type: 'warning', message: '請先選取單據' }
        }));
        return;
    }
    document.getElementById('bulkDeleteInfo').textContent =
        `確定要刪除選取的 ${selectedReceiptIds.size} 張單據嗎？此操作無法復原。`;
    document.getElementById('bulkDeleteModal').style.display = 'flex';
}

export function closeBulkDeleteModal() {
    document.getElementById('bulkDeleteModal').style.display = 'none';
}

// ========================================
// Tag Select Modal (for filtering)
// ========================================
export function openTagSelectModal() {
    UI.renderTagGrid();
    document.getElementById('tagSelectModal').style.display = 'flex';
}

export function closeTagSelectModal() {
    document.getElementById('tagSelectModal').style.display = 'none';
}

// ========================================
// Edit Tags Modal
// ========================================
export function openEditTagsModal() {
    const editReceiptTags = State.getEditReceiptTags();
    State.setTempSelectedTags(editReceiptTags.map(t => t.id));
    UI.renderEditTagsGrid();
    document.getElementById('editTagsModal').style.display = 'flex';
}

export function closeEditTagsModal() {
    document.getElementById('editTagsModal').style.display = 'none';
}

// ========================================
// Create Tag Modal
// ========================================
export function openCreateTagModal() {
    document.getElementById('newTagName').value = '';
    document.getElementById('newTagColor').value = '#3b82f6';
    UI.renderColorPalette('newTagColorPalette', '#3b82f6', (color) => {
        document.getElementById('newTagColor').value = color;
    });
    document.getElementById('createTagModal').style.display = 'flex';
}

export function closeCreateTagModal() {
    document.getElementById('createTagModal').style.display = 'none';
}

// ========================================
// Mobile Actions Modal
// ========================================
export function openMobileActionsModal() {
    const selectedReceiptIds = State.getSelectedReceiptIds();
    if (selectedReceiptIds.size === 0) {
        document.dispatchEvent(new CustomEvent('receipts:toast', {
            detail: { type: 'warning', message: '請先選取單據' }
        }));
        return;
    }
    document.getElementById('mobileActionsInfo').textContent = `已選取 ${selectedReceiptIds.size} 張單據`;
    document.getElementById('mobileActionsModal').style.display = 'flex';
}

export function closeMobileActionsModal() {
    document.getElementById('mobileActionsModal').style.display = 'none';
}

// ========================================
// PDF Export Modal
// ========================================
export function openPdfExportModal(id) {
    pdfExportReceiptId = id;
    pdfExportReceiptIds = [];

    document.getElementById('pdfBatchInfo').style.display = 'none';

    // Load templates will be called by main module
    document.dispatchEvent(new CustomEvent('receipts:loadPdfTemplates'));

    const receiptsData = State.getReceiptsData();
    const receipt = receiptsData.find(r => r.id == id);
    const defaultFilename = receipt
        ? `單據_${receipt.company_name || 'unknown'}_${receipt.receipt_date || ''}.pdf`
        : `單據_${id}.pdf`;
    document.getElementById('pdfDefaultFilename').textContent = defaultFilename;
    document.getElementById('pdfCustomFilename').value = '';

    document.getElementById('pdfExportModal').style.display = 'flex';
}

export function openBulkPdfExportModal() {
    const selectedReceiptIds = State.getSelectedReceiptIds();
    if (selectedReceiptIds.size === 0) {
        document.dispatchEvent(new CustomEvent('receipts:toast', {
            detail: { type: 'warning', message: '請先選取單據' }
        }));
        return;
    }

    pdfExportReceiptId = null;
    pdfExportReceiptIds = Array.from(selectedReceiptIds);

    document.getElementById('pdfBatchInfo').style.display = 'block';
    document.getElementById('pdfBatchCount').textContent = `將匯出 ${pdfExportReceiptIds.length} 筆單據至單一 PDF 檔案`;

    document.dispatchEvent(new CustomEvent('receipts:loadPdfTemplates'));

    const defaultFilename = `批量單據_${pdfExportReceiptIds.length}筆_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.pdf`;
    document.getElementById('pdfDefaultFilename').textContent = defaultFilename;
    document.getElementById('pdfCustomFilename').value = '';

    document.getElementById('pdfExportModal').style.display = 'flex';
}

export function closePdfExportModal() {
    pdfExportReceiptId = null;
    pdfExportReceiptIds = [];
    document.getElementById('pdfExportModal').style.display = 'none';
}

// ========================================
// Apply PDF Template
// ========================================
export function applyTemplate(template) {
    document.getElementById('pdfPageSize').value = template.page_size;
    document.getElementById('pdfMarginTop').value = template.margin_top;
    document.getElementById('pdfMarginBottom').value = template.margin_bottom;
    document.getElementById('pdfMarginLeft').value = template.margin_left;
    document.getElementById('pdfMarginRight').value = template.margin_right;

    document.getElementById('pdfHeader').value = template.header_text || '';
    const headerAlignRadio = document.querySelector(`input[name="pdfHeaderAlign"][value="${template.header_align}"]`);
    if (headerAlignRadio) headerAlignRadio.checked = true;
    document.getElementById('pdfHeaderFontSize').value = template.header_font_size;

    document.getElementById('pdfFooter').value = template.footer_text || '';
    const footerAlignRadio = document.querySelector(`input[name="pdfFooterAlign"][value="${template.footer_align}"]`);
    if (footerAlignRadio) footerAlignRadio.checked = true;
    document.getElementById('pdfFooterFontSize').value = template.footer_font_size;

    const imageAlignRadio = document.querySelector(`input[name="pdfImageAlign"][value="${template.image_align}"]`);
    if (imageAlignRadio) imageAlignRadio.checked = true;
    document.getElementById('pdfImageHeightScale').value = template.image_height_scale;
    document.getElementById('pdfImageHeightScaleValue').textContent = template.image_height_scale;
    document.getElementById('pdfImageWidthScale').value = template.image_width_scale;
    document.getElementById('pdfImageWidthScaleValue').textContent = template.image_width_scale;
}

// ========================================
// Export Modal
// ========================================
export function openExportModal() {
    document.dispatchEvent(new CustomEvent('receipts:openExport'));
}

export function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
}

export function closeAddEmptyColumnModal() {
    document.getElementById('addEmptyColumnModal').style.display = 'none';
}

// ========================================
// Attach Window Handlers
// ========================================
export function attachWindowHandlers() {
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.openEditModal = openEditModal;
    window.closeEditModal = closeEditModal;
    window.openDeleteModal = openDeleteModal;
    window.closeDeleteModal = closeDeleteModal;
    window.closeBulkTagsModal = closeBulkTagsModal;
    window.closeBulkRemoveTagsModal = closeBulkRemoveTagsModal;
    window.closeBulkDeleteModal = closeBulkDeleteModal;
    window.closeTagSelectModal = closeTagSelectModal;
    window.closeEditTagsModal = closeEditTagsModal;
    window.closeCreateTagModal = closeCreateTagModal;
    window.closeMobileActionsModal = closeMobileActionsModal;
    window.openPdfExportModal = openPdfExportModal;
    window.closePdfExportModal = closePdfExportModal;
    window.closeExportModal = closeExportModal;
    window.closeAddEmptyColumnModal = closeAddEmptyColumnModal;
}
