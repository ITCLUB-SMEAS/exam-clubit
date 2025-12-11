import { ref, onUnmounted } from 'vue';

/**
 * Liveness Detection - Verify real person with random challenges
 * Challenges: blink, turn head left/right, nod
 */
export function useLivenessDetection(options = {}) {
    const config = {
        challengeInterval: options.challengeInterval ?? 300000, // 5 minutes
        challengeTimeout: options.challengeTimeout ?? 15000, // 15 seconds to complete
        onChallengeFailed: options.onChallengeFailed ?? null,
        onChallengeSuccess: options.onChallengeSuccess ?? null,
    };

    const isActive = ref(false);
    const currentChallenge = ref(null);
    const showChallengeModal = ref(false);
    const challengeCountdown = ref(0);
    const challengesPassed = ref(0);
    const challengesFailed = ref(0);

    let challengeIntervalId = null;
    let countdownIntervalId = null;
    let detectionLoopId = null;
    let faceDetection = null;
    let videoElement = null;

    const challenges = [
        { type: 'blink', instruction: 'Kedipkan mata Anda 2 kali', detectFn: 'detectBlink' },
        { type: 'turn_left', instruction: 'Putar kepala ke KIRI', detectFn: 'detectTurnLeft' },
        { type: 'turn_right', instruction: 'Putar kepala ke KANAN', detectFn: 'detectTurnRight' },
        { type: 'nod', instruction: 'Anggukkan kepala Anda', detectFn: 'detectNod' },
    ];

    // Track for detection
    let faceHistory = [];
    let blinkCount = 0;
    let lastEyeState = 'open';

    const loadFaceMesh = async () => {
        try {
            const { FaceMesh } = await import('@mediapipe/face_mesh');
            
            faceDetection = new FaceMesh({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
            });

            faceDetection.setOptions({
                maxNumFaces: 1,
                refineLandmarks: true,
                minDetectionConfidence: 0.5,
                minTrackingConfidence: 0.5
            });

            // Set onResults once during initialization
            faceDetection.onResults(handleFaceResults);

            return true;
        } catch (error) {
            console.error('Failed to load FaceMesh:', error);
            return false;
        }
    };

    const handleFaceResults = (results) => {
        if (!showChallengeModal.value || !currentChallenge.value) return;

        if (results.multiFaceLandmarks && results.multiFaceLandmarks.length > 0) {
            const landmarks = results.multiFaceLandmarks[0];
            const detectFn = currentChallenge.value.detectFn;
            
            let success = false;
            if (detectFn === 'detectBlink') success = detectBlink(landmarks);
            else if (detectFn === 'detectTurnLeft') success = detectTurnLeft(landmarks);
            else if (detectFn === 'detectTurnRight') success = detectTurnRight(landmarks);
            else if (detectFn === 'detectNod') success = detectNod(landmarks);

            if (success) {
                passChallenge();
            }
        }
    };

    const detectBlink = (landmarks) => {
        if (!landmarks || landmarks.length === 0) return false;
        
        const leftEyeTop = landmarks[159];
        const leftEyeBottom = landmarks[145];
        const rightEyeTop = landmarks[386];
        const rightEyeBottom = landmarks[374];

        const leftEyeOpen = Math.abs(leftEyeTop.y - leftEyeBottom.y);
        const rightEyeOpen = Math.abs(rightEyeTop.y - rightEyeBottom.y);
        const avgEyeOpen = (leftEyeOpen + rightEyeOpen) / 2;

        const isEyeClosed = avgEyeOpen < 0.02; // Adjusted threshold
        
        if (isEyeClosed && lastEyeState === 'open') {
            blinkCount++;
            lastEyeState = 'closed';
        } else if (!isEyeClosed) {
            lastEyeState = 'open';
        }

        return blinkCount >= 2;
    };

    const detectTurnLeft = (landmarks) => {
        if (!landmarks || landmarks.length === 0) return false;
        
        const nose = landmarks[1];
        const leftCheek = landmarks[234];
        const rightCheek = landmarks[454];
        
        const faceWidth = Math.abs(rightCheek.x - leftCheek.x);
        if (faceWidth < 0.01) return false;
        
        const noseOffset = (nose.x - leftCheek.x) / faceWidth;
        return noseOffset < 0.35;
    };

    const detectTurnRight = (landmarks) => {
        if (!landmarks || landmarks.length === 0) return false;
        
        const nose = landmarks[1];
        const leftCheek = landmarks[234];
        const rightCheek = landmarks[454];
        
        const faceWidth = Math.abs(rightCheek.x - leftCheek.x);
        if (faceWidth < 0.01) return false;
        
        const noseOffset = (nose.x - leftCheek.x) / faceWidth;
        return noseOffset > 0.65;
    };

    const detectNod = (landmarks) => {
        if (!landmarks || landmarks.length === 0) return false;
        
        const nose = landmarks[1];
        faceHistory.push({ y: nose.y, time: Date.now() });
        
        if (faceHistory.length > 30) faceHistory.shift();
        if (faceHistory.length < 10) return false;
        
        const yValues = faceHistory.map(f => f.y);
        const minY = Math.min(...yValues);
        const maxY = Math.max(...yValues);
        
        return (maxY - minY) > 0.04;
    };

    const resetDetectionState = () => {
        blinkCount = 0;
        faceHistory = [];
        lastEyeState = 'open';
    };

    const startChallenge = () => {
        if (!isActive.value || !faceDetection || !videoElement) return;

        const randomChallenge = challenges[Math.floor(Math.random() * challenges.length)];
        currentChallenge.value = randomChallenge;
        showChallengeModal.value = true;
        challengeCountdown.value = Math.floor(config.challengeTimeout / 1000);
        resetDetectionState();

        // Countdown timer
        countdownIntervalId = setInterval(() => {
            challengeCountdown.value--;
            if (challengeCountdown.value <= 0) {
                failChallenge();
            }
        }, 1000);

        // Detection loop (throttled to ~10fps)
        const runDetection = () => {
            if (!showChallengeModal.value) return;

            try {
                if (videoElement && videoElement.readyState >= 2) {
                    faceDetection.send({ image: videoElement });
                }
            } catch (e) {
                console.warn('Detection send error:', e);
            }

            detectionLoopId = setTimeout(runDetection, 100);
        };

        runDetection();
    };

    const passChallenge = () => {
        stopChallengeTimers();
        showChallengeModal.value = false;
        currentChallenge.value = null;
        challengesPassed.value++;
        
        if (config.onChallengeSuccess) {
            config.onChallengeSuccess();
        }
    };

    const failChallenge = () => {
        stopChallengeTimers();
        showChallengeModal.value = false;
        currentChallenge.value = null;
        challengesFailed.value++;
        
        if (config.onChallengeFailed) {
            config.onChallengeFailed();
        }
    };

    const stopChallengeTimers = () => {
        if (countdownIntervalId) {
            clearInterval(countdownIntervalId);
            countdownIntervalId = null;
        }
        if (detectionLoopId) {
            clearTimeout(detectionLoopId);
            detectionLoopId = null;
        }
    };

    const initialize = async (videoEl) => {
        if (!videoEl) return false;
        
        videoElement = videoEl;
        const loaded = await loadFaceMesh();
        if (!loaded) return false;
        
        isActive.value = true;
        return true;
    };

    const start = () => {
        if (!isActive.value) return;
        
        // First challenge after 2 minutes
        setTimeout(() => {
            if (isActive.value) startChallenge();
        }, 120000);
        
        // Then every interval
        challengeIntervalId = setInterval(() => {
            if (isActive.value && !showChallengeModal.value) {
                startChallenge();
            }
        }, config.challengeInterval);
    };

    const stop = () => {
        isActive.value = false;
        showChallengeModal.value = false;
        stopChallengeTimers();
        
        if (challengeIntervalId) {
            clearInterval(challengeIntervalId);
            challengeIntervalId = null;
        }
    };

    const cleanup = () => {
        stop();
        if (faceDetection) {
            try {
                faceDetection.close();
            } catch (e) {}
            faceDetection = null;
        }
        videoElement = null;
    };

    onUnmounted(cleanup);

    return {
        isActive,
        currentChallenge,
        showChallengeModal,
        challengeCountdown,
        challengesPassed,
        challengesFailed,
        initialize,
        start,
        stop,
        cleanup,
        startChallenge, // For manual testing
    };
}

export default useLivenessDetection;
