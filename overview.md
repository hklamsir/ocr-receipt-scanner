# 修復摘要：Gemini Vision 單據圖片未儲存

## 問題
用戶在設定中啟用 Gemini Vision 後，單據能成功辨識，但儲存後單據圖片遺失。

## 可能根因
1. **前端陣列索引錯位**：Vision 模式的結構化結果與 `AppState.images` 陣列索引依賴可能因多張圖片結果合併而錯位。
2. **圖片大小設定異常**：若管理員將 `max_image_size_kb` 設為 0 或空值，`save_receipts.php` 的圖片上限會變成 500 bytes，導致所有圖片被拒絕儲存。

## 已實作修正

### 前端 (`js/app.js` + `js/modules/ocr.js`)
- Vision 成功結果現在會帶回來源圖片的 `dataUrl`。
- `processWithVision()` 將 `_imageDataUrl` 直接綁定到每筆收據物件。
- 儲存時優先使用收據物件自身的 `_imageDataUrl`，再 fallback 到 `images[index].dataUrl`。
- 記錄最後使用引擎：Vision = `3`，傳統 OCR = 實際引擎值。

### 後端 (`api/save_receipts.php`)
- 防禦 `MAX_IMAGE_SIZE_KB` 設定異常（<= 0 時自動 fallback 為 200KB）。
- 增加詳細日誌：圖片缺失、過大、MIME 無效、儲存失敗皆會記錄到 `tmp/error.log`。

### 管理設定 (`api/admin/settings.php`)
- 儲存設定時驗證 `max_image_size_kb` 必須在 50–10240 KB 之間。
- 驗證 `image_quality` 必須在 1–100 之間，避免誤設導致圖片無法儲存。

## 驗證
- JavaScript 語法檢查已通過（`node --check`）。

## 後續步驟
1. 部署到伺服器。
2. 再次使用 Gemini Vision 辨識並儲存單據。
3. 檢查 `tmp/error.log` 是否仍有圖片相關錯誤訊息。
