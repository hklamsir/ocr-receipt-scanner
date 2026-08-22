// OCR 單據系統 Service Worker
// 版本號：用於更新時清除舊緩存
// 注意：修改 JS/CSS 後若想立即讓用戶舊緩存失效，請 bump 此版本號
// （配合下方靜態資源 Stale-While-Revalidate 策略，最遲二次訪問亦會生效）
const CACHE_VERSION = 'v2';
const CACHE_NAME = `ocr-receipts-${CACHE_VERSION}`;

// 安裝事件：不預緩存，改為按需緩存
self.addEventListener('install', event => {
    console.log('[SW] Installing...');
    // 立即激活新版本
    self.skipWaiting();
});

// 激活事件：清除舊版本緩存
self.addEventListener('activate', event => {
    console.log('[SW] Activating...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name.startsWith('ocr-receipts-') && name !== CACHE_NAME)
                    .map(name => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        })
    );
    // 立即控制所有頁面
    self.clients.claim();
});

// Fetch 事件：攔截網路請求
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // 只處理同源請求
    if (url.origin !== location.origin) return;

    // 圖片請求：Cache First 策略
    if (url.pathname.includes('api/get_image.php')) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) {
                    console.log('[SW] Image from cache:', url.pathname);
                    return cached;
                }
                return fetch(event.request).then(response => {
                    // 只緩存成功的回應
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                });
            })
        );
        return;
    }

    // 靜態資源：Stale-While-Revalidate
    // 先立即返回緩存（若有）保證速度，同時於背景向網絡請求並更新緩存；
    // 這樣日後修改 JS/CSS 後，用戶最遲在第二次訪問即可拿到新版本，
    // 不必每次手動 bump CACHE_VERSION（但 bump 版本號仍可強制立即清舊緩存）。
    if (url.pathname.match(/\.(css|js|woff2?|ttf|eot)$/)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                const cached = cache.match(event.request);
                const fetched = fetch(event.request).then(networkResponse => {
                    if (networkResponse && networkResponse.ok) {
                        cache.put(event.request, networkResponse.clone());
                    }
                    return networkResponse;
                }).catch(() => cached);
                return cached.then(cachedResponse => cachedResponse || fetched);
            })
        );
        return;
    }

    // API 請求：Network First（優先取得最新資料）
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => response)
                .catch(() => {
                    // 網路失敗時嘗試使用緩存
                    return caches.match(event.request);
                })
        );
        return;
    }

    // 其他請求：Network First
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
