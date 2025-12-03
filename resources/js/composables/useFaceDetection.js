import { ref, onUnmounted } from 'vue';
import * as faceapi from 'face-api.js';

/**
 * Face Detection Composable for Anti-Cheat
 * Detects: no face, multiple faces
 */
export function useFaceDetection(options = {}) {
    const config = {
        checkInterval: options.checkInterval ?? 30000, // 30 seconds
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

    let checkIntervalId = null;

    /**
     * Load face-api models
     */
    const loadModels = async () => {
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
            return true;
        } catch (error) {
            console.error('Failed to load face detection models:', error);
            if (config.onError) config.onError('Failed to load face detection models');
            return false;
        }
    };

    /**
     * Start webcam
     */
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

    /**
     * Detect faces in current frame
     */
    const detectFaces = async () => {
        if (!videoElement.value || !isRunning.value) return;

        try {
            const detections = await faceapi.detectAllFaces(
                videoElement.value,
                new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
            );

            faceCount.value = detections.length;
            lastCheckTime.value = new Date();

            if (detections.length === 0) {
                consecutiveNoFace.value++;
                // Only trigger after 2 consecutive no-face detections
                if (consecutiveNoFace.value >= 2 && config.onNoFace) {
                    config.onNoFace();
                    consecutiveNoFace.value = 0;
                }
            } else if (detections.length > 1) {
                consecutiveNoFace.value = 0;
                if (config.onMultipleFaces) {
                    config.onMultipleFaces(detections.length);
                }
            } else {
                consecutiveNoFace.value = 0;
                if (config.onFaceDetected) {
                    config.onFaceDetected();
                }
            }
        } catch (error) {
            console.error('Face detection error:', error);
        }
    };

    /**
     * Initialize face detection
     */
    const initialize = async (videoEl) => {
        const modelsLoaded = await loadModels();
        if (!modelsLoaded) return false;

        const cameraStarted = await startCamera(videoEl);
        if (!cameraStarted) return false;

        isInitialized.value = true;
        return true;
    };

    /**
     * Start periodic face detection
     */
    const start = () => {
        if (!isInitialized.value) return;
        
        isRunning.value = true;
        // Initial check after 5 seconds
        setTimeout(detectFaces, 5000);
        // Then check every interval
        checkIntervalId = setInterval(detectFaces, config.checkInterval);
    };

    /**
     * Stop face detection
     */
    const stop = () => {
        isRunning.value = false;
        if (checkIntervalId) {
            clearInterval(checkIntervalId);
            checkIntervalId = null;
        }
    };

    /**
     * Cleanup resources
     */
    const cleanup = () => {
        stop();
        if (stream.value) {
            stream.value.getTracks().forEach(track => track.stop());
            stream.value = null;
        }
        if (videoElement.value) {
            videoElement.value.srcObject = null;
        }
        isInitialized.value = false;
    };

    onUnmounted(cleanup);

    return {
        isInitialized,
        isRunning,
        faceCount,
        lastCheckTime,
        initialize,
        start,
        stop,
        cleanup,
        detectFaces,
    };
}

export default useFaceDetection;
