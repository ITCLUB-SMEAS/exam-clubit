import { ref, onUnmounted } from 'vue';

/**
 * Audio Detection Composable
 * Realtime detection, cooldown only for violation trigger
 */
export function useAudioDetection(options = {}) {
    const config = {
        threshold: options.threshold ?? 45,
        sustainedDuration: options.sustainedDuration ?? 2000, // 2 detik sustained = violation
        onSuspiciousAudio: options.onSuspiciousAudio ?? null,
    };

    const isActive = ref(false);
    const audioLevel = ref(0);
    const isSuspicious = ref(false);
    
    let audioContext = null;
    let analyser = null;
    let microphone = null;
    let stream = null;
    let animationFrame = null;
    let lastViolationTime = 0;
    let sustainedStartTime = null;
    let isRunning = false;
    
    // Cooldown hanya untuk TRIGGER VIOLATION, bukan deteksi
    const VIOLATION_COOLDOWN = 30000; // 30 detik antar violation

    const initialize = async () => {
        try {
            if (!navigator.mediaDevices?.getUserMedia) {
                console.warn('Audio detection not supported');
                return false;
            }

            stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            microphone = audioContext.createMediaStreamSource(stream);
            
            analyser.fftSize = 512;
            analyser.smoothingTimeConstant = 0.8;
            microphone.connect(analyser);
            
            isActive.value = true;
            return true;
        } catch (e) {
            console.warn('Audio detection init failed:', e.message);
            isActive.value = false;
            return false;
        }
    };

    const start = () => {
        if (!analyser || !isActive.value || isRunning) return;
        isRunning = true;
        
        const dataArray = new Uint8Array(analyser.frequencyBinCount);
        const sampleRate = audioContext?.sampleRate || 44100;
        const nyquist = sampleRate / 2;
        const binSize = nyquist / analyser.frequencyBinCount;
        
        const minBin = Math.max(0, Math.floor(100 / binSize));
        const maxBin = Math.min(Math.ceil(3000 / binSize), analyser.frequencyBinCount - 1);
        
        if (minBin >= maxBin) {
            console.warn('Invalid frequency range');
            isRunning = false;
            return;
        }
        
        const checkAudio = () => {
            if (!isRunning || !analyser) return;
            
            try {
                analyser.getByteFrequencyData(dataArray);
                
                let sum = 0;
                let count = 0;
                for (let i = minBin; i <= maxBin; i++) {
                    sum += dataArray[i];
                    count++;
                }
                
                const average = count > 0 ? sum / count : 0;
                const level = Math.min(100, Math.round((average / 128) * 100));
                audioLevel.value = level; // Realtime update
                
                const now = Date.now();
                
                if (level > config.threshold) {
                    // Audio above threshold - start/continue counting
                    if (!sustainedStartTime) {
                        sustainedStartTime = now;
                    }
                    
                    // Check if sustained long enough
                    if ((now - sustainedStartTime) >= config.sustainedDuration) {
                        isSuspicious.value = true;
                        
                        // Trigger violation (with cooldown to prevent spam)
                        if ((now - lastViolationTime) > VIOLATION_COOLDOWN && config.onSuspiciousAudio) {
                            config.onSuspiciousAudio(level);
                            lastViolationTime = now;
                        }
                        
                        // Reset sustained timer for next detection
                        sustainedStartTime = now;
                    }
                } else {
                    // Audio dropped - reset
                    sustainedStartTime = null;
                    isSuspicious.value = false;
                }
                
                if (isRunning) {
                    animationFrame = requestAnimationFrame(checkAudio);
                }
            } catch (e) {
                console.warn('Audio check error:', e);
                if (isRunning) {
                    animationFrame = requestAnimationFrame(checkAudio);
                }
            }
        };
        
        checkAudio();
    };

    const stop = () => {
        isRunning = false;
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
        sustainedStartTime = null;
    };

    const cleanup = () => {
        stop();
        
        try {
            if (microphone) {
                microphone.disconnect();
                microphone = null;
            }
            
            if (audioContext && audioContext.state !== 'closed') {
                audioContext.close();
            }
            audioContext = null;
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        } catch (e) {
            console.warn('Audio cleanup error:', e);
        }
        
        analyser = null;
        isActive.value = false;
    };

    onUnmounted(cleanup);

    return {
        initialize,
        start,
        stop,
        cleanup,
        isActive,
        audioLevel,      // Realtime level (0-100)
        isSuspicious,    // true jika sedang ada suara mencurigakan
    };
}
