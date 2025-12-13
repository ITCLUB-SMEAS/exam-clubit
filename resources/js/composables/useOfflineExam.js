import { ref, onMounted, onUnmounted } from 'vue';
import offlineService from '../services/offlineExamService.js';

export function useOfflineExam(examGroupId) {
    const isOnline = ref(navigator.onLine);
    const isCached = ref(false);
    const syncStatus = ref('idle'); // idle, syncing, synced, error
    const pendingAnswers = ref(0);

    // Monitor online status
    const updateOnlineStatus = () => {
        isOnline.value = navigator.onLine;
        if (isOnline.value && pendingAnswers.value > 0) {
            syncAnswers();
        }
    };

    onMounted(async () => {
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        await offlineService.initDB();
        await checkCacheStatus();
    });

    onUnmounted(() => {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
    });

    // Check if exam is cached
    const checkCacheStatus = async () => {
        const cached = await offlineService.getCachedExam(examGroupId);
        isCached.value = !!cached;
        
        const unsynced = await offlineService.getUnsyncedAnswers(examGroupId);
        pendingAnswers.value = unsynced.length;
    };

    // Cache exam for offline use
    const cacheExam = async (examData) => {
        try {
            await offlineService.cacheExamData(examGroupId, examData);
            isCached.value = true;
            return true;
        } catch (e) {
            console.error('Failed to cache exam:', e);
            return false;
        }
    };

    // Save answer (works offline)
    const saveAnswer = async (answerData) => {
        const data = {
            examGroupId,
            ...answerData,
        };

        // Always save locally first
        await offlineService.saveAnswerOffline(data);
        pendingAnswers.value++;

        // Try to sync if online
        if (isOnline.value) {
            await syncAnswers();
        }
    };

    // Sync pending answers to server
    const syncAnswers = async () => {
        if (syncStatus.value === 'syncing') return;
        
        syncStatus.value = 'syncing';
        try {
            const result = await offlineService.syncAnswersToServer(examGroupId);
            pendingAnswers.value = result.pending || 0;
            syncStatus.value = result.synced > 0 ? 'synced' : 'idle';
        } catch (e) {
            syncStatus.value = 'error';
        }
    };

    // Get cached exam data
    const getCachedExam = async () => {
        return await offlineService.getCachedExam(examGroupId);
    };

    // Clear cache after exam completion
    const clearCache = async () => {
        await offlineService.clearExamCache(examGroupId);
        isCached.value = false;
        pendingAnswers.value = 0;
    };

    return {
        isOnline,
        isCached,
        syncStatus,
        pendingAnswers,
        cacheExam,
        saveAnswer,
        syncAnswers,
        getCachedExam,
        clearCache,
    };
}

export default useOfflineExam;
