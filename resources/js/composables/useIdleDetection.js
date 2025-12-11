import { ref, onUnmounted } from 'vue';

/**
 * Idle Detection Composable
 * Detects when student is AFK (away from keyboard) too long
 */
export function useIdleDetection(options = {}) {
    const config = {
        idleThreshold: options.idleThreshold ?? 60000, // 60 seconds default
        warningThreshold: options.warningThreshold ?? 30000, // 30 seconds warning
        onIdle: options.onIdle ?? null,
        onIdleWarning: options.onIdleWarning ?? null,
        onActive: options.onActive ?? null,
    };

    const isIdle = ref(false);
    const isWarning = ref(false);
    const idleTime = ref(0);
    const lastActivity = ref(Date.now());
    const totalIdleTime = ref(0);
    const idleCount = ref(0);

    let idleTimer = null;
    let checkInterval = null;
    let idleDetector = null;
    let useNativeAPI = false;

    const resetIdleTimer = () => {
        lastActivity.value = Date.now();
        
        if (isIdle.value) {
            isIdle.value = false;
            isWarning.value = false;
            config.onActive?.();
        }
    };

    const handleActivity = () => {
        resetIdleTimer();
    };

    const checkIdleStatus = () => {
        const now = Date.now();
        const elapsed = now - lastActivity.value;
        idleTime.value = elapsed;

        if (!isWarning.value && elapsed >= config.warningThreshold && elapsed < config.idleThreshold) {
            isWarning.value = true;
            config.onIdleWarning?.(elapsed);
        }

        if (!isIdle.value && elapsed >= config.idleThreshold) {
            isIdle.value = true;
            idleCount.value++;
            totalIdleTime.value += elapsed;
            config.onIdle?.(elapsed);
        }
    };

    // Try to use native IdleDetector API (Chrome 94+)
    const initNativeIdleDetector = async () => {
        if (!('IdleDetector' in window)) return false;

        try {
            const permission = await IdleDetector.requestPermission();
            if (permission !== 'granted') return false;

            idleDetector = new IdleDetector();
            
            idleDetector.addEventListener('change', () => {
                const { userState, screenState } = idleDetector;
                
                if (userState === 'idle' || screenState === 'locked') {
                    if (!isIdle.value) {
                        isIdle.value = true;
                        idleCount.value++;
                        config.onIdle?.(config.idleThreshold);
                    }
                } else {
                    if (isIdle.value) {
                        isIdle.value = false;
                        isWarning.value = false;
                        config.onActive?.();
                    }
                    lastActivity.value = Date.now();
                }
            });

            await idleDetector.start({
                threshold: config.idleThreshold,
            });

            useNativeAPI = true;
            return true;
        } catch (e) {
            console.warn('Native IdleDetector failed:', e);
            return false;
        }
    };

    // Fallback: manual event-based detection
    const initManualDetection = () => {
        const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, handleActivity, { passive: true });
        });

        // Check idle status every second
        checkInterval = setInterval(checkIdleStatus, 1000);
    };

    const start = async () => {
        // Try native API first
        const nativeSuccess = await initNativeIdleDetector();
        
        if (!nativeSuccess) {
            // Fallback to manual detection
            initManualDetection();
        }

        lastActivity.value = Date.now();
    };

    const stop = () => {
        // Stop native detector
        if (idleDetector) {
            idleDetector.stop();
            idleDetector = null;
        }

        // Stop manual detection
        if (checkInterval) {
            clearInterval(checkInterval);
            checkInterval = null;
        }

        const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.removeEventListener(event, handleActivity);
        });
    };

    const getStats = () => ({
        isIdle: isIdle.value,
        idleTime: idleTime.value,
        totalIdleTime: totalIdleTime.value,
        idleCount: idleCount.value,
        lastActivity: lastActivity.value,
    });

    onUnmounted(stop);

    return {
        isIdle,
        isWarning,
        idleTime,
        lastActivity,
        totalIdleTime,
        idleCount,
        start,
        stop,
        resetIdleTimer,
        getStats,
    };
}

export default useIdleDetection;
