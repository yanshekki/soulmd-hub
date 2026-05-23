const CACHE_NAME = 'soulmd-hub-v1';
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
    self.skipWaiting();
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

// 攔截請求 (Network First 策略，保證 API 資料實時性)
self.addEventListener('fetch', event => {
    // 忽略非 GET 請求及 API 請求
    if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // 如果成功獲取，則更新快取
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseClone);
                });
                return response;
            })
            .catch(() => {
                // 如果無網絡，回退到快取
                return caches.match(event.request);
            })
    );
});