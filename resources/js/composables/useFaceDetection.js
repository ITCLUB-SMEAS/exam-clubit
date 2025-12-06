import { ref, onUnmounted } from 'vue';

/**
 * Face Detection Composable for Anti-Cheat
 * Detects: no face, multiple faces
 * face-api.js is lazy loaded only when needed
 */
export function useFaceDetection(options = {}) {
    const config = {
        checkInterval: options.checkInterval ?? 20000, // 20 seconds default
        consecutiveThreshold: options.consecutiveThreshold ?? 2, // Consecutive fails before trigger
        onNoFace: options.onNoFace ?? null,
        onMultipleFaces: options.onMultipleFaces ?? null,
        onFaceDetected: options.onFaceDetected ?? null,
        onError: options.onError ?? null,
    };

    const isInitialized = ref(false);
    const isRunning = ref(false);
    const videoElement = ref(null);
    const stream = ref(null);
    const faceCount = ref(0);
    const lastCheckTime = ref(null);
    const consecutiveNoFace = ref(0);
    const consecutiveMultipleFaces = ref(0);

    let checkIntervalId = null;
    let faceapi = null;
    let lastNoFaceTrigger = 0;
    let lastMultipleFacesTrigger = 0;
    const TRIGGER_COOLDOWN = 60000; // 60 seconds cooldown between same violation type

    const loadModels = async () => {
        try {
            const module = await import('face-api.js');
            faceapi = module;
            await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
            return true;
        } catch (error) {
            console.error('Failed to load face detection models:', error);
            if (config.onError) config.onError('Failed to load face detection models');
            return false;
        }
    };

    const startCamera = async (videoEl) => {
        try {
            videoElement.value = videoEl;
            stream.value = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 240, facingMode: 'user' }
            });
            videoEl.srcObject = stream.value;
            await videoEl.play();
            return true;
        } catch (error) {
            console.error('Failed to access camera:', error);
            if (config.onError) config.onError('Tidak dapat mengakses kamera');
            return false;
        }
    };

    const detectFaces = async () => {
        if (!videoElement.value || !isRunning.value || !faceapi) return;

        try {
            const detections = await faceapi.detectAllFaces(
                videoElement.value,
                new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
            );

            faceCount.value = detections.length;
            lastCheckTime.value = new Date();
            const now = Date.now();

            if (detections.length === 0) {
                consecutiveNoFace.value++;
                consecutiveMultipleFaces.value = 0; // Reset other counter
                
                if (consecutiveNoFace.value >= config.consecutiveThreshold) {
                    if ((now - lastNoFaceTrigger) > TRIGGER_COOLDOWN && config.onNoFace) {
                        config.onNoFace();
                        lastNoFaceTrigger = now;
                    }
                    // Don't reset - keep counting for severity tracking
                }
            } else if (detections.length > 1) {
                consecutiveMultipleFaces.value++;
                consecutiveNoFace.value = 0; // Reset other counter
                
                if (consecutiveMultipleFaces.value >= config.consecutiveThreshold) {
                    if ((now - lastMultipleFacesTrigger) > TRIGGER_COOLDOWN && config.onMultipleFaces) {
                        config.onMultipleFaces(detections.length);
                        lastMultipleFacesTrigger = now;
                    }
                }
            } else {
                // Exactly 1 face - all good
                consecutiveNoFace.value = 0;
                consecutiveMultipleFaces.value = 0;
                if (config.onFaceDetected) config.onFaceDetected();
            }
        } catch (error) {
            console.error('Face detection error:', error);
        }
    };

    const initialize = async (videoEl) => {
        const modelsLoaded = await loadModels();
        if (!modelsLoaded) return false;

        const cameraStarted = await startCamera(videoEl);
        if (!cameraStarted) return false;

        isInitialized.value = true;
        return true;
    };

    const start = () => {
        if (!isInitialized.value) return;
        isRunning.value = true;
        // First check after 5 seconds (give time to position)
        setTimeout(detectFaces, 5000);
        checkIntervalId = setInterval(detectFaces, config.checkInterval);
    };

    const stop = () => {
        isRunning.value = false;
        if (checkIntervalId) {
            clearInterval(checkIntervalId);
            checkIntervalId = null;
        }
    };

    const cleanup = () => {
        stop();
        if (stream.value) {
            stream.value.getTracks().forEach(track => track.stop());
            stream.value = null;
        }
        if (videoElement.value) videoElement.value.srcObject = null;
        isInitialized.value = false;
    };

    onUnmounted(cleanup);

    return {
        isInitialized,
        isRunning,
        faceCount,
        lastCheckTime,
        consecutiveNoFace,
        consecutiveMultipleFaces,
        initialize,
        start,
        stop,
        cleanup,
        detectFaces,
    };
}

export default useFaceDetection;
