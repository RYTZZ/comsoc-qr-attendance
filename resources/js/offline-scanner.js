/**
 * COMSOC HUB Offline Scanner Engine (IndexedDB + Offline Queue)
 * Allows event kiosks and organizers to scan and validate attendance even when Wi-Fi/cellular connection drops.
 */

const DB_NAME = 'comsoc_offline_db';
const DB_VERSION = 1;
const STORE_MANIFEST = 'attendee_manifest';
const STORE_QUEUE = 'scan_queue';

class OfflineScannerEngine {
    constructor() {
        this.db = null;
        this.isReady = false;
        this.initPromise = this.initDB();
    }

    async initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                // Store 1: Eligible Attendees for the event
                if (!db.objectStoreNames.contains(STORE_MANIFEST)) {
                    const manifestStore = db.createObjectStore(STORE_MANIFEST, { keyPath: 'token_or_id' });
                    manifestStore.createIndex('student_number', 'student_number', { unique: false });
                }
                // Store 2: Offline Scans Queue awaiting server synchronization
                if (!db.objectStoreNames.contains(STORE_QUEUE)) {
                    const queueStore = db.createObjectStore(STORE_QUEUE, { keyPath: 'local_id', autoIncrement: true });
                    queueStore.createIndex('kiosk_id', 'kiosk_id', { unique: false });
                    queueStore.createIndex('synced', 'synced', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                this.isReady = true;
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error('[OfflineScanner] IndexedDB init error:', event.target.error);
                reject(event.target.error);
            };
        });
    }

    /**
     * Cache attendees for offline verification before event starts
     */
    async saveManifest(attendees = []) {
        await this.initPromise;
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_MANIFEST], 'readwrite');
            const store = tx.objectStore(STORE_MANIFEST);
            store.clear();

            attendees.forEach((att) => {
                store.put({
                    token_or_id: String(att.token || att.id || att.student_number).toLowerCase().trim(),
                    student_number: att.student_number || '',
                    name: att.name || 'Member',
                    program: att.program || '',
                    year_level: att.year_level || '',
                    avatar: att.avatar || '',
                    status: att.status || 'eligible',
                });
            });

            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Offline lookup when connection is lost
     */
    async lookupOffline(tokenOrId) {
        await this.initPromise;
        const cleanKey = String(tokenOrId).toLowerCase().trim();
        return new Promise((resolve) => {
            const tx = this.db.transaction([STORE_MANIFEST], 'readonly');
            const store = tx.objectStore(STORE_MANIFEST);
            const req = store.get(cleanKey);

            req.onsuccess = () => {
                resolve(req.result || null);
            };
            req.onerror = () => {
                resolve(null);
            };
        });
    }

    /**
     * Enqueue scan locally when offline
     */
    async enqueueScan(scanPayload) {
        await this.initPromise;
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_QUEUE], 'readwrite');
            const store = tx.objectStore(STORE_QUEUE);
            const record = {
                ...scanPayload,
                timestamp: new Date().toISOString(),
                synced: 0,
            };

            const req = store.add(record);
            req.onsuccess = () => resolve(req.result);
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Retrieve pending scans to sync
     */
    async getPendingQueue(kioskId) {
        await this.initPromise;
        return new Promise((resolve) => {
            const tx = this.db.transaction([STORE_QUEUE], 'readonly');
            const store = tx.objectStore(STORE_QUEUE);
            const req = store.getAll();

            req.onsuccess = () => {
                const results = req.result || [];
                const filtered = results.filter(r => !r.synced && (!kioskId || r.kiosk_id === kioskId));
                resolve(filtered);
            };
            req.onerror = () => resolve([]);
        });
    }

    /**
     * Mark items as synced
     */
    async markSynced(localIds = []) {
        if (!localIds.length) return;
        await this.initPromise;
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_QUEUE], 'readwrite');
            const store = tx.objectStore(STORE_QUEUE);

            localIds.forEach((id) => {
                store.delete(id);
            });

            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    }
}

export const offlineScanner = new OfflineScannerEngine();
window.offlineScanner = offlineScanner;
