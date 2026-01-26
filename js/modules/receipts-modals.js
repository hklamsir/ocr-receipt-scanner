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
    const modalImg = document.getElementById('modalImg');
    const modal = document.getElementById('modal');
    if (modalImg) modalImg.src = src;
    if (modal) modal.style.display = 'flex';
}

export function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) modal.style.display = 'none';
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

    const editId = document.getElementById('editId');
    const editDate = document.getElementById('editDate');
    const editTime = document.getElementById('editTime');
    const editCompany = document.getElementById('editCompany');
    const editItems = document.getElementById('editItems');
    const editPayment = document.getElementById('editPayment');
    const editAmount = document.getElementById('editAmount');
    const editSummary = document.getElementById('editSummary');
    const editModal = document.getElementById('editModal');

    if (editId) editId.value = id;
    if (editDate) editDate.value = receipt.receipt_date || '';
    if (editTime) editTime.value = receipt.receipt_time || '';
    if (editCompany) editCompany.value = receipt.company_name || '';
    if (editItems) editItems.value = receipt.items_summary || '';
    if (editPayment) editPayment.value = receipt.payment_method || '';
    if (editAmount) editAmount.value = receipt.total_amount || '';
    if (editSummary) editSummary.value = receipt.summary || '';

    UI.renderEditTags();
    if (editModal) editModal.style.display = 'flex';
}

export function closeEditModal() {
    const editModal = document.getElementById('editModal');
    if (editModal) editModal.style.display = 'none';
    State.setEditReceiptId(null);
    State.setEditReceiptTags([]);
}

// ========================================
// Delete Modal
// ========================================
export function openDeleteModal(id) {
    State.setDeleteTargetId(id);
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) deleteModal.style.display = 'flex';
}

export function closeDeleteModal() {
    State.setDeleteTargetId(null);
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) deleteModal.style.display = 'none';
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
    const bulkTagsInfo = document.getElementById('bulkTagsInfo');
    if (bulkTagsInfo) bulkTagsInfo.textContent = `將為 ${selectedReceiptIds.size} 張單據加入標籤`;
    UI.renderBulkTagsGrid();
    const bulkTagsModal = document.getElementById('bulkTagsModal');
    if (bulkTagsModal) bulkTagsModal.style.display = 'flex';
}

export function closeBulkTagsModal() {
    const bulkTagsModal = document.getElementById('bulkTagsModal');
    if (bulkTagsModal) bulkTagsModal.style.display = 'none';
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
    const bulkRemoveTagsInfo = document.getElementById('bulkRemoveTagsInfo');
    if (bulkRemoveTagsInfo) bulkRemoveTagsInfo.textContent = `將從 ${selectedReceiptIds.size} 張單據移除標籤`;
    UI.renderBulkRemoveTagsGrid();
    const bulkRemoveTagsModal = document.getElementById('bulkRemoveTagsModal');
    if (bulkRemoveTagsModal) bulkRemoveTagsModal.style.display = 'flex';
}

export function closeBulkRemoveTagsModal() {
    const bulkRemoveTagsModal = document.getElementById('bulkRemoveTagsModal');
    if (bulkRemoveTagsModal) bulkRemoveTagsModal.style.display = 'none';
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
    const bulkDeleteInfo = document.getElementById('bulkDeleteInfo');
    if (bulkDeleteInfo) {
        bulkDeleteInfo.textContent = `確定要刪除選取的 ${selectedReceiptIds.size} 張單據嗎？此操作無法復原。`;
    }
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    if (bulkDeleteModal) bulkDeleteModal.style.display = 'flex';
}

export function closeBulkDeleteModal() {
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    if (bulkDeleteModal) bulkDeleteModal.style.display = 'none';
}

// ========================================
// Tag Select Modal (for filtering)
// ========================================
export function openTagSelectModal() {
    UI.renderTagGrid();
    const tagSelectModal = document.getElementById('tagSelectModal');
    if (tagSelectModal) tagSelectModal.style.display = 'flex';
}

export function closeTagSelectModal() {
    const tagSelectModal = document.getElementById('tagSelectModal');
    if (tagSelectModal) tagSelectModal.style.display = 'none';
}

// ========================================
// Edit Tags Modal
// ========================================
export function openEditTagsModal() {
    const editReceiptTags = State.getEditReceiptTags();
    State.setTempSelectedTags(editReceiptTags.map(t => t.id));
    UI.renderEditTagsGrid();
    const editTagsModal = document.getElementById('editTagsModal');
    if (editTagsModal) editTagsModal.style.display = 'flex';
}

export function closeEditTagsModal() {
    const editTagsModal = document.getElementById('editTagsModal');
    if (editTagsModal) editTagsModal.style.display = 'none';
}

// ========================================
// Create Tag Modal
// ========================================
export function openCreateTagModal() {
    const newTagName = document.getElementById('newTagName');
    const newTagColor = document.getElementById('newTagColor');
    const createTagModal = document.getElementById('createTagModal');

    if (newTagName) newTagName.value = '';
    if (newTagColor) newTagColor.value = '#3b82f6';
    UI.renderColorPalette('newTagColorPalette', '#3b82f6', (color) => {
        const colorInput = document.getElementById('newTagColor');
        if (colorInput) colorInput.value = color;
    });
    if (createTagModal) createTagModal.style.display = 'flex';
}

export function closeCreateTagModal() {
    const createTagModal = document.getElementById('createTagModal');
    if (createTagModal) createTagModal.style.display = 'none';
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
    const mobileActionsInfo = document.getElementById('mobileActionsInfo');
    if (mobileActionsInfo) mobileActionsInfo.textContent = `已選取 ${selectedReceiptIds.size} 張單據`;
    const mobileActionsModal = document.getElementById('mobileActionsModal');
    if (mobileActionsModal) mobileActionsModal.style.display = 'flex';
}

export function closeMobileActionsModal() {
    const mobileActionsModal = document.getElementById('mobileActionsModal');
    if (mobileActionsModal) mobileActionsModal.style.display = 'none';
}

// ========================================
// PDF Export Modal
// ========================================
export function openPdfExportModal(id) {
    pdfExportReceiptId = id;
    pdfExportReceiptIds = [];

    const pdfBatchInfo = document.getElementById('pdfBatchInfo');
    if (pdfBatchInfo) pdfBatchInfo.style.display = 'none';

    // Load templates will be called by main module
    document.dispatchEvent(new CustomEvent('receipts:loadPdfTemplates'));

    const receiptsData = State.getReceiptsData();
    const receipt = receiptsData.find(r => r.id == id);
    const defaultFilename = receipt
        ? `單據_${receipt.company_name || 'unknown'}_${receipt.receipt_date || ''}.pdf`
        : `單據_${id}.pdf`;

    const pdfDefaultFilename = document.getElementById('pdfDefaultFilename');
    const pdfCustomFilename = document.getElementById('pdfCustomFilename');
    const pdfExportModal = document.getElementById('pdfExportModal');

    if (pdfDefaultFilename) pdfDefaultFilename.textContent = defaultFilename;
    if (pdfCustomFilename) pdfCustomFilename.value = '';
    if (pdfExportModal) pdfExportModal.style.display = 'flex';
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

    const pdfBatchInfo = document.getElementById('pdfBatchInfo');
    const pdfBatchCount = document.getElementById('pdfBatchCount');
    if (pdfBatchInfo) pdfBatchInfo.style.display = 'block';
    if (pdfBatchCount) pdfBatchCount.textContent = `將匯出 ${pdfExportReceiptIds.length} 筆單據至單一 PDF 檔案`;

    document.dispatchEvent(new CustomEvent('receipts:loadPdfTemplates'));

    const defaultFilename = `批量單據_${pdfExportReceiptIds.length}筆_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.pdf`;
    const pdfDefaultFilename = document.getElementById('pdfDefaultFilename');
    const pdfCustomFilename = document.getElementById('pdfCustomFilename');
    const pdfExportModal = document.getElementById('pdfExportModal');

    if (pdfDefaultFilename) pdfDefaultFilename.textContent = defaultFilename;
    if (pdfCustomFilename) pdfCustomFilename.value = '';
    if (pdfExportModal) pdfExportModal.style.display = 'flex';
}

export function closePdfExportModal() {
    pdfExportReceiptId = null;
    pdfExportReceiptIds = [];
    const pdfExportModal = document.getElementById('pdfExportModal');
    if (pdfExportModal) pdfExportModal.style.display = 'none';
}

// ========================================
// Apply PDF Template
// ========================================
export function applyTemplate(template) {
    document.getElementById('pdf_pageSize').value = template.page_size;
    document.getElementById('pdf_marginTop').value = template.margin_top;
    document.getElementById('pdf_marginBottom').value = template.margin_bottom;
    document.getElementById('pdf_marginLeft').value = template.margin_left;
    document.getElementById('pdf_marginRight').value = template.margin_right;

    document.getElementById('pdf_headerText').value = template.header_text || '';
    const headerAlignRadio = document.querySelector(`input[name="pdf_headerAlign"][value="${template.header_align}"]`);
    if (headerAlignRadio) headerAlignRadio.checked = true;
    document.getElementById('pdf_headerFontSize').value = template.header_font_size;

    document.getElementById('pdf_footerText').value = template.footer_text || '';
    const footerAlignRadio = document.querySelector(`input[name="pdf_footerAlign"][value="${template.footer_align}"]`);
    if (footerAlignRadio) footerAlignRadio.checked = true;
    document.getElementById('pdf_footerFontSize').value = template.footer_font_size;

    const imageAlignRadio = document.querySelector(`input[name="pdf_imageAlign"][value="${template.image_align}"]`);
    if (imageAlignRadio) imageAlignRadio.checked = true;
    document.getElementById('pdf_imageHeightScale').value = template.image_height_scale;
    document.getElementById('pdf_imageHeightScaleValue').textContent = template.image_height_scale;
    document.getElementById('pdf_imageWidthScale').value = template.image_width_scale;
    document.getElementById('pdf_imageWidthScaleValue').textContent = template.image_width_scale;
}

// ========================================
// Export Modal
// ========================================
export function openExportModal() {
    document.dispatchEvent(new CustomEvent('receipts:openExport'));
}

export function closeExportModal() {
    const exportModal = document.getElementById('exportModal');
    if (exportModal) exportModal.style.display = 'none';
}

export function closeAddEmptyColumnModal() {
    const addEmptyColumnModal = document.getElementById('addEmptyColumnModal');
    if (addEmptyColumnModal) addEmptyColumnModal.style.display = 'none';
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
