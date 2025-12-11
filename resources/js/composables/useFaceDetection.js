import { ref, onUnmounted } from 'vue';

/**
 * Face Detection Composable for Anti-Cheat using MediaPipe
 * Detects: no face, multiple faces
 */
export function useFaceDetection(options = {}) {
    const config = {
        checkInterval: options.checkInterval ?? 20000,
        consecutiveThreshold: options.consecutiveThreshold ?? 2,
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
    let faceDetection = null;
    let lastNoFaceTrigger = 0;
    let lastMultipleFacesTrigger = 0;
    const TRIGGER_COOLDOWN = 60000;

    const loadModels = async () => {
        try {
            const { FaceDetection } = await import('@mediapipe/face_detection');
            
            faceDetection = new FaceDetection({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`
            });

            await faceDetection.setOptions({
                model: 'short',
                minDetectionConfidence: 0.5
            });

            return true;
        } catch (error) {
            console.error('Failed to load MediaPipe face detection:', error);
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
        if (!videoElement.value || !isRunning.value || !faceDetection) return;
        if (videoElement.value.readyState < 2) return;

        try {
            const detections = await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    resolve([]); // Timeout - assume no detection
                }, 5000);
                
                faceDetection.onResults((results) => {
                    clearTimeout(timeout);
                    resolve(results.detections || []);
                });
                
                try {
                    faceDetection.send({ image: videoElement.value });
                } catch (e) {
                    clearTimeout(timeout);
                    reject(e);
                }
            });

            faceCount.value = detections.length;
            lastCheckTime.value = new Date();
            const now = Date.now();

            if (detections.length === 0) {
                consecutiveNoFace.value++;
                consecutiveMultipleFaces.value = 0;
                
                if (consecutiveNoFace.value >= config.consecutiveThreshold) {
                    if ((now - lastNoFaceTrigger) > TRIGGER_COOLDOWN && config.onNoFace) {
                        config.onNoFace();
                        lastNoFaceTrigger = now;
                    }
                }
            } else if (detections.length > 1) {
                consecutiveMultipleFaces.value++;
                consecutiveNoFace.value = 0;
                
                if (consecutiveMultipleFaces.value >= config.consecutiveThreshold) {
                    if ((now - lastMultipleFacesTrigger) > TRIGGER_COOLDOWN && config.onMultipleFaces) {
                        config.onMultipleFaces(detections.length);
                        lastMultipleFacesTrigger = now;
                    }
                }
            } else {
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
        if (faceDetection) {
            try {
                if (typeof faceDetection.close === 'function') {
                    faceDetection.close();
                }
            } catch (e) {
                console.warn('FaceDetection cleanup error:', e);
            }
            faceDetection = null;
        }
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
