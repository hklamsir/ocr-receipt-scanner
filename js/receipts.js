/**
 * receipts.js - Main orchestration module for receipts page
 * Imports and coordinates all sub-modules
 */
import { Toast } from './modules/toast.js';
import * as State from './modules/receipts-state.js';
import * as API from './modules/receipts-api.js';
import * as UI from './modules/receipts-ui.js';
import * as Modals from './modules/receipts-modals.js';
import * as Export from './modules/receipts-export.js';

// ========================================
// Filter and Render
// ========================================
async function filterAndRenderReceipts() {
    const search = document.getElementById('searchInput').value.trim().toLowerCase();
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    const selectedFilterTags = State.getSelectedFilterTags();

    // Check if any filter is active
    const hasActiveFilter = search || month || year || selectedFilterTags.length > 0;

    // If filter is active and we haven't loaded all data yet, load it first
    if (hasActiveFilter && State.getHasMoreReceipts()) {
        console.log('[Filter] Active filter detected, loading all remaining data first...');
        UI.showLoadingMore();
        await API.loadAllRemainingReceipts();
        UI.hideLoadingMore();
        console.log('[Filter] All data loaded, proceeding with filter. Cache size:', State.getAllReceiptsCache().length);
    }

    let filtered = State.getAllReceiptsCache();

    // Keyword search
    if (search) {
        filtered = filtered.filter(r =>
            (r.company_name || '').toLowerCase().includes(search) ||
            (r.items_summary || '').toLowerCase().includes(search) ||
            (r.payment_method || '').toLowerCase().includes(search)
        );
    }

    // Year filter
    if (year) {
        filtered = filtered.filter(r => r.receipt_date && r.receipt_date.startsWith(year));
    }

    // Month filter
    if (month) {
        filtered = filtered.filter(r => {
            if (!r.receipt_date) return false;
            const receiptMonth = r.receipt_date.substring(5, 7);
            return receiptMonth === month;
        });
    }

    // Tag filter
    if (selectedFilterTags.length > 0) {
        filtered = filtered.filter(r => {
            const rTagIds = (r.tags || []).map(t => t.id);
            return selectedFilterTags.every(tid => rTagIds.includes(tid));
        });
    }

    // Sort
    const sortValue = document.getElementById('sortSelect').value;
    const [sortField, sortDir] = sortValue.split('_');
    filtered.sort((a, b) => {
        let valA, valB;
        switch (sortField) {
            case 'date':
                valA = (a.receipt_date || '') + (a.receipt_time || '');
                valB = (b.receipt_date || '') + (b.receipt_time || '');
                break;
            case 'company':
                valA = (a.company_name || '').toLowerCase();
                valB = (b.company_name || '').toLowerCase();
                break;
            case 'payment':
                valA = (a.payment_method || '').toLowerCase();
                valB = (b.payment_method || '').toLowerCase();
                break;
            case 'amount':
                valA = parseFloat(a.total_amount) || 0;
                valB = parseFloat(b.total_amount) || 0;
                break;
            default:
                valA = '';
                valB = '';
        }
        if (valA < valB) return sortDir === 'asc' ? -1 : 1;
        if (valA > valB) return sortDir === 'asc' ? 1 : -1;
        return 0;
    });

    const container = document.getElementById('receipts-container');
    const emptyState = document.getElementById('empty-state');

    State.setReceiptsData(filtered);

    // Clear selection when filter changes to avoid confusion
    const selectedIds = State.getSelectedReceiptIds();
    if (selectedIds.size > 0) {
        // Keep only selections that are still in filtered results
        const filteredIds = new Set(filtered.map(r => r.id));
        const idsToRemove = [];
        selectedIds.forEach(id => {
            if (!filteredIds.has(id)) {
                idsToRemove.push(id);
            }
        });
        idsToRemove.forEach(id => State.removeSelectedReceiptId(id));
    }

    if (filtered.length > 0) {
        UI.renderReceipts(filtered);
        emptyState.style.display = 'none';
        document.getElementById('no-filter-results').style.display = 'none';
        container.style.display = '';
    } else {
        container.innerHTML = '';
        container.style.display = 'none';

        // Show different empty state based on whether filter is active
        if (hasActiveFilter) {
            // Filter is active but no results - show "no filter results" message
            document.getElementById('no-filter-results').style.display = 'block';
            emptyState.style.display = 'none';
        } else {
            // No filter and no receipts - show "no records" message
            emptyState.style.display = 'block';
            document.getElementById('no-filter-results').style.display = 'none';
        }
    }

    // Update filtered count display
    UI.updateFilteredCount(hasActiveFilter, filtered.length, State.getTotalReceiptCount());
    UI.updateSelectedTagsBar();
    UI.updateReceiptCount();

    // Show/hide end of list
    if (!State.getHasMoreReceipts() && State.getAllReceiptsCache().length > 0) {
        UI.showEndOfList();
    } else {
        UI.hideEndOfList();
    }
}

// ========================================
// Load Initial Data
// ========================================
async function loadInitialReceipts() {
    State.clearCache();
    UI.hideEndOfList();

    const result = await API.loadReceipts(1, 20, false);
    if (result) {
        filterAndRenderReceipts();
        UI.preloadAllImages(result.receipts);
    }
}

// ========================================
// Infinite Scroll Handler
// ========================================
async function handleInfiniteScroll() {
    if (State.getIsLoadingMore() || !State.getHasMoreReceipts()) return;

    const scrollBottom = window.innerHeight + window.scrollY;
    const threshold = document.body.offsetHeight - 500;

    if (scrollBottom >= threshold) {
        UI.showLoadingMore();

        const result = await API.loadMoreReceipts(20);

        UI.hideLoadingMore();

        if (result) {
            // Append new receipts to view
            filterAndRenderReceipts();
        }
    }
}

// ========================================
// Initialize Years
// ========================================
async function initYears() {
    const years = await API.loadYears();
    if (years.length > 0) {
        UI.renderYearSelector(years);
    }
}

// ========================================
// Initialize Tags
// ========================================
async function initTags() {
    await API.loadTags();
    UI.renderTagGrid();
}

// ========================================
// PDF Templates Handler
// ========================================
async function loadAndApplyPdfTemplates() {
    const templates = await API.loadPdfTemplates();
    Modals.setPdfTemplates(templates);

    const select = document.getElementById('pdfTemplateSelect');
    select.innerHTML = '';

    templates.forEach(t => {
        const option = document.createElement('option');
        option.value = t.id;
        let label = t.template_name;
        if (t.is_system) label += ' (系統)';
        if (t.is_default) label += ' [預設]';
        option.textContent = label;
        select.appendChild(option);
    });

    // Add "no template" option last
    const noTemplateOption = document.createElement('option');
    noTemplateOption.value = '';
    noTemplateOption.textContent = '不使用模板';
    select.appendChild(noTemplateOption);

    // Apply default template if exists
    const defaultTemplate = templates.find(t => t.is_default && !t.is_system);
    if (defaultTemplate) {
        Modals.applyTemplate(defaultTemplate);
        select.value = defaultTemplate.id;
    }
}

// ========================================
// Event Listeners
// ========================================
function initEventListeners() {
    // Cancel selection
    document.getElementById('cancelSelectBtn').addEventListener('click', () => {
        State.clearSelectedReceiptIds();
        document.querySelectorAll('.card-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllCheckbox').checked = false;
        document.getElementById('selectAllCheckbox').indeterminate = false;
        UI.updateSelectedCount();
    });

    // Bulk add tags
    document.getElementById('bulkAddTagBtn').addEventListener('click', () => {
        Modals.openBulkTagsModal();
    });

    document.getElementById('saveBulkTagsBtn').addEventListener('click', async () => {
        const bulkSelectedTags = UI.getBulkSelectedTags();
        if (bulkSelectedTags.length === 0) {
            Toast.warning('請選擇至少一個標籤');
            return;
        }

        const receiptIds = Array.from(State.getSelectedReceiptIds());
        const allReceiptsCache = State.getAllReceiptsCache();
        const allTags = State.getAllTags();
        let successCount = 0;

        for (const receiptId of receiptIds) {
            try {
                const receipt = allReceiptsCache.find(r => r.id === receiptId);
                const existingTagIds = (receipt?.tags || []).map(t => t.id);
                const newTagIds = [...new Set([...existingTagIds, ...bulkSelectedTags])].slice(0, 5);

                const res = await fetch('api/receipt_tags.php', {
                    method: 'PUT',
                    headers: API.getCSRFHeaders(),
                    body: JSON.stringify({ receipt_id: receiptId, tag_ids: newTagIds })
                });
                const result = await res.json();
                if (result.success) successCount++;
            } catch (err) {
                console.error('更新標籤失敗:', err);
            }
        }

        Modals.closeBulkTagsModal();
        Toast.success(`已為 ${successCount} 張單據加入標籤`);
        loadInitialReceipts();
    });

    // Bulk remove tags
    document.getElementById('bulkRemoveTagBtn').addEventListener('click', () => {
        Modals.openBulkRemoveTagsModal();
    });

    document.getElementById('saveBulkRemoveTagsBtn').addEventListener('click', async () => {
        const bulkRemoveTags = UI.getBulkRemoveTags();
        if (bulkRemoveTags.length === 0) {
            Toast.warning('請選擇至少一個標籤');
            return;
        }

        const receiptIds = Array.from(State.getSelectedReceiptIds());
        const allReceiptsCache = State.getAllReceiptsCache();
        let successCount = 0;

        for (const receiptId of receiptIds) {
            try {
                const receipt = allReceiptsCache.find(r => r.id === receiptId);
                const existingTagIds = (receipt?.tags || []).map(t => t.id);
                const newTagIds = existingTagIds.filter(id => !bulkRemoveTags.includes(id));

                if (newTagIds.length !== existingTagIds.length) {
                    const res = await fetch('api/receipt_tags.php', {
                        method: 'PUT',
                        headers: API.getCSRFHeaders(),
                        body: JSON.stringify({ receipt_id: receiptId, tag_ids: newTagIds })
                    });
                    const result = await res.json();
                    if (result.success) successCount++;
                }
            } catch (err) {
                console.error('移除標籤失敗:', err);
            }
        }

        Modals.closeBulkRemoveTagsModal();
        Toast.success(`已從 ${successCount} 張單據移除標籤`);
        loadInitialReceipts();
    });

    // Bulk delete
    document.getElementById('bulkDeleteBtn').addEventListener('click', () => {
        Modals.openBulkDeleteModal();
    });

    document.getElementById('confirmBulkDeleteBtn').addEventListener('click', async () => {
        const receiptIds = Array.from(State.getSelectedReceiptIds());
        let successCount = 0;

        for (const id of receiptIds) {
            try {
                const res = await fetch('api/delete_receipt.php', {
                    method: 'POST',
                    headers: API.getCSRFHeaders(),
                    body: JSON.stringify({ id })
                });
                const result = await res.json();
                if (result.success) successCount++;
            } catch (err) {
                console.error('刪除失敗:', err);
            }
        }

        Modals.closeBulkDeleteModal();
        State.clearSelectedReceiptIds();
        UI.updateSelectedCount();
        Toast.success(`已刪除 ${successCount} 張單據`);
        loadInitialReceipts();
    });

    // Bulk export PDF
    document.getElementById('bulkExportPdfBtn').addEventListener('click', () => {
        Modals.openBulkPdfExportModal();
    });

    // Bottom export button
    document.getElementById('bottomExportBtn').addEventListener('click', () => {
        document.getElementById('exportExcelBtn').click();
    });

    // Select all - use named function to allow re-attaching
    async function handleSelectAllChange(e) {
        const totalCount = State.getTotalReceiptCount();
        const loadedCount = State.getAllReceiptsCache().length;
        const hasMore = State.getHasMoreReceipts();

        console.log('[SelectAll] Triggered:', { checked: e.target.checked, totalCount, loadedCount, hasMore });

        if (e.target.checked) {
            // If there are more receipts to load, load them all first (data only, no UI re-render)
            if (hasMore && loadedCount < totalCount) {
                console.log('[SelectAll] Loading remaining receipts...');
                const selectAllCb = e.target;
                selectAllCb.disabled = true;

                // Show loading state
                const label = selectAllCb.closest('label') || selectAllCb.parentElement;
                if (label) {
                    label.innerHTML = '<input type="checkbox" id="selectAllCheckbox" disabled checked> 載入中...';
                }

                // Load all remaining receipts using dedicated function
                await API.loadAllRemainingReceipts();

                console.log('[SelectAll] After loading:', {
                    cacheLength: State.getAllReceiptsCache().length,
                    hasMore: State.getHasMoreReceipts()
                });

                // Restore checkbox without re-rendering UI
                if (label) {
                    label.innerHTML = '<input type="checkbox" id="selectAllCheckbox" checked> 全選';
                    const newCb = document.getElementById('selectAllCheckbox');
                    newCb.checked = true;
                    // Re-attach event listener using named function
                    newCb.addEventListener('change', handleSelectAllChange);
                }
            } else {
                console.log('[SelectAll] No need to load more, using cache directly');
            }

            // Select receipts from current filtered view (not entire cache)
            // This ensures we only select what the user can see after filtering
            const currentFilteredReceipts = State.getReceiptsData();
            console.log('[SelectAll] Selecting from current filtered view:', currentFilteredReceipts.length, 'receipts');
            currentFilteredReceipts.forEach(r => {
                State.addSelectedReceiptId(r.id);
            });

            // Update visible checkboxes to match
            document.querySelectorAll('.card-checkbox').forEach(cb => {
                cb.checked = true;
            });

            console.log('[SelectAll] Final selected count:', State.getSelectedReceiptIds().size);
        } else {
            // Deselect all
            State.clearSelectedReceiptIds();
            document.querySelectorAll('.card-checkbox').forEach(cb => {
                cb.checked = false;
            });
        }

        UI.updateSelectAllState();
        UI.updateSelectedCount();
    }
    document.getElementById('selectAllCheckbox').addEventListener('change', handleSelectAllChange);

    // Export Excel
    document.getElementById('exportExcelBtn').addEventListener('click', () => {
        Export.openExportModal();
    });

    document.getElementById('addEmptyColumnBtn').addEventListener('click', () => {
        document.getElementById('emptyColumnName').value = '';
        document.getElementById('addEmptyColumnModal').style.display = 'flex';
        document.getElementById('emptyColumnName').focus();
    });

    document.getElementById('confirmAddEmptyColumnBtn').addEventListener('click', () => {
        const name = document.getElementById('emptyColumnName').value.trim();
        if (Export.addEmptyColumn(name)) {
            Modals.closeAddEmptyColumnModal();
        }
    });

    document.getElementById('emptyColumnName').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            document.getElementById('confirmAddEmptyColumnBtn').click();
        }
    });

    document.getElementById('confirmExportBtn').addEventListener('click', () => {
        if (Export.executeExport()) {
            Modals.closeExportModal();
        }
    });

    // Edit tags
    document.getElementById('selectTagsBtn').addEventListener('click', () => {
        Modals.openEditTagsModal();
    });

    document.getElementById('saveEditTagsBtn').addEventListener('click', async () => {
        const tempSelectedTags = State.getTempSelectedTags();
        const allTags = State.getAllTags();
        const editReceiptTags = tempSelectedTags.map(id => allTags.find(t => t.id === id)).filter(t => t);
        State.setEditReceiptTags(editReceiptTags);
        UI.renderEditTags();
        Modals.closeEditTagsModal();
        Toast.success('標籤已更新（尚未儲存）');
    });

    // Edit form
    document.getElementById('editForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = document.getElementById('editId').value;
        const data = {
            id: parseInt(id),
            date: document.getElementById('editDate').value,
            time: document.getElementById('editTime').value,
            company: document.getElementById('editCompany').value,
            items: document.getElementById('editItems').value,
            payment: document.getElementById('editPayment').value,
            amount: document.getElementById('editAmount').value,
            summary: document.getElementById('editSummary').value
        };

        try {
            const res = await fetch('api/update_receipt.php', {
                method: 'POST',
                headers: API.getCSRFHeaders(),
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (result.success) {
                const editReceiptTags = State.getEditReceiptTags();
                const tagIds = editReceiptTags.map(t => t.id);
                try {
                    const tagRes = await fetch('api/receipt_tags.php', {
                        method: 'PUT',
                        headers: API.getCSRFHeaders(),
                        body: JSON.stringify({ receipt_id: State.getEditReceiptId(), tag_ids: tagIds })
                    });
                    const tagResult = await tagRes.json();
                    if (!tagResult.success) {
                        console.error('標籤更新失敗:', tagResult.error);
                    }
                } catch (tagErr) {
                    console.error('標籤更新錯誤:', tagErr);
                }

                Toast.success('更新成功');
                Modals.closeEditModal();
                loadInitialReceipts();
            } else {
                Toast.error(result.error || '更新失敗');
            }
        } catch (err) {
            Toast.error('更新失敗');
        }
    });

    // Delete confirm
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
        const deleteTargetId = State.getDeleteTargetId();
        if (!deleteTargetId) return;

        try {
            const res = await fetch('api/delete_receipt.php', {
                method: 'POST',
                headers: API.getCSRFHeaders(),
                body: JSON.stringify({ id: deleteTargetId })
            });
            const result = await res.json();

            if (result.success) {
                Toast.success('刪除成功');
                Modals.closeDeleteModal();
                loadInitialReceipts();
            } else {
                Toast.error(result.error || '刪除失敗');
            }
        } catch (err) {
            Toast.error('刪除失敗');
        }
    });

    // Tag filter
    document.getElementById('tagFilterBtn').addEventListener('click', () => {
        Modals.openTagSelectModal();
    });

    document.getElementById('applyTagFilterBtn').addEventListener('click', () => {
        Modals.closeTagSelectModal();
        filterAndRenderReceipts();
    });

    // Search debounce
    let searchTimeout = null;
    document.getElementById('searchInput').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterAndRenderReceipts, 300);
    });

    document.getElementById('yearFilter').addEventListener('change', () => {
        document.getElementById('monthFilter').value = '';
        filterAndRenderReceipts();
    });

    document.getElementById('monthFilter').addEventListener('change', () => {
        document.getElementById('yearFilter').value = '';
        filterAndRenderReceipts();
    });

    document.getElementById('sortSelect').addEventListener('change', filterAndRenderReceipts);

    document.getElementById('clearFilterBtn').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        document.getElementById('yearFilter').value = '';
        document.getElementById('monthFilter').value = '';
        State.clearSelectedFilterTags();
        filterAndRenderReceipts();
    });

    // Clear filter button in empty state
    const clearFilterInEmptyBtn = document.getElementById('clearFilterInEmptyBtn');
    if (clearFilterInEmptyBtn) {
        clearFilterInEmptyBtn.addEventListener('click', () => {
            document.getElementById('searchInput').value = '';
            document.getElementById('yearFilter').value = '';
            document.getElementById('monthFilter').value = '';
            State.clearSelectedFilterTags();
            filterAndRenderReceipts();
        });
    }

    // Mobile actions
    const mobileActionsBtn = document.getElementById('mobileActionsBtn');
    if (mobileActionsBtn) {
        mobileActionsBtn.addEventListener('click', () => {
            Modals.openMobileActionsModal();
        });
    }

    const mobileAddTagBtn = document.getElementById('mobileAddTagBtn');
    if (mobileAddTagBtn) {
        mobileAddTagBtn.addEventListener('click', () => {
            Modals.closeMobileActionsModal();
            Modals.openBulkTagsModal();
        });
    }

    const mobileRemoveTagBtn = document.getElementById('mobileRemoveTagBtn');
    if (mobileRemoveTagBtn) {
        mobileRemoveTagBtn.addEventListener('click', () => {
            Modals.closeMobileActionsModal();
            Modals.openBulkRemoveTagsModal();
        });
    }

    const mobileDeleteBtn = document.getElementById('mobileDeleteBtn');
    if (mobileDeleteBtn) {
        mobileDeleteBtn.addEventListener('click', () => {
            Modals.closeMobileActionsModal();
            Modals.openBulkDeleteModal();
        });
    }

    const mobileExportPdfBtn = document.getElementById('mobileExportPdfBtn');
    if (mobileExportPdfBtn) {
        mobileExportPdfBtn.addEventListener('click', () => {
            Modals.closeMobileActionsModal();
            Modals.openBulkPdfExportModal();
        });
    }

    // Bulk create new tag button
    const bulkCreateNewTagBtn = document.getElementById('bulkCreateNewTagBtn');
    if (bulkCreateNewTagBtn) {
        bulkCreateNewTagBtn.addEventListener('click', () => {
            Modals.openCreateTagModal();
        });
    }

    // Create new tag
    document.getElementById('createNewTagBtn').addEventListener('click', () => {
        Modals.openCreateTagModal();
    });

    document.getElementById('saveNewTagBtn').addEventListener('click', async () => {
        const name = document.getElementById('newTagName').value.trim();
        const color = document.getElementById('newTagColor').value;

        if (!name) {
            Toast.warning('請輸入標籤名稱');
            return;
        }

        const newTag = await API.createTag(name, color);
        if (newTag) {
            Modals.closeCreateTagModal();
            await initTags();

            // Handle creating from edit modal
            const editReceiptId = State.getEditReceiptId();
            const editReceiptTags = State.getEditReceiptTags();
            if (editReceiptId && editReceiptTags.length < 5) {
                const tagIds = [...editReceiptTags.map(t => t.id), newTag.id];
                const saveRes = await fetch('api/receipt_tags.php', {
                    method: 'PUT',
                    headers: API.getCSRFHeaders(),
                    body: JSON.stringify({ receipt_id: editReceiptId, tag_ids: tagIds })
                });
                const saveResult = await saveRes.json();
                if (saveResult.success) {
                    State.setEditReceiptTags([...editReceiptTags, newTag]);
                    State.setTempSelectedTags(tagIds);
                    UI.renderEditTags();
                    loadInitialReceipts();
                }
            }

            // Handle creating from bulk tags modal
            const bulkTagsModal = document.getElementById('bulkTagsModal');
            if (bulkTagsModal && bulkTagsModal.style.display === 'flex') {
                const bulkSelectedTags = UI.getBulkSelectedTags();
                if (!bulkSelectedTags.includes(newTag.id)) {
                    UI.setBulkSelectedTags([...bulkSelectedTags, newTag.id]);
                }
                UI.renderBulkTagsGrid();
            }
        }
    });

    // PDF template apply
    document.getElementById('applyTemplateBtn').addEventListener('click', () => {
        const select = document.getElementById('pdfTemplateSelect');
        const templateId = select.value;

        if (!templateId) {
            Toast.warning('請先選擇模板');
            return;
        }

        const template = Modals.getPdfTemplates().find(t => t.id == templateId);
        if (template) {
            Modals.applyTemplate(template);
            Toast.success('模板套用成功');
        }
    });

    // PDF template save
    document.getElementById('saveTemplateBtn').addEventListener('click', async () => {
        const templateName = prompt('請輸入模板名稱:');
        if (!templateName || !templateName.trim()) return;

        const isDefault = confirm('是否設為預設模板？');

        const templateData = {
            template_name: templateName.trim(),
            is_default: isDefault,
            page_size: document.getElementById('pdfPageSize').value,
            margin_top: document.getElementById('pdfMarginTop').value,
            margin_bottom: document.getElementById('pdfMarginBottom').value,
            margin_left: document.getElementById('pdfMarginLeft').value,
            margin_right: document.getElementById('pdfMarginRight').value,
            header_text: document.getElementById('pdfHeader').value,
            header_align: document.querySelector('input[name="pdfHeaderAlign"]:checked')?.value || 'C',
            header_font_size: document.getElementById('pdfHeaderFontSize').value,
            footer_text: document.getElementById('pdfFooter').value,
            footer_align: document.querySelector('input[name="pdfFooterAlign"]:checked')?.value || 'C',
            footer_font_size: document.getElementById('pdfFooterFontSize').value,
            image_align: document.querySelector('input[name="pdfImageAlign"]:checked')?.value || 'C',
            image_height_scale: document.getElementById('pdfImageHeightScale').value,
            image_width_scale: document.getElementById('pdfImageWidthScale').value
        };

        try {
            const res = await fetch('api/save_pdf_template.php', {
                method: 'POST',
                headers: API.getCSRFHeaders(),
                body: JSON.stringify(templateData)
            });
            const data = await res.json();

            if (data.success) {
                Toast.success('模板儲存成功');
                loadAndApplyPdfTemplates();
            } else {
                Toast.error(data.error || '儲存失敗');
            }
        } catch (err) {
            console.error('儲存模板失敗:', err);
            Toast.error('儲存模板發生錯誤');
        }
    });

    // PDF export form
    document.getElementById('pdfExportForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const pdfExportReceiptId = Modals.getPdfExportReceiptId();
        const pdfExportReceiptIds = Modals.getPdfExportReceiptIds();
        const isBatchMode = pdfExportReceiptIds.length > 0;

        if (!isBatchMode && !pdfExportReceiptId) {
            Toast.error('未選擇單據');
            return;
        }

        // Show loading overlay
        const exportBtn = document.getElementById('pdfExportBtn');
        const originalBtnText = exportBtn.textContent;
        exportBtn.disabled = true;
        exportBtn.innerHTML = '<span class="btn-spinner"></span> 生成中...';

        // Show modal overlay
        let loadingOverlay = document.getElementById('pdfLoadingOverlay');
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'pdfLoadingOverlay';
            loadingOverlay.className = 'pdf-loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="pdf-loading-content">
                    <div class="pdf-loading-spinner"></div>
                    <div class="pdf-loading-text">正在生成 PDF...</div>
                    <div class="pdf-loading-subtext">${isBatchMode ? `處理 ${pdfExportReceiptIds.length} 筆單據` : '請稍候'}</div>
                </div>
            `;
            document.getElementById('pdfExportModal').querySelector('.edit-modal-content').appendChild(loadingOverlay);
        } else {
            loadingOverlay.querySelector('.pdf-loading-subtext').textContent =
                isBatchMode ? `處理 ${pdfExportReceiptIds.length} 筆單據` : '請稍候';
            loadingOverlay.style.display = 'flex';
        }

        const formData = {
            page_size: document.getElementById('pdfPageSize').value,
            margin_top: document.getElementById('pdfMarginTop').value,
            margin_bottom: document.getElementById('pdfMarginBottom').value,
            margin_left: document.getElementById('pdfMarginLeft').value,
            margin_right: document.getElementById('pdfMarginRight').value,
            header_text: document.getElementById('pdfHeader').value,
            header_align: document.querySelector('input[name="pdfHeaderAlign"]:checked')?.value || 'C',
            header_font_size: document.getElementById('pdfHeaderFontSize').value,
            footer_text: document.getElementById('pdfFooter').value,
            footer_align: document.querySelector('input[name="pdfFooterAlign"]:checked')?.value || 'C',
            footer_font_size: document.getElementById('pdfFooterFontSize').value,
            image_align: document.querySelector('input[name="pdfImageAlign"]:checked')?.value || 'C',
            image_height_scale: document.getElementById('pdfImageHeightScale').value,
            image_width_scale: document.getElementById('pdfImageWidthScale').value
        };

        if (isBatchMode) {
            formData.receipt_ids = pdfExportReceiptIds;
        } else {
            formData.receipt_id = pdfExportReceiptId;
        }

        const hideLoading = () => {
            exportBtn.disabled = false;
            exportBtn.textContent = originalBtnText;
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        };

        try {
            const response = await fetch('api/export_pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...API.getCSRFHeaders()
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'PDF 生成失敗');
                } else {
                    const errorText = await response.text();
                    console.error('伺服器錯誤:', errorText);
                    throw new Error('PDF 生成失敗（伺服器錯誤）');
                }
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/pdf')) {
                const text = await response.text();
                try {
                    const jsonData = JSON.parse(text);
                    throw new Error(jsonData.error || 'PDF 生成失敗');
                } catch (e) {
                    console.error('回應內容:', text);
                    throw new Error('伺服器返回了非預期的內容');
                }
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;

            let filename;
            const customFilename = document.getElementById('pdfCustomFilename').value.trim();

            if (customFilename) {
                filename = customFilename.endsWith('.pdf') ? customFilename : customFilename + '.pdf';
            } else if (isBatchMode) {
                filename = `批量單據_${pdfExportReceiptIds.length}筆_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.pdf`;
            } else {
                const receiptsData = State.getReceiptsData();
                const receipt = receiptsData.find(r => r.id == pdfExportReceiptId);
                filename = receipt
                    ? `單據_${receipt.company_name || 'unknown'}_${receipt.receipt_date || ''}.pdf`
                    : `單據_${pdfExportReceiptId}.pdf`;
            }

            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            hideLoading();
            Toast.success('PDF 匯出成功');
            Modals.closePdfExportModal();
        } catch (err) {
            hideLoading();
            console.error('PDF 匯出錯誤:', err);
            Toast.error('PDF 匯出失敗：' + err.message);
        }
    });

    // PDF sliders
    const pdfImageHeightScale = document.getElementById('pdfImageHeightScale');
    if (pdfImageHeightScale) {
        pdfImageHeightScale.addEventListener('input', function () {
            document.getElementById('pdfImageHeightScaleValue').textContent = this.value;
        });
    }

    const pdfImageWidthScale = document.getElementById('pdfImageWidthScale');
    if (pdfImageWidthScale) {
        pdfImageWidthScale.addEventListener('input', function () {
            document.getElementById('pdfImageWidthScaleValue').textContent = this.value;
        });
    }

    // Infinite scroll
    let scrollTimeout = null;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(handleInfiniteScroll, 100);
    });

    // Custom events
    document.addEventListener('receipts:refilter', filterAndRenderReceipts);
    document.addEventListener('receipts:loadPdfTemplates', loadAndApplyPdfTemplates);
    document.addEventListener('receipts:openExport', () => Export.openExportModal());
    document.addEventListener('receipts:toast', (e) => {
        const { type, message } = e.detail;
        if (type === 'warning') Toast.warning(message);
        else if (type === 'error') Toast.error(message);
        else if (type === 'success') Toast.success(message);
        else Toast.info(message);
    });
}

// ========================================
// Initialize
// ========================================
function init() {
    Modals.attachWindowHandlers();
    initEventListeners();
    initYears();
    initTags();
    loadInitialReceipts();
}

init();
