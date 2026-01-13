// 狀態管理模組
export const AppState = {
    images: [],
    currentData: [],
    maxFiles: 20,
    userId: null,

    // 取得用戶特定的 localStorage key
    getStorageKey() {
        return this.userId ? `ocr_app_state_user_${this.userId}` : 'ocr_app_state';
    },

    // 初始化設定
    init(config) {
        if (config && config.maxFiles) {
            this.maxFiles = config.maxFiles;
        }
        if (config && config.userId) {
            this.userId = config.userId;

            // 清除舊格式的 localStorage (無用戶區分)
            const oldState = localStorage.getItem('ocr_app_state');
            if (oldState) {
                console.log('清除舊格式的 localStorage 資料');
                localStorage.removeItem('ocr_app_state');
            }
        }
        // 嘗試從 localStorage 恢復資料
        this.loadFromLocalStorage();
    },

    // 新增圖片
    addImage(image) {
        if (this.images.length >= this.maxFiles) {
            throw new Error('已達最大檔案數量');
        }
        this.images.push(image);
        this.saveToLocalStorage();
    },

    // 移除圖片
    removeImage(index) {
        this.images.splice(index, 1);
        this.saveToLocalStorage();
    },

    // 取得所有圖片
    getAllImages() {
        return this.images;
    },

    // 設定結構化資料
    setCurrentData(data) {
        this.currentData = data;
        this.saveToLocalStorage();
    },

    // 取得結構化資料
    getCurrentData() {
        return this.currentData;
    },

    // 清除所有資料
    clearAll() {
        this.images = [];
        this.currentData = [];
        this.saveToLocalStorage();
    },

    // 取得圖片數量
    getImageCount() {
        return this.images.length;
    },

    // 取得最大檔案數
    getMaxFiles() {
        return this.maxFiles;
    },

    // 保存到 localStorage（處理手機熄屏問題）
    saveToLocalStorage() {
        try {
            const state = {
                images: this.images,
                currentData: this.currentData,
                timestamp: Date.now(),
                userId: this.userId
            };
            localStorage.setItem(this.getStorageKey(), JSON.stringify(state));
        } catch (err) {
            console.warn('無法保存狀態到 localStorage:', err);
        }
    },

    // 從 localStorage 載入（頁面重載時自動恢復）
    loadFromLocalStorage() {
        try {
            const saved = localStorage.getItem(this.getStorageKey());
            if (saved) {
                const state = JSON.parse(saved);

                // 檢查用戶 ID 是否匹配（防止用戶切換後看到舊資料）
                if (state.userId && this.userId && state.userId !== this.userId) {
                    console.log('用戶已切換，清除舊資料');
                    localStorage.removeItem(this.getStorageKey());
                    return false;
                }

                // 檢查資料是否過期（24小時）
                const age = Date.now() - state.timestamp;
                if (age < 24 * 60 * 60 * 1000) {
                    this.images = state.images || [];
                    this.currentData = state.currentData || [];
                    console.log('已從 localStorage 恢復資料：', this.images.length, '張圖片');
                    return true;
                } else {
                    // 資料過期，清除
                    localStorage.removeItem(this.getStorageKey());
                }
            }
        } catch (err) {
            console.warn('無法從 localStorage 載入狀態:', err);
        }
        return false;
    }
};
