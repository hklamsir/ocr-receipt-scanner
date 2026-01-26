<?php
// PDF 匯出 API
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

// 檢查 TCPDF 是否存在
$tcpdfPath = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    // 備用路徑：直接在 includes 目錄
    $tcpdfPath = __DIR__ . '/../includes/tcpdf/tcpdf.php';
}

if (!file_exists($tcpdfPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'TCPDF 庫未安裝。請下載 TCPDF 並放置到 includes/tcpdf/ 目錄，或使用 Composer 安裝：composer require tecnickcom/tcpdf'
    ]);
    exit;
}

require_once $tcpdfPath;

try {
    // 獲取 POST 數據
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('缺少必要參數');
    }

    // 支援單一單據或批量單據
    $receiptIds = [];
    if (isset($data['receipt_id'])) {
        // 單一單據模式
        $receiptIds = [(int) $data['receipt_id']];
    } elseif (isset($data['receipt_ids']) && is_array($data['receipt_ids'])) {
        // 批量模式
        $receiptIds = array_map('intval', $data['receipt_ids']);
    } else {
        throw new Exception('缺少單據ID參數');
    }

    if (empty($receiptIds)) {
        throw new Exception('沒有可匯出的單據');
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // 查詢所有單據資料
    $placeholders = implode(',', array_fill(0, count($receiptIds), '?'));
    $stmt = $pdo->prepare("
        SELECT r.*, u.username
        FROM receipts r
        JOIN users u ON r.user_id = u.id
        WHERE r.id IN ($placeholders) AND r.user_id = ?
        ORDER BY r.receipt_date DESC, r.receipt_time DESC
    ");
    $stmt->execute(array_merge($receiptIds, [$userId]));
    $receipts = $stmt->fetchAll();

    if (empty($receipts)) {
        throw new Exception('找不到指定的單據');
    }

    // 解析參數，設定預設值
    $pageSize = strtoupper($data['page_size'] ?? 'A4');
    $marginTop = (float) ($data['margin_top'] ?? 15);
    $marginBottom = (float) ($data['margin_bottom'] ?? 15);
    $marginLeft = (float) ($data['margin_left'] ?? 15);
    $marginRight = (float) ($data['margin_right'] ?? 15);

    $headerText = $data['header_text'] ?? '';
    $headerAlign = $data['header_align'] ?? 'C';
    $headerFontSize = (int) ($data['header_font_size'] ?? 16);

    $footerText = $data['footer_text'] ?? '';
    $footerAlign = $data['footer_align'] ?? 'C';
    $footerFontSize = (int) ($data['footer_font_size'] ?? 16);

    $imageAlign = $data['image_align'] ?? 'C';
    $imageHeightScale = (float) ($data['image_height_scale'] ?? 80) / 100; // 轉換為 0.1-1.0
    $imageWidthScale = (float) ($data['image_width_scale'] ?? 40) / 100; // 轉換為 0.2-1.0

    // 創建 PDF
    $pdf = new TCPDF($orientation = 'P', $unit = 'mm', $format = $pageSize, $unicode = true, $encoding = 'UTF-8');

    // 設定文檔資訊
    $pdf->SetCreator('OCR Receipt System');
    $pdf->SetAuthor($receipts[0]['username']);
    if (count($receipts) === 1) {
        $pdf->SetTitle('單據 - ' . ($receipts[0]['company_name'] ?: '無標題'));
    } else {
        $pdf->SetTitle('批量單據匯出 (' . count($receipts) . ' 筆)');
    }

    // 移除預設的頁首和頁尾
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // 設定邊界
    $pdf->SetMargins($marginLeft, $marginTop, $marginRight);
    $pdf->SetAutoPageBreak(true, $marginBottom);

    // 設定中文字體 - 使用 TCPDF 內建的中文宋體
    // stsongstdlight 是 TCPDF 內建的簡體中文字體
    $pdf->SetFont('stsongstdlight', '', 12);

    // 循環處理每筆單據
    foreach ($receipts as $index => $receipt) {
        // 添加新頁
        $pdf->AddPage();

        // 定義共用的系統變數替換函數
        $replaceCommonVariables = function ($text, $pdf, $receipt) {
            $vars = [
                '{PAGENO}' => $pdf->PageNo(),
                '{PAGES}' => $pdf->getAliasNbPages(),
                '{TODAY}' => date('Y-m-d'),
                '{NOW}' => date('H:i'),
                '{USER}' => $receipt['username'] ?? '',
                '{COMPANY}' => $receipt['company_name'] ?? '',
                '{DATE}' => $receipt['receipt_date'] ?? '',
                '{AMOUNT}' => $receipt['total_amount'] ?? '',
                '{PAYMENT}' => $receipt['payment_method'] ?? '',
                '{SUMMARY}' => $receipt['summary'] ?? '',
                '{ITEMS}' => $receipt['items_summary'] ?? ''
            ];
            return strtr($text, $vars);
        };

        // 如果有頁首文字
        if (!empty($headerText)) {
            $pdf->SetFont('stsongstdlight', '', $headerFontSize);
            // 替換變數
            $currentHeaderContent = $replaceCommonVariables($headerText, $pdf, $receipt);
            // 將 \n 轉換為實際換行，支援多行
            $headerLines = explode("\n", $currentHeaderContent);
            $lineCount = min(count($headerLines), 5); // 最多5行
            foreach (array_slice($headerLines, 0, $lineCount) as $line) {
                $pdf->Cell(0, 8, trim($line), 0, 1, $headerAlign);
            }
            $pdf->Ln(5);
        }

        // 插入單據圖片
        if (!empty($receipt['image_filename'])) {
            // ... (保持原本的圖片處理邏輯)
            $imagePath = __DIR__ . '/../receipts/' . $receipt['username'] . '/' . $receipt['image_filename'];

            if (file_exists($imagePath)) {
                // 計算可用空間
                $pageWidth = $pdf->getPageWidth();
                $pageHeight = $pdf->getPageHeight();
                $availableWidth = $pageWidth - $marginLeft - $marginRight;
                $availableHeight = $pageHeight - $marginTop - $marginBottom;

                // 獲取圖片信息
                $imageInfo = getimagesize($imagePath);
                if ($imageInfo !== false) {
                    list($imgWidth, $imgHeight) = $imageInfo;

                    // 計算最大寬度和高度
                    $maxHeight = $availableHeight * $imageHeightScale;
                    $maxWidth = $pageWidth * $imageWidthScale;

                    // 按比例計算
                    $aspectRatio = $imgWidth / $imgHeight;

                    // 先按高度縮放
                    $targetHeight = $maxHeight;
                    $targetWidth = $targetHeight * $aspectRatio;

                    // 如果寬度超過上限，則以寬度為準重新計算
                    if ($targetWidth > $maxWidth) {
                        $targetWidth = $maxWidth;
                        $targetHeight = $targetWidth / $aspectRatio;
                    }

                    // 如果寬度仍超出可用空間，按可用空間縮放
                    if ($targetWidth > $availableWidth) {
                        $targetWidth = $availableWidth;
                        $targetHeight = $targetWidth / $aspectRatio;
                    }

                    // 計算 X 位置（對齊方式）
                    $x = $marginLeft;
                    if ($imageAlign === 'C') {
                        $x = ($pageWidth - $targetWidth) / 2;
                    } elseif ($imageAlign === 'R') {
                        $x = $pageWidth - $marginRight - $targetWidth;
                    }

                    $y = $pdf->GetY();

                    // 插入圖片 - 明確指定圖片類型
                    $imageType = $imageInfo[2];
                    $ext = '';
                    if ($imageType === IMAGETYPE_JPEG) {
                        $ext = 'JPEG';
                    } elseif ($imageType === IMAGETYPE_PNG) {
                        $ext = 'PNG';
                    } elseif ($imageType === IMAGETYPE_GIF) {
                        $ext = 'GIF';
                    }

                    try {
                        $pdf->Image($imagePath, $x, $y, $targetWidth, $targetHeight, $ext, '', '', true, 300, '', false, false, 0, false, false, false);
                        // 移動 Y 位置到圖片下方
                        $pdf->SetY($y + $targetHeight + 10);
                    } catch (Exception $imgError) {
                        // 圖片插入失敗，添加錯誤信息到 PDF
                        $pdf->SetFont('stsongstdlight', '', 10);
                        $pdf->Cell(0, 10, '(圖片載入失敗)', 0, 1, 'C');
                        $pdf->Ln(5);
                    }
                }
            } else {
                // 圖片檔案不存在
                $pdf->SetFont('stsongstdlight', '', 10);
                $pdf->Cell(0, 10, '(圖片檔案不存在)', 0, 1, 'C');
                $pdf->Ln(5);
            }
        }

        // 不顯示單據資訊，只顯示圖片和頁首頁尾

        // 如果有頁尾文字
        if (!empty($footerText)) {
            // 替換變數
            $currentFooterContent = $replaceCommonVariables($footerText, $pdf, $receipt);

            // 將 \n 轉換為實際換行，支援多行
            $footerLines = explode("\n", $currentFooterContent);
            $lineCount = min(count($footerLines), 5); // 最多5行

            // 移動到頁面底部，根據行數調整位置
            $footerHeight = $lineCount * 8 + 5;
            $pdf->SetY(-($marginBottom + $footerHeight));
            $pdf->SetFont('stsongstdlight', '', $footerFontSize);

            foreach (array_slice($footerLines, 0, $lineCount) as $line) {
                $pdf->Cell(0, 8, trim($line), 0, 1, $footerAlign);
            }
        }
    } // 結束 foreach ($receipts as $index => $receipt) 循環

    // 輸出 PDF
    if (count($receipts) === 1) {
        $filename = '單據_' . ($receipts[0]['company_name'] ?: 'receipt') . '_' . ($receipts[0]['receipt_date'] ?: date('Y-m-d')) . '.pdf';
    } else {
        $filename = '批量單據_' . count($receipts) . '筆_' . date('Y-m-d_His') . '.pdf';
    }

    // 清除所有輸出緩衝區
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 設定 headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // 輸出 PDF 到瀏覽器
    $pdf->Output($filename, 'I');
    exit;

} catch (Exception $e) {
    // 清除任何之前的輸出
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    // 捕獲 PHP 錯誤（例如 TCPDF 相關錯誤）
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => '系統錯誤: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
