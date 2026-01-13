# 📃 收據發票 OCR 辨識系統

<p align="center">
  <img src="images/logo.svg" alt="OCR Receipt Scanner Logo" width="120">
</p>

<p align="center">
  <strong>一站式收據管理解決方案</strong><br>
  支援批次上傳、智慧辨識、標籤分類、Excel/PDF 匯出
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
| 📸 **批次上傳** | 一次上傳多達 20 張收據圖片，支援相機直接拍照 |
| 🤖 **智慧 OCR** | 整合 OCR.space + DeepSeek AI，自動提取日期、金額、商家等資訊 |
| 🏷️ **標籤系統** | 自訂標籤分類，批次新增/移除標籤，快速篩選 |
| 📊 **資料匯出** | 一鍵匯出 Excel 或 PDF 報表，支援自訂 PDF 模板 |
| 👥 **多用戶** | 用戶資料隔離，管理員可管理所有用戶 |
| 🔒 **安全設計** | CSRF 保護、SQL 注入防護、密碼 Bcrypt 加密 |
| 📱 **響應式** | 完美適配桌面與手機裝置 |

---

## 🖥️ 系統截圖

> 📝 *建議在此處加入系統截圖展示主要功能*

---

## 🚀 快速開始

### 環境需求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- 支援 PDO 擴展
- Web 伺服器 (Apache/Nginx/InfinityFree)

### 安裝步驟

#### 1. 下載專案

```bash
git clone https://github.com/YOUR_USERNAME/ocr-receipt-scanner.git
cd ocr-receipt-scanner
```

#### 2. 設定資料庫

```sql
-- 建立資料庫
CREATE DATABASE ocr_receipts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 匯入資料表結構
source sql/schema.sql;
source sql/tags_migration.sql;
source sql/create_pdf_templates.sql;
```

#### 3. 設定敏感資料

複製範本並填入實際值：

```bash
cp config/config.example.php config/secret.php
```

編輯 `config/secret.php`：

```php
<?php
return [
    'deepseek_api_key' => '您的 DeepSeek API Key',
    'google_apps_script_url' => '您的 Google Apps Script URL（可選）',
    'ocr_api_key' => '您的 OCR.space API Key',
    'max_files' => 20,
    
    // 資料庫設定
    'db_host' => 'localhost',
    'db_name' => 'ocr_receipts',
    'db_user' => 'your_db_user',
    'db_pass' => 'your_db_password',
];
```

#### 4. 設定目錄權限

```bash
chmod 755 receipts/
chmod 755 tmp/
```

#### 5. 登入系統

- 預設管理員帳號：`admin`
- 預設密碼：`admin123`

> ⚠️ **重要**：首次登入後請立即修改密碼！

---

## 📁 專案結構

```
ocr_ds/
├── api/                    # RESTful API 端點
│   ├── admin/              # 管理員專用 API
│   ├── get_receipts.php    # 取得收據列表
│   ├── save_receipts.php   # 儲存收據
│   ├── export_excel.php    # 匯出 Excel
│   ├── export_pdf.php      # 匯出 PDF
│   └── ...
├── config/
│   ├── config.example.php  # 設定範本
│   └── secret.php          # 實際設定（不上傳）
├── css/                    # 樣式表
│   ├── design-system.css   # 設計系統
│   └── styles.css          # 主樣式
├── includes/               # 共用模組
│   ├── auth_check.php      # 身份驗證
│   ├── security.php        # 安全機制
│   ├── api_response.php    # API 回應格式
│   └── tcppdf/             # PDF 生成庫
├── js/                     # 前端 JavaScript
│   ├── app.js              # 主頁面邏輯
│   ├── receipts.js         # 收據管理邏輯
│   └── modules/            # 模組化功能
├── sql/                    # 資料庫腳本
│   ├── schema.sql          # 主結構
│   └── *.sql               # 遷移腳本
├── receipts/               # 用戶上傳圖片（不上傳）
├── index.php               # 首頁（上傳 & OCR）
├── receipts.php            # 收據管理
├── settings.php            # 設定頁面
├── admin.php               # 管理員頁面
└── login.php               # 登入頁面
```

---

## 🔧 API 文件

### 取得收據列表

```http
GET /api/get_receipts.php
```

**查詢參數**：
- `search` - 關鍵字搜尋
- `date_from` / `date_to` - 日期範圍
- `tags` - 標籤 ID（逗號分隔）

### 儲存收據

```http
POST /api/save_receipts.php
Content-Type: application/json

{
  "results": [...],
  "tag_ids": [1, 2, 3]
}
```

---

## 🛡️ 安全性

本系統實作了多層安全機制：

- ✅ **SQL 注入防護** - 使用 PDO Prepared Statements
- ✅ **XSS 防護** - 輸出轉義與 CSP 標頭
- ✅ **CSRF 防護** - Token 驗證機制
- ✅ **密碼安全** - Bcrypt 雜湊演算法
- ✅ **檔案上傳** - MIME Type 與 Magic Bytes 檢查

---

## 📝 待辦事項

- [ ] 實作分頁功能（提升大量資料效能）
- [ ] 深色模式支援
- [ ] PWA 離線功能
- [ ] 消費統計報表
- [ ] 多語言支援

---

## 🤝 貢獻

歡迎提交 Pull Request！請確保：

1. Fork 本專案
2. 建立功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交變更 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟 Pull Request

---

## 📄 授權

本專案採用 MIT 授權條款 - 詳見 [LICENSE](LICENSE) 檔案

---

## 🙏 致謝

- [OCR.space](https://ocr.space/) - OCR 服務
- [DeepSeek](https://deepseek.com/) - AI 文字解析
- [TCPDF](https://tcpdf.org/) - PDF 生成

---

<p align="center">
  Made with ❤️ in Hong Kong
</p>
