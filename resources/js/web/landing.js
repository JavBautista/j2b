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