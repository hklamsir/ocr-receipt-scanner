// 工具函數模組

// 壓縮圖片（更激進的壓縮以適應 localStorage 限制）
// config: { quality: 1-100, maxSizeKb: number }
export function compressImage(file, config = {}) {
    // 預設值（向後相容）
    const quality = (config.quality || 60) / 100; // 轉換為 0-1 範圍
    const maxSizeBytes = (config.maxSizeKb || 200) * 1024; // 轉換為 bytes
    const minQuality = 0.3; // 最低品質

    return new Promise(resolve => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let w = img.width, h = img.height;

                // 更積極的尺寸壓縮：最大寬度 1200px（原 2000px）
                const MAX_W = 1200;

                if (w > MAX_W) {
                    h *= MAX_W / w;
                    w = MAX_W;
                }

                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);

                // 動態調整品質以符合大小限制
                let q = quality; // 使用設定的起始品質
                let data;
                do {
                    data = canvas.toDataURL('image/jpeg', q);
                    q -= 0.05;
                } while (data.length > maxSizeBytes && q > minQuality);

                resolve({ name: file.name, dataUrl: data });
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// 驗證圖片檔案
export function validateImageFile(file) {
    return file.type.startsWith('image/');
}

// 排序表格資料
export function sortTableData(data, colIndex, direction) {
    return [...data].sort((a, b) => {
        let A = Object.values(a)[colIndex] || '';
        let B = Object.values(b)[colIndex] || '';

        if (colIndex === 0 && A && B) { // 日期
            return direction === 'asc' ? new Date(A) - new Date(B) : new Date(B) - new Date(A);
        }
        if (colIndex === 5) { // 總金額（索引保持 5）
            A = parseFloat(A) || 0;
            B = parseFloat(B) || 0;
            return direction === 'asc' ? A - B : B - A;
        }

        return direction === 'asc' ? A.localeCompare(B, 'zh-HK') : B.localeCompare(A, 'zh-HK');
    });
}

// 轉換為 TSV 格式（用於 Excel 複製）
export function convertToTSV(data) {
    const headers = ['日期', '時間', '公司名稱', '購買物品摘要', '支付方式', '總金額', '總結'];
    let tsv = headers.join('\t') + '\n';

    data.forEach(row => {
        tsv += [
            row.日期 || '',
            row.時間 || '',
            row.公司名稱 || '',
            row.購買物品摘要 || '',
            row.支付方式 || '',
            row.總金額 || '',
            row.總結 || ''
        ].join('\t') + '\n';
    });

    return tsv;
}

// 轉義 HTML：同時防範文字內容與屬性上下文的 XSS（涵蓋 & < > " '）
export function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// 標籤顏色白名單：僅允許 #rgb / #rrggbb，其餘回傳預設色，避免注入任意 CSS/屬性
export function safeColor(color) {
    if (typeof color === 'string' && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(color)) {
        return color;
    }
    return '#94a3b8';
}
