/**
 * validation.js - 資料驗證模組
 * 提供日期、時間、金額等欄位的格式驗證功能
 */

/**
 * 驗證日期格式 (YYYY-MM-DD)
 * @param {string} dateStr - 日期字串
 * @returns {boolean} 是否有效
 */
export function isValidDate(dateStr) {
    if (!dateStr || typeof dateStr !== 'string') return false;
    
    // 檢查格式 YYYY-MM-DD
    const datePattern = /^\d{4}-\d{2}-\d{2}$/;
    if (!datePattern.test(dateStr)) return false;
    
    // 檢查是否為有效日期
    const date = new Date(dateStr);
    const timestamp = date.getTime();
    
    if (isNaN(timestamp)) return false;
    
    // 檢查日期是否在合理範圍內 (1900-2100)
    const year = date.getFullYear();
    if (year < 1900 || year > 2100) return false;
    
    // 驗證月份和日期是否符合
    const [y, m, d] = dateStr.split('-').map(Number);
    return date.getFullYear() === y && 
           date.getMonth() === m - 1 && 
           date.getDate() === d;
}

/**
 * 驗證時間格式 (HH:MM:SS 或 HH:MM)
 * @param {string} timeStr - 時間字串
 * @returns {boolean} 是否有效
 */
export function isValidTime(timeStr) {
    if (!timeStr || typeof timeStr !== 'string') return false;
    
    // 支援 HH:MM:SS 或 HH:MM
    const timePattern = /^([0-1]?\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/;
    return timePattern.test(timeStr);
}

/**
 * 驗證金額格式
 * @param {string|number} amount - 金額
 * @returns {boolean} 是否有效
 */
export function isValidAmount(amount) {
    if (amount === null || amount === undefined || amount === '') return false;
    
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    // 檢查是否為有效數字
    if (isNaN(numAmount)) return false;
    
    // 檢查是否為合理範圍 (0-1000000)
    if (numAmount < 0 || numAmount > 1000000) return false;
    
    return true;
}

/**
 * 驗證收據資料
 * @param {Object} receipt - 收據物件，包含 日期、時間、總金額 等欄位
 * @returns {Array} 錯誤陣列，每個錯誤包含 field 和 message
 */
export function validateReceipt(receipt) {
    const errors = [];
    
    // 驗證日期
    if (receipt.日期 && !isValidDate(receipt.日期)) {
        errors.push({
            field: '日期',
            message: '日期格式錯誤，應為 YYYY-MM-DD',
            value: receipt.日期,
            severity: 'error'
        });
    }
    
    // 驗證時間
    if (receipt.時間 && !isValidTime(receipt.時間)) {
        errors.push({
            field: '時間',
            message: '時間格式錯誤，應為 HH:MM:SS 或 HH:MM',
            value: receipt.時間,
            severity: 'error'
        });
    }
    
    // 驗證金額
    if (receipt.總金額 && !isValidAmount(receipt.總金額)) {
        errors.push({
            field: '總金額',
            message: '金額格式錯誤或超出合理範圍',
            value: receipt.總金額,
            severity: 'error'
        });
    }
    
    // 檢查日期是否為未來日期（警告）
    if (receipt.日期 && isValidDate(receipt.日期)) {
        // 使用字串比較避免時區問題
        const receiptDateStr = receipt.日期; // YYYY-MM-DD
        const now = new Date();
        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        
        // 只有當收據日期 > 今天時才警告（今天的日期不警告）
        if (receiptDateStr > todayStr) {
            errors.push({
                field: '日期',
                message: '收據日期為未來日期，請確認是否正確',
                value: receipt.日期,
                severity: 'warning'
            });
        }
    }
    
    // 檢查金額是否為零（警告）
    if (receipt.總金額 && parseFloat(receipt.總金額) === 0) {
        errors.push({
            field: '總金額',
            message: '總金額為 0，請確認是否正確',
            value: receipt.總金額,
            severity: 'warning'
        });
    }
    
    return errors;
}

/**
 * 嘗試自動修正日期格式
 * @param {string} dateStr - 原始日期字串
 * @returns {Object} { success: boolean, value: string, message: string }
 */
export function autoFixDate(dateStr) {
    if (!dateStr) return { success: false, value: '', message: '日期為空' };
    
    // 已經是正確格式
    if (isValidDate(dateStr)) {
        return { success: true, value: dateStr, message: '日期格式正確' };
    }
    
    // 嘗試各種格式
    const formats = [
        // YYYY/MM/DD -> YYYY-MM-DD
        { pattern: /^(\d{4})\/(\d{1,2})\/(\d{1,2})$/, format: (m) => `${m[1]}-${m[2].padStart(2, '0')}-${m[3].padStart(2, '0')}` },
        // DD/MM/YYYY -> YYYY-MM-DD
        { pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/, format: (m) => `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}` },
        // YYYYMMDD -> YYYY-MM-DD
        { pattern: /^(\d{4})(\d{2})(\d{2})$/, format: (m) => `${m[1]}-${m[2]}-${m[3]}` },
        // DD-MM-YYYY -> YYYY-MM-DD
        { pattern: /^(\d{1,2})-(\d{1,2})-(\d{4})$/, format: (m) => `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}` }
    ];
    
    for (const fmt of formats) {
        const match = dateStr.match(fmt.pattern);
        if (match) {
            const fixed = fmt.format(match);
            if (isValidDate(fixed)) {
                return { success: true, value: fixed, message: '已自動修正日期格式' };
            }
        }
    }
    
    return { success: false, value: dateStr, message: '無法自動修正日期格式' };
}

/**
 * 嘗試自動修正時間格式
 * @param {string} timeStr - 原始時間字串
 * @returns {Object} { success: boolean, value: string, message: string }
 */
export function autoFixTime(timeStr) {
    if (!timeStr) return { success: false, value: '', message: '時間為空' };
    
    // 已經是正確格式
    if (isValidTime(timeStr)) {
        return { success: true, value: timeStr, message: '時間格式正確' };
    }
    
    // 嘗試補齊秒數
    const hmPattern = /^([0-1]?\d|2[0-3]):([0-5]\d)$/;
    const match = timeStr.match(hmPattern);
    if (match) {
        const fixed = `${timeStr}:00`;
        return { success: true, value: fixed, message: '已補齊秒數' };
    }
    
    // 嘗試移除空格
    const trimmed = timeStr.replace(/\s/g, '');
    if (isValidTime(trimmed)) {
        return { success: true, value: trimmed, message: '已移除多餘空格' };
    }
    
    return { success: false, value: timeStr, message: '無法自動修正時間格式' };
}
