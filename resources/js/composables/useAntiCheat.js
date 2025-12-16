import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

/**
 * Anti-Cheat Composable for Vue 3
 *
 * This composable provides comprehensive anti-cheat functionality for online exams:
 * - Tab/Window switching detection
 * - Fullscreen enforcement (desktop only)
 * - Copy/Paste prevention
 * - Right-click prevention
 * - DevTools detection
 * - Keyboard shortcut blocking
 * - Window blur detection
 * - Mobile-friendly adaptations
 */
export function useAntiCheat(options = {}) {
    // Detect mobile device
    const isMobileDevice = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(
        navigator.userAgent.toLowerCase()
    ) || window.innerWidth < 768;

    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent.toLowerCase());

    // Configuration with defaults (mobile-aware)
    const config = ref({
        enabled: options.enabled ?? true,
        fullscreenRequired: (options.fullscreenRequired ?? true) && !isMobileDevice, // Disable on mobile
        blockTabSwitch: options.blockTabSwitch ?? true,
        blockCopyPaste: options.blockCopyPaste ?? true,
        blockRightClick: (options.blockRightClick ?? true) && !isMobileDevice, // Not relevant on mobile
        blockMultipleMonitors: !isMobileDevice, // Only desktop
        blockVirtualMachine: !isMobileDevice,   // Only desktop
        detectDevtools: (options.detectDevtools ?? true) && !isMobileDevice,
        maxViolations: options.maxViolations ?? 3,
        warningThreshold: options.warningThreshold ?? 2,
        autoSubmitOnMaxViolations: options.autoSubmitOnMaxViolations ?? false,
        examId: options.examId ?? null,
        examSessionId: options.examSessionId ?? null,
        gradeId: options.gradeId ?? null,
        externalVideoElement: options.externalVideoElement ?? null,
        onViolation: options.onViolation ?? null,
        onWarningThreshold: options.onWarningThreshold ?? null,
        onMaxViolations: options.onMaxViolations ?? null,
        onAutoSubmit: options.onAutoSubmit ?? null,
        onBlocked: options.onBlocked ?? null,
        onBlockedEnvironment: options.onBlockedEnvironment ?? null,
        onDuplicateTab: options.onDuplicateTab ?? null,
        isMobile: isMobileDevice,
        isIOS: isIOS,
    });

    // State
    const isFullscreen = ref(false);
    const violationCount = ref(0);
    const remainingViolations = ref(config.value.maxViolations);
    const warningReached = ref(false);
    const isInitialized = ref(false);
    const pendingViolations = ref([]);
    const lastViolationTime = ref({});

    // Debounce threshold (ms) to prevent duplicate violations
    const VIOLATION_DEBOUNCE = 2000;

    // Multiple monitor detection state
    const hasMultipleMonitors = ref(false);
    const screenCount = ref(1);

    // Virtual machine detection state
    const isVirtualMachine = ref(false);

    // Blocked environment state
    const isBlockedEnvironment = ref(false);
    const blockedReason = ref('');

    // Fullscreen warning counter (warn first, then violation)
    const fullscreenExitCount = ref(0);

    // Window Focus Duration tracking
    const windowFocused = ref(true);
    const blurStartTime = ref(null);
    const totalBlurDuration = ref(0); // Total time unfocused in ms
    const blurCount = ref(0); // Number of times window lost focus
    const BLUR_WARNING_THRESHOLD = 10000; // 10 seconds unfocused triggers warning
    const BLUR_VIOLATION_THRESHOLD = 30000; // 30 seconds total unfocused triggers violation

    // Video stream reference for snapshot
    const videoStream = ref(null);
    const videoElement = ref(null);

    /**
     * Check if a violation should be recorded (debouncing)
     */
    const shouldRecordViolation = (type) => {
        const now = Date.now();
        const lastTime = lastViolationTime.value[type] || 0;

        if (now - lastTime < VIOLATION_DEBOUNCE) {
            return false;
        }

        lastViolationTime.value[type] = now;
        return true;
    };

    /**
     * Capture snapshot from webcam (HD quality)
     * Returns base64 image or null if capture fails
     */
    const captureSnapshot = () => {
        // Use external video element (from face detection) if available
        const video = config.value.externalVideoElement?.value || videoElement.value;
        if (!video || (!videoStream.value && !config.value.externalVideoElement)) {
            console.warn('Snapshot capture skipped: no video source available');
            return null;
        }

        try {
            const canvas = document.createElement('canvas');
            // HD resolution (720p)
            canvas.width = 1280;
            canvas.height = 720;
            const ctx = canvas.getContext('2d');

            // Check if video is ready
            if (video.readyState < 2) {
                console.warn('Snapshot capture skipped: video not ready');
                return null;
            }

            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85); // 85% quality for clear image
        } catch (e) {
            console.error('Snapshot capture failed:', e.message);
            return null;
        }
    };

    /**
     * Initialize video stream for snapshots
     */
    const initVideoStream = async () => {
        // Skip if external video element is provided (e.g., from face detection)
        if (config.value.externalVideoElement) {
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 240, facingMode: 'user' }
            });
            videoStream.value = stream;

            // Create hidden video element
            const video = document.createElement('video');
            video.srcObject = stream;
            video.autoplay = true;
            video.playsInline = true;
            video.style.display = 'none';
            document.body.appendChild(video);
            videoElement.value = video;
        } catch (e) {
            console.warn('Could not init video for snapshots:', e);
        }
    };

    /**
     * Record a violation
     */
    const recordViolation = async (type, description = null, metadata = null) => {
        if (!config.value.enabled) return;
        if (!shouldRecordViolation(type)) return;

        // Capture snapshot
        const snapshot = captureSnapshot();

        // Store previous values for rollback on failure
        const prevViolationCount = violationCount.value;
        const prevRemainingViolations = remainingViolations.value;
        const prevWarningReached = warningReached.value;

        // Optimistically increment local counter
        violationCount.value++;
        remainingViolations.value = Math.max(0, config.value.maxViolations - violationCount.value);

        // Call onViolation callback if provided
        if (config.value.onViolation) {
            config.value.onViolation({
                type,
                description,
                totalViolations: violationCount.value,
                remaining: remainingViolations.value,
            });
        }

        // Check warning threshold
        if (violationCount.value >= config.value.warningThreshold && !warningReached.value) {
            warningReached.value = true;
            if (config.value.onWarningThreshold) {
                config.value.onWarningThreshold({
                    totalViolations: violationCount.value,
                    remaining: remainingViolations.value,
                });
            }
        }

        // Check max violations
        if (violationCount.value >= config.value.maxViolations) {
            if (config.value.onMaxViolations) {
                config.value.onMaxViolations({
                    totalViolations: violationCount.value,
                });
            }

            if (config.value.autoSubmitOnMaxViolations && config.value.onAutoSubmit) {
                config.value.onAutoSubmit();
            }
        }

        // Send to server if credentials are provided
        if (config.value.examId && config.value.examSessionId && config.value.gradeId) {
            try {
                const response = await axios.post('/student/anticheat/violation', {
                    exam_id: config.value.examId,
                    exam_session_id: config.value.examSessionId,
                    grade_id: config.value.gradeId,
                    violation_type: type,
                    description: description,
                    metadata: metadata,
                    snapshot: snapshot,
                }, {
                    headers: {
                        'X-Session-ID': sessionStorage.getItem('exam_session_id') || Math.random().toString(36)
                    }
                });

                // Sync counter from server response (source of truth)
                if (response.data?.success && response.data?.data) {
                    violationCount.value = response.data.data.total_violations ?? violationCount.value;
                    remainingViolations.value = response.data.data.remaining_violations ?? remainingViolations.value;
                    warningReached.value = response.data.data.warning_reached ?? warningReached.value;
                }

                // Check if student got blocked
                if (response.data?.data?.is_blocked && config.value.onBlocked) {
                    config.value.onBlocked();
                }

                // Try to send pending violations after successful request
                retryPendingViolations();
            } catch (error) {
                console.error('Failed to record violation:', error);

                // Rollback optimistic update on failure
                violationCount.value = prevViolationCount;
                remainingViolations.value = prevRemainingViolations;
                warningReached.value = prevWarningReached;

                // Queue for retry (max 10 pending)
                if (pendingViolations.value.length < 10) {
                    pendingViolations.value.push({ type, description, metadata, timestamp: Date.now() });
                }
            }
        }
    };

    /**
     * Retry sending pending violations
     */
    const retryPendingViolations = async () => {
        if (pendingViolations.value.length === 0) return;

        const toRetry = [...pendingViolations.value];
        pendingViolations.value = [];

        for (const v of toRetry) {
            try {
                await axios.post('/student/anticheat/violation', {
                    exam_id: config.value.examId,
                    exam_session_id: config.value.examSessionId,
                    grade_id: config.value.gradeId,
                    violation_type: v.type,
                    description: v.description,
                    metadata: v.metadata,
                });
            } catch (e) {
                // Re-queue if still failing (only if not too old - 5 min max)
                if (Date.now() - v.timestamp < 300000) {
                    pendingViolations.value.push(v);
                }
            }
        }
    };

    /**
     * Handle visibility change (tab switching)
     */
    const handleVisibilityChange = () => {
        if (!config.value.blockTabSwitch) return;

        if (document.hidden) {
            recordViolation('tab_switch', 'Siswa berpindah ke tab lain');
        }
    };

    /**
     * Handle window blur (lost focus)
     */
    const handleWindowBlur = () => {
        if (!config.value.blockTabSwitch) return;

        windowFocused.value = false;
        blurStartTime.value = Date.now();
        blurCount.value++;

        recordViolation('blur', 'Window ujian kehilangan fokus');

        // Start checking blur duration
        startBlurDurationCheck();
    };

    /**
     * Handle window focus (regained focus)
     */
    const handleWindowFocus = () => {
        if (!windowFocused.value && blurStartTime.value) {
            const blurDuration = Date.now() - blurStartTime.value;
            totalBlurDuration.value += blurDuration;

            // Record extended blur as separate violation if too long
            if (blurDuration >= BLUR_WARNING_THRESHOLD) {
                recordViolation('extended_blur',
                    `Window tidak fokus selama ${Math.round(blurDuration / 1000)} detik`,
                    { duration: blurDuration, totalBlurTime: totalBlurDuration.value }
                );
            }

            // Check if total blur time exceeded threshold
            if (totalBlurDuration.value >= BLUR_VIOLATION_THRESHOLD) {
                recordViolation('excessive_blur',
                    `Total waktu tidak fokus: ${Math.round(totalBlurDuration.value / 1000)} detik`,
                    { totalBlurTime: totalBlurDuration.value, blurCount: blurCount.value }
                );
            }
        }

        windowFocused.value = true;
        blurStartTime.value = null;
        stopBlurDurationCheck();
    };

    // Blur duration check interval
    let blurCheckInterval = null;

    const startBlurDurationCheck = () => {
        if (blurCheckInterval) return;

        blurCheckInterval = setInterval(() => {
            if (!windowFocused.value && blurStartTime.value) {
                const currentBlurDuration = Date.now() - blurStartTime.value;

                // Warn every 10 seconds while unfocused
                if (currentBlurDuration >= BLUR_WARNING_THRESHOLD &&
                    currentBlurDuration % BLUR_WARNING_THRESHOLD < 1000) {
                    recordViolation('prolonged_blur',
                        `Window tidak fokus selama ${Math.round(currentBlurDuration / 1000)} detik`,
                        { currentDuration: currentBlurDuration }
                    );
                }
            }
        }, 5000); // Check every 5 seconds
    };

    const stopBlurDurationCheck = () => {
        if (blurCheckInterval) {
            clearInterval(blurCheckInterval);
            blurCheckInterval = null;
        }
    };

    /**
     * Handle fullscreen change
     */
    const handleFullscreenChange = () => {
        isFullscreen.value = !!document.fullscreenElement;

        if (!isFullscreen.value && config.value.fullscreenRequired && isInitialized.value) {
            fullscreenExitCount.value++;

            // First exit = warning only, subsequent exits = violation
            if (fullscreenExitCount.value === 1) {
                // Just show warning via onViolation callback without recording
                if (config.value.onViolation) {
                    config.value.onViolation({
                        type: 'fullscreen_warning',
                        description: 'Peringatan: Jangan keluar dari mode fullscreen! Pelanggaran berikutnya akan dicatat.',
                        isWarningOnly: true
                    });
                }
            } else {
                recordViolation('fullscreen_exit', 'Siswa keluar dari mode fullscreen');
            }
        }
    };

    /**
     * Handle copy event
     */
    const handleCopy = (e) => {
        if (!config.value.blockCopyPaste) return;

        e.preventDefault();
        recordViolation('copy_paste', 'Siswa mencoba melakukan copy');
    };

    /**
     * Handle paste event
     */
    const handlePaste = (e) => {
        if (!config.value.blockCopyPaste) return;

        e.preventDefault();
        recordViolation('copy_paste', 'Siswa mencoba melakukan paste');
    };

    /**
     * Handle cut event
     */
    const handleCut = (e) => {
        if (!config.value.blockCopyPaste) return;

        e.preventDefault();
        recordViolation('copy_paste', 'Siswa mencoba melakukan cut');
    };

    /**
     * Handle right click
     */
    const handleContextMenu = (e) => {
        if (!config.value.blockRightClick) return;

        e.preventDefault();
        recordViolation('right_click', 'Siswa mencoba klik kanan');
        return false;
    };

    /**
     * Handle keyboard shortcuts
     */
    const handleKeyDown = (e) => {
        // Detect screenshot keys first (PrintScreen, etc.)
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            recordViolation('screenshot', 'Siswa menekan tombol Print Screen');
            return false;
        }

        // Windows Snipping Tool: Win+Shift+S (metaKey = Windows key)
        if (e.metaKey && e.shiftKey && e.key.toUpperCase() === 'S') {
            e.preventDefault();
            recordViolation('screenshot', 'Siswa mencoba membuka Snipping Tool (Win+Shift+S)');
            return false;
        }

        // Block dangerous keyboard shortcuts
        const blockedCombinations = [
            // DevTools
            { key: 'F12', ctrl: false, shift: false, alt: false },
            { key: 'I', ctrl: true, shift: true, alt: false },
            { key: 'J', ctrl: true, shift: true, alt: false },
            { key: 'C', ctrl: true, shift: true, alt: false },
            { key: 'U', ctrl: true, shift: false, alt: false }, // View source

            // Copy/Paste (if blocked)
            ...(config.value.blockCopyPaste ? [
                { key: 'C', ctrl: true, shift: false, alt: false },
                { key: 'V', ctrl: true, shift: false, alt: false },
                { key: 'X', ctrl: true, shift: false, alt: false },
                { key: 'A', ctrl: true, shift: false, alt: false }, // Select all
            ] : []),

            // Print
            { key: 'P', ctrl: true, shift: false, alt: false },

            // Save
            { key: 'S', ctrl: true, shift: false, alt: false },
        ];

        const key = e.key.toUpperCase();
        const isCtrl = e.ctrlKey || e.metaKey;
        const isShift = e.shiftKey;
        const isAlt = e.altKey;

        for (const combo of blockedCombinations) {
            if (
                combo.key.toUpperCase() === key &&
                combo.ctrl === isCtrl &&
                combo.shift === isShift &&
                combo.alt === isAlt
            ) {
                e.preventDefault();
                e.stopPropagation();

                let violationType = 'keyboard_shortcut';
                let description = `Shortcut terlarang: `;

                if (key === 'F12' || (isCtrl && isShift && ['I', 'J', 'C'].includes(key))) {
                    if (config.value.detectDevtools) {
                        violationType = 'devtools';
                        description = 'Siswa mencoba membuka DevTools';
                    }
                } else if (['C', 'V', 'X', 'A'].includes(key) && isCtrl && !isShift) {
                    violationType = 'copy_paste';
                    description = `Shortcut copy/paste: Ctrl+${key}`;
                } else {
                    description += `${isCtrl ? 'Ctrl+' : ''}${isShift ? 'Shift+' : ''}${isAlt ? 'Alt+' : ''}${key}`;
                }

                recordViolation(violationType, description, { key, ctrl: isCtrl, shift: isShift, alt: isAlt });
                return false;
            }
        }
    };

    /**
     * Handle keyup for PrintScreen (some browsers only fire on keyup)
     * Note: Uses same debounce as keydown to prevent double recording
     */
    const handleKeyUp = (e) => {
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            // recordViolation already has debounce, safe to call
            recordViolation('screenshot', 'Siswa menekan tombol Print Screen');

            // Try to clear clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(() => { });
            }
            return false;
        }
    };

    /**
     * Detect multiple monitors using Screen API
     */
    const detectMultipleMonitors = async () => {
        try {
            // Method 1: Use Window Management API (modern browsers)
            if ('getScreenDetails' in window) {
                const screenDetails = await window.getScreenDetails();
                screenCount.value = screenDetails.screens.length;
                if (screenDetails.screens.length > 1) {
                    hasMultipleMonitors.value = true;
                    recordViolation('multiple_monitors',
                        `Terdeteksi ${screenDetails.screens.length} monitor`,
                        { screenCount: screenDetails.screens.length }
                    );
                    return true;
                }
            }

            // Method 2: Check screen dimensions vs window dimensions
            const screenWidth = window.screen.width;
            const screenHeight = window.screen.height;
            const availWidth = window.screen.availWidth;
            const availHeight = window.screen.availHeight;

            // Check for ultra-wide monitors (21:9 = 2.33, 32:9 = 3.56) to avoid false positives
            const aspectRatio = screenWidth / screenHeight;
            const isUltraWide = aspectRatio > 2.0; // Ultra-wide starts at ~2.33 (21:9)

            // If available width is much larger than screen width, likely multiple monitors
            // But skip if it's an ultra-wide monitor configuration
            if (availWidth > screenWidth * 1.5 && !isUltraWide) {
                hasMultipleMonitors.value = true;
                screenCount.value = Math.ceil(availWidth / screenWidth);
                recordViolation('multiple_monitors',
                    'Terdeteksi kemungkinan multiple monitor (screen size)',
                    { availWidth, screenWidth, ratio: availWidth / screenWidth, aspectRatio }
                );
                return true;
            }

            // Method 3: Check if window can be moved outside screen bounds
            const originalX = window.screenX;
            const originalY = window.screenY;

            // Check if window position suggests multiple monitors
            if (originalX < 0 || originalX > screenWidth || originalY < 0 || originalY > screenHeight) {
                hasMultipleMonitors.value = true;
                recordViolation('multiple_monitors',
                    'Window berada di luar batas monitor utama',
                    { screenX: originalX, screenY: originalY, screenWidth, screenHeight }
                );
                return true;
            }

            return false;
        } catch (error) {
            console.log('Multiple monitor detection not fully supported:', error);
            return false;
        }
    };

    // Remote desktop detection state
    const isRemoteDesktop = ref(false);
    let remoteDesktopCheckInterval = null;

    /**
     * Detect Remote Desktop software (TeamViewer, AnyDesk, etc.)
     * Uses multiple detection methods as no single method is 100% reliable
     */
    const detectRemoteDesktop = () => {
        try {
            const indicators = [];

            // Method 1: Check for low color depth (remote sessions often use reduced colors)
            const colorDepth = window.screen.colorDepth || screen.colorDepth;
            if (colorDepth && colorDepth < 24) {
                indicators.push({ method: 'color_depth', value: colorDepth });
            }

            // Method 2: Check for unusual screen dimensions (RDP often uses non-standard sizes)
            const width = window.screen.width;
            const height = window.screen.height;
            const commonResolutions = [
                [1920, 1080], [1366, 768], [1536, 864], [1440, 900], [1280, 720],
                [1600, 900], [2560, 1440], [3840, 2160], [1280, 800], [1024, 768]
            ];
            const isCommonResolution = commonResolutions.some(
                ([w, h]) => Math.abs(width - w) < 10 && Math.abs(height - h) < 10
            );

            // Method 3: Check pixel ratio (remote desktop often has ratio of 1)
            const pixelRatio = window.devicePixelRatio || 1;

            // Method 4: Check for WebGL renderer anomalies
            let webglRenderer = '';
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                if (gl) {
                    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                    if (debugInfo) {
                        webglRenderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || '';
                        // Remote desktop software often shows virtual/generic renderers
                        const suspiciousRenderers = [
                            'swiftshader', 'llvmpipe', 'virtualbox', 'vmware',
                            'microsoft basic render', 'rdp', 'remote', 'virtual'
                        ];
                        const rendererLower = webglRenderer.toLowerCase();
                        if (suspiciousRenderers.some(s => rendererLower.includes(s))) {
                            indicators.push({ method: 'webgl_renderer', value: webglRenderer });
                        }
                    }
                }
            } catch (e) {
                // WebGL not available
            }

            // Method 5: Check user agent for remote indicators
            const userAgent = navigator.userAgent.toLowerCase();
            const remoteUserAgentIndicators = ['rdp', 'remote', 'teamviewer', 'anydesk', 'vnc'];
            if (remoteUserAgentIndicators.some(indicator => userAgent.includes(indicator))) {
                indicators.push({ method: 'user_agent', value: navigator.userAgent });
            }

            // Method 6: Check for specific window properties that remote software might set
            if (window.chrome && window.chrome.runtime === undefined && !isCommonResolution) {
                // Headless or remote browser indicators
                indicators.push({ method: 'browser_anomaly', value: 'non-standard chrome' });
            }

            // Method 7: Performance timing check (remote connections have latency)
            const performanceCheck = () => {
                const start = performance.now();
                // Force a layout/paint
                document.body.offsetHeight;
                const end = performance.now();
                // Remote desktop typically has higher latency (> 50ms for simple operation)
                return end - start > 50;
            };

            // Method 8: Check navigator.connection for unusual network conditions
            if (navigator.connection) {
                const conn = navigator.connection;
                // Remote desktop might show unusual RTT or downlink values
                if (conn.rtt && conn.rtt > 500) {
                    indicators.push({ method: 'network_latency', value: conn.rtt });
                }
            }

            // If multiple indicators found, likely remote desktop
            if (indicators.length >= 2) {
                isRemoteDesktop.value = true;
                recordViolation('remote_desktop',
                    'Terdeteksi kemungkinan penggunaan Remote Desktop',
                    { indicators, colorDepth, resolution: `${width}x${height}`, pixelRatio, webglRenderer }
                );
                return true;
            }

            // Single strong indicator (WebGL or user agent)
            if (indicators.some(i => i.method === 'webgl_renderer' || i.method === 'user_agent')) {
                isRemoteDesktop.value = true;
                recordViolation('remote_desktop',
                    'Terdeteksi software Remote Desktop',
                    { indicators, webglRenderer }
                );
                return true;
            }

            return false;
        } catch (error) {
            console.log('Remote desktop detection error:', error);
            return false;
        }
    };

    /**
     * Start periodic remote desktop detection
     */
    const startRemoteDesktopDetection = () => {
        // Initial check
        detectRemoteDesktop();

        // Periodic check every 30 seconds
        remoteDesktopCheckInterval = setInterval(detectRemoteDesktop, 30000);
    };

    /**
     * Stop remote desktop detection
     */
    const stopRemoteDesktopDetection = () => {
        if (remoteDesktopCheckInterval) {
            clearInterval(remoteDesktopCheckInterval);
            remoteDesktopCheckInterval = null;
        }
    };

    /**
     * Detect Virtual Machine environment
     */
    const detectVirtualMachine = () => {
        try {
            const indicators = [];

            // Method 1: WebGL renderer check for VM signatures
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                if (gl) {
                    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                    if (debugInfo) {
                        const renderer = (gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || '').toLowerCase();
                        const vendor = (gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) || '').toLowerCase();

                        const vmRenderers = ['vmware', 'virtualbox', 'hyper-v', 'parallels', 'qemu', 'xen', 'kvm', 'bochs'];
                        if (vmRenderers.some(vm => renderer.includes(vm) || vendor.includes(vm))) {
                            indicators.push({ method: 'webgl', value: renderer });
                        }
                    }
                }
            } catch (e) { }

            // Method 2: Screen dimensions typical of VMs
            const width = window.screen.width;
            const height = window.screen.height;
            const vmResolutions = [[800, 600], [1024, 768], [1280, 1024]];
            if (vmResolutions.some(([w, h]) => width === w && height === h)) {
                indicators.push({ method: 'resolution', value: `${width}x${height}` });
            }

            // Method 3: Hardware concurrency (VMs often have limited cores)
            if (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2) {
                indicators.push({ method: 'cpu_cores', value: navigator.hardwareConcurrency });
            }

            // Method 4: Device memory (VMs often have limited RAM)
            if (navigator.deviceMemory && navigator.deviceMemory <= 2) {
                indicators.push({ method: 'memory', value: navigator.deviceMemory });
            }

            // Require WebGL indicator (strong) or 2+ weak indicators
            const hasStrongIndicator = indicators.some(i => i.method === 'webgl');
            if (hasStrongIndicator || indicators.length >= 2) {
                isVirtualMachine.value = true;
                recordViolation('virtual_machine', 'Terdeteksi penggunaan Virtual Machine', { indicators });
                return true;
            }

            return false;
        } catch (error) {
            console.log('VM detection error:', error);
            return false;
        }
    };

    /**
     * Check for blocked environment and trigger callback
     */
    const checkBlockedEnvironment = async () => {
        // Check multiple monitors
        if (config.value.blockMultipleMonitors && hasMultipleMonitors.value) {
            isBlockedEnvironment.value = true;
            blockedReason.value = 'multiple_monitors';
            if (config.value.onBlockedEnvironment) {
                config.value.onBlockedEnvironment({ reason: 'multiple_monitors', message: 'Penggunaan multiple monitor tidak diizinkan' });
            }
            return true;
        }

        // Check virtual machine
        if (config.value.blockVirtualMachine && isVirtualMachine.value) {
            isBlockedEnvironment.value = true;
            blockedReason.value = 'virtual_machine';
            if (config.value.onBlockedEnvironment) {
                config.value.onBlockedEnvironment({ reason: 'virtual_machine', message: 'Penggunaan Virtual Machine tidak diizinkan' });
            }
            return true;
        }

        return false;
    };

    /**
     * Handle screen change (monitor connected/disconnected)
     */
    const handleScreenChange = async () => {
        await detectMultipleMonitors();
        await checkBlockedEnvironment();
    };

    /**
     * Handle window move (detect if moved to another monitor)
     */
    let lastScreenX = null;
    let lastScreenY = null;
    const handleWindowMove = () => {
        const currentX = window.screenX;
        const currentY = window.screenY;
        const screenWidth = window.screen.width;

        // Skip first check (initialization)
        if (lastScreenX === null) {
            lastScreenX = currentX;
            lastScreenY = currentY;
            return;
        }

        // Check if window moved to a different monitor (significant X change)
        if (Math.abs(currentX - lastScreenX) > screenWidth / 2) {
            recordViolation('multiple_monitors',
                'Window dipindahkan ke monitor lain',
                { fromX: lastScreenX, toX: currentX }
            );
        }

        lastScreenX = currentX;
        lastScreenY = currentY;
    };

    /**
     * DevTools detection via window size
     */
    let devtoolsCheckInterval = null;
    let monitorCheckInterval = null;
    let screenDetailsRef = null;

    const startDevtoolsDetection = () => {
        if (!config.value.detectDevtools) return;

        // Check window size difference (DevTools docked changes inner/outer size)
        const checkWindowSize = () => {
            const widthThreshold = window.outerWidth - window.innerWidth > 160;
            const heightThreshold = window.outerHeight - window.innerHeight > 160;

            if (widthThreshold || heightThreshold) {
                recordViolation('devtools', 'DevTools terdeteksi (window size)');
            }
        };

        // Run checks periodically
        devtoolsCheckInterval = setInterval(checkWindowSize, 5000);
    };

    const stopDevtoolsDetection = () => {
        if (devtoolsCheckInterval) {
            clearInterval(devtoolsCheckInterval);
            devtoolsCheckInterval = null;
        }
        if (monitorCheckInterval) {
            clearInterval(monitorCheckInterval);
            monitorCheckInterval = null;
        }
    };

    /**
     * Single Tab Enforcement using BroadcastChannel
     * Prevents opening exam in multiple tabs
     * Note: BroadcastChannel not supported in Safari < 15.4
     */
    let broadcastChannel = null;
    const isSingleTabViolation = ref(false);

    const initSingleTabEnforcement = () => {
        if (!config.value.examId) return;

        // Feature detection for Safari < 15.4 compatibility
        if (!('BroadcastChannel' in window)) {
            console.warn('BroadcastChannel not supported in this browser. Single-tab enforcement disabled.');
            return;
        }

        try {
            const channelName = `exam_session_${config.value.examId}_${config.value.gradeId}`;
            broadcastChannel = new BroadcastChannel(channelName);

            // Announce this tab is active
            broadcastChannel.postMessage({ type: 'tab_active', timestamp: Date.now() });

            // Listen for other tabs
            broadcastChannel.onmessage = (event) => {
                if (event.data.type === 'tab_active') {
                    // Another tab opened! Send warning back
                    broadcastChannel.postMessage({ type: 'tab_exists', timestamp: Date.now() });
                    recordViolation('multiple_tabs', 'Ujian dibuka di tab lain');
                } else if (event.data.type === 'tab_exists') {
                    // This is a duplicate tab
                    isSingleTabViolation.value = true;
                    recordViolation('multiple_tabs', 'Mencoba membuka ujian di tab baru');
                    if (config.value.onDuplicateTab) {
                        config.value.onDuplicateTab();
                    }
                }
            };
        } catch (error) {
            console.warn('Failed to initialize BroadcastChannel:', error);
        }
    };

    const cleanupSingleTabEnforcement = () => {
        if (broadcastChannel) {
            broadcastChannel.close();
            broadcastChannel = null;
        }
    };

    /**
     * Browser Lockdown - Block popups and new windows
     */
    const originalWindowOpen = window.open;

    const initBrowserLockdown = () => {
        // Block window.open
        window.open = () => {
            recordViolation('popup_blocked', 'Mencoba membuka popup/window baru');
            return null;
        };

        // Block links with target="_blank"
        document.addEventListener('click', handleLinkClick, true);

        // Block beforeunload during exam (warn user)
        window.addEventListener('beforeunload', handleBeforeUnload);
    };

    const handleLinkClick = (e) => {
        const link = e.target.closest('a');
        if (link && (link.target === '_blank' || link.href?.startsWith('http'))) {
            // Allow internal navigation
            if (link.href?.includes(window.location.hostname)) return;
            e.preventDefault();
            e.stopPropagation();
            recordViolation('external_link', 'Mencoba membuka link eksternal');
        }
    };

    const handleBeforeUnload = (e) => {
        if (isInitialized.value) {
            e.preventDefault();
            e.returnValue = 'Ujian sedang berlangsung. Yakin ingin meninggalkan halaman?';
            return e.returnValue;
        }
    };

    const cleanupBrowserLockdown = () => {
        window.open = originalWindowOpen;
        document.removeEventListener('click', handleLinkClick, true);
        window.removeEventListener('beforeunload', handleBeforeUnload);
    };

    /**
     * Request fullscreen
     */
    const enterFullscreen = async () => {
        try {
            const elem = document.documentElement;

            if (elem.requestFullscreen) {
                await elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                await elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                await elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                await elem.msRequestFullscreen();
            }

            isFullscreen.value = true;
            return true;
        } catch (error) {
            console.error('Failed to enter fullscreen:', error);
            return false;
        }
    };

    /**
     * Exit fullscreen
     */
    const exitFullscreen = async () => {
        try {
            if (document.exitFullscreen) {
                await document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                await document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                await document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                await document.msExitFullscreen();
            }

            isFullscreen.value = false;
            return true;
        } catch (error) {
            console.error('Failed to exit fullscreen:', error);
            return false;
        }
    };

    /**
     * Initialize anti-cheat
     */
    // Time Anomaly Detection
    let timeCheckInterval = null;
    let serverTimeOffset = 0; // Difference between server and client time
    const TIME_ANOMALY_THRESHOLD = 30000; // 30 seconds tolerance
    const lastClientTime = ref(Date.now());

    /**
     * Initialize server time sync
     */
    const initTimeSync = async () => {
        try {
            const clientBefore = Date.now();
            const response = await axios.get('/student/anticheat/server-time');
            const clientAfter = Date.now();

            if (response.data.success) {
                const serverTime = response.data.server_time;
                const roundTrip = clientAfter - clientBefore;
                const estimatedServerTime = serverTime + (roundTrip / 2);
                serverTimeOffset = estimatedServerTime - clientAfter;
                lastClientTime.value = clientAfter;
            }
        } catch (error) {
            console.error('Failed to sync server time:', error);
        }
    };

    /**
     * Check for time anomaly (system time manipulation)
     * Skips detection when window is unfocused to prevent false positives
     * due to browser timer throttling
     */
    const checkTimeAnomaly = async () => {
        const currentClientTime = Date.now();
        const expectedElapsed = currentClientTime - lastClientTime.value;

        // Skip check if window is not focused (browsers throttle JS timers when inactive)
        // This prevents false positives from natural timer delays
        if (!windowFocused.value) {
            lastClientTime.value = currentClientTime;
            return;
        }

        // Check if time jumped backwards (user set clock back)
        if (expectedElapsed < -5000) { // More than 5 seconds backwards
            recordViolation('time_manipulation',
                'Waktu sistem diubah mundur',
                { expected: expectedElapsed, jumped: true, direction: 'backward' }
            );
            lastClientTime.value = currentClientTime;
            return;
        }

        // Check if time jumped forward too much (user set clock forward)
        // Use higher threshold (5 minutes) to account for browser throttling edge cases
        if (expectedElapsed > 300000) { // More than 5 minutes jump (was 2 min, increased for safety)
            recordViolation('time_manipulation',
                'Waktu sistem diubah maju secara tidak wajar',
                { expected: 10000, actual: expectedElapsed, direction: 'forward' }
            );
        }

        // Verify against server time periodically
        try {
            const clientBefore = Date.now();
            const response = await axios.get('/student/anticheat/server-time');
            const clientAfter = Date.now();

            if (response.data.success) {
                const serverTime = response.data.server_time;
                const roundTrip = clientAfter - clientBefore;
                const estimatedServerTime = serverTime + (roundTrip / 2);
                const currentOffset = estimatedServerTime - clientAfter;
                const offsetDrift = Math.abs(currentOffset - serverTimeOffset);

                // If offset changed significantly, time was manipulated
                if (offsetDrift > TIME_ANOMALY_THRESHOLD) {
                    recordViolation('time_manipulation',
                        'Terdeteksi manipulasi waktu sistem',
                        {
                            originalOffset: serverTimeOffset,
                            currentOffset: currentOffset,
                            drift: offsetDrift
                        }
                    );
                    // Update offset to new value
                    serverTimeOffset = currentOffset;
                }
            }
        } catch (error) {
            // Network error, skip this check
        }

        lastClientTime.value = currentClientTime;
    };

    /**
     * Start time anomaly detection
     */
    const startTimeAnomalyDetection = async () => {
        await initTimeSync();
        // Check every 10 seconds
        timeCheckInterval = setInterval(checkTimeAnomaly, 10000);
    };

    /**
     * Stop time anomaly detection
     */
    const stopTimeAnomalyDetection = () => {
        if (timeCheckInterval) {
            clearInterval(timeCheckInterval);
            timeCheckInterval = null;
        }
    };

    /**
     * Initialize anti-cheat
     */
    const initialize = async () => {
        if (!config.value.enabled) return;

        // Generate and store session ID
        if (!sessionStorage.getItem('exam_session_id')) {
            sessionStorage.setItem('exam_session_id', Math.random().toString(36).substring(2) + Date.now().toString(36));
        }

        // Initialize video stream for snapshots
        await initVideoStream();

        // Add event listeners
        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleWindowBlur);
        window.addEventListener('focus', handleWindowFocus);
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('MSFullscreenChange', handleFullscreenChange);
        document.addEventListener('copy', handleCopy);
        document.addEventListener('paste', handlePaste);
        document.addEventListener('cut', handleCut);
        document.addEventListener('contextmenu', handleContextMenu);
        document.addEventListener('keydown', handleKeyDown);
        document.addEventListener('keyup', handleKeyUp);

        // Start devtools detection
        startDevtoolsDetection();

        // Start remote desktop detection
        startRemoteDesktopDetection();

        // Start time anomaly detection
        await startTimeAnomalyDetection();

        // Detect multiple monitors on init
        await detectMultipleMonitors();

        // Detect virtual machine
        detectVirtualMachine();

        // Check if environment is blocked
        await checkBlockedEnvironment();

        // Listen for screen changes (if supported)
        if ('getScreenDetails' in window) {
            try {
                screenDetailsRef = await window.getScreenDetails();
                screenDetailsRef.addEventListener('screenschange', handleScreenChange);
            } catch (e) {
                // Permission denied or not supported
            }
        }

        // Periodic check for window movement to other monitors
        monitorCheckInterval = setInterval(handleWindowMove, 3000);

        // Initialize single tab enforcement
        initSingleTabEnforcement();

        // Initialize browser lockdown
        initBrowserLockdown();

        // Request fullscreen if required
        if (config.value.fullscreenRequired) {
            await enterFullscreen();
        }

        // Fetch current violation status from server
        if (config.value.gradeId) {
            try {
                const response = await axios.get('/student/anticheat/status', {
                    params: { grade_id: config.value.gradeId }
                });

                if (response.data.success) {
                    violationCount.value = response.data.data.total_violations;
                    remainingViolations.value = response.data.data.remaining_violations;
                    warningReached.value = response.data.data.warning_reached;
                }
            } catch (error) {
                console.error('Failed to fetch violation status:', error);
            }
        }

        isInitialized.value = true;
    };

    /**
     * Cleanup anti-cheat
     */
    const cleanup = () => {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('blur', handleWindowBlur);
        window.removeEventListener('focus', handleWindowFocus);
        stopBlurDurationCheck();
        document.removeEventListener('fullscreenchange', handleFullscreenChange);
        document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
        document.removeEventListener('MSFullscreenChange', handleFullscreenChange);
        document.removeEventListener('copy', handleCopy);
        document.removeEventListener('paste', handlePaste);
        document.removeEventListener('cut', handleCut);
        document.removeEventListener('contextmenu', handleContextMenu);
        document.removeEventListener('keydown', handleKeyDown);
        document.removeEventListener('keyup', handleKeyUp);

        // Cleanup video stream for snapshots
        if (videoStream.value) {
            videoStream.value.getTracks().forEach(track => track.stop());
            videoStream.value = null;
        }
        if (videoElement.value) {
            videoElement.value.remove();
            videoElement.value = null;
        }

        // Cleanup screen change listener
        if (screenDetailsRef) {
            screenDetailsRef.removeEventListener('screenschange', handleScreenChange);
            screenDetailsRef = null;
        }

        stopDevtoolsDetection();
        stopRemoteDesktopDetection();
        stopTimeAnomalyDetection();
        cleanupSingleTabEnforcement();
        cleanupBrowserLockdown();

        isInitialized.value = false;
    };

    /**
     * Update configuration
     */
    const updateConfig = (newConfig) => {
        Object.assign(config.value, newConfig);
    };

    /**
     * Get current status
     */
    const getStatus = () => ({
        isFullscreen: isFullscreen.value,
        violationCount: violationCount.value,
        remainingViolations: remainingViolations.value,
        warningReached: warningReached.value,
        isInitialized: isInitialized.value,
        isBlockedEnvironment: isBlockedEnvironment.value,
        blockedReason: blockedReason.value,
        hasMultipleMonitors: hasMultipleMonitors.value,
        isVirtualMachine: isVirtualMachine.value,
        windowFocused: windowFocused.value,
        totalBlurDuration: totalBlurDuration.value,
        blurCount: blurCount.value,
        config: config.value,
    });

    // Lifecycle hooks
    onMounted(() => {
        if (config.value.enabled) {
            initialize();
        }
    });

    onUnmounted(() => {
        cleanup();
    });

    return {
        // State
        config,
        isFullscreen,
        violationCount,
        remainingViolations,
        warningReached,
        isInitialized,
        isBlockedEnvironment,
        blockedReason,
        hasMultipleMonitors,
        isVirtualMachine,
        windowFocused,
        totalBlurDuration,
        blurCount,

        // Methods
        initialize,
        cleanup,
        enterFullscreen,
        exitFullscreen,
        recordViolation,
        updateConfig,
        getStatus,
    };
}

export default useAntiCheat;
