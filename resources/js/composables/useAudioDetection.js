import { ref, onUnmounted } from 'vue';

/**
 * Audio Detection Composable
 * Detects suspicious audio (talking, whispering) during exams
 * Focuses on human voice frequencies (85Hz - 3000Hz)
 */
export function useAudioDetection(options = {}) {
    const config = {
        threshold: options.threshold ?? 40,              // Audio level threshold (0-100)
        sustainedDuration: options.sustainedDuration ?? 2000, // ms of sustained audio to trigger
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
    let lastTriggerTime = 0;
    let sustainedStartTime = null;
    const TRIGGER_COOLDOWN = 15000; // 15 seconds between triggers

    const initialize = async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            microphone = audioContext.createMediaStreamSource(stream);
            
            analyser.fftSize = 512; // More frequency resolution
            analyser.smoothingTimeConstant = 0.8;
            microphone.connect(analyser);
            
            isActive.value = true;
            return true;
        } catch (e) {
            console.warn('Audio detection not available:', e.message);
            return false;
        }
    };

    const start = () => {
        if (!analyser || !isActive.value) return;
        
        const dataArray = new Uint8Array(analyser.frequencyBinCount);
        const sampleRate = audioContext.sampleRate;
        const nyquist = sampleRate / 2;
        const binSize = nyquist / analyser.frequencyBinCount;
        
        // Calculate bin indices for human voice range (85Hz - 3000Hz)
        // Ensure we don't go out of bounds
        const minBin = Math.max(0, Math.floor(85 / binSize));
        const maxBin = Math.min(
            Math.ceil(3000 / binSize), 
            analyser.frequencyBinCount - 1
        );
        
        // Validate range
        if (minBin >= maxBin) {
            console.warn('Invalid frequency range for voice detection');
            return;
        }
        
        const checkAudio = () => {
            analyser.getByteFrequencyData(dataArray);
            
            // Calculate average only for voice frequency range
            let sum = 0;
            let count = 0;
            for (let i = minBin; i <= maxBin; i++) {
                sum += dataArray[i];
                count++;
            }
            const average = count > 0 ? sum / count : 0;
            const level = Math.min(100, Math.round((average / 128) * 100));
            audioLevel.value = level;
            
            const now = Date.now();
            
            // Check for sustained audio above threshold
            if (level > config.threshold) {
                if (!sustainedStartTime) {
                    sustainedStartTime = now;
                } else if ((now - sustainedStartTime) >= config.sustainedDuration) {
                    // Sustained audio detected
                    if ((now - lastTriggerTime) > TRIGGER_COOLDOWN) {
                        isSuspicious.value = true;
                        lastTriggerTime = now;
                        
                        if (config.onSuspiciousAudio) {
                            config.onSuspiciousAudio(level);
                        }
                    }
                    sustainedStartTime = null; // Reset for next detection
                }
            } else {
                // Audio dropped below threshold, reset sustained timer
                sustainedStartTime = null;
                isSuspicious.value = false;
            }
            
            animationFrame = requestAnimationFrame(checkAudio);
        };
        
        checkAudio();
    };

    const stop = () => {
        if (animationFrame) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
        sustainedStartTime = null;
    };

    const cleanup = () => {
        stop();
        
        if (microphone) {
            microphone.disconnect();
            microphone = null;
        }
        
        if (audioContext) {
            audioContext.close();
            audioContext = null;
        }
        
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        isActive.value = false;
    };

    onUnmounted(cleanup);

    return {
        initialize,
        start,
        stop,
        cleanup,
        isActive,
        audioLevel,
        isSuspicious,
    };
}
