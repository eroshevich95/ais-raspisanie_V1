/* Service Worker для АИС-Расписание */

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE  = 'static-' + CACHE_VERSION;
const RUNTIME_CACHE = 'runtime-' + CACHE_VERSION;

const PRECACHE_URLS = [
    '/',
    '/dashboard.php',
    '/schedule.php',
    '/attendance.php',
    '/offline.html',
    '/manifest.json',
    '/asset/icons/icon-192.png',
    '/asset/icons/icon-512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'
];

// === Установка: кешируем базовые файлы ===
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(PRECACHE_URLS.map(url => new Request(url, { credentials: 'same-origin' })))
                .catch(err => console.warn('Precache ошибка:', err));
        }).then(() => self.skipWaiting())
    );
});

// === Активация: чистим старые кеши ===
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter(key => key !== STATIC_CACHE && key !== RUNTIME_CACHE)
                    .map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// === Fetch: стратегия по типу запроса ===
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Не кешируем POST и запросы с авторизацией/мутациями
    if (request.method !== 'GET') return;

    // Не кешируем API портала КузГТУ — там всегда свежие данные
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstWithTimeout(request, 3000));
        return;
    }

    // PHP-страницы портала: network-first (свежие данные важнее)
    if (url.pathname.endsWith('.php') || url.pathname === '/') {
        event.respondWith(networkFirst(request));
        return;
    }

    // Статика (CSS, JS, картинки, шрифты): cache-first
    if (/\.(css|js|png|jpg|jpeg|webp|svg|woff2?|ttf|ico)$/.test(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Всё остальное — просто сеть с fallback на офлайн
    event.respondWith(networkFirst(request));
});

// === Стратегии ===

async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) cache.put(request, response.clone());
        return response;
    } catch {
        return new Response('Ресурс недоступен', { status: 503 });
    }
}

async function networkFirst(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    try {
        const response = await fetch(request);
        if (response.ok) cache.put(request, response.clone());
        return response;
    } catch {
        const cached = await cache.match(request);
        if (cached) return cached;

        // Для HTML-запросов показываем офлайн-страницу
        if (request.headers.get('accept')?.includes('text/html')) {
            return caches.match('/offline.html');
        }
        return new Response('Офлайн', { status: 503 });
    }
}

async function networkFirstWithTimeout(request, timeout) {
    const cache = await caches.open(RUNTIME_CACHE);
    const controller = new AbortController();
    const t = setTimeout(() => controller.abort(), timeout);

    try {
        const response = await fetch(request, { signal: controller.signal });
        clearTimeout(t);
        if (response.ok) cache.put(request, response.clone());
        return response;
    } catch {
        clearTimeout(t);
        const cached = await cache.match(request);
        if (cached) return cached;
        return new Response(JSON.stringify({ error: 'offline' }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}