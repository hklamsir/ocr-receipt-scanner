/**
 * receipts-state.js - Shared state management for receipts page
 */

// ========================================
// State Variables
// ========================================
let receiptsData = [];
let allReceiptsCache = [];
let allTags = [];
let availableYears = [];
let deleteTargetId = null;
let editReceiptId = null;
let editReceiptTags = [];
let selectedFilterTags = [];
let tempSelectedTags = [];
let searchTimeout = null;
let selectedReceiptIds = new Set();

// Pagination state
let currentPage = 1;
let isLoadingMore = false;
let hasMoreReceipts = true;
let totalReceiptCount = 0;

// ========================================
// State Getters
// ========================================
export function getReceiptsData() { return receiptsData; }
export function getAllReceiptsCache() { return allReceiptsCache; }
export function getAllTags() { return allTags; }
export function getAvailableYears() { return availableYears; }
export function getDeleteTargetId() { return deleteTargetId; }
export function getEditReceiptId() { return editReceiptId; }
export function getEditReceiptTags() { return editReceiptTags; }
export function getSelectedFilterTags() { return selectedFilterTags; }
export function getTempSelectedTags() { return tempSelectedTags; }
export function getSearchTimeout() { return searchTimeout; }
export function getSelectedReceiptIds() { return selectedReceiptIds; }
export function getCurrentPage() { return currentPage; }
export function getIsLoadingMore() { return isLoadingMore; }
export function getHasMoreReceipts() { return hasMoreReceipts; }
export function getTotalReceiptCount() { return totalReceiptCount; }

// ========================================
// State Setters
// ========================================
export function setReceiptsData(data) { receiptsData = data; }
export function setAllReceiptsCache(data) { allReceiptsCache = data; }
export function setAllTags(data) { allTags = data; }
export function setAvailableYears(data) { availableYears = data; }
export function setDeleteTargetId(id) { deleteTargetId = id; }
export function setEditReceiptId(id) { editReceiptId = id; }
export function setEditReceiptTags(tags) { editReceiptTags = tags; }
export function setSelectedFilterTags(tags) { selectedFilterTags = tags; }
export function setTempSelectedTags(tags) { tempSelectedTags = tags; }
export function setSearchTimeout(timeout) { searchTimeout = timeout; }
export function setCurrentPage(page) { currentPage = page; }
export function setIsLoadingMore(loading) { isLoadingMore = loading; }
export function setHasMoreReceipts(hasMore) { hasMoreReceipts = hasMore; }
export function setTotalReceiptCount(count) { totalReceiptCount = count; }

// ========================================
// State Utilities
// ========================================
export function appendToCache(newReceipts) {
    allReceiptsCache = [...allReceiptsCache, ...newReceipts];
}

export function clearCache() {
    allReceiptsCache = [];
    currentPage = 1;
    hasMoreReceipts = true;
    totalReceiptCount = 0;
}

export function addSelectedReceiptId(id) {
    selectedReceiptIds.add(id);
}

export function removeSelectedReceiptId(id) {
    selectedReceiptIds.delete(id);
}

export function clearSelectedReceiptIds() {
    selectedReceiptIds.clear();
}

export function addSelectedFilterTag(id) {
    if (!selectedFilterTags.includes(id)) {
        selectedFilterTags.push(id);
    }
}

export function removeSelectedFilterTag(id) {
    selectedFilterTags = selectedFilterTags.filter(x => x !== id);
}

export function clearSelectedFilterTags() {
    selectedFilterTags = [];
}

export function addTempSelectedTag(id) {
    if (!tempSelectedTags.includes(id)) {
        tempSelectedTags.push(id);
    }
}

export function removeTempSelectedTag(id) {
    tempSelectedTags = tempSelectedTags.filter(x => x !== id);
}

export function clearTempSelectedTags() {
    tempSelectedTags = [];
}
