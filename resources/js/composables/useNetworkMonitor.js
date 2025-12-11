import { ref, onUnmounted } from 'vue';

/**
 * Network Activity Monitor Composable
 * Detects suspicious network activity (external requests to AI/search sites)
 */
export function useNetworkMonitor(options = {}) {
    const config = {
        onSuspiciousActivity: options.onSuspiciousActivity ?? null,
        // Domains that trigger violation
        blockedDomains: options.blockedDomains ?? [
            'chat.openai.com', 'chatgpt.com', 'openai.com',
            'bard.google.com', 'gemini.google.com',
            'claude.ai', 'anthropic.com',
            'bing.com/chat', 'copilot.microsoft.com',
            'perplexity.ai', 'you.com',
            'brainly.com', 'chegg.com', 'quizlet.com',
            'wolframalpha.com', 'symbolab.com',
            'translate.google', 'deepl.com',
        ],
        // Whitelist (allowed domains)
        allowedDomains: options.allowedDomains ?? [],
    };

    const isMonitoring = ref(false);
    const suspiciousRequests = ref([]);
    const requestCount = ref(0);

    let observer = null;

    const isSuspiciousDomain = (url) => {
        try {
            const urlObj = new URL(url);
            const hostname = urlObj.hostname.toLowerCase();
            
            // Check whitelist first
            if (config.allowedDomains.some(d => hostname.includes(d))) {
                return false;
            }
            
            // Check blocked domains
            return config.blockedDomains.some(d => hostname.includes(d));
        } catch {
            return false;
        }
    };

    const handleResourceTiming = (entries) => {
        for (const entry of entries) {
            requestCount.value++;
            
            if (entry.initiatorType === 'fetch' || entry.initiatorType === 'xmlhttprequest') {
                if (isSuspiciousDomain(entry.name)) {
                    const record = {
                        url: entry.name,
                        time: new Date().toISOString(),
                        type: entry.initiatorType,
                    };
                    suspiciousRequests.value.push(record);
                    config.onSuspiciousActivity?.(record);
                }
            }
        }
    };

    // Intercept fetch
    let originalFetch = null;
    const interceptFetch = () => {
        originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const url = args[0]?.url || args[0];
            if (typeof url === 'string' && isSuspiciousDomain(url)) {
                const record = {
                    url,
                    time: new Date().toISOString(),
                    type: 'fetch',
                };
                suspiciousRequests.value.push(record);
                config.onSuspiciousActivity?.(record);
            }
            return originalFetch.apply(window, args);
        };
    };

    // Intercept XMLHttpRequest
    let originalXHROpen = null;
    const interceptXHR = () => {
        originalXHROpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(method, url, ...rest) {
            if (typeof url === 'string' && isSuspiciousDomain(url)) {
                const record = {
                    url,
                    time: new Date().toISOString(),
                    type: 'xhr',
                };
                suspiciousRequests.value.push(record);
                config.onSuspiciousActivity?.(record);
            }
            return originalXHROpen.apply(this, [method, url, ...rest]);
        };
    };

    const start = () => {
        if (isMonitoring.value) return;
        
        // Use PerformanceObserver for resource timing
        if (typeof PerformanceObserver !== 'undefined') {
            try {
                observer = new PerformanceObserver((list) => {
                    handleResourceTiming(list.getEntries());
                });
                observer.observe({ entryTypes: ['resource'] });
            } catch (e) {
                console.warn('PerformanceObserver not supported:', e);
            }
        }

        // Intercept fetch and XHR
        interceptFetch();
        interceptXHR();

        isMonitoring.value = true;
    };

    const stop = () => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
        
        // Restore original fetch
        if (originalFetch) {
            window.fetch = originalFetch;
            originalFetch = null;
        }
        
        // Restore original XHR
        if (originalXHROpen) {
            XMLHttpRequest.prototype.open = originalXHROpen;
            originalXHROpen = null;
        }

        isMonitoring.value = false;
    };

    const getSuspiciousRequests = () => [...suspiciousRequests.value];
    
    const clearHistory = () => {
        suspiciousRequests.value = [];
    };

    onUnmounted(stop);

    return {
        isMonitoring,
        suspiciousRequests,
        requestCount,
        start,
        stop,
        getSuspiciousRequests,
        clearHistory,
    };
}

export default useNetworkMonitor;
