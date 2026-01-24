// 主應用程式（使用模組化架構）
import { AppState } from './modules/state.js';
import { processImagesParallel, extractStructuredData, combineOCRResults } from './modules/ocr.js';
import { updateGlobalStatus, updateOCRStatus, showOCRResultModal, closeOCRModal, openImageModal, closeImageModal, renderTable, showError, toggleCopyButton, clearUI } from './modules/ui.js';
import { compressImage, validateImageFile, sortTableData, convertToTSV } from './modules/utils.js';
import { Toast, Dialog } from './modules/toast.js';

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

// 全域設定
let appConfig = { ocrProxyUrl: 'ocr_proxy.php', maxFiles: 20 };

// 初始化應用程式
async function initApp() {
    try {
        const response = await fetch('api/get_config.php');
        appConfig = await response.json();
        AppState.init(appConfig);
    } catch (err) {
        console.error('設定載入失敗，使用預設值', err);
        AppState.init(appConfig);
    }

    setupEventListeners();

    // 如果有恢復的資料，重新渲染 UI
    if (AppState.getImageCount() > 0) {
        renderPreview();
        updateGlobalStatus('已自動恢復 ' + AppState.getImageCount() + ' 張圖片');

        // 如果有結構化資料，也恢復表格
        const currentData = AppState.getCurrentData();
        if (currentData && currentData.length > 0) {
            renderTable(currentData);
            toggleCopyButton(true);
        }
    }
}

// 設定事件監聽器
function setupEventListeners() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');

    // Dropzone 事件
    dropzone.onclick = () => fileInput.click();
    dropzone.ondragover = e => { e.preventDefault(); dropzone.classList.add('dragover'); };
    dropzone.ondragleave = () => dropzone.classList.remove('dragover');
    dropzone.ondrop = e => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    };

    // 檔案輸入事件
    fileInput.onchange = e => handleFiles(e.target.files);

    // 按鈕事件
    document.getElementById('startBtn').onclick = startOCR;
    document.getElementById('clearBtn').onclick = clearAll;
    document.getElementById('copyBtn').onclick = copyToExcel;
    document.getElementById('saveBtn').onclick = saveReceipts;

    // Modal 事件
    document.getElementById('ocrModal').onclick = e => {
        if (e.target === document.getElementById('ocrModal')) closeOCRModal();
    };
    document.getElementById('modal').onclick = closeImageModal;

    // 全域函數（供 onclick 使用）
    window.closeOcrModal = closeOCRModal;
    window.closeModal = closeImageModal;
    window.sortTable = sortTable;
}

// 處理檔案上傳
async function handleFiles(fileList) {
    for (let file of Array.from(fileList)) {
        try {
            if (AppState.getImageCount() >= appConfig.maxFiles) {
                Toast.warning(`最多 ${appConfig.maxFiles} 張`);
                return;
            }

            if (!validateImageFile(file)) continue;

            const compressed = await compressImage(file);
            AppState.addImage(compressed);
            renderPreview();
        } catch (err) {
            console.error('圖片處理錯誤:', err);
        }
    }
}

// 渲染預覽
function renderPreview() {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';

    AppState.getAllImages().forEach((img, idx) => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
      <img src="${img.dataUrl}">
      <div class="info">${img.name}</div>
      <div class="status">等待 OCR</div>
      <button class="crop-btn">裁切</button>
      <button class="delete-btn">×</button>
    `;

        card.querySelector('img').onclick = () => openImageModal(img.dataUrl);
        card.querySelector('.crop-btn').onclick = (e) => {
            e.stopPropagation();
            openCropModal(idx);
        };
        card.querySelector('.delete-btn').onclick = () => {
            AppState.removeImage(idx);
            renderPreview();
        };

        preview.appendChild(card);
    });
}

// 開始 OCR 處理
async function startOCR() {
    const images = AppState.getAllImages();
    if (images.length === 0) {
        Toast.warning('請先加入圖片');
        return;
    }

    updateGlobalStatus('OCR 處理中...');

    // 並行處理 OCR（限制 2 個）
    const results = await processImagesParallel(images, appConfig.ocrProxyUrl, updateOCRStatus);

    // 檢查是否有配置錯誤（如 API Key 未設定）
    const configError = results.find(r =>
        r.status === 'fulfilled' && r.value.error && r.value.error.includes('系統尚未設定')
    );
    if (configError) {
        Toast.error(configError.value.error);
        updateGlobalStatus('錯誤：' + configError.value.error);
        return;
    }

    // 組合 OCR 結果
    const ocrText = combineOCRResults(results);

    updateGlobalStatus('OCR完成，LLM正在提取結構化資料...');

    // 顯示 OCR 結果按鈕
    const buttonContainer = document.getElementById('ocrButtonContainer');
    buttonContainer.innerHTML = '';

    if (ocrText.trim()) {
        const showBtn = document.createElement('button');
        showBtn.innerText = '查看完整 OCR 結果';
        showBtn.className = 'btn btn-info';
        showBtn.style.marginBottom = '15px';
        showBtn.onclick = () => showOCRResultModal(ocrText);
        buttonContainer.appendChild(showBtn);

        // 提取結構化資料
        try {
            const data = await extractStructuredData(ocrText);

            if (data.success) {
                AppState.setCurrentData(data.result);
                renderTable(data.result);
                toggleCopyButton(true);

                updateGlobalStatus('全部完成（含結構化提取）');
            } else {
                showError(data.error || '未知錯誤', data.raw);
                toggleCopyButton(false);
                updateGlobalStatus('OCR 完成，但結構化提取失敗');
            }
        } catch (err) {
            console.error('提取錯誤:', err);
            showError(err.message);
            updateGlobalStatus('提取請求失敗');
        }
    } else {
        updateGlobalStatus('OCR 完成（無文字內容）');
    }
}

// 表格排序
function sortTable(colIndex, direction) {
    const currentData = AppState.getCurrentData();
    const sortedData = sortTableData(currentData, colIndex, direction);
    renderTable(sortedData);

    document.querySelectorAll('th').forEach((th, i) => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        if (i === colIndex) {
            th.classList.add(direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
        }
    });
}

// 複製到 Excel
function copyToExcel() {
    const tsv = convertToTSV(AppState.getCurrentData());
    navigator.clipboard.writeText(tsv).then(() => {
        Toast.success('已複製表格內容，可直接貼到 Excel！');
    }).catch(err => {
        console.error('複製失敗:', err);
        Toast.error('複製失敗，請手動選取表格內容');
    });
}

// 30 色調色盤 (6 hues x 5 shades)
const PRESET_COLORS = [
    '#fca5a5', '#fdba74', '#86efac', '#93c5fd', '#d8b4fe', '#f9a8d4',
    '#f87171', '#fb923c', '#4ade80', '#60a5fa', '#a78bfa', '#f472b6',
    '#ef4444', '#f97316', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899',
    '#dc2626', '#ea580c', '#16a34a', '#2563eb', '#7c3aed', '#db2777',
    '#b91c1c', '#c2410c', '#15803d', '#1d4ed8', '#6d28d9', '#be185d'
];

let allTagsCache = [];
let selectedSaveTags = [];

// 載入所有 tags
async function loadTagsForSave() {
    try {
        const res = await fetch('api/tags.php');
        const data = await res.json();
        if (data.success) {
            allTagsCache = data.tags;
            renderSaveTagsGrid();
        }
    } catch (err) {
        console.error('載入標籤失敗:', err);
    }
}

// 渲染儲存時的 tag 選擇 grid
function renderSaveTagsGrid() {
    const grid = document.getElementById('saveTagsGrid');
    if (!grid) return;

    if (allTagsCache.length === 0) {
        grid.innerHTML = '<p style="color:#999;text-align:center;">尚無標籤，請先建立</p>';
        return;
    }
    grid.innerHTML = allTagsCache.map(t => `
        <div class="tag-item ${selectedSaveTags.includes(t.id) ? 'selected' : ''}" 
             data-id="${t.id}" style="--tag-color:${t.color};">
            <span class="tag" style="background:${t.color};">${t.name}</span>
        </div>
    `).join('');

    grid.querySelectorAll('.tag-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = parseInt(item.dataset.id);
            if (selectedSaveTags.includes(id)) {
                selectedSaveTags = selectedSaveTags.filter(x => x !== id);
                item.classList.remove('selected');
            } else {
                if (selectedSaveTags.length >= 5) {
                    Toast.warning('最多只能選擇 5 個標籤');
                    return;
                }
                selectedSaveTags.push(id);
                item.classList.add('selected');
            }
        });
    });
}

// 渲染顏色調色盤
function renderColorPaletteIndex(containerId, selectedColor, onSelect) {
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

// 儲存記錄到資料庫 (先選擇 tags)
async function saveReceipts() {
    const currentData = AppState.getCurrentData();
    const images = AppState.getAllImages();

    if (currentData.length === 0) {
        Toast.warning('沒有資料可儲存');
        return;
    }

    // 先載入 tags 並顯示選擇 modal
    selectedSaveTags = [];
    await loadTagsForSave();
    document.getElementById('saveTagsModal').style.display = 'flex';
}

// 關閉 tag 選擇 modal
window.closeSaveTagsModal = function () {
    document.getElementById('saveTagsModal').style.display = 'none';
};

// 新建標籤按鈕
document.getElementById('createTagInSaveBtn')?.addEventListener('click', () => {
    document.getElementById('newTagNameIndex').value = '';
    document.getElementById('newTagColorIndex').value = '#3b82f6';
    renderColorPaletteIndex('newTagColorPaletteIndex', '#3b82f6', (color) => {
        document.getElementById('newTagColorIndex').value = color;
    });
    document.getElementById('createTagModalIndex').style.display = 'flex';
});

window.closeCreateTagModalIndex = function () {
    document.getElementById('createTagModalIndex').style.display = 'none';
};

// 建立新標籤
document.getElementById('saveNewTagBtnIndex')?.addEventListener('click', async () => {
    const name = document.getElementById('newTagNameIndex').value.trim();
    const color = document.getElementById('newTagColorIndex').value;

    if (!name) {
        Toast.warning('請輸入標籤名稱');
        return;
    }

    try {
        const res = await fetch('api/tags.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ name, color })
        });
        const result = await res.json();
        if (result.success) {
            Toast.success('標籤已建立');
            closeCreateTagModalIndex();
            await loadTagsForSave();
            // 自動選中新建的標籤
            if (result.tag && selectedSaveTags.length < 5) {
                selectedSaveTags.push(result.tag.id);
                renderSaveTagsGrid();
            }
        } else {
            Toast.error(result.error || '建立失敗');
        }
    } catch (err) {
        Toast.error('建立標籤失敗');
    }
});

// 確認儲存按鈕
document.getElementById('confirmSaveWithTagsBtn')?.addEventListener('click', async () => {
    const currentData = AppState.getCurrentData();
    const images = AppState.getAllImages();

    // 組合資料
    const receipts = currentData.map((item, index) => ({
        date: item.日期,
        time: item.時間,
        company: item.公司名稱,
        items: item.購買物品摘要,
        payment: item.支付方式,
        amount: item.總金額,
        summary: item.總結,
        image: images[index]?.dataUrl || '',
        engine: window.lastOcrEngine || 2,
        tag_ids: selectedSaveTags  // 加入選中的 tags
    }));

    // Show loading overlay
    const loadingOverlay = document.getElementById('saveLoadingOverlay');
    const saveBtn = document.getElementById('confirmSaveWithTagsBtn');

    if (loadingOverlay) loadingOverlay.style.display = 'flex';
    if (saveBtn) saveBtn.disabled = true;

    try {
        const response = await fetch('api/save_receipts.php', {
            method: 'POST',
            headers: getCSRFHeaders(),
            body: JSON.stringify({ receipts })
        });

        const result = await response.json();

        if (result.success) {
            Toast.success(`成功儲存 ${result.saved} 筆單據！`);
            closeSaveTagsModal();
            // Clear data after successful save
            AppState.clearAll();
            renderPreview();
            clearUI();
            closeOCRModal();
        } else {
            Toast.error('儲存失敗：' + (result.error || '未知錯誤'));
        }
    } catch (err) {
        console.error('儲存錯誤:', err);
        Toast.error('儲存失敗，請稍後再試');
    } finally {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
        if (saveBtn) saveBtn.disabled = false;
    }
});

// 清除所有
async function clearAll() {
    const confirmed = await Dialog.confirm(
        '確定清除所有單據？',
        { title: '清除確認', danger: true }
    );

    if (!confirmed) return;

    AppState.clearAll();
    renderPreview();
    clearUI();
    closeOCRModal();
}

// ========================================
//    Cropper 相關功能
// ========================================

let currentCropper = null;
let currentCropIndex = -1;
let cropScaleX = 1;
let cropScaleY = 1;

// 開啟裁剪 Modal
function openCropModal(index) {
    currentCropIndex = index;
    cropScaleX = 1;
    cropScaleY = 1;

    const images = AppState.getAllImages();
    const img = images[index];
    if (!img) return;

    const cropImage = document.getElementById('cropImage');
    cropImage.src = img.dataUrl;

    document.getElementById('cropModal').style.display = 'flex';

    // 等待圖片載入後初始化 Cropper
    cropImage.onload = function () {
        if (currentCropper) {
            currentCropper.destroy();
        }
        currentCropper = new Cropper(cropImage, {
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: true,
            background: true,
        });
    };
}

// 關閉裁剪 Modal
window.closeCropModal = function () {
    if (currentCropper) {
        currentCropper.destroy();
        currentCropper = null;
    }
    currentCropIndex = -1;
    document.getElementById('cropModal').style.display = 'none';
};

// 旋轉
window.cropperRotate = function (degree) {
    if (currentCropper) {
        currentCropper.rotate(degree);
    }
};

// 水平翻轉
window.cropperFlipH = function () {
    if (currentCropper) {
        cropScaleX = cropScaleX * -1;
        currentCropper.scaleX(cropScaleX);
    }
};

// 垂直翻轉
window.cropperFlipV = function () {
    if (currentCropper) {
        cropScaleY = cropScaleY * -1;
        currentCropper.scaleY(cropScaleY);
    }
};

// 重置
window.cropperReset = function () {
    if (currentCropper) {
        cropScaleX = 1;
        cropScaleY = 1;
        currentCropper.reset();
    }
};

// 套用裁剪
window.applyCrop = function () {
    if (!currentCropper || currentCropIndex < 0) return;

    // 取得裁剪後的 canvas
    const canvas = currentCropper.getCroppedCanvas({
        maxWidth: 4096,
        maxHeight: 4096,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    if (!canvas) {
        Toast.error('裁剪失敗，請重試');
        return;
    }

    // 轉換為 dataUrl
    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);

    // 更新 AppState 中的圖片
    const images = AppState.getAllImages();
    if (images[currentCropIndex]) {
        images[currentCropIndex].dataUrl = croppedDataUrl;
        AppState.saveToLocalStorage(); // 儲存到 storage
        Toast.success('圖片已裁剪');
    }

    // 重新渲染預覽
    closeCropModal();
    renderPreview();
};

// 初始化
initApp();
