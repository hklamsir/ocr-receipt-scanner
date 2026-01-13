/**
 * receipts-api.js - API calls for receipts page
 */
import * as State from './receipts-state.js';
import { Toast } from './toast.js';

// ========================================
// CSRF Token Helper
// ========================================
export function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

export function getCSRFHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken()
    };
}

// ========================================
// Load Years
// ========================================
export async function loadYears() {
    try {
        const res = await fetch('api/get_receipts.php?years=1');
        const data = await res.json();
        if (data.success) {
            State.setAvailableYears(data.years);
            return data.years;
        }
        return [];
    } catch (err) {
        console.error('載入年份失敗:', err);
        return [];
    }
}

// ========================================
// Load Tags
// ========================================
export async function loadTags() {
    try {
        const res = await fetch('api/tags.php');
        const data = await res.json();
        if (data.success) {
            State.setAllTags(data.tags);
            return data.tags;
        }
        return [];
    } catch (err) {
        console.error('載入標籤失敗:', err);
        return [];
    }
}

// ========================================
// Load Receipts (Paginated)
// ========================================
export async function loadReceipts(page = 1, limit = 20, append = false) {
    if (State.getIsLoadingMore()) return null;

    State.setIsLoadingMore(true);

    try {
        const url = `api/get_receipts.php?page=${page}&limit=${limit}`;
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            const { receipts, pagination } = data;

            if (append) {
                State.appendToCache(receipts);
            } else {
                State.setAllReceiptsCache(receipts);
            }

            State.setCurrentPage(pagination.page);
            State.setHasMoreReceipts(pagination.has_more);
            State.setTotalReceiptCount(pagination.total_count);

            return {
                receipts,
                pagination
            };
        }

        return null;
    } catch (err) {
        console.error('載入單據失敗:', err);
        Toast.error('載入記錄失敗');
        return null;
    } finally {
        State.setIsLoadingMore(false);
    }
}

// ========================================
// Load More Receipts (for infinite scroll)
// ========================================
export async function loadMoreReceipts(limit = 20) {
    if (!State.getHasMoreReceipts() || State.getIsLoadingMore()) {
        return null;
    }

    const nextPage = State.getCurrentPage() + 1;
    return loadReceipts(nextPage, limit, true);
}

// ========================================
// Load ALL Remaining Receipts (for select-all)
// ========================================
export async function loadAllRemainingReceipts() {
    console.log('[loadAllRemaining] Starting, hasMore:', State.getHasMoreReceipts(), 'currentPage:', State.getCurrentPage());

    // Load all remaining receipts in batches until done
    while (State.getHasMoreReceipts()) {
        const nextPage = State.getCurrentPage() + 1;
        const limit = 20; // Must match initial load limit for correct OFFSET calculation

        try {
            const url = `api/get_receipts.php?page=${nextPage}&limit=${limit}`;
            console.log('[loadAllRemaining] Fetching:', url);
            const res = await fetch(url);
            const data = await res.json();
            console.log('[loadAllRemaining] Response:', data);

            if (data.success) {
                const { receipts, pagination } = data;
                console.log('[loadAllRemaining] Got', receipts.length, 'receipts, pagination:', pagination);
                State.appendToCache(receipts);
                console.log('[loadAllRemaining] Cache after append:', State.getAllReceiptsCache().length);
                State.setCurrentPage(pagination.page);
                State.setHasMoreReceipts(pagination.has_more);
                State.setTotalReceiptCount(pagination.total_count);
            } else {
                console.log('[loadAllRemaining] API returned success=false');
                break;
            }
        } catch (err) {
            console.error('[loadAllRemaining] Error:', err);
            break;
        }
    }

    console.log('[loadAllRemaining] Done, final cache length:', State.getAllReceiptsCache().length);
    return State.getAllReceiptsCache();
}

// ========================================
// Update Receipt
// ========================================
export async function updateReceipt(id, receiptData) {
    try {
        const res = await fetch('api/update_receipt.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ id, ...receiptData })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success('更新成功');
            return true;
        } else {
            Toast.error(data.message || '更新失敗');
            return false;
        }
    } catch (err) {
        console.error('更新失敗:', err);
        Toast.error('更新失敗');
        return false;
    }
}

// ========================================
// Delete Receipt
// ========================================
export async function deleteReceipt(id) {
    try {
        const res = await fetch('api/delete_receipt.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success('刪除成功');
            return true;
        } else {
            Toast.error(data.message || '刪除失敗');
            return false;
        }
    } catch (err) {
        console.error('刪除失敗:', err);
        Toast.error('刪除失敗');
        return false;
    }
}

// ========================================
// Bulk Add Tags
// ========================================
export async function bulkAddTags(receiptIds, tagIds) {
    try {
        const res = await fetch('api/bulk_add_tags.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ receipt_ids: receiptIds, tag_ids: tagIds })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success('標籤已加入');
            return true;
        } else {
            Toast.error(data.message || '操作失敗');
            return false;
        }
    } catch (err) {
        console.error('批量加標籤失敗:', err);
        Toast.error('操作失敗');
        return false;
    }
}

// ========================================
// Bulk Remove Tags
// ========================================
export async function bulkRemoveTags(receiptIds, tagIds) {
    try {
        const res = await fetch('api/bulk_remove_tags.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ receipt_ids: receiptIds, tag_ids: tagIds })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success('標籤已移除');
            return true;
        } else {
            Toast.error(data.message || '操作失敗');
            return false;
        }
    } catch (err) {
        console.error('批量移除標籤失敗:', err);
        Toast.error('操作失敗');
        return false;
    }
}

// ========================================
// Bulk Delete
// ========================================
export async function bulkDelete(receiptIds) {
    try {
        const res = await fetch('api/bulk_delete_receipts.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ ids: receiptIds })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success(`已刪除 ${receiptIds.length} 筆單據`);
            return true;
        } else {
            Toast.error(data.message || '刪除失敗');
            return false;
        }
    } catch (err) {
        console.error('批量刪除失敗:', err);
        Toast.error('刪除失敗');
        return false;
    }
}

// ========================================
// Create Tag
// ========================================
export async function createTag(name, color) {
    try {
        const res = await fetch('api/tags.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ name, color })
        });
        const data = await res.json();

        if (data.success) {
            Toast.success('標籤已建立');
            return data.tag;
        } else {
            Toast.error(data.message || '建立失敗');
            return null;
        }
    } catch (err) {
        console.error('建立標籤失敗:', err);
        Toast.error('建立失敗');
        return null;
    }
}

// ========================================
// Load PDF Templates
// ========================================
export async function loadPdfTemplates() {
    try {
        const res = await fetch('api/get_pdf_templates.php');
        const data = await res.json();

        if (data.success) {
            return data.templates;
        }
        return [];
    } catch (err) {
        console.error('載入模板失敗:', err);
        return [];
    }
}
