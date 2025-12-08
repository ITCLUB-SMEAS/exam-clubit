import { ref, onMounted, onUnmounted } from 'vue';

export function useMobileDetection() {
    const isMobile = ref(false);
    const isTablet = ref(false);
    const isLandscape = ref(false);
    const screenWidth = ref(window.innerWidth);
    const screenHeight = ref(window.innerHeight);

    const detectDevice = () => {
        const ua = navigator.userAgent.toLowerCase();
        const width = window.innerWidth;
        
        // Mobile detection
        isMobile.value = /android|webos|iphone|ipod|blackberry|iemobile|opera mini/i.test(ua) || width < 768;
        
        // Tablet detection
        isTablet.value = /ipad|android(?!.*mobile)|tablet/i.test(ua) || (width >= 768 && width < 1024);
        
        // Landscape detection
        isLandscape.value = window.innerWidth > window.innerHeight;
        
        screenWidth.value = window.innerWidth;
        screenHeight.value = window.innerHeight;
    };

    const handleResize = () => {
        detectDevice();
    };

    const handleOrientationChange = () => {
        detectDevice();
    };

    onMounted(() => {
        detectDevice();
        window.addEventListener('resize', handleResize);
        window.addEventListener('orientationchange', handleOrientationChange);
    });

    onUnmounted(() => {
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('orientationchange', handleOrientationChange);
    });

    const isIOS = () => {
        return /iphone|ipad|ipod/i.test(navigator.userAgent.toLowerCase());
    };

    const isAndroid = () => {
        return /android/i.test(navigator.userAgent.toLowerCase());
    };

    const supportsFullscreen = () => {
        return !!(
            document.fullscreenEnabled ||
            document.webkitFullscreenEnabled ||
            document.mozFullScreenEnabled ||
            document.msFullscreenEnabled
        ) && !isIOS();
    };

    return {
        isMobile,
        isTablet,
        isLandscape,
        screenWidth,
        screenHeight,
        isIOS,
        isAndroid,
        supportsFullscreen,
    };
}
