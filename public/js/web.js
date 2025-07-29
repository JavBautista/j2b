/**
 * Stats Counter Component
 * Lazy-loaded component for animated counters
 */

class StatsCounter {
    constructor(element) {
        this.element = element;
        this.counters = element.querySelectorAll('[data-target]');
        this.animated = false;
        
        this.init();
    }

    init() {
        // Only animate once when the component becomes visible
        if (!this.animated) {
            this.animateCounters();
            this.animated = true;
        }
    }

    animateCounters() {
        this.counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = parseInt(counter.getAttribute('data-duration')) || 2000;
            const formatter = counter.getAttribute('data-format') || 'number';
            
            this.animateCounter(counter, target, duration, formatter);
        });
    }

    animateCounter(counter, target, duration, formatter) {
        let current = 0;
        const increment = target / (duration / 16); // 60 FPS
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Use easing function for smooth animation
            const easedProgress = this.easeOutCubic(progress);
            current = target * easedProgress;

            // Format and display the number
            counter.textContent = this.formatNumber(Math.floor(current), formatter, target);

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // Ensure final value is exact
                counter.textContent = this.formatNumber(target, formatter, target);
            }
        };

        requestAnimationFrame(animate);
    }

    formatNumber(value, formatter, target) {
        switch (formatter) {
            case 'thousands':
                if (target >= 10000) {
                    return Math.floor(value / 1000) + 'K+';
                }
                return value.toLocaleString() + '+';
            
            case 'percentage':
                return value + '%';
            
            case 'time':
                return value + '/7';
            
            case 'countries':
                return value + '+';
            
            case 'country':
                return value;
            
            default:
                return value.toLocaleString();
        }
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
}

// Make StatsCounter available globally for concatenated builds
window.StatsCounter = StatsCounter;
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
    loadStatsCounter(element) {
        try {
            // Use globally available StatsCounter class
            if (window.StatsCounter) {
                new window.StatsCounter(element);
                element.classList.remove('lazy-loading');
                element.classList.add('lazy-loaded');
                this.loadedComponents.add(element);
            } else {
                console.error('StatsCounter class not available');
                element.classList.add('lazy-error');
            }
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

// Make LazyLoader available globally for concatenated builds
window.LazyLoader = LazyLoader;
/**
 * j2biznes Landing Page JavaScript
 * Separated from index.blade.php for better organization
 */

// Note: Lazy loading functionality should be loaded separately or included directly

class LandingPage {
    constructor() {
        this.isMenuOpen = false;
        this.lastScrollTop = 0;
        this.statsAnimated = false;
        this.ticking = false;
        this.performanceMetrics = {
            startTime: performance.now(),
            domContentLoaded: null,
            fullyLoaded: null,
            firstPaint: null,
            firstContentfulPaint: null
        };
        
        this.init();
    }

    init() {
        this.measurePerformance();
        this.bindEvents();
        this.initializeAnimations();
        this.preloadResources();
        this.setupAccessibility();
        this.optimizeThirdPartyLoading();
        
        // Remove loading class when page loads
        window.addEventListener('load', () => {
            document.body.classList.remove('loading');
            document.body.classList.add('loaded');
            this.performanceMetrics.fullyLoaded = performance.now();
            this.logPerformanceMetrics();
            console.log('j2biznes landing page loaded successfully');
            console.log('All animations and interactions initialized');
        });

        // Measure DOM content loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.performanceMetrics.domContentLoaded = performance.now();
            });
        } else {
            this.performanceMetrics.domContentLoaded = performance.now();
        }
    }

    measurePerformance() {
        // Measure paint metrics if available
        if ('performance' in window && 'getEntriesByType' in performance) {
            // Check for paint entries periodically
            const checkPaintMetrics = () => {
                const paintEntries = performance.getEntriesByType('paint');
                paintEntries.forEach(entry => {
                    if (entry.name === 'first-paint') {
                        this.performanceMetrics.firstPaint = entry.startTime;
                    } else if (entry.name === 'first-contentful-paint') {
                        this.performanceMetrics.firstContentfulPaint = entry.startTime;
                    }
                });
            };

            // Check immediately and after a delay
            checkPaintMetrics();
            setTimeout(checkPaintMetrics, 1000);
        }
    }

    bindEvents() {
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', this.handleSmoothScroll.bind(this));
        });

        // Header scroll effect
        window.addEventListener('scroll', this.debounce(this.handleScroll.bind(this), 10));
        
        // Parallax effect for hero section
        window.addEventListener('scroll', this.requestParallaxUpdate.bind(this));

        // Mobile menu functionality
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', this.toggleMobileMenu.bind(this));
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', this.handleOutsideClick.bind(this));

        // Close mobile menu when clicking a link
        if (navLinks) {
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', this.closeMobileMenu.bind(this));
            });
        }

        // Enhanced hover effects
        this.setupHoverEffects();
        
        // FAQ functionality
        this.setupFAQ();
    }

    handleSmoothScroll(e) {
        e.preventDefault();
        const target = document.querySelector(e.target.getAttribute('href'));
        if (target) {
            const headerHeight = document.querySelector('.header').offsetHeight;
            const targetPosition = target.offsetTop - headerHeight;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    }

    handleScroll() {
        const header = document.querySelector('.header');
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Header background effect
        if (scrollTop > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // Navigation highlighting
        this.highlightNavigation();
        
        this.lastScrollTop = scrollTop;
    }

    highlightNavigation() {
        const sections = document.querySelectorAll('section[id]');
        const navItems = document.querySelectorAll('.nav-links a');
        let current = '';
        const scrollPosition = window.pageYOffset + 200;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });

        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === `#${current}`) {
                item.classList.add('active');
            }
        });
    }

    requestParallaxUpdate() {
        if (!this.ticking) {
            requestAnimationFrame(this.updateParallax.bind(this));
            this.ticking = true;
        }
    }

    updateParallax() {
        const scrolled = window.pageYOffset;
        const hero = document.querySelector('.hero');
        
        if (hero && scrolled < hero.offsetHeight) {
            const heroContent = hero.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.transform = `translateY(${scrolled * 0.1}px)`;
            }
        }
        
        this.ticking = false;
    }

    toggleMobileMenu(e) {
        e.stopPropagation();
        const navLinks = document.querySelector('.nav-links');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        this.isMenuOpen = !this.isMenuOpen;
        
        if (this.isMenuOpen) {
            navLinks.classList.add('active');
            mobileMenuBtn.innerHTML = '<i class="fas fa-times"></i>';
        } else {
            navLinks.classList.remove('active');
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    }

    handleOutsideClick(e) {
        if (this.isMenuOpen && !e.target.closest('.nav')) {
            this.closeMobileMenu();
        }
    }

    closeMobileMenu() {
        const navLinks = document.querySelector('.nav-links');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        navLinks.classList.remove('active');
        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        this.isMenuOpen = false;
    }

    initializeAnimations() {
        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Counter animation handled by lazy-loaded component stats-counter.js

        // Smooth reveal animation stagger
        document.querySelectorAll('.features-grid .feature-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });

        document.querySelectorAll('.testimonial-grid .testimonial-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.15}s`;
        });
    }


    setupHoverEffects() {
        // Enhanced hover effects for feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-12px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Enhanced hover effects for testimonial cards
        document.querySelectorAll('.testimonial-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.borderLeftWidth = '6px';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.borderLeftWidth = '4px';
            });
        });
    }

    setupFAQ() {
        // FAQ accordion functionality
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            
            if (question) {
                question.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');
                    
                    // Close all other FAQ items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                        }
                    });
                    
                    // Toggle current item
                    if (isActive) {
                        item.classList.remove('active');
                    } else {
                        item.classList.add('active');
                    }
                });
            }
        });
        
        // Close FAQ on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                faqItems.forEach(item => {
                    item.classList.remove('active');
                });
            }
        });
    }

    preloadResources() {
        // Preload critical fonts
        const fontLink = document.createElement('link');
        fontLink.rel = 'preload';
        fontLink.as = 'font';
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap';
        fontLink.crossOrigin = 'anonymous';
        document.head.appendChild(fontLink);

        // Preload Font Awesome
        const iconLink = document.createElement('link');
        iconLink.rel = 'preload';
        iconLink.as = 'style';
        iconLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        iconLink.crossOrigin = 'anonymous';
        document.head.appendChild(iconLink);

        // Error handling for external resources
        this.handleResourceErrors();

        // Font loading management
        document.fonts.ready.then(() => {
            document.body.classList.add('fonts-loaded');
        }).catch(() => {
            console.warn('Font loading failed, using system fonts');
            document.body.style.fontFamily = 'system-ui, -apple-system, sans-serif';
        });
    }

    handleResourceErrors() {
        const handleResourceError = (resource, fallback) => {
            resource.addEventListener('error', () => {
                console.warn(`Failed to load ${resource.href || resource.src}, using fallback`);
                if (fallback) fallback();
            });
        };

        // Add error handling for dynamically loaded resources
        document.querySelectorAll('link[rel="preload"]').forEach(link => {
            handleResourceError(link);
        });
    }

    setupAccessibility() {
        // ESC key closes mobile menu
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMobileMenu();
            }
        });

        // Focus management for mobile menu
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('focus', () => {
                mobileMenuBtn.style.outline = '2px solid var(--primary-color)';
            });

            mobileMenuBtn.addEventListener('blur', () => {
                mobileMenuBtn.style.outline = 'none';
            });
        }
    }

    optimizeThirdPartyLoading() {
        // Lazy load third-party resources after main content is loaded
        setTimeout(() => {
            this.loadThirdPartyResources();
        }, 2000);

        // Load third-party resources on user interaction
        const interactionEvents = ['scroll', 'click', 'touchstart', 'mouseover'];
        const loadOnInteraction = () => {
            this.loadThirdPartyResources();
            interactionEvents.forEach(event => {
                document.removeEventListener(event, loadOnInteraction);
            });
        };

        interactionEvents.forEach(event => {
            document.addEventListener(event, loadOnInteraction, { once: true, passive: true });
        });
    }

    loadThirdPartyResources() {
        // Load Google Analytics or other tracking scripts
        this.loadGoogleAnalytics();
        
        // Load social media widgets
        this.loadSocialWidgets();
        
        // Load chat widgets
        this.loadChatWidget();
        
        console.log('Third-party resources loaded');
    }

    loadGoogleAnalytics() {
        // Replace with your GA tracking ID
        const GA_TRACKING_ID = 'G-XXXXXXXXXX';
        
        if (window.gtag || document.querySelector(`script[src*="googletagmanager"]`)) {
            return; // Already loaded
        }

        // Create and load GA script
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_TRACKING_ID}`;
        
        script.onload = () => {
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', GA_TRACKING_ID, {
                page_title: document.title,
                page_location: window.location.href
            });
        };
        
        document.head.appendChild(script);
    }

    loadSocialWidgets() {
        // Load Facebook SDK for social plugins
        if (!window.FB && document.querySelector('.fb-like, .fb-share-button')) {
            const script = document.createElement('script');
            script.async = true;
            script.defer = true;
            script.crossOrigin = 'anonymous';
            script.src = 'https://connect.facebook.net/es_MX/sdk.js#xfbml=1&version=v18.0';
            document.head.appendChild(script);
        }

        // Load Twitter widgets
        if (!window.twttr && document.querySelector('.twitter-share-button')) {
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://platform.twitter.com/widgets.js';
            document.head.appendChild(script);
        }
    }

    loadChatWidget() {
        // Example: Load Crisp chat widget
        // Replace with your actual chat solution
        if (window.CRISP_WEBSITE_ID && !window.$crisp) {
            window.$crisp = [];
            window.CRISP_WEBSITE_ID = "your-crisp-id";
            
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://client.crisp.chat/l.js';
            document.head.appendChild(script);
        }
    }

    logPerformanceMetrics() {
        const metrics = this.performanceMetrics;
        const totalLoadTime = metrics.fullyLoaded - metrics.startTime;
        const domLoadTime = metrics.domContentLoaded - metrics.startTime;

        console.group('🚀 Métricas de Performance');
        console.log(`⏱️ Tiempo total de carga: ${Math.round(totalLoadTime)}ms`);
        console.log(`📄 DOM Content Loaded: ${Math.round(domLoadTime)}ms`);
        
        if (metrics.firstPaint) {
            console.log(`🎨 First Paint: ${Math.round(metrics.firstPaint)}ms`);
        }
        
        if (metrics.firstContentfulPaint) {
            console.log(`📖 First Contentful Paint: ${Math.round(metrics.firstContentfulPaint)}ms`);
        }

        // Log lazy loading stats if available
        if (window.lazyLoader) {
            const lazyStats = window.lazyLoader.getStats();
            console.log(`📷 Imágenes lazy-loaded: ${lazyStats.imagesLoaded}/${lazyStats.totalImages}`);
            console.log(`⚡ Componentes lazy-loaded: ${lazyStats.componentsLoaded}/${lazyStats.totalComponents}`);
        }

        console.groupEnd();

        // Send metrics to analytics if available
        if (window.gtag) {
            gtag('event', 'timing_complete', {
                'name': 'page_load',
                'value': Math.round(totalLoadTime)
            });
        }
    }

    // Utility function: debounce
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize the landing page when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new LandingPage();
});