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
            
            default:
                return value.toLocaleString();
        }
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
}

export default StatsCounter;