/**
 * Lazy Loading System for j2biznes Landing Page
 * Optimized for performance and SEO
 */

class LazyLoader {
    constructor() {
        this.imageObserver = null;
        this.componentObserver = null;
        this.loadedImages = new Set();
        this.loadedComponents = new Set();
        
        this.init();
    }

    init() {
        // Check if Intersection Observer is supported
        if ('IntersectionObserver' in window) {
            this.setupImageLazyLoading();
            this.setupComponentLazyLoading();
            this.setupResourceHints();
        } else {
            // Fallback for older browsers
            this.loadAllImagesImmediately();
        }

        // Preload critical resources
        this.preloadCriticalResources();
    }

    /**
     * Setup lazy loading for images
     */
    setupImageLazyLoading() {
        const imageOptions = {
            root: null,
            rootMargin: '50px 0px', // Start loading 50px before entering viewport
            threshold: 0.1
        };

        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, imageOptions);

        // Observe all lazy images
        const lazyImages = document.querySelectorAll('img[data-src], picture[data-src]');
        lazyImages.forEach(img => {
            this.imageObserver.observe(img);
        });
    }

    /**
     * Load individual image
     */
    loadImage(imageElement) {
        const src = imageElement.dataset.src;
        const srcset = imageElement.dataset.srcset;
        
        if (!src && !srcset) return;

        // Add loading class
        imageElement.classList.add('lazy-loading');

        // Create new image to preload
        const img = new Image();
        
        img.onload = () => {
            // Update the actual image
            if (srcset) {
                imageElement.srcset = srcset;
            }
            if (src) {
                imageElement.src = src;
            }

            // Add loaded class and remove loading class
            imageElement.classList.remove('lazy-loading', 'lazy-placeholder');
            imageElement.classList.add('lazy-loaded');
            
            // Track loaded image
            this.loadedImages.add(imageElement);

            // Dispatch custom event
            imageElement.dispatchEvent(new CustomEvent('lazyloaded', { 
                detail: { src, srcset } 
            }));
        };

        img.onerror = () => {
            imageElement.classList.remove('lazy-loading');
            imageElement.classList.add('lazy-error');
            console.warn('Failed to load lazy image:', src);
        };

        // Start loading
        if (srcset) img.srcset = srcset;
        if (src) img.src = src;
    }

    /**
     * Setup lazy loading for heavy components
     */
    setupComponentLazyLoading() {
        const componentOptions = {
            root: null,
            rootMargin: '100px 0px', // Load components 100px before viewport
            threshold: 0.1
        };

        this.componentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadComponent(entry.target);
                    this.componentObserver.unobserve(entry.target);
                }
            });
        }, componentOptions);

        // Observe lazy components
        const lazyComponents = document.querySelectorAll('[data-lazy-component]');
        lazyComponents.forEach(component => {
            this.componentObserver.observe(component);
        });
    }

    /**
     * Load heavy components
     */
    loadComponent(element) {
        const componentType = element.dataset.lazyComponent;
        
        element.classList.add('lazy-loading');

        switch (componentType) {
            case 'stats-counter':
                this.loadStatsCounter(element);
                break;
            case 'testimonials':
                this.loadTestimonials(element);
                break;
            case 'contact-form':
                this.loadContactForm(element);
                break;
            case 'video-player':
                this.loadVideoPlayer(element);
                break;
            default:
                console.warn('Unknown lazy component type:', componentType);
        }
    }

    /**
     * Load stats counter component
     */
    async loadStatsCounter(element) {
        try {
            // Import counter animation dynamically
            const module = await import('./components/stats-counter.js');
            const StatsCounter = module.default;
            new StatsCounter(element);
            element.classList.remove('lazy-loading');
            element.classList.add('lazy-loaded');
            this.loadedComponents.add(element);
        } catch (error) {
            console.error('Failed to load stats counter:', error);
            element.classList.add('lazy-error');
        }
    }

    /**
     * Load testimonials component
     */
    loadTestimonials(element) {
        // Simulate loading testimonials data
        setTimeout(() => {
            element.classList.remove('lazy-loading');
            element.classList.add('lazy-loaded');
            this.loadedComponents.add(element);
        }, 100);
    }

    /**
     * Load contact form component
     */
    loadContactForm(element) {
        // Load form validation and submission logic
        import('./components/contact-form.js')
            .then(module => {
                const ContactForm = module.default;
                new ContactForm(element);
                element.classList.remove('lazy-loading');
                element.classList.add('lazy-loaded');
                this.loadedComponents.add(element);
            })
            .catch(error => {
                console.error('Failed to load contact form:', error);
                element.classList.add('lazy-error');
            });
    }

    /**
     * Load video player component
     */
    loadVideoPlayer(element) {
        const videoSrc = element.dataset.videoSrc;
        const posterSrc = element.dataset.posterSrc;

        const video = document.createElement('video');
        video.src = videoSrc;
        video.poster = posterSrc;
        video.controls = true;
        video.preload = 'metadata';
        
        element.appendChild(video);
        element.classList.remove('lazy-loading');
        element.classList.add('lazy-loaded');
        this.loadedComponents.add(element);
    }

    /**
     * Setup resource hints for better performance
     */
    setupResourceHints() {
        // DNS prefetch for external resources
        const dnsPrefetchDomains = [
            '//fonts.googleapis.com',
            '//fonts.gstatic.com',
            '//cdnjs.cloudflare.com'
        ];

        dnsPrefetchDomains.forEach(domain => {
            if (!document.querySelector(`link[href="${domain}"]`)) {
                const link = document.createElement('link');
                link.rel = 'dns-prefetch';
                link.href = domain;
                document.head.appendChild(link);
            }
        });
    }

    /**
     * Preload critical resources
     */
    preloadCriticalResources() {
        // Preload critical images that should load immediately
        const criticalImages = document.querySelectorAll('img[data-critical]');
        criticalImages.forEach(img => {
            this.loadImage(img);
        });

        // Preload next section's images when user scrolls
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.preloadNextSectionImages();
            }, 150);
        }, { passive: true });
    }

    /**
     * Preload images in the next section
     */
    preloadNextSectionImages() {
        const scrollPosition = window.pageYOffset;
        const windowHeight = window.innerHeight;
        const nextSectionPosition = scrollPosition + windowHeight * 1.5;

        const allImages = document.querySelectorAll('img[data-src]');
        allImages.forEach(img => {
            const imgPosition = img.offsetTop;
            if (imgPosition <= nextSectionPosition && !this.loadedImages.has(img)) {
                this.loadImage(img);
            }
        });
    }

    /**
     * Fallback for browsers without Intersection Observer
     */
    loadAllImagesImmediately() {
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => this.loadImage(img));

        const lazyComponents = document.querySelectorAll('[data-lazy-component]');
        lazyComponents.forEach(component => this.loadComponent(component));
    }

    /**
     * Get loading statistics
     */
    getStats() {
        return {
            imagesLoaded: this.loadedImages.size,
            componentsLoaded: this.loadedComponents.size,
            totalImages: document.querySelectorAll('img[data-src]').length,
            totalComponents: document.querySelectorAll('[data-lazy-component]').length
        };
    }

    /**
     * Force load all remaining lazy content
     */
    loadAll() {
        const remainingImages = document.querySelectorAll('img[data-src]:not(.lazy-loaded)');
        remainingImages.forEach(img => {
            this.imageObserver?.unobserve(img);
            this.loadImage(img);
        });

        const remainingComponents = document.querySelectorAll('[data-lazy-component]:not(.lazy-loaded)');
        remainingComponents.forEach(component => {
            this.componentObserver?.unobserve(component);
            this.loadComponent(component);
        });
    }

    /**
     * Cleanup observers
     */
    destroy() {
        if (this.imageObserver) {
            this.imageObserver.disconnect();
        }
        if (this.componentObserver) {
            this.componentObserver.disconnect();
        }
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.lazyLoader = new LazyLoader();
    });
} else {
    window.lazyLoader = new LazyLoader();
}

export default LazyLoader;