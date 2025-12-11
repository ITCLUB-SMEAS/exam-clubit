import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useSessionTimeout(options = {}) {
    const {
        warningMinutes = 5,
        sessionLifetime = 120, // default Laravel session lifetime
        onWarning = null,
        onExpired = null,
    } = options;

    const showWarning = ref(false);
    const remainingMinutes = ref(0);
    const remainingSeconds = ref(0);

    let activityTimer = null;
    let countdownTimer = null;
    let lastActivity = Date.now();

    const warningTime = (sessionLifetime - warningMinutes) * 60 * 1000;
    const sessionTime = sessionLifetime * 60 * 1000;

    const resetTimer = () => {
        lastActivity = Date.now();
        showWarning.value = false;
    };

    const checkSession = () => {
        const elapsed = Date.now() - lastActivity;
        const remaining = sessionTime - elapsed;

        if (remaining <= 0) {
            showWarning.value = false;
            if (onExpired) onExpired();
            else window.location.href = '/';
            return;
        }

        if (elapsed >= warningTime && !showWarning.value) {
            showWarning.value = true;
            if (onWarning) onWarning();
            startCountdown(remaining);
        }
    };

    const startCountdown = (remaining) => {
        const updateCountdown = () => {
            const now = Date.now();
            const timeLeft = sessionTime - (now - lastActivity);
            
            if (timeLeft <= 0) {
                showWarning.value = false;
                if (onExpired) onExpired();
                else window.location.href = '/';
                return;
            }

            remainingMinutes.value = Math.floor(timeLeft / 60000);
            remainingSeconds.value = Math.floor((timeLeft % 60000) / 1000);
        };

        updateCountdown();
        countdownTimer = setInterval(updateCountdown, 1000);
    };

    const extendSession = async () => {
        try {
            await fetch('/student/session/extend', { 
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': usePage().props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                }
            });
            resetTimer();
            if (countdownTimer) clearInterval(countdownTimer);
        } catch (e) {
            console.error('Failed to extend session:', e);
        }
    };

    const handleActivity = () => {
        if (!showWarning.value) {
            lastActivity = Date.now();
        }
    };

    onMounted(() => {
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        events.forEach(e => document.addEventListener(e, handleActivity, { passive: true }));
        
        activityTimer = setInterval(checkSession, 30000); // check every 30s
    });

    onUnmounted(() => {
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        events.forEach(e => document.removeEventListener(e, handleActivity));
        
        if (activityTimer) clearInterval(activityTimer);
        if (countdownTimer) clearInterval(countdownTimer);
    });

    return {
        showWarning,
        remainingMinutes,
        remainingSeconds,
        extendSession,
        resetTimer,
    };
}
