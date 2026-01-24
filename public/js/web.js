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
/**
 * Contact Form Validation & Security
 * J2Biznes Landing Page
 *
 * Validaciones:
 * - Campos requeridos
 * - Formato email
 * - Teléfono 10 dígitos
 * - Sanitización contra XSS/Inyección
 * - Protección contra spam básica
 */

(function() {
    'use strict';

    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initContactForm();
    });

    function initContactForm() {
        const form = document.getElementById('contactForm');
        if (!form) return;

        // Referencias a elementos
        const fields = {
            name: document.getElementById('contact_name'),
            email: document.getElementById('contact_email'),
            phone: document.getElementById('contact_phone'),
            company: document.getElementById('contact_company'),
            message: document.getElementById('contact_message'),
            isWhatsapp: document.getElementById('contact_is_whatsapp')
        };

        const errors = {
            name: document.getElementById('error_name'),
            email: document.getElementById('error_email'),
            phone: document.getElementById('error_phone'),
            company: document.getElementById('error_company'),
            message: document.getElementById('error_message')
        };

        const formError = document.getElementById('form_error');
        const formErrorText = document.getElementById('form_error_text');
        const formSuccess = document.getElementById('form_success');
        const submitBtn = document.getElementById('contact_submit_btn');

        // Honeypot anti-spam (campo oculto)
        addHoneypot(form);

        // Timestamp anti-bot
        const formLoadTime = Date.now();

        // Validación en tiempo real
        if (fields.name) {
            fields.name.addEventListener('blur', () => validateName(fields.name, errors.name));
            fields.name.addEventListener('input', () => sanitizeInput(fields.name));
        }

        if (fields.email) {
            fields.email.addEventListener('blur', () => validateEmail(fields.email, errors.email));
            fields.email.addEventListener('input', () => sanitizeInput(fields.email));
        }

        if (fields.phone) {
            fields.phone.addEventListener('input', (e) => {
                // Solo permitir números
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                sanitizeInput(fields.phone);
            });
            fields.phone.addEventListener('blur', () => validatePhone(fields.phone, errors.phone));
        }

        if (fields.company) {
            fields.company.addEventListener('input', () => sanitizeInput(fields.company));
        }

        if (fields.message) {
            fields.message.addEventListener('blur', () => validateMessage(fields.message, errors.message));
            fields.message.addEventListener('input', () => sanitizeInput(fields.message));
        }

        // Envío del formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Ocultar alertas previas
            hideElement(formError);
            hideElement(formSuccess);

            // Anti-bot: verificar tiempo mínimo (2 segundos)
            const timeDiff = Date.now() - formLoadTime;
            if (timeDiff < 2000) {
                showError(formError, formErrorText, 'Por favor espera un momento antes de enviar.');
                return;
            }

            // Verificar honeypot
            const honeypot = form.querySelector('input[name="website_url"]');
            if (honeypot && honeypot.value !== '') {
                // Bot detectado, simular éxito sin enviar
                console.warn('Bot detected via honeypot');
                showElement(formSuccess);
                return;
            }

            // Validar todos los campos
            const isValid = validateAll(fields, errors);

            if (!isValid) {
                showError(formError, formErrorText, 'Por favor corrige los errores marcados en rojo.');
                scrollToElement(formError);
                return;
            }

            // Sanitizar todos los datos antes de enviar
            const formData = sanitizeFormData(fields);

            // Verificar contenido malicioso
            const maliciousCheck = checkMaliciousContent(formData);
            if (maliciousCheck.isMalicious) {
                showError(formError, formErrorText, 'Se detectó contenido no permitido: ' + maliciousCheck.reason);
                return;
            }

            // Mostrar estado de carga
            setLoadingState(submitBtn, true);

            // Enviar formulario al backend
            submitToBackend(form, formData, formSuccess, formError, formErrorText, submitBtn, fields);
        });
    }

    // ==================== VALIDADORES ====================

    function validateName(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El nombre es requerido');
            return false;
        }

        if (value.length < 3) {
            setError(input, errorEl, 'El nombre debe tener al menos 3 caracteres');
            return false;
        }

        if (value.length > 100) {
            setError(input, errorEl, 'El nombre no puede exceder 100 caracteres');
            return false;
        }

        // Solo letras, espacios y caracteres latinos
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'-]+$/;
        if (!nameRegex.test(value)) {
            setError(input, errorEl, 'El nombre solo puede contener letras');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validateEmail(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El email es requerido');
            return false;
        }

        // Regex para email válido
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(value)) {
            setError(input, errorEl, 'Ingresa un email válido');
            return false;
        }

        if (value.length > 100) {
            setError(input, errorEl, 'El email no puede exceder 100 caracteres');
            return false;
        }

        // Verificar dominios de email temporales/spam comunes
        const spamDomains = ['tempmail', 'throwaway', 'guerrillamail', 'mailinator', '10minutemail'];
        const domain = value.split('@')[1].toLowerCase();
        if (spamDomains.some(spam => domain.includes(spam))) {
            setError(input, errorEl, 'Por favor usa un email válido, no temporal');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validatePhone(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El teléfono es requerido');
            return false;
        }

        // Solo números, exactamente 10 dígitos
        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(value)) {
            setError(input, errorEl, 'El teléfono debe tener exactamente 10 dígitos');
            return false;
        }

        // Verificar que no sean todos el mismo dígito
        if (/^(\d)\1{9}$/.test(value)) {
            setError(input, errorEl, 'Ingresa un número de teléfono válido');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validateMessage(input, errorEl) {
        const value = input.value.trim();

        if (!value) {
            setError(input, errorEl, 'El mensaje es requerido');
            return false;
        }

        if (value.length < 10) {
            setError(input, errorEl, 'El mensaje debe tener al menos 10 caracteres');
            return false;
        }

        if (value.length > 1000) {
            setError(input, errorEl, 'El mensaje no puede exceder 1000 caracteres');
            return false;
        }

        setValid(input, errorEl);
        return true;
    }

    function validateAll(fields, errors) {
        let isValid = true;

        if (!validateName(fields.name, errors.name)) isValid = false;
        if (!validateEmail(fields.email, errors.email)) isValid = false;
        if (!validatePhone(fields.phone, errors.phone)) isValid = false;
        if (!validateMessage(fields.message, errors.message)) isValid = false;

        return isValid;
    }

    // ==================== SANITIZACIÓN ====================

    function sanitizeInput(input) {
        if (!input) return;

        let value = input.value;

        // Eliminar tags HTML
        value = value.replace(/<[^>]*>/g, '');

        // Eliminar scripts inline
        value = value.replace(/javascript:/gi, '');
        value = value.replace(/on\w+\s*=/gi, '');

        // Eliminar caracteres de control
        value = value.replace(/[\x00-\x1F\x7F]/g, '');

        input.value = value;
    }

    function sanitizeFormData(fields) {
        return {
            name: escapeHtml(fields.name.value.trim()),
            email: escapeHtml(fields.email.value.trim().toLowerCase()),
            phone: fields.phone.value.trim().replace(/[^0-9]/g, ''),
            company: escapeHtml(fields.company.value.trim()),
            message: escapeHtml(fields.message.value.trim()),
            is_whatsapp: fields.isWhatsapp.checked ? 1 : 0
        };
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };
        return text.replace(/[&<>"'`=\/]/g, function(m) { return map[m]; });
    }

    // ==================== DETECCIÓN DE CONTENIDO MALICIOSO ====================

    function checkMaliciousContent(data) {
        const allText = Object.values(data).join(' ').toLowerCase();

        // Patrones de SQL Injection
        const sqlPatterns = [
            /(\b(select|insert|update|delete|drop|union|exec|execute)\b.*\b(from|into|where|table)\b)/i,
            /(--|;|\/\*|\*\/)/,
            /(\bor\b|\band\b)\s*[\d'"].*[=<>]/i,
            /['"].*(\bor\b|\band\b).*['"]/i
        ];

        // Patrones de XSS
        const xssPatterns = [
            /<script[\s\S]*?>[\s\S]*?<\/script>/gi,
            /javascript\s*:/gi,
            /on\w+\s*=\s*["']?[^"']*["']?/gi,
            /<iframe[\s\S]*?>/gi,
            /<object[\s\S]*?>/gi,
            /<embed[\s\S]*?>/gi,
            /<link[\s\S]*?>/gi,
            /expression\s*\(/gi,
            /url\s*\(\s*["']?\s*data:/gi
        ];

        // Patrones de inyección de comandos
        const cmdPatterns = [
            /[;&|`$]|\$\(/,
            /\.\.\//,
            /(\/etc\/passwd|\/bin\/sh|cmd\.exe)/i
        ];

        // Patrones de phishing/spam
        const spamPatterns = [
            /\b(viagra|cialis|lottery|winner|congratulations|click here|act now|limited time)\b/gi,
            /(bit\.ly|tinyurl|goo\.gl|t\.co)\/\w+/gi,
            /\b\d{16}\b/, // Números de tarjeta
        ];

        // Verificar SQL Injection
        for (const pattern of sqlPatterns) {
            if (pattern.test(allText)) {
                return { isMalicious: true, reason: 'Caracteres no permitidos detectados' };
            }
        }

        // Verificar XSS
        for (const pattern of xssPatterns) {
            if (pattern.test(allText)) {
                return { isMalicious: true, reason: 'Código no permitido detectado' };
            }
        }

        // Verificar inyección de comandos
        for (const pattern of cmdPatterns) {
            if (pattern.test(allText)) {
                return { isMalicious: true, reason: 'Caracteres especiales no permitidos' };
            }
        }

        // Verificar spam/phishing
        let spamCount = 0;
        for (const pattern of spamPatterns) {
            if (pattern.test(allText)) spamCount++;
        }
        if (spamCount >= 2) {
            return { isMalicious: true, reason: 'Contenido sospechoso detectado' };
        }

        // Verificar URLs excesivas
        const urlRegex = /https?:\/\/[^\s]+/gi;
        const urls = allText.match(urlRegex) || [];
        if (urls.length > 3) {
            return { isMalicious: true, reason: 'Demasiados enlaces en el mensaje' };
        }

        return { isMalicious: false };
    }

    // ==================== HONEYPOT ANTI-SPAM ====================

    function addHoneypot(form) {
        const honeypot = document.createElement('div');
        honeypot.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
        honeypot.innerHTML = '<input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">';
        form.appendChild(honeypot);
    }

    // ==================== UI HELPERS ====================

    function setError(input, errorEl, message) {
        if (input) {
            input.classList.remove('valid');
            input.classList.add('error');
        }
        if (errorEl) {
            errorEl.textContent = message;
        }
    }

    function setValid(input, errorEl) {
        if (input) {
            input.classList.remove('error');
            input.classList.add('valid');
        }
        if (errorEl) {
            errorEl.textContent = '';
        }
    }

    function showError(element, textElement, message) {
        if (textElement) textElement.textContent = message;
        showElement(element);
    }

    function showElement(element) {
        if (element) element.style.display = 'flex';
    }

    function hideElement(element) {
        if (element) element.style.display = 'none';
    }

    function scrollToElement(element) {
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function setLoadingState(btn, isLoading) {
        if (!btn) return;

        if (isLoading) {
            btn.classList.add('loading');
            btn.disabled = true;
        } else {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }

    function resetForm(form, fields) {
        form.reset();

        // Limpiar clases de validación
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('error', 'valid');
            }
        });

        // Limpiar mensajes de error
        document.querySelectorAll('.field-error').forEach(el => {
            el.textContent = '';
        });
    }

    // ==================== ENVÍO AL BACKEND ====================

    function submitToBackend(form, formData, successEl, errorEl, errorTextEl, submitBtn, fields) {
        // Obtener CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || document.querySelector('input[name="_token"]')?.value;

        // Preparar datos para enviar
        const payload = {
            name: formData.name,
            email: formData.email,
            phone: formData.phone,
            is_whatsapp: formData.is_whatsapp,
            company: formData.company,
            message: formData.message,
            website_url: form.querySelector('input[name="website_url"]')?.value || '' // Honeypot
        };

        // Enviar al backend
        fetch('/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json().then(data => ({ status: response.status, data })))
        .then(({ status, data }) => {
            setLoadingState(submitBtn, false);

            if (status === 200 && data.success) {
                // Éxito
                showElement(successEl);
                resetForm(form, fields);
                scrollToElement(successEl);

                // Ocultar mensaje de éxito después de 5 segundos
                setTimeout(function() {
                    hideElement(successEl);
                }, 5000);
            } else if (status === 422 && data.errors) {
                // Errores de validación
                showError(errorEl, errorTextEl, data.message || 'Por favor corrige los errores.');

                // Mostrar errores en campos específicos
                Object.keys(data.errors).forEach(field => {
                    const errorSpan = document.getElementById('error_' + field);
                    const input = document.getElementById('contact_' + field);
                    if (errorSpan) {
                        errorSpan.textContent = data.errors[field][0];
                    }
                    if (input) {
                        input.classList.add('error');
                        input.classList.remove('valid');
                    }
                });

                scrollToElement(errorEl);
            } else if (status === 429) {
                // Rate limit
                showError(errorEl, errorTextEl, data.message || 'Demasiados intentos. Intenta más tarde.');
                scrollToElement(errorEl);
            } else {
                // Otro error
                showError(errorEl, errorTextEl, data.message || 'Ocurrió un error. Intenta de nuevo.');
                scrollToElement(errorEl);
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            setLoadingState(submitBtn, false);
            showError(errorEl, errorTextEl, 'Error de conexión. Verifica tu internet e intenta de nuevo.');
            scrollToElement(errorEl);
        });
    }

})();
