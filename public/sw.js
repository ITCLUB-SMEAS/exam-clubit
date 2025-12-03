const CACHE_NAME = 'ujian-online-v3';
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png'
];

// Install - cache static assets
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

// Fetch - network first, fallback to cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Only handle http/https requests (skip chrome-extension, etc)
    if (!url.protocol.startsWith('http')) return;
    
    // Skip non-GET requests
    if (request.method !== 'GET') return;
    
    // Skip API requests - always network
    if (url.pathname.startsWith('/api/')) return;
    
    event.respondWith(
        fetch(request)
            .then((response) => {
                // Cache successful responses
                if (response.ok && response.type === 'basic') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request).then((cached) => cached || caches.match('/offline.html')))
    );
});
