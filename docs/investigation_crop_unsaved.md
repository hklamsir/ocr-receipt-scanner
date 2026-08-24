# 調查報告：裁切後 OCR，圖片未能儲存（單據資料可存）

## 現象
- 相機拍攝 → 不裁切 → Gemini Vision OCR → 儲存：**圖片成功存入**
- 相機拍攝 → **裁切後**才 OCR → 儲存：**單據資料存入，但圖片檔案沒有存**

## 根因
**裁切後產生的 dataURL 體積超過後端 `MAX_IMAGE_SIZE_KB`（預設 200KB）限制，後端靜默放棄圖片、只寫單據資料。**

這是一個「前端裁切再編碼時沒有重新壓到大小上限」與「後端超過上限就只丟圖不報錯」共同造成的問題。

## 數據流對照

### 不裁切（成功路徑）
1. 拍照 → `handleFiles` → `compressImage()`（`js/modules/utils.js:5`）
   - 縮放至最大寬 1200px
   - 用 `while (data.length > maxSizeBytes && q > minQuality)` 逐步降質，直到 base64 字串 < `maxImageSizeKb`(200KB)
   - 結果二進位約 ≤ 153KB，**低於後端限制**
2. OCR(Vision) 送 `img.dataUrl`（小圖）
3. 儲存：`item._imageDataUrl` = 小圖 → 後端大小檢查通過 → 圖片存檔 ✅

### 裁切後 OCR（失敗路徑）
1. 拍照 → `compressImage`（小圖，<200KB）
2. 按「裁切」→ `applyCrop()`（`js/app.js:673-705`）：
   - `cropQuality = Math.min((imageQuality||60)/100 + 0.2, 0.95)` → **預設 0.8**（`app.js:691`）
   - `getCroppedCanvas({ maxWidth:4096, maxHeight:4096 })`（`app.js:677`）
   - `canvas.toDataURL('image/jpeg', cropQuality)`（`app.js:692`）
   - **關鍵缺失：重新編碼時用固定較高品質 0.8，且完全沒有再壓到大小上限**
   - 寫回 `images[currentCropIndex].dataUrl = croppedDataUrl`（`app.js:697`，會真正覆寫並傳到 OCR）
3. OCR(Vision) 送 `img.dataUrl`（此時已是裁切後的大圖）→ `r.value.image = img.dataUrl`（`js/modules/ocr.js:149`）→ `item._imageDataUrl` = 大圖（`app.js:319`）
4. 儲存：後端解碼後 `imageSize` 通常 225–500KB+，**超過 `MAX_IMAGE_SIZE_KB*1024+500 ≈ 205KB`**

## 後端「靜默放棄」的關鍵碼
`api/save_receipts.php:150-159`
```php
$maxImageBytes = $configuredMaxKb * 1024 + 500;
if ($imageSize > $maxImageBytes) {
    logError("Image too large for receipt $index: ... - saving receipt without image");
    // 不使用 continue，繼續儲存單據資料（沒有圖片）
}
elseif (!isValidImageMime($imageBytes)) {
    logError("Invalid image MIME type ... - saving receipt without image");
}
else {
    // 只有這裡才真正寫檔
}
```
一旦超過大小（或 MIME 不符），只 `logError` 並**繼續寫單據資料列、`image_filename = null`**，回傳仍是 `success`。所以前端只看到「儲存成功、單據在」，圖片卻不見。

> 註：MIME 不是本次差異點——裁切輸出與壓縮輸出都是 `image/jpeg`，兩者都通過 magic bytes 檢查。**差異點只在「體積」**。

## 為什麼只在「裁切才 OCR」出現（而非「OCR 後才裁切」）
Vision 模式下 `item._imageDataUrl` 是在 **OCR 當下**綁定的（`app.js:319` 取 `r.value.image` = 當時的 `img.dataUrl`）：
- **裁切 → 才 OCR**：OCR 時 `img.dataUrl` 已是裁切後的大圖 → `_imageDataUrl` 大 → 後端丟圖 ✗
- **OCR → 才裁切**：OCR 時 `img.dataUrl` 仍是原始小圖 → `_imageDataUrl` 小 → 後端存圖 ✓（即使之後預覽被換成大圖，存檔用的仍是 OCR 當下那張小圖）

這完全吻合你回報的「裁切了才 OCR 才出問題」。

## 設定值確認
- `includes/config.php:36`：`MAX_IMAGE_SIZE_KB` 預設 200（可由 DB `system_settings.max_image_size_kb` 覆寫）
- 後端實際上限：`200*1024 + 500 ≈ 205,300 bytes`（`save_receipts.php:150`）
- 前端 `cropQuality` 預設 0.8（`app.js:691`）

## 建議修復方向（未實作，僅供參考）
1. **前端（治本）**：在 `applyCrop()` 重新編碼後，套用與 `compressImage` 相同的大小預算邏輯——若 `croppedDataUrl` 超過 `maxImageSizeKb`，逐步降質（或縮小尺寸）直到符合，再寫回 `dataUrl`。可抽取 `compressImage` 的核心邏輯複用。
2. **後端（防禦）**：`save_receipts.php` 超過大小時，除了 `logError`，可選擇「伺服器端重新壓縮到上限」或「回傳明確警告」，避免使用者以為圖片已存。
3. 兩者同做最穩：前端確保送出體積可控，後端在極端情況下仍能自救或明確提示。

---
調查僅讀碼、未改動任何程式。所有結論均基於上述檔案與行號。
