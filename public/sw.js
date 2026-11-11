/**
 * COMSOC HUB Service Worker
 * Handles offline app caching, dynamic pass persistence, and offline scanner queue synchronization.
 */

const CACHE_VERSION = 'comsoc-hub-v1';
const STATIC_CACHE_NAME = `comsoc-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE_NAME = `comsoc-dynamic-${CACHE_VERSION}`;

// Core static assets required for the app shell to render offline
const STATIC_ASSETS = [
    '/',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/favicon-32x32.png',
    '/apple-touch-icon.png',
    '/images/COMSOC.png',
];

// Install Event: Precaches the application shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[SW] Precache failed for some assets, continuing:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event: Cleanup stale caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== STATIC_CACHE_NAME && key !== DYNAMIC_CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event Strategy:
// 1. Static Assets (CSS, JS, Fonts, Images) -> Cache First with Network Fallback
// 2. Navigation / HTML pages -> Network First with Offline Page Fallback
// 3. API / POST Requests -> Network Only (Offline scanner queues them in IndexedDB)
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests in SW cache
    if (request.method !== 'GET') {
        return;
    }

    // Skip Chrome extension & non-http requests
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Static Asset Caching (Vite build assets, images, icons, fonts)
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(STATIC_CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Fallback if network offline
                    return cachedResponse || new Response('', { status: 408 });
                });
            })
        );
        return;
    }

    // HTML / Page Navigation Strategy: Network-First with Cache Fallback
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(DYNAMIC_CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Return the cached root shell if individual page wasn't cached
                    const rootFallback = await caches.match('/');
                    return rootFallback || new Response('Offline: Please check your internet connection.', {
                        headers: { 'Content-Type': 'text/plain' },
                        status: 503
                    });
                })
        );
    }
});
