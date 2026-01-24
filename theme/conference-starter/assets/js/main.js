/**
 * Conference Starter Theme - Main JavaScript
 *
 * @package Conference_Starter
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==========================================================================
    // LOGGER UTILITY
    // ==========================================================================

    const Logger = {
        isDebug: typeof conferenceStarterData !== 'undefined' && conferenceStarterData.isDebug,
        
        log: function(message, data = null) {
            if (this.isDebug) {
                console.log(`[Conference Starter] ${message}`, data || '');
            }
        },
        
        warn: function(message, data = null) {
            if (this.isDebug) {
                console.warn(`[Conference Starter] ${message}`, data || '');
            }
        },
        
        error: function(message, data = null) {
            console.error(`[Conference Starter] ${message}`, data || '');
        }
    };

    // ==========================================================================
    // MOBILE NAVIGATION
    // ==========================================================================

    const MobileNav = {
        toggle: null,
        nav: null,
        overlay: null,
        isOpen: false,

        init: function() {
            this.toggle = document.querySelector('.mobile-menu-toggle');
            this.nav = document.getElementById('mobile-navigation');
            this.overlay = document.querySelector('.mobile-nav-overlay');

            if (!this.toggle || !this.nav) {
                Logger.log('Mobile navigation elements not found');
                return;
            }

            this.bindEvents();
            Logger.log('Mobile navigation initialized');
        },

        bindEvents: function() {
            // Toggle button click
            this.toggle.addEventListener('click', () => this.toggleMenu());

            // Overlay click
            if (this.overlay) {
                this.overlay.addEventListener('click', () => this.closeMenu());
            }

            // Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.closeMenu();
                }
            });

            // Handle submenu toggles on mobile
            const menuItems = this.nav.querySelectorAll('.menu-item-has-children > a');
            menuItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    const parent = item.parentElement;
                    const submenu = parent.querySelector('.sub-menu');
                    
                    if (submenu) {
                        e.preventDefault();
                        parent.classList.toggle('is-expanded');
                        submenu.style.display = parent.classList.contains('is-expanded') ? 'block' : 'none';
                    }
                });
            });
        },

        toggleMenu: function() {
            this.isOpen ? this.closeMenu() : this.openMenu();
        },

        openMenu: function() {
            this.isOpen = true;
            this.toggle.setAttribute('aria-expanded', 'true');
            this.nav.classList.add('is-open');
            this.nav.setAttribute('aria-hidden', 'false');
            
            if (this.overlay) {
                this.overlay.classList.add('is-visible');
            }
            
            document.body.style.overflow = 'hidden';
            Logger.log('Mobile menu opened');
        },

        closeMenu: function() {
            this.isOpen = false;
            this.toggle.setAttribute('aria-expanded', 'false');
            this.nav.classList.remove('is-open');
            this.nav.setAttribute('aria-hidden', 'true');
            
            if (this.overlay) {
                this.overlay.classList.remove('is-visible');
            }
            
            document.body.style.overflow = '';
            Logger.log('Mobile menu closed');
        }
    };

    // ==========================================================================
    // STICKY HEADER
    // ==========================================================================

    const StickyHeader = {
        header: null,
        scrollThreshold: 50,
        isScrolled: false,
        ticking: false,

        init: function() {
            this.header = document.querySelector('.site-header');
            
            if (!this.header) {
                Logger.log('Header element not found');
                return;
            }

            this.bindEvents();
            this.checkScroll();
            Logger.log('Sticky header initialized');
        },

        bindEvents: function() {
            window.addEventListener('scroll', () => this.onScroll(), { passive: true });
        },

        onScroll: function() {
            if (!this.ticking) {
                requestAnimationFrame(() => {
                    this.checkScroll();
                    this.ticking = false;
                });
                this.ticking = true;
            }
        },

        checkScroll: function() {
            const scrolled = window.scrollY > this.scrollThreshold;
            
            if (scrolled !== this.isScrolled) {
                this.isScrolled = scrolled;
                this.header.classList.toggle('is-scrolled', scrolled);
            }
        }
    };

    // ==========================================================================
    // COUNTDOWN TIMER
    // ==========================================================================

    const CountdownTimer = {
        elements: null,
        targetDate: null,
        interval: null,

        init: function() {
            this.elements = document.querySelectorAll('[data-countdown]');
            
            if (!this.elements.length) {
                Logger.log('No countdown elements found');
                return;
            }

            this.elements.forEach(element => this.initCountdown(element));
            Logger.log('Countdown timers initialized', { count: this.elements.length });
        },

        initCountdown: function(element) {
            const targetDate = element.getAttribute('data-countdown');
            
            if (!targetDate) {
                Logger.warn('Countdown element missing data-countdown attribute');
                return;
            }

            const target = new Date(targetDate).getTime();
            
            if (isNaN(target)) {
                Logger.error('Invalid countdown date', { date: targetDate });
                return;
            }

            // Initial update
            this.updateCountdown(element, target);

            // Update every second
            setInterval(() => this.updateCountdown(element, target), 1000);
        },

        updateCountdown: function(element, target) {
            const now = new Date().getTime();
            const distance = target - now;

            if (distance < 0) {
                element.innerHTML = '<span class="countdown-expired">Event has started!</span>';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            const i18n = typeof conferenceStarterData !== 'undefined' ? conferenceStarterData.i18n : {
                days: 'Days',
                hours: 'Hours',
                minutes: 'Minutes',
                seconds: 'Seconds'
            };

            element.innerHTML = `
                <div class="countdown-item">
                    <span class="countdown-value">${days}</span>
                    <span class="countdown-label">${i18n.days}</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value">${hours.toString().padStart(2, '0')}</span>
                    <span class="countdown-label">${i18n.hours}</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value">${minutes.toString().padStart(2, '0')}</span>
                    <span class="countdown-label">${i18n.minutes}</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value">${seconds.toString().padStart(2, '0')}</span>
                    <span class="countdown-label">${i18n.seconds}</span>
                </div>
            `;
        }
    };

    // ==========================================================================
    // SMOOTH SCROLL
    // ==========================================================================

    const SmoothScroll = {
        init: function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => this.handleClick(e, anchor));
            });
            Logger.log('Smooth scroll initialized');
        },

        handleClick: function(e, anchor) {
            const targetId = anchor.getAttribute('href');
            
            // Ignore empty hashes and non-hash links
            if (!targetId || targetId === '#') return;
            
            const target = document.querySelector(targetId);
            
            if (target) {
                e.preventDefault();
                
                const headerOffset = 100; // Account for sticky header
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Update URL without jumping
                history.pushState(null, null, targetId);
                
                Logger.log('Smooth scrolled to', { target: targetId });
            }
        }
    };

    // ==========================================================================
    // SCROLL REVEAL ANIMATIONS
    // ==========================================================================

    const ScrollReveal = {
        elements: null,
        observer: null,

        init: function() {
            this.elements = document.querySelectorAll('[data-reveal]');
            
            if (!this.elements.length) {
                Logger.log('No reveal elements found');
                return;
            }

            if (!('IntersectionObserver' in window)) {
                // Fallback: show all elements
                this.elements.forEach(el => el.classList.add('is-revealed'));
                Logger.warn('IntersectionObserver not supported, showing all elements');
                return;
            }

            this.setupObserver();
            this.observe();
            Logger.log('Scroll reveal initialized', { count: this.elements.length });
        },

        setupObserver: function() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        this.observer.unobserve(entry.target);
                    }
                });
            }, {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.1
            });
        },

        observe: function() {
            this.elements.forEach(el => this.observer.observe(el));
        }
    };

    // ==========================================================================
    // SCHEDULE TABS
    // ==========================================================================

    const ScheduleTabs = {
        init: function() {
            const tabContainers = document.querySelectorAll('.schedule-tabs');
            
            if (!tabContainers.length) {
                Logger.log('No schedule tabs found');
                return;
            }

            tabContainers.forEach(container => this.initTabs(container));
            Logger.log('Schedule tabs initialized');
        },

        initTabs: function(container) {
            const tabs = container.querySelectorAll('.schedule-tab');
            const panels = container.parentElement.querySelectorAll('.schedule-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Deactivate all tabs and panels
                    tabs.forEach(t => t.classList.remove('is-active'));
                    panels.forEach(p => p.classList.remove('is-active'));

                    // Activate clicked tab and corresponding panel
                    tab.classList.add('is-active');
                    const panelId = tab.getAttribute('data-panel');
                    const panel = document.getElementById(panelId);
                    
                    if (panel) {
                        panel.classList.add('is-active');
                    }
                });
            });
        }
    };

    // ==========================================================================
    // ACCORDION / FAQ
    // ==========================================================================

    const Accordion = {
        init: function() {
            const accordions = document.querySelectorAll('[data-accordion]');
            
            if (!accordions.length) {
                Logger.log('No accordion elements found');
                return;
            }

            accordions.forEach(accordion => this.initAccordion(accordion));
            Logger.log('Accordions initialized', { count: accordions.length });
        },

        initAccordion: function(accordion) {
            const items = accordion.querySelectorAll('.accordion-item');
            const allowMultiple = accordion.getAttribute('data-accordion') === 'multiple';

            items.forEach(item => {
                const trigger = item.querySelector('.accordion-trigger');
                const content = item.querySelector('.accordion-content');

                if (!trigger || !content) return;

                trigger.addEventListener('click', () => {
                    const isOpen = item.classList.contains('is-open');

                    // If single mode, close all other items
                    if (!allowMultiple) {
                        items.forEach(i => {
                            i.classList.remove('is-open');
                            const c = i.querySelector('.accordion-content');
                            if (c) c.style.maxHeight = null;
                        });
                    }

                    // Toggle current item
                    if (isOpen) {
                        item.classList.remove('is-open');
                        content.style.maxHeight = null;
                    } else {
                        item.classList.add('is-open');
                        content.style.maxHeight = content.scrollHeight + 'px';
                    }
                });
            });
        }
    };

    // ==========================================================================
    // FORM VALIDATION
    // ==========================================================================

    const FormValidation = {
        init: function() {
            const forms = document.querySelectorAll('form[data-validate]');
            
            if (!forms.length) {
                Logger.log('No forms to validate');
                return;
            }

            forms.forEach(form => this.initForm(form));
            Logger.log('Form validation initialized', { count: forms.length });
        },

        initForm: function(form) {
            const inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                // Real-time validation
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearError(input));
            });

            // Form submission
            form.addEventListener('submit', (e) => {
                let isValid = true;

                inputs.forEach(input => {
                    if (!this.validateField(input)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    Logger.log('Form validation failed');
                }
            });
        },

        validateField: function(input) {
            const value = input.value.trim();
            let isValid = true;
            let errorMessage = '';

            // Required validation
            if (input.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required';
            }

            // Email validation
            if (input.type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
            }

            // Min length validation
            const minLength = input.getAttribute('minlength');
            if (minLength && value.length < parseInt(minLength)) {
                isValid = false;
                errorMessage = `Please enter at least ${minLength} characters`;
            }

            // Show/hide error
            if (!isValid) {
                this.showError(input, errorMessage);
            } else {
                this.clearError(input);
            }

            return isValid;
        },

        showError: function(input, message) {
            input.classList.add('is-invalid');
            
            let errorEl = input.parentElement.querySelector('.form-error');
            
            if (!errorEl) {
                errorEl = document.createElement('span');
                errorEl.className = 'form-error';
                input.parentElement.appendChild(errorEl);
            }
            
            errorEl.textContent = message;
        },

        clearError: function(input) {
            input.classList.remove('is-invalid');
            
            const errorEl = input.parentElement.querySelector('.form-error');
            if (errorEl) {
                errorEl.remove();
            }
        }
    };

    // ==========================================================================
    // LAZY LOADING
    // ==========================================================================

    const LazyLoad = {
        init: function() {
            if ('loading' in HTMLImageElement.prototype) {
                // Native lazy loading supported
                const images = document.querySelectorAll('img[loading="lazy"]');
                Logger.log('Native lazy loading used', { count: images.length });
            } else {
                // Fallback with IntersectionObserver
                this.initFallback();
            }
        },

        initFallback: function() {
            const images = document.querySelectorAll('img[data-src]');
            
            if (!images.length || !('IntersectionObserver' in window)) {
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.getAttribute('data-src');
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => observer.observe(img));
            Logger.log('Lazy loading fallback initialized', { count: images.length });
        }
    };

    // ==========================================================================
    // INITIALIZE ALL MODULES
    // ==========================================================================

    function init() {
        Logger.log('Initializing Conference Starter theme JavaScript');

        // Core functionality
        MobileNav.init();
        StickyHeader.init();
        SmoothScroll.init();

        // Interactive components
        CountdownTimer.init();
        ScheduleTabs.init();
        Accordion.init();

        // Enhancements
        ScrollReveal.init();
        FormValidation.init();
        LazyLoad.init();

        Logger.log('All modules initialized');
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
