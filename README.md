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
| 🤖 **智慧 OCR** | 整合 OCR.space + DeepSeek AI，自動提取日期、金額、商家並生成摘要 |
| 🏷️ **標籤系統** | 自訂標籤分類，支援顏色管理與批次操作 |
| 📊 **資料匯出** | 一鍵匯出 Excel 或 PDF 報表，支援高度客製化 PDF 模板 |
| 👥 **多用戶管理** | 用戶資料隔離，管理員後台可監控系統狀態與管理用戶 |
| 🔒 **安全設計** | 完整的 CSRF 保護、SQL 注入防護、登入嘗試限制 |
| 📱 **響應式設計** | 完美適配桌面與手機裝置，操作體驗流暢 |

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

請在您的 MySQL 資料庫中執行根目錄下的 `database.sql` 檔案。這將會自動建立所有需要的資料表並匯入預設資料。

```sql
-- 使用 phpMyAdmin 或 MySQL CLI 匯入
source database.sql;
```

#### 3. 設定敏感資料

複製設定範本並填入實際值：

```bash
cp config/config.example.php config/secret.php
```

編輯 `config/secret.php`：

```php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => '您的資料庫名稱',
    'db_user' => '您的資料庫使用者',
    'db_pass' => '您的資料庫密碼',
    
    // API 金鑰 (請至對應服務申請)
    'deepseek_api_key' => '您的 DeepSeek API Key',
    'ocr_api_key' => '您的 OCR.space API Key',
];
```

#### 4. 設定目錄權限

確保網頁伺服器對以下目錄有寫入權限：

```bash
chmod 755 receipts/
chmod 755 tmp/
```

#### 5. 登入系統

- **管理員帳號**：`admin`
- **預設密碼**：`admin123`

> ⚠️ **重要安全提醒**：首次登入後，請務必立即至「設定」頁面修改密碼！

---

## 📁 專案結構

```
ocr_ds/
├── api/                    # RESTful API 端點 (處理 AJAX 請求)
├── config/                 # 系統設定檔
├── css/                    # 樣式表 (包含 Design System)
├── includes/               # PHP 共用模組 (Auth, DB, TCPDF)
├── js/                     # 前端 JavaScript 邏輯
├── sql/                    # 歷史資料庫遷移腳本 (僅供參考)
├── receipts/               # 用戶上傳的收據圖片 (需寫入權限)
├── database.sql            # 完整資料庫初始化腳本
├── index.php               # 首頁 (上傳與 OCR 處理)
├── receipts.php            # 收據列表 (查詢、編輯、匯出)
├── settings.php            # 系統與個人設定
└── admin.php               # 管理員後台
```

---

## 🔧 系統管理

管理員登入後可進入「管理後台」(`admin.php`) 進行以下操作：

- **系統監控**：檢視每日 OCR 請求統計、儲存空間使用量。
- **用戶管理**：查看用戶列表、停用違規帳號、設定用戶配額。
- **系統設定**：調整全域參數（如上傳限制、圖片壓縮品質）。
- **公告管理**：發布系統公告給所有用戶。

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
- [TCPDF](https://tcpdf.org/) - 支援 PDF 報表生成
