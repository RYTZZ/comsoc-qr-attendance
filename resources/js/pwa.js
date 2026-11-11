/**
 * COMSOC HUB PWA Manager
 * Registers service worker, manages install prompts, and checks online/offline state.
 */

export function initPWA() {
    // 1. Register Service Worker in production / supported environments
    if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((registration) => {
                    // Check for updates periodically
                    registration.onupdatefound = () => {
                        const installingWorker = registration.installing;
                        if (installingWorker) {
                            installingWorker.onstatechange = () => {
                                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('[PWA] New content is available; please refresh.');
                                }
                            };
                        }
                    };
                })
                .catch((error) => {
                    console.warn('[PWA] Service worker registration failed:', error);
                });
        });
    }

    // 2. Handle PWA Install Prompt ('beforeinstallprompt')
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        window.dispatchEvent(new CustomEvent('comsoc:pwa-installable', { detail: { prompt: deferredPrompt } }));
    });

    window.installCOMSOCApp = async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`[PWA] Install prompt outcome: ${outcome}`);
        deferredPrompt = null;
    };

    // 3. Online / Offline Network Status Broadcasting
    const updateOnlineStatus = () => {
        const isOnline = navigator.onLine;
        window.dispatchEvent(new CustomEvent('comsoc:network-change', { detail: { online: isOnline } }));
        if (!isOnline) {
            console.warn('[PWA] Offline mode active. Offline scanner fallback is ready.');
        } else {
            console.log('[PWA] Online connection restored. Syncing offline queues.');
        }
    };

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
}

