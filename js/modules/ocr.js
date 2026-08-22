// OCR 處理模組
// 用戶需求：並行限制為 2 個
const CONCURRENT_LIMIT = 2;
const MAX_RETRIES = 3;
const RETRY_DELAY = 1000;

// 並行處理圖片
export async function processImagesParallel(images, ocrProxyUrl, onProgress) {
    const results = [];

    for (let i = 0; i < images.length; i += CONCURRENT_LIMIT) {
        const batch = images.slice(i, i + CONCURRENT_LIMIT);
        const promises = batch.map((img, idx) =>
            processOCRWithRetry(img, i + idx, ocrProxyUrl, onProgress)
        );
        const batchResults = await Promise.allSettled(promises);
        results.push(...batchResults);
    }

    return results;
}

// OCR 處理（含重試機制）
async function processOCRWithRetry(img, index, ocrProxyUrl, onProgress) {
    let retries = MAX_RETRIES;

    while (retries > 0) {
        try {
            onProgress(index, '處理中...');

            const response = await fetch(ocrProxyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: img.dataUrl })
            });

            const json = await response.json();

            // 檢查是否有錯誤（包括 503 等 HTTP 錯誤）
            if (json.error) {
                onProgress(index, '錯誤');
                return { success: false, name: img.name, error: json.error };
            }

            if (json.text) {
                onProgress(index, '完成');
                return {
                    success: true,
                    name: img.name,
                    text: json.text,
                    engine: json.engine
                };
            } else {
                onProgress(index, '失敗');
                return { success: false, name: img.name };
            }
        } catch (err) {
            retries--;
            if (retries === 0) {
                console.error(`OCR 錯誤 (${img.name}):`, err);
                onProgress(index, '錯誤');
                return { success: false, name: img.name, error: err.message };
            }
            // 等待後重試
            await new Promise(r => setTimeout(r, RETRY_DELAY));
        }
    }
}

// 提取結構化資料
export async function extractStructuredData(ocrText) {
    const response = await fetch('proxy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=extract&ocr_text=' + encodeURIComponent(ocrText)
    });

    const json = await response.json();

    // 若後端返回錯誤訊息，優先使用它
    if (!response.ok || json.error) {
        throw new Error(json.error || `HTTP ${response.status}: ${response.statusText}`);
    }

    return json;
}

// 將 OCR 結果組合為文字
export function combineOCRResults(results) {
    let ocrText = '';

    results.forEach(result => {
        if (result.status === 'fulfilled' && result.value.success) {
            const data = result.value;
            ocrText += `【${data.name}｜Engine ${data.engine}】\n${data.text}\n\n`;
        }
    });

    return ocrText;
}

// =============== Gemini 視覺端到端 ===============

// 並行處理圖片（視覺模式，限制 2 個）——直接送圖給 vision_proxy.php
export async function processImagesVisionParallel(images, visionProxyUrl, onProgress) {
    const results = [];

    for (let i = 0; i < images.length; i += CONCURRENT_LIMIT) {
        const batch = images.slice(i, i + CONCURRENT_LIMIT);
        const promises = batch.map((img, idx) =>
            processVisionWithRetry(img, i + idx, visionProxyUrl, onProgress)
        );
        const batchResults = await Promise.allSettled(promises);
        results.push(...batchResults);
    }

    return results;
}

// 視覺處理（含重試機制）
async function processVisionWithRetry(img, index, visionProxyUrl, onProgress) {
    let retries = MAX_RETRIES;

    while (retries > 0) {
        try {
            onProgress(index, '視覺處理中...');

            const response = await fetch(visionProxyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: img.dataUrl })
            });

            const json = await response.json();

            // 檢查是否有錯誤（包括 503 等 HTTP 錯誤）
            if (json.error) {
                onProgress(index, '錯誤');
                return { success: false, name: img.name, error: json.error };
            }

            if (json.success && json.result) {
                onProgress(index, '完成');
                return {
                    success: true,
                    name: img.name,
                    result: json.result,
                    engine: json.engine || 'gemini-vision',
                    image: img.dataUrl
                };
            } else {
                onProgress(index, '失敗');
                return { success: false, name: img.name };
            }
        } catch (err) {
            retries--;
            if (retries === 0) {
                console.error(`Vision 錯誤 (${img.name}):`, err);
                onProgress(index, '錯誤');
                return { success: false, name: img.name, error: err.message };
            }
            // 等待後重試
            await new Promise(r => setTimeout(r, RETRY_DELAY));
        }
    }
}

// 單張圖片視覺提取（供外部單獨呼叫）
export async function extractWithVision(image, mimeType) {
    const response = await fetch('vision_proxy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: image.dataUrl })
    });

    const json = await response.json();

    if (!response.ok || json.error) {
        throw new Error(json.error || `HTTP ${response.status}: ${response.statusText}`);
    }

    return json;
}
