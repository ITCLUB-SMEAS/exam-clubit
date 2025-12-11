<template>
    <Teleport to="body">
        <div v-if="showWarning" class="session-warning-overlay">
            <div class="session-warning-modal">
                <div class="warning-icon">
                    <i class="fa fa-clock"></i>
                </div>
                <h4>Sesi Akan Berakhir</h4>
                <p>Sesi Anda akan berakhir dalam:</p>
                <div class="countdown">
                    <span class="time">{{ remainingMinutes }}</span>
                    <span class="label">menit</span>
                    <span class="time">{{ remainingSeconds.toString().padStart(2, '0') }}</span>
                    <span class="label">detik</span>
                </div>
                <p class="text-muted small">Klik tombol di bawah untuk memperpanjang sesi.</p>
                <button @click="extendSession" class="btn btn-primary">
                    <i class="fa fa-refresh me-1"></i>
                    Perpanjang Sesi
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { useSessionTimeout } from '../composables/useSessionTimeout';

const props = defineProps({
    sessionLifetime: { type: Number, default: 120 },
    warningMinutes: { type: Number, default: 5 },
});

const { showWarning, remainingMinutes, remainingSeconds, extendSession } = useSessionTimeout({
    sessionLifetime: props.sessionLifetime,
    warningMinutes: props.warningMinutes,
});
</script>

<style scoped>
.session-warning-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.session-warning-modal {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.warning-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 1rem;
}

.countdown {
    display: flex;
    justify-content: center;
    align-items: baseline;
    gap: 0.5rem;
    margin: 1.5rem 0;
}

.countdown .time {
    font-size: 2.5rem;
    font-weight: bold;
    color: #dc2626;
}

.countdown .label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-right: 1rem;
}
</style>
