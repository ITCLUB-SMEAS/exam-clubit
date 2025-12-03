import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

/**
 * Anti-Cheat Composable for Vue 3
 *
 * This composable provides comprehensive anti-cheat functionality for online exams:
 * - Tab/Window switching detection
 * - Fullscreen enforcement
 * - Copy/Paste prevention
 * - Right-click prevention
 * - DevTools detection
 * - Keyboard shortcut blocking
 * - Window blur detection
 */
export function useAntiCheat(options = {}) {
    // Configuration with defaults
    const config = ref({
        enabled: options.enabled ?? true,
        fullscreenRequired: options.fullscreenRequired ?? true,
        blockTabSwitch: options.blockTabSwitch ?? true,
        blockCopyPaste: options.blockCopyPaste ?? true,
        blockRightClick: options.blockRightClick ?? true,
        blockMultipleMonitors: true, // Always enabled
        blockVirtualMachine: true,   // Always enabled
        detectDevtools: options.detectDevtools ?? true,
        maxViolations: options.maxViolations ?? 3,
        warningThreshold: options.warningThreshold ?? 2,
        autoSubmitOnMaxViolations: options.autoSubmitOnMaxViolations ?? false,
        examId: options.examId ?? null,
        examSessionId: options.examSessionId ?? null,
        gradeId: options.gradeId ?? null,
        onViolation: options.onViolation ?? null,
        onWarningThreshold: options.onWarningThreshold ?? null,
        onMaxViolations: options.onMaxViolations ?? null,
        onAutoSubmit: options.onAutoSubmit ?? null,
        onBlockedEnvironment: options.onBlockedEnvironment ?? null,
        onDuplicateTab: options.onDuplicateTab ?? null,
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
     * Record a violation
     */
    const recordViolation = async (type, description = null, metadata = null) => {
        if (!config.value.enabled) return;
        if (!shouldRecordViolation(type)) return;

        // Increment local counter immediately
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
                });

                // Check if student got blocked
                if (response.data?.data?.is_blocked && config.value.onBlocked) {
                    config.value.onBlocked();
                }
            } catch (error) {
                console.error('Failed to record violation:', error);
                // Queue for retry
                pendingViolations.value.push({ type, description, metadata, timestamp: Date.now() });
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

        recordViolation('blur', 'Window ujian kehilangan fokus');
    };

    /**
     * Handle fullscreen change
     */
    const handleFullscreenChange = () => {
        isFullscreen.value = !!document.fullscreenElement;

        if (!isFullscreen.value && config.value.fullscreenRequired && isInitialized.value) {
            recordViolation('fullscreen_exit', 'Siswa keluar dari mode fullscreen');
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
                navigator.clipboard.writeText('').catch(() => {});
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
            
            // If available width is much larger than screen width, likely multiple monitors
            if (availWidth > screenWidth * 1.5) {
                hasMultipleMonitors.value = true;
                screenCount.value = Math.ceil(availWidth / screenWidth);
                recordViolation('multiple_monitors', 
                    'Terdeteksi kemungkinan multiple monitor (screen size)', 
                    { availWidth, screenWidth, ratio: availWidth / screenWidth }
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
            } catch (e) {}

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
     */
    let broadcastChannel = null;
    const isSingleTabViolation = ref(false);

    const initSingleTabEnforcement = () => {
        if (!config.value.examId) return;

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
    const initialize = async () => {
        if (!config.value.enabled) return;

        // Add event listeners
        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleWindowBlur);
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

        // Cleanup screen change listener
        if (screenDetailsRef) {
            screenDetailsRef.removeEventListener('screenschange', handleScreenChange);
            screenDetailsRef = null;
        }

        stopDevtoolsDetection();
        stopRemoteDesktopDetection();
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
