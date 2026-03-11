import { useEffect } from 'react';

export function usePerformanceMonitoring() {
    useEffect(() => {
        if (process.env.NODE_ENV !== 'production') {
            return;
        }

        if ('PerformanceObserver' in window) {
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log('LCP:', lastEntry.startTime);
            });
            
            try {
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {}

            const fidObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    const delay = entry.processingStart - entry.startTime;
                    console.log('FID:', delay);
                }
            });
            
            try {
                fidObserver.observe({ entryTypes: ['first-input'] });
            } catch (e) {}

            let clsValue = 0;
            const clsObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
                console.log('CLS:', clsValue);
            });
            
            try {
                clsObserver.observe({ entryTypes: ['layout-shift'] });
            } catch (e) {}

            window.addEventListener('load', () => {
                const navEntry = performance.getEntriesByType('navigation')[0];
                if (navEntry) {
                    console.log('TTFB:', navEntry.responseStart);
                    console.log('FCP:', navEntry.domContentLoadedEventEnd);
                }
            });

            return () => {
                lcpObserver.disconnect();
                fidObserver.disconnect();
                clsObserver.disconnect();
            };
        }
    }, []);
}

export function preloadCriticalImage(src) {
    if (!src || typeof window === 'undefined') return;
    
    const link = document.createElement('link');
    link.rel = 'preload';
    link.as = 'image';
    link.href = src;
    link.fetchPriority = 'high';
    document.head.appendChild(link);
}

export function markPerformance(name) {
    if (typeof performance !== 'undefined' && performance.mark) {
        performance.mark(name);
        console.log(`[Performance] ${name}`);
    }
}

export function measurePerformance(name, startMark, endMark) {
    if (typeof performance !== 'undefined' && performance.measure) {
        try {
            performance.measure(name, startMark, endMark);
            const entries = performance.getEntriesByName(name);
            if (entries.length > 0) {
                console.log(`[Performance] ${name}: ${entries[0].duration}ms`);
            }
        } catch (e) {
            console.warn(`[Performance] Failed to measure ${name}:`, e);
        }
    }
}
