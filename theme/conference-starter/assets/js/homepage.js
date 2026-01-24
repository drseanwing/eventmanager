/**
 * Homepage JavaScript
 * 
 * Handles countdown timer and other homepage interactions.
 *
 * @package Conference_Starter
 * @since 1.2.0
 */

(function() {
    'use strict';

    /**
     * Countdown Timer
     * 
     * Displays a countdown to the event date specified in data-countdown attribute.
     */
    function initCountdown() {
        const countdownElement = document.querySelector('.hero__countdown');
        
        if (!countdownElement) {
            return;
        }

        const targetDate = countdownElement.getAttribute('data-countdown');
        
        if (!targetDate) {
            countdownElement.style.display = 'none';
            return;
        }

        const endDate = new Date(targetDate + 'T00:00:00').getTime();
        
        // Check if date is valid
        if (isNaN(endDate)) {
            console.warn('Invalid countdown date:', targetDate);
            countdownElement.style.display = 'none';
            return;
        }

        const daysEl = countdownElement.querySelector('[data-days]');
        const hoursEl = countdownElement.querySelector('[data-hours]');
        const minutesEl = countdownElement.querySelector('[data-minutes]');
        const secondsEl = countdownElement.querySelector('[data-seconds]');

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endDate - now;

            // If countdown is finished
            if (distance < 0) {
                countdownElement.style.display = 'none';
                return;
            }

            // Calculate time units
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Update display
            if (daysEl) daysEl.textContent = days.toString().padStart(2, '0');
            if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
            if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
            if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
        }

        // Initial update
        updateCountdown();

        // Update every second
        setInterval(updateCountdown, 1000);
    }

    /**
     * Smooth Scroll for Anchor Links
     * 
     * Enables smooth scrolling for on-page anchor links.
     */
    function initSmoothScroll() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        
        anchorLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                
                // Skip if it's just "#"
                if (targetId === '#') {
                    return;
                }
                
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    e.preventDefault();
                    
                    const headerOffset = 100; // Account for sticky header
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    /**
     * Intersection Observer for Animations
     * 
     * Adds animation classes when elements come into view.
     */
    function initScrollAnimations() {
        // Check for reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const animatedElements = document.querySelectorAll(
            '.value-card, .program-card, .testimonial-card, .stat-item'
        );

        if (animatedElements.length === 0) {
            return;
        }

        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -50px 0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedElements.forEach(function(el) {
            el.classList.add('animate-on-scroll');
            observer.observe(el);
        });
    }

    /**
     * Hero Parallax Effect
     * 
     * Subtle parallax effect on hero background.
     */
    function initHeroParallax() {
        // Check for reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const hero = document.querySelector('.hero--homepage');
        
        if (!hero) {
            return;
        }

        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const scrolled = window.pageYOffset;
                    const heroHeight = hero.offsetHeight;
                    
                    // Only apply effect when hero is visible
                    if (scrolled < heroHeight) {
                        const parallaxOffset = scrolled * 0.3;
                        hero.style.backgroundPositionY = parallaxOffset + 'px';
                    }
                    
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        initCountdown();
        initSmoothScroll();
        initScrollAnimations();
        initHeroParallax();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();


/**
 * Additional CSS for scroll animations (injected)
 */
(function() {
    // Check for reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const style = document.createElement('style');
    style.textContent = `
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Stagger animation for grids */
        .values-grid .animate-on-scroll:nth-child(2) { transition-delay: 0.1s; }
        .values-grid .animate-on-scroll:nth-child(3) { transition-delay: 0.2s; }
        
        .programs-grid .animate-on-scroll:nth-child(2) { transition-delay: 0.1s; }
        .programs-grid .animate-on-scroll:nth-child(3) { transition-delay: 0.2s; }
        .programs-grid .animate-on-scroll:nth-child(4) { transition-delay: 0.3s; }
        
        .stats-grid .animate-on-scroll:nth-child(2) { transition-delay: 0.1s; }
        .stats-grid .animate-on-scroll:nth-child(3) { transition-delay: 0.2s; }
        .stats-grid .animate-on-scroll:nth-child(4) { transition-delay: 0.3s; }
        
        .testimonials-grid .animate-on-scroll:nth-child(2) { transition-delay: 0.15s; }
    `;
    document.head.appendChild(style);
})();
