const CACHE_NAME = 'ujian-online-v4';
const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png'
];

// Install - cache only offline page and icons
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => 
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

// Fetch - ALWAYS network first for exam pages, only show offline if truly offline
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Only handle http/https requests
    if (!url.protocol.startsWith('http')) return;
    
    // Skip non-GET requests
    if (request.method !== 'GET') return;
    
    // Skip API, anticheat, exam routes - NEVER cache these
    if (url.pathname.startsWith('/api/') ||
        url.pathname.startsWith('/student/exam') ||
        url.pathname.startsWith('/student/anticheat') ||
        url.pathname.includes('exam-') ||
        url.pathname.includes('duration')) {
        return; // Let browser handle normally
    }
    
    // For navigation requests (HTML pages)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(() => {
                    // Only show offline page if network truly fails
                    return caches.match('/offline.html');
                })
        );
        return;
    }
    
    // For static assets (JS, CSS, images) - cache with network fallback
    if (url.pathname.startsWith('/build/') || 
        url.pathname.startsWith('/assets/') ||
        url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fetchPromise = fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
                return cached || fetchPromise;
            })
        );
        return;
    }
});
