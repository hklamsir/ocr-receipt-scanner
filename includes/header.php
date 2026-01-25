<?php
/**
 * header.php - 共用頁首組件
 * 
 * 使用方式：
 * $pageTitle = '頁面標題';
 * $showNav = true;  // 是否顯示導航列，預設 true
 * include __DIR__ . '/includes/header.php';
 */
require_once __DIR__ . '/security.php';

// 預設值
if (!isset($pageTitle))
    $pageTitle = '智慧單據辨識';
if (!isset($showNav))
    $showNav = true;
if (!isset($headerTitle))
    $headerTitle = $pageTitle;
?>
<!DOCTYPE html>
<html lang="zh-HK">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo Security::getCSRFToken(); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?> - 智慧單據辨識工具</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/pdf-buttons.css">
    <link rel="icon" type="image/svg+xml" href="images/logo.svg">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <?php if (isset($extraStyles)): ?>
        <?php echo $extraStyles; ?>
    <?php endif; ?>
    <script>
        // 註冊 Service Worker（僅在支援且非重定向環境下）
        if ('serviceWorker' in navigator) {
            // 動態計算 sw.js 的正確路徑
            var swPath = 'sw.js';
            var swScope = './';

            navigator.serviceWorker.register(swPath, { scope: swScope })
                .then(function (reg) {
                    console.log('SW registered successfully');
                })
                .catch(function (err) {
                    // InfinityFree 等免費主機可能不支援 SW，靜默忽略
                    if (err.name !== 'SecurityError') {
                        console.log('SW registration failed:', err.message);
                    }
                });
        }
    </script>
</head>

<body>

    <header>
        <div class="header-branding">
            <img src="images/logo.svg" alt="DS OCR Logo" class="header-logo">
            <span class="header-title">
                <?php echo htmlspecialchars($headerTitle); ?>
            </span>
        </div>
        <?php if ($showNav): ?>
            <?php include __DIR__ . '/nav.php'; ?>
        <?php endif; ?>
    </header>

    <!-- 公告橫幅 -->
    <div id="announcementBanner" class="announcement-banner" style="display:none;">
        <div class="announcement-content">
            <span class="announcement-icon">📢</span>
            <span id="announcementText"></span>
            <button class="announcement-close" onclick="closeAnnouncement()">×</button>
        </div>
    </div>