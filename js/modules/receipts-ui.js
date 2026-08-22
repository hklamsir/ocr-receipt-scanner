/**
 * receipts-ui.js - UI rendering functions for receipts page
 */
import * as State from './receipts-state.js';
import { escapeHtml, safeColor } from './utils.js';

// 30 色調色盤 (6 hues x 5 shades)
export const PRESET_COLORS = [
    '#fca5a5', '#fdba74', '#86efac', '#93c5fd', '#d8b4fe', '#f9a8d4',
    '#f87171', '#fb923c', '#4ade80', '#60a5fa', '#a78bfa', '#f472b6',
    '#ef4444', '#f97316', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899',
    '#dc2626', '#ea580c', '#16a34a', '#2563eb', '#7c3aed', '#db2777',
    '#b91c1c', '#c2410c', '#15803d', '#1d4ed8', '#6d28d9', '#be185d'
];

// ========================================
// Color Palette
// ========================================
export function renderColorPalette(containerId, selectedColor, onSelect) {
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

// ========================================
// Year Selector
// ========================================
export function renderYearSelector(years) {
    const select = document.getElementById('yearFilter');
    if (!select) return;

    select.innerHTML = '<option value="">所有年份</option>' +
        years.map(y => `<option value="${escapeHtml(y)}">${escapeHtml(y)}</option>`).join('');
}

// ========================================
// Tag Grid
// ========================================
export function renderTagGrid() {
    const allTags = State.getAllTags();
    const selectedFilterTags = State.getSelectedFilterTags();
    const grid = document.getElementById('tagGrid');
    if (!grid) return;

    if (allTags.length === 0) {
        grid.innerHTML = '<p style="color:#999;text-align:center;">尚無標籤，請先在設定中建立</p>';
        return;
    }
    grid.innerHTML = allTags.map(t => `
            <div class="tag-item ${selectedFilterTags.includes(t.id) ? 'selected' : ''}" 
                 data-id="${t.id}" style="--tag-color:${safeColor(t.color)};">
                <span class="tag" style="background:${safeColor(t.color)};">${escapeHtml(t.name)}</span>
            </div>
        `).join('');

    grid.querySelectorAll('.tag-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = parseInt(item.dataset.id);
            if (selectedFilterTags.includes(id)) {
                State.removeSelectedFilterTag(id);
                item.classList.remove('selected');
            } else {
                State.addSelectedFilterTag(id);
                item.classList.add('selected');
            }
        });
    });
}

// ========================================
// Receipt Card Rendering
// ========================================
export function truncateText(text, maxLen = 15) {
    if (!text) return '無';
    if (text.length > maxLen) {
        return text.substring(0, maxLen) + '...';
    }
    return text;
}

export function renderReceipts(receipts, append = false) {
    const container = document.getElementById('receipts-container');
    if (!container) return;

    const selectedReceiptIds = State.getSelectedReceiptIds();

    const html = receipts.map(r => `
    <div class="receipt-card" data-id="${r.id}">
      <label class="card-checkbox-wrapper" onclick="event.stopPropagation()">
        <input type="checkbox" class="card-checkbox" data-id="${r.id}" ${selectedReceiptIds.has(r.id) ? 'checked' : ''}>
      </label>
      ${r.image_filename ? `
      <div class="receipt-img-wrap" data-tip="放大圖片">
        <img class="lazy-img" data-src="api/get_image.php?filename=${escapeHtml(r.image_filename)}" onclick="openModal('api/get_image.php?filename=${escapeHtml(r.image_filename)}')">
        <button type="button" class="crop-btn" title="裁剪圖片" aria-label="裁剪圖片" onclick="event.stopPropagation(); openCropModal(${r.id});">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
        </button>
      </div>` : ''}
      <div class="receipt-info">
        <div><strong>日期：</strong>${escapeHtml(r.receipt_date) || '無'} ${escapeHtml(r.receipt_time) || ''}</div>
        <div><strong>公司：</strong>${escapeHtml(r.company_name) || '無'}</div>
        <div><strong>項目：</strong>${escapeHtml(truncateText(r.items_summary, 15))}</div>
        <div><strong>總結：</strong>${escapeHtml(r.summary) || '無'}</div>
        <div><strong>支付：</strong>${escapeHtml(r.payment_method) || '無'}</div>
        <div><strong>金額：</strong>${escapeHtml(r.total_amount) || '無'}</div>
        ${r.tags && r.tags.length > 0 ? `
        <div class="receipt-tags">
            ${r.tags.map(t => `<span class="tag tag-sm" style="background:${safeColor(t.color)};">${escapeHtml(t.name)}</span>`).join('')}
        </div>
        ` : ''}
        <div style="margin-top:10px;color:#999;font-size:12px;">
          建立時間：${escapeHtml(r.created_at)}
        </div>
      </div>
      <div class="receipt-card-actions">
        <button class="btn btn-sm btn-primary" onclick="openEditModal(${r.id})">✏️ 編輯</button>
        <button class="btn btn-sm btn-danger" onclick="openDeleteModal(${r.id})">🗑️ 刪除</button>
        <button class="btn btn-sm btn-pdf" onclick="openPdfExportModal(${r.id})">📄 匯出PDF</button>
      </div>
    </div>
  `).join('');

    if (append) {
        container.insertAdjacentHTML('beforeend', html);
    } else {
        container.innerHTML = html;
    }

    // Re-attach checkbox listeners
    container.querySelectorAll('.card-checkbox').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const id = parseInt(e.target.dataset.id);
            if (e.target.checked) {
                State.addSelectedReceiptId(id);
            } else {
                State.removeSelectedReceiptId(id);
            }
            updateSelectAllState();
            updateSelectedCount();
        });
    });

    updateSelectAllState();
    updateSelectedCount();
    setupLazyLoading();
}

// ========================================
// Lazy Loading
// ========================================
export function setupLazyLoading() {
    const lazyImages = document.querySelectorAll('.lazy-img:not([src])');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '300px'
        });
        lazyImages.forEach(img => observer.observe(img));
    } else {
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
        });
    }
}

// ========================================
// Image Preloading
// ========================================
export function preloadAllImages(receipts) {
    const loadImage = (filename) => {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = resolve;
            img.onerror = resolve;
            img.src = `api/get_image.php?filename=${filename}`;
        });
    };

    const imagesWithFiles = receipts.filter(r => r.image_filename);
    let index = 0;

    const loadNext = () => {
        if (index >= imagesWithFiles.length) return;

        const receipt = imagesWithFiles[index++];
        loadImage(receipt.image_filename).then(() => {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(loadNext, { timeout: 500 });
            } else {
                setTimeout(loadNext, 50);
            }
        });
    };

    if ('requestIdleCallback' in window) {
        requestIdleCallback(loadNext, { timeout: 1000 });
    } else {
        setTimeout(loadNext, 100);
    }
}

// ========================================
// Update UI Elements
// ========================================
export function updateReceiptCount() {
    const countSpan = document.getElementById('receiptCount');
    if (countSpan) {
        const total = State.getTotalReceiptCount();
        const loaded = State.getAllReceiptsCache().length;
        if (total > 0) {
            countSpan.textContent = `已載入 ${loaded} / ${total} 張`;
        } else {
            countSpan.textContent = '';
        }
    }
}

// ========================================
// Filtered Count Display
// ========================================
export function updateFilteredCount(hasActiveFilter, filteredCount, totalCount) {
    const filteredCountSpan = document.getElementById('filteredCount');
    const bottomFilteredCountSpan = document.getElementById('bottomFilteredCount');

    const updateSpan = (span) => {
        if (!span) return;
        if (hasActiveFilter && totalCount > 0) {
            span.textContent = `已篩選 ${filteredCount} 張單據`;
            span.style.display = 'inline';
        } else {
            span.style.display = 'none';
        }
    };

    updateSpan(filteredCountSpan);
    updateSpan(bottomFilteredCountSpan);
}

export function updateSelectedTagsBar() {
    const selectedFilterTags = State.getSelectedFilterTags();
    const allTags = State.getAllTags();
    const bar = document.getElementById('selectedTagsBar');
    const list = document.getElementById('selectedTagsList');
    if (!bar || !list) return;

    if (selectedFilterTags.length === 0) {
        bar.style.display = 'none';
        return;
    }

    bar.style.display = 'flex';
    list.innerHTML = selectedFilterTags.map(id => {
        const tag = allTags.find(t => t.id === id);
        if (!tag) return '';
        return `<span class="tag" style="background:${safeColor(tag.color)};">${escapeHtml(tag.name)}
                <button class="tag-remove" data-id="${id}">×</button>
            </span>`;
    }).join('');

    list.querySelectorAll('.tag-remove').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = parseInt(btn.dataset.id);
            State.removeSelectedFilterTag(id);
            // Trigger re-filter (will be called by main module)
            document.dispatchEvent(new CustomEvent('receipts:refilter'));
        });
    });
}

export function updateSelectAllState() {
    const checkboxes = document.querySelectorAll('.card-checkbox');
    const selectAllCb = document.getElementById('selectAllCheckbox');
    const bottomSelectAllCb = document.getElementById('bottomSelectAllCheckbox');

    const updateCheckbox = (cb) => {
        if (!cb) return;
        if (checkboxes.length === 0) {
            cb.checked = false;
            cb.indeterminate = false;
            return;
        }
        const checkedCount = document.querySelectorAll('.card-checkbox:checked').length;
        cb.checked = checkedCount === checkboxes.length;
        cb.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    };

    updateCheckbox(selectAllCb);
    updateCheckbox(bottomSelectAllCb);
}

export function updateSelectedCount() {
    const countSpan = document.getElementById('selectedCount');
    const bottomCountSpan = document.getElementById('bottomSelectedCount');
    const selectedIds = State.getSelectedReceiptIds();
    const count = selectedIds.size;

    // Show count of selected items that are in current view
    const currentData = State.getReceiptsData();
    const currentIds = new Set(currentData.map(r => r.id));
    let visibleSelectedCount = 0;
    selectedIds.forEach(id => {
        if (currentIds.has(id)) visibleSelectedCount++;
    });

    const updateSpan = (span) => {
        if (!span) return;
        if (count > 0) {
            // If all selected items are visible, just show the count
            // Otherwise show "visible / total" to indicate some are filtered out
            if (visibleSelectedCount === count) {
                span.textContent = `已選 ${count} 筆`;
            } else {
                span.textContent = `已選 ${visibleSelectedCount} / ${count} 筆`;
            }
        } else {
            span.textContent = '';
        }
    };

    updateSpan(countSpan);
    updateSpan(bottomCountSpan);

    const toolbarActions = document.getElementById('toolbarActions');
    const bottomToolbarActions = document.getElementById('bottomToolbarActions');
    if (toolbarActions) {
        toolbarActions.style.display = count > 0 ? 'flex' : 'none';
    }
    if (bottomToolbarActions) {
        bottomToolbarActions.style.display = count > 0 ? 'flex' : 'none';
    }
}

// ========================================
// Edit Tags Display
// ========================================
export function renderEditTags() {
    const editReceiptTags = State.getEditReceiptTags();
    const container = document.getElementById('editTagsContainer');
    if (!container) return;

    if (editReceiptTags.length === 0) {
        container.innerHTML = '<span style="color:#999;font-size:13px;">尚無標籤</span>';
        return;
    }
    container.innerHTML = editReceiptTags.map(t => `
            <span class="tag" style="background:${safeColor(t.color)};">${escapeHtml(t.name)}</span>
        `).join('');
}

// ========================================
// Bulk Tags Grid
// ========================================
let bulkSelectedTags = [];
let bulkRemoveTags = [];

export function getBulkSelectedTags() { return bulkSelectedTags; }
export function setBulkSelectedTags(tags) { bulkSelectedTags = tags; }
export function getBulkRemoveTags() { return bulkRemoveTags; }
export function setBulkRemoveTags(tags) { bulkRemoveTags = tags; }

export function renderBulkTagsGrid() {
    const allTags = State.getAllTags();
    const grid = document.getElementById('bulkTagsGrid');
    if (!grid) return;

    if (allTags.length === 0) {
        grid.innerHTML = '<p style="color:#999;text-align:center;">尚無標籤，請先在設定中建立</p>';
        return;
    }
    grid.innerHTML = allTags.map(t => `
            <div class="tag-item ${bulkSelectedTags.includes(t.id) ? 'selected' : ''}" 
                 data-id="${t.id}" style="--tag-color:${safeColor(t.color)};">
                <span class="tag" style="background:${safeColor(t.color)};">${escapeHtml(t.name)}</span>
            </div>
        `).join('');

    grid.querySelectorAll('.tag-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = parseInt(item.dataset.id);
            if (bulkSelectedTags.includes(id)) {
                bulkSelectedTags = bulkSelectedTags.filter(x => x !== id);
                item.classList.remove('selected');
            } else {
                bulkSelectedTags.push(id);
                item.classList.add('selected');
            }
        });
    });
}

export function renderBulkRemoveTagsGrid() {
    const allTags = State.getAllTags();
    const grid = document.getElementById('bulkRemoveTagsGrid');
    if (!grid) return;

    if (allTags.length === 0) {
        grid.innerHTML = '<p style="color:#999;text-align:center;">尚無標籤</p>';
        return;
    }
    grid.innerHTML = allTags.map(t => `
            <div class="tag-item ${bulkRemoveTags.includes(t.id) ? 'selected' : ''}" 
                 data-id="${t.id}" style="--tag-color:${safeColor(t.color)};">
                <span class="tag" style="background:${safeColor(t.color)};">${escapeHtml(t.name)}</span>
            </div>
        `).join('');

    grid.querySelectorAll('.tag-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = parseInt(item.dataset.id);
            if (bulkRemoveTags.includes(id)) {
                bulkRemoveTags = bulkRemoveTags.filter(x => x !== id);
                item.classList.remove('selected');
            } else {
                bulkRemoveTags.push(id);
                item.classList.add('selected');
            }
        });
    });
}

// ========================================
// Edit Tags Modal Grid
// ========================================
export function renderEditTagsGrid() {
    const allTags = State.getAllTags();
    const tempSelectedTags = State.getTempSelectedTags();
    const grid = document.getElementById('editTagsGrid');
    if (!grid) return;

    if (allTags.length === 0) {
        grid.innerHTML = '<p style="color:#999;text-align:center;">尚無標籤，請先在設定中建立</p>';
        return;
    }
    grid.innerHTML = allTags.map(t => `
            <div class="tag-item ${tempSelectedTags.includes(t.id) ? 'selected' : ''}" 
                 data-id="${t.id}" style="--tag-color:${safeColor(t.color)};">
                <span class="tag" style="background:${safeColor(t.color)};">${escapeHtml(t.name)}</span>
            </div>
        `).join('');

    grid.querySelectorAll('.tag-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = parseInt(item.dataset.id);
            const currentTemp = State.getTempSelectedTags();
            if (currentTemp.includes(id)) {
                State.removeTempSelectedTag(id);
                item.classList.remove('selected');
            } else {
                if (currentTemp.length >= 5) {
                    // Import Toast dynamically or use a callback
                    document.dispatchEvent(new CustomEvent('receipts:toast', {
                        detail: { type: 'warning', message: '每張單據最多只能有 5 個標籤' }
                    }));
                    return;
                }
                State.addTempSelectedTag(id);
                item.classList.add('selected');
            }
        });
    });
}

// ========================================
// Loading Indicator
// ========================================
export function showLoadingMore() {
    let indicator = document.getElementById('loadingMore');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'loadingMore';
        indicator.className = 'loading-more';
        indicator.innerHTML = '<div class="spinner"></div><span>載入更多...</span>';
        const container = document.getElementById('receipts-container');
        container.parentNode.insertBefore(indicator, container.nextSibling);
    }
    indicator.style.display = 'flex';
}

export function hideLoadingMore() {
    const indicator = document.getElementById('loadingMore');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

export function showEndOfList() {
    let indicator = document.getElementById('endOfList');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'endOfList';
        indicator.className = 'end-of-list';
        indicator.innerHTML = '已載入全部單據';
        const container = document.getElementById('receipts-container');
        container.parentNode.insertBefore(indicator, container.nextSibling);
    }
    indicator.style.display = 'block';
}

export function hideEndOfList() {
    const indicator = document.getElementById('endOfList');
    if (indicator) {
        indicator.style.display = 'none';
    }
}
