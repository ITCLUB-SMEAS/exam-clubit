import { ref, onUnmounted } from 'vue';

/**
 * Audio Detection Composable
 * Detects suspicious audio levels (talking, whispering) during exams
 */
export function useAudioDetection(options = {}) {
    const config = {
        threshold: options.threshold ?? 30,        // Audio level threshold (0-100)
        silenceDuration: options.silenceDuration ?? 2000,  // ms of silence before reset
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
    const TRIGGER_COOLDOWN = 10000; // 10 seconds between triggers

    const initialize = async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            microphone = audioContext.createMediaStreamSource(stream);
            
            analyser.fftSize = 256;
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
        
        const checkAudio = () => {
            analyser.getByteFrequencyData(dataArray);
            
            // Calculate average audio level
            const average = dataArray.reduce((a, b) => a + b, 0) / dataArray.length;
            const level = Math.min(100, Math.round((average / 128) * 100));
            audioLevel.value = level;
            
            // Check if suspicious
            const now = Date.now();
            if (level > config.threshold && (now - lastTriggerTime) > TRIGGER_COOLDOWN) {
                isSuspicious.value = true;
                lastTriggerTime = now;
                
                if (config.onSuspiciousAudio) {
                    config.onSuspiciousAudio(level);
                }
            } else if (level <= config.threshold) {
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
