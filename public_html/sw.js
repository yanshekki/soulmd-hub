const CACHE_NAME = 'soulmd-hub-v3'; // 升級緩存版本強制刷新
const ASSETS_TO_CACHE = [
    '/',
    '/browse',
    '/manifest.json'
];

// 安裝 Service Worker 並快取基本資源
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
        .then(cache => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting(); // 🚨 強制新的 Service Worker 立即接管，踢走舊的壞版本
});

// 清理舊快取
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// 攔截請求 (Network First 策略)
self.addEventListener('fetch', event => {
    // 忽略非 GET 請求、API 請求及 CDN 外部腳本 (避免快取壞掉的腳本)
    if (event.request.method !== 'GET' || 
        event.request.url.includes('/api/') || 
        event.request.url.includes('esm.sh') || 
        event.request.url.includes('jsdelivr')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // 🚨 嚴格修復：只緩存成功的請求 (200 OK)，防止把錯誤存入緩存
                if (response && response.status === 200 && response.type === 'basic') {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // 如果無網絡，回退到快取
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // 🚨 終極修復 TypeError：如果快取都無，必須回傳一個標準的 Response，絕對不能回傳 undefined
                    return new Response(
                        'Network error. Please check your internet connection.',
                        { 
                            status: 503, 
                            statusText: 'Service Unavailable',
                            headers: new Headers({'Content-Type': 'text/plain'})
                        }
                    );
                });
            })
    );
});