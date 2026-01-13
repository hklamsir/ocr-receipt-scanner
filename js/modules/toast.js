/**
 * Toast 通知與 Dialog 對話框模組
 * 用於替代原生 alert(), confirm(), prompt()
 */

// 確保 Toast 容器存在
function ensureToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

// Toast 圖標映射
const TOAST_ICONS = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
};

/**
 * Toast 通知類別
 */
export const Toast = {
    /**
     * 顯示通知
     * @param {string} message - 訊息內容
     * @param {string} type - 類型：success, error, warning, info
     * @param {number} duration - 顯示時間（毫秒），0 表示不自動關閉
     */
    show(message, type = 'info', duration = 3000) {
        const container = ensureToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
      <span class="toast-icon">${TOAST_ICONS[type] || TOAST_ICONS.info}</span>
      <span class="toast-message">${message}</span>
      <button class="toast-close" aria-label="關閉">×</button>
    `;

        // 關閉按鈕事件
        toast.querySelector('.toast-close').onclick = () => this._remove(toast);

        container.appendChild(toast);

        // 自動關閉
        if (duration > 0) {
            setTimeout(() => this._remove(toast), duration);
        }

        return toast;
    },

    _remove(toast) {
        if (!toast || toast.classList.contains('toast-exit')) return;

        toast.classList.add('toast-exit');
        setTimeout(() => toast.remove(), 300);
    },

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    },

    error(message, duration = 4000) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration = 3500) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    }
};

/**
 * Dialog 對話框類別
 */
export const Dialog = {
    /**
     * 確認對話框（替代 confirm）
     * @param {string} message - 確認訊息
     * @param {Object} options - 選項
     * @returns {Promise<boolean>} - 是否確認
     */
    confirm(message, options = {}) {
        const {
            title = '確認',
            confirmText = '確定',
            cancelText = '取消',
            danger = false
        } = options;

        return new Promise(resolve => {
            const overlay = this._createOverlay();

            overlay.innerHTML = `
        <div class="dialog">
          <div class="dialog-header">${title}</div>
          <div class="dialog-body">${message}</div>
          <div class="dialog-footer">
            <button class="btn btn-secondary dialog-cancel">${cancelText}</button>
            <button class="btn ${danger ? 'btn-danger' : 'btn-primary'} dialog-confirm">${confirmText}</button>
          </div>
        </div>
      `;

            const confirmBtn = overlay.querySelector('.dialog-confirm');
            const cancelBtn = overlay.querySelector('.dialog-cancel');

            const close = (result) => {
                this._removeOverlay(overlay);
                resolve(result);
            };

            confirmBtn.onclick = () => close(true);
            cancelBtn.onclick = () => close(false);
            overlay.onclick = (e) => {
                if (e.target === overlay) close(false);
            };

            // ESC 鍵關閉
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', handleEsc);
                    close(false);
                }
            };
            document.addEventListener('keydown', handleEsc);

            document.body.appendChild(overlay);
            confirmBtn.focus();
        });
    },

    /**
     * 輸入對話框（替代 prompt）
     * @param {string} message - 提示訊息
     * @param {Object} options - 選項
     * @returns {Promise<string|null>} - 輸入值或 null
     */
    prompt(message, options = {}) {
        const {
            title = '請輸入',
            placeholder = '',
            defaultValue = '',
            confirmText = '確定',
            cancelText = '取消',
            inputType = 'text'
        } = options;

        return new Promise(resolve => {
            const overlay = this._createOverlay();

            overlay.innerHTML = `
        <div class="dialog">
          <div class="dialog-header">${title}</div>
          <div class="dialog-body">
            ${message}
            <input type="${inputType}" class="dialog-input" placeholder="${placeholder}" value="${defaultValue}">
          </div>
          <div class="dialog-footer">
            <button class="btn btn-secondary dialog-cancel">${cancelText}</button>
            <button class="btn btn-primary dialog-confirm">${confirmText}</button>
          </div>
        </div>
      `;

            const input = overlay.querySelector('.dialog-input');
            const confirmBtn = overlay.querySelector('.dialog-confirm');
            const cancelBtn = overlay.querySelector('.dialog-cancel');

            const close = (value) => {
                this._removeOverlay(overlay);
                resolve(value);
            };

            confirmBtn.onclick = () => close(input.value || null);
            cancelBtn.onclick = () => close(null);
            overlay.onclick = (e) => {
                if (e.target === overlay) close(null);
            };

            // Enter 鍵確認
            input.onkeydown = (e) => {
                if (e.key === 'Enter') close(input.value || null);
                if (e.key === 'Escape') close(null);
            };

            document.body.appendChild(overlay);
            input.focus();
            input.select();
        });
    },

    /**
     * 訊息對話框（替代 alert）
     * @param {string} message - 訊息內容
     * @param {Object} options - 選項
     * @returns {Promise<void>}
     */
    alert(message, options = {}) {
        const {
            title = '提示',
            confirmText = '確定'
        } = options;

        return new Promise(resolve => {
            const overlay = this._createOverlay();

            overlay.innerHTML = `
        <div class="dialog">
          <div class="dialog-header">${title}</div>
          <div class="dialog-body">${message}</div>
          <div class="dialog-footer">
            <button class="btn btn-primary dialog-confirm">${confirmText}</button>
          </div>
        </div>
      `;

            const confirmBtn = overlay.querySelector('.dialog-confirm');

            const close = () => {
                this._removeOverlay(overlay);
                resolve();
            };

            confirmBtn.onclick = close;
            overlay.onclick = (e) => {
                if (e.target === overlay) close();
            };

            // ESC 或 Enter 鍵關閉
            const handleKey = (e) => {
                if (e.key === 'Escape' || e.key === 'Enter') {
                    document.removeEventListener('keydown', handleKey);
                    close();
                }
            };
            document.addEventListener('keydown', handleKey);

            document.body.appendChild(overlay);
            confirmBtn.focus();
        });
    },

    _createOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'dialog-overlay';
        return overlay;
    },

    _removeOverlay(overlay) {
        if (!overlay) return;
        overlay.classList.add('dialog-exit');
        setTimeout(() => overlay.remove(), 200);
    }
};

// 全域註冊（供非模組腳本使用）
if (typeof window !== 'undefined') {
    window.Toast = Toast;
    window.Dialog = Dialog;
}
