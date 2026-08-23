# 📃 收據浣熊

<p align="center">
  <img src="images/logo.svg" alt="收據浣熊 Logo" width="120">
</p>

<p align="center">
  <strong>收據浣熊 ReceiptsRaccoon — 一站式收據 / 發票 OCR 辨識與管理系統</strong><br>
  支援批次上傳、AI 智能辨識、標籤分類、Excel / PDF 匯出、多用戶隔離
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 7.4+">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black" alt="JavaScript ES6+">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="MIT License">
</p>

---

## ✨ 功能特色

| 功能 | 說明 |
|------|------|
| 📸 **批次上傳** | 一次上傳多達 20 張收據圖片（設定值 `max_files_per_upload`），支援相機直接拍照 |
| 🤖 **智慧 OCR** | 整合 OCR.space 出文字 + AI 結構化（自動提取日期、金額、商家並生成摘要） |
| 🧠 **可切換 AI 引擎** | 管理後台可選 **DeepSeek** 或 **Gemini** 文字模型；亦可另開 **Gemini 視覺**端到端模式（直接讀圖出 JSON，跳過 OCR.space） |
| 🏷️ **標籤系統** | 自訂標籤分類，支援顏色管理與批次操作 |
| 📊 **資料匯出** | 一鍵匯出 Excel 或 PDF 報表，支援高度客製化 PDF 模板 |
| 👥 **多用戶管理** | 用戶資料隔離，管理員後台可監控系統狀態與管理用戶 |
| 🔒 **安全設計** | 完整的 CSRF 保護、SQL 注入防護、登入嘗試限制 |
| 📱 **響應式設計** | 完美適配桌面與手機裝置，操作體驗流暢 |

---

## 🚀 快速開始

### 環境需求

- PHP 7.4 或更高版本（需 PDO 擴展）
- MySQL 5.7 或更高版本
- Web 伺服器（Apache / Nginx / InfinityFree 等）

### 安裝步驟

#### 1. 取得專案

```bash
git clone <你的倉庫網址>
cd <專案資料夾>
```

#### 2. 設定資料庫

在 MySQL 執行根目錄嘅 `database.sql`，會自動建立所有資料表並匯入預設設定（含 `system_settings` 中的 API Key 欄位）：

```sql
source database.sql;
```

#### 3. 設定資料庫連線

複製設定範本並填入實際資料庫連線資訊：

```bash
cp config/config.example.php config/secret.php
```

編輯 `config/secret.php`（**只含資料庫連線**，API Key 不在此檔）：

```php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => '你的資料庫名稱',
    'db_user' => '你的資料庫使用者',
    'db_pass' => '你的資料庫密碼',
];
```

> 💡 **API Key 喺邊？** OCR.space、DeepSeek、Gemini 嘅 API Key，以及 LLM 提供者 / 視覺開關，都儲存在資料庫嘅 `system_settings` 表，請於登入後到「設定」頁填寫（見下方步驟 5），**唔係**寫入 `secret.php`。

#### 4. 設定目錄權限

確保網頁伺服器對以下目錄有寫入權限：

```bash
chmod 755 receipts/
chmod 755 tmp/
```

#### 5. 登入並填寫 API Key

- **管理員帳號**：`admin`
- **預設密碼**：`admin123`

登入後到「設定」頁面：

- **API 金鑰**：填寫 `OCR.space API Key`、`DeepSeek API Key`、`Gemini API Key`
- **LLM 提供者**：選 `deepseek` 或 `gemini`（文字結構化模型）
- **Gemini 視覺**：可開啟端到端視覺模式（開啟後 DeepSeek 自動停用）

> ⚠️ **重要安全提醒（務必執行，否則系統極易被入侵）**：
> 1. **改密碼**：首次登入後，請立即至「設定」頁修改密碼。預設密碼 `admin123` 已公開於本檔，任何人都能直接登入。
> 2. **刪 setup.php**：改完密碼後，**請立刻從伺服器刪除 `config/setup.php`**！此檔會將 `admin` 密碼重設回 `admin123`，若不刪除，任何人重新瀏覽該檔即可奪回管理員權限、重置你的密碼。
> 3. 部署清單：① 瀏覽 `config/setup.php` 建立 admin 帳號 → ② 登入改密碼 → ③ 刪除 `config/setup.php`。三步缺一不可。

---

## 🧠 AI 引擎模式說明

系統提供三種處理管線，由「Gemini 視覺」開關與「LLM 提供者」共同決定：

| Gemini 視覺 | LLM 提供者 | 實際管線 |
|-------------|------------|----------|
| 關 | `deepseek`（預設） | OCR.space 出文字 → DeepSeek 出結構化 JSON |
| 關 | `gemini` | OCR.space 出文字 → Gemini 文字模型出 JSON |
| 開 | （忽略） | Gemini 視覺端到端：直接讀圖出 JSON（DeepSeek 停用） |

- **Gemini 模型級自動降級**：所有 Gemini 呼叫（文字與視覺）共用 `FALLBACK_MODELS` 優先清單，遇逾時 / 429 / 5xx 自動跳備援模型並有冷卻與指數退避，提升可用性。
- **無跨 LLM 降級**：選 `deepseek` 與 `gemini` 之間不互相降級，失敗直接報錯。

---

## 🔧 系統管理

管理員登入後可進入「管理後台」(`admin.php`) 進行以下操作：

- **系統監控**：檢視每日 OCR 請求統計、儲存空間使用量。
- **用戶管理**：查看用戶列表、停用違規帳號、設定用戶配額。
- **系統設定**：調整全域參數（上傳限制、圖片壓縮品質、LLM 提供者、Gemini 視覺開關等）。
- **公告管理**：發布系統公告給所有用戶。

---

## 📁 專案結構

```
ocr_ds/
├── api/                    # RESTful API 端點 (處理 AJAX 請求)
├── config/                 # 系統設定檔 (secret.php 僅存 DB 連線；setup.php 為一次性初始化腳本，用完必刪)
├── css/                    # 樣式表 (包含 Design System)
├── includes/               # PHP 共用模組 (Auth, DB, config, llm_gemini, TCPDF)
├── js/                     # 前端 JavaScript 邏輯
├── sql/                    # 歷史資料庫遷移腳本 (僅供參考)
├── receipts/               # 用戶上傳的收據圖片 (需寫入權限)
├── images/                 # 圖示與 Logo
├── database.sql            # 完整資料庫初始化腳本
├── index.php               # 首頁 (上傳與 OCR 處理)
├── receipts.php            # 收據列表 (查詢、編輯、匯出)
├── settings.php            # 系統與個人設定
├── admin.php               # 管理員後台
└── login.php               # 登入頁
```

---

## 🤝 貢獻指南

歡迎提交 Pull Request！請確保：

1. Fork 本專案
2. 建立功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交變更 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟 Pull Request

---

## 📄 授權

本專案採用 MIT 授權條款。

---

## 🙏 致謝

- [OCR.space](https://ocr.space/) - 提供 OCR 辨識服務
- [DeepSeek](https://deepseek.com/) - 提供強大的 AI 語意分析
- [Google Gemini](https://ai.google.dev/) - 提供 Gemini 文字與視覺多模態能力
- [TCPDF](https://tcpdf.org/) - 支援 PDF 報表生成
