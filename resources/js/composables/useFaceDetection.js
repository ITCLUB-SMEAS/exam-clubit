import { ref, onUnmounted } from 'vue';

/**
 * Face Detection Composable for Anti-Cheat using MediaPipe
 * More tolerant settings to reduce false positives
 */
export function useFaceDetection(options = {}) {
    const config = {
        checkInterval: options.checkInterval ?? 30000,
        consecutiveThreshold: options.consecutiveThreshold ?? 5,
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
    let isDetecting = false;
    const TRIGGER_COOLDOWN = 120000;

    const loadModels = async () => {
        try {
            const { FaceDetection } = await import('@mediapipe/face_detection');
            
            faceDetection = new FaceDetection({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`
            });

            faceDetection.setOptions({
                model: 'short',
                minDetectionConfidence: 0.3
            });

            return true;
        } catch (error) {
            console.error('Failed to load face detection:', error);
            if (config.onError) config.onError('Failed to load face detection');
            return false;
        }
    };

    const startCamera = async (videoEl) => {
        try {
            if (!navigator.mediaDevices?.getUserMedia) {
                console.warn('Camera not supported');
                return false;
            }

            videoElement.value = videoEl;
            stream.value = await navigator.mediaDevices.getUserMedia({
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 }, 
                    facingMode: 'user' 
                }
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
        // Prevent concurrent detection
        if (isDetecting || !videoElement.value || !isRunning.value || !faceDetection) return;
        if (videoElement.value.readyState < 2) return;

        isDetecting = true;

        try {
            const detections = await new Promise((resolve) => {
                let resolved = false;
                
                const timeout = setTimeout(() => {
                    if (!resolved) {
                        resolved = true;
                        resolve([{ dummy: true }]); // Timeout - benefit of doubt
                    }
                }, 5000);
                
                const handleResults = (results) => {
                    if (!resolved) {
                        resolved = true;
                        clearTimeout(timeout);
                        resolve(results.detections || []);
                    }
                };
                
                faceDetection.onResults(handleResults);
                
                try {
                    faceDetection.send({ image: videoElement.value });
                } catch (e) {
                    if (!resolved) {
                        resolved = true;
                        clearTimeout(timeout);
                        resolve([{ dummy: true }]); // Error - benefit of doubt
                    }
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
                        consecutiveNoFace.value = 0;
                    }
                }
            } else if (detections.length > 1 && !detections[0].dummy) {
                consecutiveMultipleFaces.value++;
                consecutiveNoFace.value = 0;
                
                if (consecutiveMultipleFaces.value >= config.consecutiveThreshold) {
                    if ((now - lastMultipleFacesTrigger) > TRIGGER_COOLDOWN && config.onMultipleFaces) {
                        config.onMultipleFaces(detections.length);
                        lastMultipleFacesTrigger = now;
                        consecutiveMultipleFaces.value = 0;
                    }
                }
            } else {
                consecutiveNoFace.value = 0;
                consecutiveMultipleFaces.value = 0;
                if (config.onFaceDetected) config.onFaceDetected();
            }
        } catch (error) {
            console.error('Face detection error:', error);
            // On error, don't penalize
            consecutiveNoFace.value = 0;
        } finally {
            isDetecting = false;
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
        
        // First check after 10 seconds
        setTimeout(() => {
            if (isRunning.value) detectFaces();
        }, 10000);
        
        checkIntervalId = setInterval(() => {
            if (isRunning.value) detectFaces();
        }, config.checkInterval);
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
        
        if (videoElement.value) {
            videoElement.value.srcObject = null;
        }
        
        if (faceDetection) {
            try {
                if (typeof faceDetection.close === 'function') {
                    faceDetection.close();
                }
            } catch (e) {
                // Ignore cleanup errors
            }
            faceDetection = null;
        }
        
        isInitialized.value = false;
        isDetecting = false;
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
