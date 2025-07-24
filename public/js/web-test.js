/**
 * Test Script for Lazy Loading Implementation
 * Run this in browser console to test lazy loading functionality
 */

class LazyLoadingTester {
    constructor() {
        this.results = {
            intersectionObserver: false,
            lazyImages: 0,
            lazyComponents: 0,
            loadedImages: 0,
            loadedComponents: 0,
            performanceMetrics: {}
        };
        
        this.init();
    }

    init() {
        console.group('🧪 Lazy Loading Test Suite');
        
        this.testBrowserSupport();
        this.testLazyElements();
        this.testLazyLoader();
        this.testPerformance();
        this.showResults();
        
        console.groupEnd();
    }

    testBrowserSupport() {
        console.log('🔍 Testing Browser Support...');
        
        // Test Intersection Observer
        this.results.intersectionObserver = 'IntersectionObserver' in window;
        console.log(`   IntersectionObserver: ${this.results.intersectionObserver ? '✅' : '❌'}`);
        
        // Test Performance API
        const hasPerformanceAPI = 'performance' in window && 'getEntriesByType' in performance;
        console.log(`   Performance API: ${hasPerformanceAPI ? '✅' : '❌'}`);
        
        // Test Import/Export
        const hasModules = 'import' in document.createElement('script');
        console.log(`   ES6 Modules: ${hasModules ? '✅' : '❌'}`);
    }

    testLazyElements() {
        console.log('🖼️ Testing Lazy Elements...');
        
        // Count lazy images
        const lazyImages = document.querySelectorAll('img[data-src]');
        this.results.lazyImages = lazyImages.length;
        console.log(`   Lazy Images Found: ${lazyImages.length}`);
        
        // Count critical images
        const criticalImages = document.querySelectorAll('img[data-critical]');
        console.log(`   Critical Images: ${criticalImages.length}`);
        
        // Count lazy components
        const lazyComponents = document.querySelectorAll('[data-lazy-component]');
        this.results.lazyComponents = lazyComponents.length;
        console.log(`   Lazy Components Found: ${lazyComponents.length}`);
        
        // List component types
        lazyComponents.forEach(component => {
            const type = component.dataset.lazyComponent;
            console.log(`   - Component: ${type}`);
        });
    }

    testLazyLoader() {
        console.log('⚡ Testing LazyLoader Instance...');
        
        if (window.lazyLoader) {
            console.log('   ✅ LazyLoader instance found');
            
            // Get stats if available
            if (typeof window.lazyLoader.getStats === 'function') {
                const stats = window.lazyLoader.getStats();
                console.log('   📊 LazyLoader Stats:', stats);
                
                this.results.loadedImages = stats.imagesLoaded;
                this.results.loadedComponents = stats.componentsLoaded;
            }
        } else {
            console.log('   ❌ LazyLoader instance not found');
        }
    }

    testPerformance() {
        console.log('📈 Testing Performance...');
        
        if ('performance' in window) {
            const navigation = performance.getEntriesByType('navigation')[0];
            const paint = performance.getEntriesByType('paint');
            
            if (navigation) {
                const domContentLoaded = navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart;
                const loadComplete = navigation.loadEventEnd - navigation.loadEventStart;
                
                this.results.performanceMetrics = {
                    domContentLoaded: Math.round(domContentLoaded),
                    loadComplete: Math.round(loadComplete),
                    transferSize: navigation.transferSize,
                    decodedBodySize: navigation.decodedBodySize
                };
                
                console.log(`   DOM Content Loaded: ${Math.round(domContentLoaded)}ms`);
                console.log(`   Load Complete: ${Math.round(loadComplete)}ms`);
                console.log(`   Transfer Size: ${this.formatBytes(navigation.transferSize)}`);
            }
            
            paint.forEach(entry => {
                console.log(`   ${entry.name}: ${Math.round(entry.startTime)}ms`);
            });
        }
    }

    showResults() {
        console.log('📋 Test Results Summary:');
        console.table(this.results);
        
        // Test score
        let score = 0;
        if (this.results.intersectionObserver) score += 20;
        if (this.results.lazyImages > 0) score += 30;
        if (this.results.lazyComponents > 0) score += 20;
        if (window.lazyLoader) score += 30;
        
        console.log(`🏆 Lazy Loading Score: ${score}/100`);
        
        if (score >= 80) {
            console.log('✅ Excellent implementation!');
        } else if (score >= 60) {
            console.log('⚠️ Good, but could be improved');
        } else {
            console.log('❌ Needs work');
        }
    }

    // Utility methods
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Test specific lazy loading functionality
    static testImageLazyLoading() {
        console.group('🖼️ Testing Image Lazy Loading');
        
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        lazyImages.forEach((img, index) => {
            const hasDataSrc = img.hasAttribute('data-src');
            const hasPlaceholder = img.classList.contains('lazy-placeholder');
            const isLoaded = img.classList.contains('lazy-loaded');
            
            console.log(`Image ${index + 1}:`, {
                hasDataSrc,
                hasPlaceholder,
                isLoaded,
                src: img.getAttribute('data-src'),
                alt: img.alt
            });
        });
        
        console.groupEnd();
    }

    static testComponentLazyLoading() {
        console.group('⚡ Testing Component Lazy Loading');
        
        const lazyComponents = document.querySelectorAll('[data-lazy-component]');
        
        lazyComponents.forEach((component, index) => {
            const type = component.dataset.lazyComponent;
            const isLoading = component.classList.contains('lazy-loading');
            const isLoaded = component.classList.contains('lazy-loaded');
            
            console.log(`Component ${index + 1}:`, {
                type,
                isLoading,
                isLoaded,
                element: component.tagName
            });
        });
        
        console.groupEnd();
    }

    static simulateSlowConnection() {
        console.log('🐌 Simulating slow connection...');
        console.log('Open DevTools → Network → Throttling → Slow 3G to test lazy loading');
    }

    static forceLoadAll() {
        console.log('⚡ Force loading all lazy content...');
        
        if (window.lazyLoader && typeof window.lazyLoader.loadAll === 'function') {
            window.lazyLoader.loadAll();
            console.log('✅ All lazy content loaded');
        } else {
            console.log('❌ LazyLoader.loadAll() not available');
        }
    }
}

// Auto-run test when script loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new LazyLoadingTester();
    });
} else {
    new LazyLoadingTester();
}

// Export test methods for manual testing
window.LazyLoadingTester = LazyLoadingTester;

// Console helpers
console.log(`
🧪 Lazy Loading Test Tools Available:
   LazyLoadingTester.testImageLazyLoading() - Test image lazy loading
   LazyLoadingTester.testComponentLazyLoading() - Test component lazy loading
   LazyLoadingTester.simulateSlowConnection() - Instructions for slow connection testing
   LazyLoadingTester.forceLoadAll() - Force load all lazy content
`);

export default LazyLoadingTester;