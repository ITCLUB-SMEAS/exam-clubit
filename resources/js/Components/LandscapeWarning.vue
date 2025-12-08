<template>
    <div v-if="showWarning" class="landscape-warning">
        <div class="landscape-warning-content">
            <i class="fas fa-mobile-alt fa-3x mb-3"></i>
            <h5>Rotasi Layar Terdeteksi</h5>
            <p>Untuk pengalaman terbaik, gunakan mode <strong>Portrait</strong> (vertikal)</p>
            <button @click="dismissWarning" class="btn btn-sm btn-light mt-2">
                Lanjutkan
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useMobileDetection } from '../composables/useMobileDetection';

const { isMobile, isLandscape } = useMobileDetection();
const showWarning = ref(false);
const dismissed = ref(false);

const checkOrientation = () => {
    if (isMobile.value && isLandscape.value && !dismissed.value) {
        showWarning.value = true;
    } else {
        showWarning.value = false;
    }
};

const dismissWarning = () => {
    dismissed.value = true;
    showWarning.value = false;
};

onMounted(() => {
    checkOrientation();
    window.addEventListener('resize', checkOrientation);
    window.addEventListener('orientationchange', checkOrientation);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkOrientation);
    window.removeEventListener('orientationchange', checkOrientation);
});
</script>

<style scoped>
.landscape-warning {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 20px;
}

.landscape-warning-content {
    max-width: 400px;
}

.landscape-warning i {
    color: #ffc107;
}
</style>
