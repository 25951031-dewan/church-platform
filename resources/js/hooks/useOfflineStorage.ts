/**
 * useOfflineStorage
 *
 * IndexedDB wrapper for persisting downloaded content (PDFs, hymns, verses)
 * so they remain accessible when the device goes offline.
 *
 * Usage:
 *   const { save, load, remove, list } = useOfflineStorage('hymns');
 */

import { useCallback } from 'react';

const DB_NAME    = 'church-offline';
const DB_VERSION = 1;

type StoreName = 'hymns' | 'verses' | 'sermons' | 'pdfs';

interface OfflineItem<T = unknown> {
    id: string;
    data: T;
    savedAt: number;
}

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);

        req.onupgradeneeded = (e) => {
            const db = (e.target as IDBOpenDBRequest).result;
            const stores: StoreName[] = ['hymns', 'verses', 'sermons', 'pdfs'];
            stores.forEach((name) => {
                if (!db.objectStoreNames.contains(name)) {
                    db.createObjectStore(name, { keyPath: 'id' });
                }
            });
        };

        req.onsuccess = (e) => resolve((e.target as IDBOpenDBRequest).result);
        req.onerror   = (e) => reject((e.target as IDBOpenDBRequest).error);
    });
}

export function useOfflineStorage<T = unknown>(storeName: StoreName) {
    const save = useCallback(async (id: string, data: T): Promise<void> => {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).put({ id, data, savedAt: Date.now() } as OfflineItem<T>);
            req.onsuccess = () => resolve();
            req.onerror   = () => reject(req.error);
        });
    }, [storeName]);

    const load = useCallback(async (id: string): Promise<T | null> => {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).get(id);
            req.onsuccess = () => {
                const item = req.result as OfflineItem<T> | undefined;
                resolve(item?.data ?? null);
            };
            req.onerror = () => reject(req.error);
        });
    }, [storeName]);

    const remove = useCallback(async (id: string): Promise<void> => {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).delete(id);
            req.onsuccess = () => resolve();
            req.onerror   = () => reject(req.error);
        });
    }, [storeName]);

    const list = useCallback(async (): Promise<OfflineItem<T>[]> => {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = () => resolve((req.result as OfflineItem<T>[]) ?? []);
            req.onerror   = () => reject(req.error);
        });
    }, [storeName]);

    return { save, load, remove, list };
}

/**
 * Tiny hook: returns true when the browser is offline.
 * Shows the "Available Offline" indicator in mobile UI.
 */
import { useState, useEffect } from 'react';

export function useIsOffline(): boolean {
    const [offline, setOffline] = useState(!navigator.onLine);

    useEffect(() => {
        const on  = () => setOffline(true);
        const off = () => setOffline(false);
        window.addEventListener('offline', on);
        window.addEventListener('online', off);
        return () => {
            window.removeEventListener('offline', on);
            window.removeEventListener('online', off);
        };
    }, []);

    return offline;
}
