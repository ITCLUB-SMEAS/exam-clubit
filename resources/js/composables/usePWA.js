import { ref, onMounted } from 'vue';

const deferredPrompt = ref(null);
const isInstallable = ref(false);
const isInstalled = ref(false);

export function usePWA() {
    onMounted(() => {
        // Check if already installed
        isInstalled.value = window.matchMedia('(display-mode: standalone)').matches;

        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt.value = e;
            isInstallable.value = true;
        });

        // Listen for successful install
        window.addEventListener('appinstalled', () => {
            isInstalled.value = true;
            isInstallable.value = false;
            deferredPrompt.value = null;
        });
    });

    const install = async () => {
        if (!deferredPrompt.value) return false;
        
        deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;
        deferredPrompt.value = null;
        isInstallable.value = false;
        
        return outcome === 'accepted';
    };

    return { isInstallable, isInstalled, install };
}
