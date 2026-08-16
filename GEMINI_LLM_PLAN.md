# ocr_ds 接入 Gemini LLM 計劃書（v2 修訂）

> 狀態：**已實施（2026-08-16）** — 程式碼已寫入 workspace，待部署時匯入 SQL 並填入 Gemini API Key。
> 目標系統：`ocr_ds`（當前 workspace）
> 參考檔：`live-translator/build/stt/api/{config_sample.php, translate.php}`

---

## 1. 目標

在 ocr_ds 智慧 OCR 流程中增加 Gemini 選項：

- **文字 LLM 提供者**由 admin 用開關選 **deepseek 或 gemini**（兩者皆先經 OCR.space 出文字，再出結構化 JSON）；**deepseek 與 gemini 之間沒有降級**。
- 可獨立開啟 **Gemini 視覺（端到端）** 模式；**開啟後直接停用 DeepSeek**（視覺模式不經 OCR.space / DeepSeek，Gemini 直接讀圖出 JSON）。
- **Gemini 呼叫採用模型級降級**（移植參考檔的 `FALLBACK_MODELS` 優先清單 + 超時/冷卻/退避狀態機），套用於所有 Gemini 呼叫（文字與視覺）。

---

## 2. 現狀摘要（對齊用）

- OCR 流程：`js/modules/ocr.js` 的 `processImagesParallel` → `ocr_proxy.php`（OCR.space 出文字）→ `extractStructuredData` → `proxy.php`（DeepSeek `deepseek-chat` 出 JSON）。
- 設定存於 `system_settings`（key-value）；`api/admin/settings.php` 提供 GET/POST。
- `includes/config.php` 已定義 `DEEPSEEK_API_KEY`、`OCR_API_KEY`、`OCR_ENGINE`。
- 參考檔 `translate.php` 提供 Gemini `generateContent` + `x-goog-api-key` 呼叫，以及 `FALLBACK_MODELS` 模型級降級狀態機（讀寫 `fallback_state.json`、429 冷卻、指數退避 + 抖動、主力恢復計時）。

---

## 3. 已確認決策（v2 修訂）

1. 目標 = `ocr_ds`；參考檔僅借鏡 Gemini 呼叫與 fallback 機制。
2. 文字 LLM 提供者：admin 開關選 **deepseek | gemini**，**無** deepseek↔gemini 降級。
3. Gemini 視覺：獨立開關；**開啟後停用 DeepSeek**（視覺端到端，不經 OCR.space / DeepSeek）。
4. 「降級」定義 = **Gemini 模型級降級**，直接移植參考檔：
   - `FALLBACK_MODELS = ['gemini-3.5-flash-lite','gemini-3.1-flash-lite','gemini-3.5-flash','gemini-3.6-flash']`（末項由 preview 的 gemini-3-flash 改為 GA 的 gemini-3.6-flash，2026/7/21）
   - `FALLBACK_TIMEOUTS = [15, 8, 4, 1]`（秒）
   - `FALLBACK_TOTAL_TIMEOUT = 28`、`FALLBACK_COOLDOWN = 120`、`FALLBACK_COOLDOWN_RPD = 3600`、`FALLBACK_MAX_RETRIES = 4`、`FALLBACK_BASE_BACKOFF = 2`、`FALLBACK_RESTORE_INTERVAL = 300`
   - 套用於所有 Gemini 呼叫（文字與視覺）。

---

## 4. 後台設定

- `gemini_api_key`（密碼框，API 金鑰面板）
- `llm_provider`：文字 LLM 提供者，`deepseek` | `gemini`（一般設定，下拉/開關）
- `gemini_vision_enabled`：`0` | `1`（一般設定，開關）。開啟時顯示提示「啟用後直接使用 Gemini 視覺端到端處理，DeepSeek 自動停用」，並停用/隱藏 `llm_provider` 切換（值仍保留，但實際不走文字管線）。

### 處理模式對照
| vision | provider | 實際管線 |
|--------|----------|----------|
| 關 | deepseek | OCR.space + DeepSeek（現有，不變） |
| 關 | gemini | OCR.space + Gemini 文字（FALLBACK_MODELS 降級） |
| 開 | （忽略） | Gemini 視覺端到端（FALLBACK_MODELS 降級；DeepSeek 停用） |

---

## 5. 改動檔案清單

### 後端 PHP
- **`includes/config.php`**
  - 新增 `GEMINI_API_KEY`、`LLM_PROVIDER`、`GEMINI_VISION_ENABLED` 常數（從 `system_settings` 讀取）。
  - `FALLBACK_*` 常數與 `FALLBACK_MODELS` 清單定義於 `includes/llm_gemini.php`。
- **`includes/llm_gemini.php`**（新增）
  - 移植參考檔：`build_gemini_url()`、`read_fallback_state()`、`write_fallback_state()`、`get_available_model_index()`、`is_rpd_error()`、`backoff_sleep()`、`call_gemini_api()`，以及 `FALLBACK_*` 常數 / `FALLBACK_MODELS` / `FALLBACK_TIMEOUTS` / `FALLBACK_STATE_FILE`。
  - 高階封裝：
    - `generateGeminiText(string $systemPrompt, string $userPrompt): string` — 跑 FALLBACK_MODELS 迴圈，回傳解析文字。
    - `generateGeminiVision(string $promptText, string $mimeType, string $base64): string` — 同上，附 `inline_data`。
  - 狀態檔 `gemini_fallback_state.json` 置於可寫目錄（同參考檔 flock 防並發）。
- **`proxy.php`**（重構）
  - 讀 `LLM_PROVIDER`：deepseek → 現有 `callDeepSeek()`；gemini → `generateGeminiText()`。
  - 失敗直接報錯（**無跨 LLM 降級**）。
  - 回傳契約統一為 `{ success, result, engine }`。
- **`vision_proxy.php`**（新增）
  - 收圖片 base64 + mime，呼叫 `generateGeminiVision()`。
  - 回傳 `{ success, result, engine: 'gemini-vision' }`。
  - 含 `auth_check.php` / `security.php`（Referer 校驗）/ `checkRateLimit` / `db.php` 配額檢查（與現有端點一致）。

### 資料庫 / SQL
- `database.sql` 與 `sql/admin_features.sql` 的 `INSERT IGNORE ... system_settings` 增加：
  - `('gemini_api_key', '', 'Gemini API 金鑰')`
  - `('llm_provider', 'deepseek', '文字 LLM 提供者 (deepseek|gemini)')`
  - `('gemini_vision_enabled', '0', '啟用 Gemini 視覺端到端 (0=關, 1=開)')`
- 已部署環境另附 `INSERT ... ON DUPLICATE KEY UPDATE` 遷移 SQL。

### 前端
- **`api/get_config.php`**：回傳 `geminiVisionEnabled`（供分支），可附 `llmProvider`。
- **`js/app.js`**：依 `geminiVisionEnabled` 分支——開 → 跳過 `processImagesParallel` / `extractStructuredData`，改 `extractWithVision`；關 → 現有流程（後端依 `llm_provider` 決定 deepseek/gemini）。
- **`js/modules/ocr.js`**：新增 `extractWithVision(image, mimeType)`，POST 圖片至 `vision_proxy.php`，回傳與 `extractStructuredData` 相同契約；`extractStructuredData` 參數化 endpoint（相容）。

### 後台 UI
- **`js/admin.js`**：
  - `API_KEY_SETTINGS` 加 `'gemini_api_key'`（自動渲染密碼框 + 眼睛切換）。
  - `SETTING_LABELS` 加 `gemini_api_key` / `llm_provider` / `gemini_vision_enabled`。
  - 渲染 `llm_provider` 為下拉（deepseek|gemini）、`gemini_vision_enabled` 為開關（仿 `ocr_engine` 特例處理）。

---

## 6. Gemini 呼叫細節（移植參考檔）

### 6.1 文字（`generateGeminiText`）
```
POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
Headers: Content-Type: application/json
         x-goog-api-key: {GEMINI_API_KEY}
Body:
{
  "systemInstruction": { "parts": [{ "text": "你是一個嚴格遵守指示的助手，只輸出純粹的 JSON。" }] },
  "contents": [{ "role": "user", "parts": [{ "text": "<現有收據解析 prompt + OCR 文字>" }] }],
  "generationConfig": { "temperature": 0.3, "maxOutputTokens": 2048, "responseMimeType": "application/json" }
}
```
`model` 取自 `FALLBACK_MODELS` 當前可用項。回應取 `candidates[0].content.parts[0].text`，正則擷取 JSON 陣列（與 DeepSeek 相同解析）。

### 6.2 視覺（`generateGeminiVision`）
```
{
  "contents": [{ "parts": [
    { "text": "<視覺版收據解析 prompt：直接讀圖、輸出相同 JSON Schema>" },
    { "inline_data": { "mime_type": "{mimeType}", "data": "{base64}" } }
  ] }],
  "generationConfig": { "temperature": 0.3, "maxOutputTokens": 2048, "responseMimeType": "application/json" }
}
```
呼應參考檔 `translate.php` 的 `inline_data` 多模態寫法。

### 6.3 模型級降級（FALLBACK_MODELS，移植 `translate.php`）
- 依 `FALLBACK_MODELS` 順序選可用模型；每層超時 `FALLBACK_TIMEOUTS`；總上限 `FALLBACK_TOTAL_TIMEOUT`。
- 429 → 依是否 RPD 套用 `FALLBACK_COOLDOWN` / `FALLBACK_COOLDOWN_RPD` 冷卻；cURL 錯誤 / 5xx → 短冷卻後試下一個。
- 指數退避 + 隨機抖動（避免驚羣）；主力模型恢復計時（`FALLBACK_RESTORE_INTERVAL`）。
- 狀態寫 `gemini_fallback_state.json`（flock 防並發覆寫）。

---

## 7. 安全性（沿用既有機制）

- `proxy.php` 與 `vision_proxy.php` 皆 `require` `auth_check.php`、`security.php`（Referer 校驗）、`checkRateLimit`、`db.php` 配額檢查。
- API Key 僅存於 `system_settings`，不在前端暴露；`get_config.php` 不回傳任何 key。
- Gemini 回應 JSON 解析前先 `json_decode` 驗證，避免輸出注入。

---

## 8. 測試計畫

1. **單元**：三種設定組合下 `proxy.php` / `vision_proxy.php` 回傳契約。
2. **Gemini 降級**：暫時把主力模型名改成無效，確認自動跳到備援模型仍出 JSON。
3. **前端**：三模式上傳收據；vision 跳過 OCR；文字模式依 provider 走 deepseek/gemini。
4. **後台**：`gemini_api_key` 密碼框、`llm_provider` 下拉、`gemini_vision_enabled` 開關可存可取；`get_config.php` 不含金鑰。

---

## 9. 已確認細節與部署注意

- **模型名稱（已確認為現有模型，非預占位）**：原樣移植你貼的 `FALLBACK_MODELS`（gemini-3.5-flash-lite …）。經官方 changelog 查證，四個模型皆為目前可用的現有模型（發布日期見下方查證記錄），**無須部署前替換**。降級機制本身亦不受名稱影響。

### 9.1 模型名稱查證記錄（官方來源）

> 來源：Gemini API 版本資訊（changelog）— https://ai.google.dev/gemini-api/docs/changelog?hl=zh-tw
> 查證日期：2026-08-16

| 模型 ID | 狀態 | 官方發布日期 / 備註 |
|---------|------|----------------------|
| `gemini-3.5-flash-lite` | GA（正式版） | 2026/7/21 發布 |
| `gemini-3.1-flash-lite` | GA（正式版） | 2026/5/7 發布 |
| `gemini-3.5-flash` | GA（正式版） | 2026/5/19 發布，為 `gemini-flash-latest` 幕後推手 |
| `gemini-3-flash` | Preview（預覽版） | 2025/12/17 推出 `gemini-3-flash-preview` |

結論：四個均為現有模型。**拍板決定**：末項由 preview 的 `gemini-3-flash` 改為 GA 的 `gemini-3.6-flash`（2026/7/21），使 `FALLBACK_MODELS` 全為穩定版。
- **視覺模式降級（已確認）**：視覺與文字共用同一份 `FALLBACK_MODELS` 降級清單。
- **FALLBACK_TOTAL_TIMEOUT 與 InfinityFree 限制（部署注意，非阻塞）**：參考檔設 28s。ocr_ds 文字模式先跑 OCR.space（現有 `CURLOPT_TIMEOUT=90s`）再跑 Gemini，兩者相加可能逼近主機 PHP 執行上限。實作時會確認 `PHP max_execution_time`，必要時下調 OCR 超時或 Gemini 總時限；此為上線前調校項，不影響邏輯。

---

請確認上述方案與第 9 節的細節，或指出需要調整之處。拍板後我會依此實作。
