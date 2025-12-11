import { ref } from 'vue';

/**
 * Browser Fingerprint Composable
 * Detects if student changes device/browser mid-exam
 */
export function useBrowserFingerprint(options = {}) {
    const config = {
        onDeviceChanged: options.onDeviceChanged ?? null,
        storageKey: options.storageKey ?? 'exam_device_fingerprint',
    };

    const currentFingerprint = ref(null);
    const initialFingerprint = ref(null);
    const deviceChanged = ref(false);

    const generateFingerprint = async () => {
        const components = [];

        // Screen info
        components.push(`${screen.width}x${screen.height}x${screen.colorDepth}`);
        
        // Timezone
        components.push(Intl.DateTimeFormat().resolvedOptions().timeZone);
        
        // Language
        components.push(navigator.language);
        
        // Platform
        components.push(navigator.platform);
        
        // Hardware concurrency (CPU cores)
        components.push(navigator.hardwareConcurrency || 'unknown');
        
        // Device memory (if available)
        components.push(navigator.deviceMemory || 'unknown');
        
        // Touch support
        components.push('ontouchstart' in window ? 'touch' : 'no-touch');
        
        // WebGL renderer (GPU info)
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                if (debugInfo) {
                    components.push(gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL));
                }
            }
        } catch (e) {
            components.push('no-webgl');
        }

        // Canvas fingerprint
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('fingerprint', 2, 2);
            components.push(canvas.toDataURL().slice(-50));
        } catch (e) {
            components.push('no-canvas');
        }

        // Create hash
        const str = components.join('|');
        const hash = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(str));
        return Array.from(new Uint8Array(hash)).map(b => b.toString(16).padStart(2, '0')).join('');
    };

    const initialize = async () => {
        currentFingerprint.value = await generateFingerprint();
        
        // Check if we have a stored fingerprint
        const stored = sessionStorage.getItem(config.storageKey);
        
        if (stored) {
            initialFingerprint.value = stored;
            // Compare fingerprints
            if (stored !== currentFingerprint.value) {
                deviceChanged.value = true;
                config.onDeviceChanged?.({
                    initial: stored,
                    current: currentFingerprint.value
                });
            }
        } else {
            // First time - store fingerprint
            initialFingerprint.value = currentFingerprint.value;
            sessionStorage.setItem(config.storageKey, currentFingerprint.value);
        }

        return !deviceChanged.value;
    };

    const verify = async () => {
        const newFingerprint = await generateFingerprint();
        if (initialFingerprint.value && newFingerprint !== initialFingerprint.value) {
            deviceChanged.value = true;
            config.onDeviceChanged?.({
                initial: initialFingerprint.value,
                current: newFingerprint
            });
            return false;
        }
        return true;
    };

    const getFingerprint = () => currentFingerprint.value;

    return {
        currentFingerprint,
        initialFingerprint,
        deviceChanged,
        initialize,
        verify,
        getFingerprint,
    };
}

export default useBrowserFingerprint;
