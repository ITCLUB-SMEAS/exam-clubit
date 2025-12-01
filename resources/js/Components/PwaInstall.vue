<template>
    <div v-if="showPrompt" class="pwa-install-banner">
        <div class="pwa-content">
            <span>📱 Install aplikasi untuk pengalaman lebih baik</span>
            <div class="pwa-buttons">
                <button @click="installPwa" class="btn btn-sm btn-light">Install</button>
                <button @click="dismissPrompt" class="btn btn-sm btn-link text-white">Nanti</button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
    setup() {
        const showPrompt = ref(false);
        let deferredPrompt = null;

        onMounted(() => {
            // Check if already dismissed
            if (localStorage.getItem('pwa-dismissed')) return;

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                showPrompt.value = true;
            });
        });

        const installPwa = async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            showPrompt.value = false;
        };

        const dismissPrompt = () => {
            showPrompt.value = false;
            localStorage.setItem('pwa-dismissed', 'true');
        };

        return { showPrompt, installPwa, dismissPrompt };
    }
}
</script>

<style scoped>
.pwa-install-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
    color: white;
    padding: 12px 20px;
    z-index: 9999;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
}
.pwa-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    flex-wrap: wrap;
    gap: 10px;
}
.pwa-buttons { display: flex; gap: 10px; }
@media (max-width: 576px) {
    .pwa-content { flex-direction: column; text-align: center; }
}
</style>
