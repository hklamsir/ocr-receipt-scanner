// UI 更新模組

// 更新全域狀態訊息
export function updateGlobalStatus(message) {
    document.getElementById('globalStatus').innerText = message;
}

// 更新單張圖片的 OCR 狀態
export function updateOCRStatus(index, status) {
    const statuses = document.querySelectorAll('.status');
    if (statuses[index]) {
        statuses[index].innerText = status;
    }
}

// 顯示 OCR 結果 Modal
export function showOCRResultModal(ocrText) {
    document.getElementById('result').value = ocrText;
    document.getElementById('ocrModal').style.display = 'flex';
}

// 關閉 OCR 結果 Modal
export function closeOCRModal() {
    document.getElementById('ocrModal').style.display = 'none';
}

// 顯示圖片放大 Modal
export function openImageModal(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('modal').style.display = 'flex';
}

// 關閉圖片 Modal
export function closeImageModal() {
    document.getElementById('modal').style.display = 'none';
}

// 渲染表格（含驗證）
export function renderTable(data) {
    // 動態導入驗證模組
    import('./validation.js').then(({ validateReceipt }) => {
        let tableHtml = '<table><thead><tr>' +
            '<th onclick="sortTable(0, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">日期</th>' +
            '<th onclick="sortTable(1, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">時間</th>' +
            '<th onclick="sortTable(2, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">公司名稱</th>' +
            '<th onclick="sortTable(3, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">購買物品</th>' +
            '<th onclick="sortTable(4, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">支付方式</th>' +
            '<th onclick="sortTable(5, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">總金額</th>' +
            '</tr></thead><tbody>';

        for (let item of data) {
            // 驗證資料
            const errors = validateReceipt(item);
            const errorMap = new Map();
            errors.forEach(err => {
                if (!errorMap.has(err.field)) {
                    errorMap.set(err.field, []);
                }
                errorMap.get(err.field).push(err);
            });

            // 生成錯誤提示
            const getFieldHtml = (field, value) => {
                if (errorMap.has(field)) {
                    const fieldErrors = errorMap.get(field);
                    const hasError = fieldErrors.some(e => e.severity === 'error');
                    const hasWarning = fieldErrors.some(e => e.severity === 'warning');
                    const className = hasError ? 'field-error' : (hasWarning ? 'field-warning' : '');
                    const icon = hasError ? '⚠️' : (hasWarning ? '⚡' : '');
                    const tooltip = fieldErrors.map(e => e.message).join('; ');

                    return `<span class="${className}" data-tooltip="${tooltip}">${icon} ${value || ''}</span>`;
                }
                return value || '';
            };

            // 購買物品欄：總結（第1行）+ 購買物品摘要（第2行）
            const summary = item.總結 || '';
            const items = item.購買物品摘要 || '';
            const purchaseContent = summary
                ? `${summary}<br><span style="color:#666;font-size:12px;">${items}</span>`
                : items;

            tableHtml += `<tr>
      <td>${getFieldHtml('日期', item.日期)}</td>
      <td>${getFieldHtml('時間', item.時間)}</td>
      <td>${item.公司名稱 || ''}</td>
      <td>${purchaseContent}</td>
      <td>${item.支付方式 || ''}</td>
      <td>${getFieldHtml('總金額', item.總金額)}</td>
    </tr>`;
        }
        tableHtml += '</tbody></table>';
        document.getElementById('structuredTable').innerHTML = tableHtml;
    }).catch(err => {
        console.error('Failed to load validation module:', err);
        // Fallback: 渲染無驗證的表格
        renderTableWithoutValidation(data);
    });
}

// 備用渲染函數（無驗證）
function renderTableWithoutValidation(data) {
    let tableHtml = '<table><thead><tr>' +
        '<th onclick="sortTable(0, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">日期</th>' +
        '<th onclick="sortTable(1, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">時間</th>' +
        '<th onclick="sortTable(2, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">公司名稱</th>' +
        '<th onclick="sortTable(3, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">購買物品</th>' +
        '<th onclick="sortTable(4, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">支付方式</th>' +
        '<th onclick="sortTable(5, this.classList.contains(\'sorted-asc\') ? \'desc\' : \'asc\')">總金額</th>' +
        '</tr></thead><tbody>';

    for (let item of data) {
        const summary = item.總結 || '';
        const items = item.購買物品摘要 || '';
        const purchaseContent = summary
            ? `${summary}<br><span style="color:#666;font-size:12px;">${items}</span>`
            : items;

        tableHtml += `<tr>
      <td>${item.日期 || ''}</td>
      <td>${item.時間 || ''}</td>
      <td>${item.公司名稱 || ''}</td>
      <td>${purchaseContent}</td>
      <td>${item.支付方式 || ''}</td>
      <td>${item.總金額 || ''}</td>
    </tr>`;
    }
    tableHtml += '</tbody></table>';
    document.getElementById('structuredTable').innerHTML = tableHtml;
}

// 顯示錯誤訊息
export function showError(message, rawData = null) {
    const tableDiv = document.getElementById('structuredTable');
    tableDiv.innerHTML = `<p style="color: red;">提取失敗：${message}</p>`;
    if (rawData) {
        tableDiv.innerHTML += `<pre style="background:#f0f0f0;padding:10px;overflow:auto;white-space:pre-wrap;">${rawData}</pre>`;
    }
}

// 顯示/隱藏複製按鈕容器
export function toggleCopyButton(show) {
    const actionsContainer = document.querySelector('.table-actions');
    if (actionsContainer) {
        actionsContainer.style.display = show ? 'flex' : 'none';
    }
}

// 清空 UI 元素
export function clearUI() {
    document.getElementById('ocrButtonContainer').innerHTML = '';
    document.getElementById('structuredTable').innerHTML = '';
    const actionsContainer = document.querySelector('.table-actions');
    if (actionsContainer) {
        actionsContainer.style.display = 'none';
    }
    document.getElementById('globalStatus').innerText = '';
}
