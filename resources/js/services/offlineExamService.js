/**
 * Offline Exam Service - IndexedDB storage for offline exam capability
 */

const DB_NAME = 'ujian_online_db';
const DB_VERSION = 1;

let db = null;

export const initDB = () => {
    return new Promise((resolve, reject) => {
        if (db) return resolve(db);

        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            db = request.result;
            resolve(db);
        };

        request.onupgradeneeded = (event) => {
            const database = event.target.result;

            // Store exam data for offline use
            if (!database.objectStoreNames.contains('exams')) {
                database.createObjectStore('exams', { keyPath: 'examGroupId' });
            }

            // Store answers locally
            if (!database.objectStoreNames.contains('answers')) {
                const store = database.createObjectStore('answers', { keyPath: 'id', autoIncrement: true });
                store.createIndex('examGroupId', 'examGroupId', { unique: false });
                store.createIndex('synced', 'synced', { unique: false });
            }

            // Store pending sync queue
            if (!database.objectStoreNames.contains('syncQueue')) {
                database.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
};

// Cache exam data for offline use
export const cacheExamData = async (examGroupId, examData) => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('exams', 'readwrite');
        const store = tx.objectStore('exams');
        
        const data = {
            examGroupId,
            ...examData,
            cachedAt: Date.now()
        };
        
        const request = store.put(data);
        request.onsuccess = () => resolve(true);
        request.onerror = () => reject(request.error);
    });
};

// Get cached exam data
export const getCachedExam = async (examGroupId) => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('exams', 'readonly');
        const store = tx.objectStore('exams');
        const request = store.get(examGroupId);
        
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
};

// Save answer locally
export const saveAnswerOffline = async (answerData) => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('answers', 'readwrite');
        const store = tx.objectStore('answers');
        
        const data = {
            ...answerData,
            synced: false,
            timestamp: Date.now()
        };
        
        const request = store.put(data);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
};

// Get unsynced answers
export const getUnsyncedAnswers = async (examGroupId) => {
    await initDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('answers', 'readonly');
        const store = tx.objectStore('answers');
        const index = store.index('examGroupId');
        const request = index.getAll(examGroupId);
        
        request.onsuccess = () => {
            const unsynced = request.result.filter(a => !a.synced);
            resolve(unsynced);
        };
        request.onerror = () => reject(request.error);
    });
};

// Mark answers as synced
export const markAnswersSynced = async (ids) => {
    await initDB();
    const tx = db.transaction('answers', 'readwrite');
    const store = tx.objectStore('answers');
    
    for (const id of ids) {
        const request = store.get(id);
        request.onsuccess = () => {
            const data = request.result;
            if (data) {
                data.synced = true;
                store.put(data);
            }
        };
    }
};

// Sync answers to server
export const syncAnswersToServer = async (examGroupId) => {
    const unsynced = await getUnsyncedAnswers(examGroupId);
    if (unsynced.length === 0) return { synced: 0 };

    try {
        const response = await fetch('/student/exam-sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ answers: unsynced })
        });

        if (response.ok) {
            await markAnswersSynced(unsynced.map(a => a.id));
            return { synced: unsynced.length };
        }
    } catch (e) {
        console.error('Sync failed:', e);
    }
    
    return { synced: 0, pending: unsynced.length };
};

// Check if online
export const isOnline = () => navigator.onLine;

// Clear exam cache
export const clearExamCache = async (examGroupId) => {
    await initDB();
    const tx = db.transaction(['exams', 'answers'], 'readwrite');
    tx.objectStore('exams').delete(examGroupId);
    
    const answerStore = tx.objectStore('answers');
    const index = answerStore.index('examGroupId');
    const request = index.getAllKeys(examGroupId);
    
    request.onsuccess = () => {
        request.result.forEach(key => answerStore.delete(key));
    };
};

export default {
    initDB,
    cacheExamData,
    getCachedExam,
    saveAnswerOffline,
    getUnsyncedAnswers,
    syncAnswersToServer,
    isOnline,
    clearExamCache
};
