/**
 * Conference Starter Theme - Main JavaScript
 *
 * Handles mobile navigation, sticky header, and other interactions.
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * DOM Ready
     */
    $(document).ready(function() {
        initMobileNav();
        initStickyHeader();
        initSmoothScroll();
        initScheduleTabs();
        initExpandableContent();
    });

    /**
     * Mobile Navigation Toggle
     */
    function initMobileNav() {
        var $toggle = $('#menu-toggle');
        var $nav = $('#primary-menu');
        var $body = $('body');

        if (!$toggle.length || !$nav.length) {
            return;
        }

        $toggle.on('click', function(e) {
            e.preventDefault();
            
            var isExpanded = $(this).attr('aria-expanded') === 'true';
            
            $(this).attr('aria-expanded', !isExpanded);
            $nav.toggleClass('active');
            $body.toggleClass('nav-open');
        });

        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.site-header').length && $nav.hasClass('active')) {
                $toggle.attr('aria-expanded', 'false');
                $nav.removeClass('active');
                $body.removeClass('nav-open');
            }
        });

        // Close menu on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $nav.hasClass('active')) {
                $toggle.attr('aria-expanded', 'false');
                $nav.removeClass('active');
                $body.removeClass('nav-open');
                $toggle.focus();
            }
        });

        // Handle window resize
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if ($(window).width() > 768 && $nav.hasClass('active')) {
                    $toggle.attr('aria-expanded', 'false');
                    $nav.removeClass('active');
                    $body.removeClass('nav-open');
                }
            }, 250);
        });
    }

    /**
     * Sticky Header
     */
    function initStickyHeader() {
        var $header = $('.site-header');
        var $body = $('body');
        
        if (!$body.hasClass('sticky-header') || !$header.length) {
            return;
        }

        var headerHeight = $header.outerHeight();
        var lastScrollTop = 0;
        var scrollThreshold = 100;

        // Add placeholder for fixed header
        $('<div class="header-placeholder"></div>')
            .css('height', headerHeight)
            .insertAfter($header);

        $(window).on('scroll', function() {
            var scrollTop = $(this).scrollTop();

            if (scrollTop > scrollThreshold) {
                $header.addClass('sticky');
            } else {
                $header.removeClass('sticky');
            }

            lastScrollTop = scrollTop;
        });

        // Trigger on load
        $(window).trigger('scroll');
    }

    /**
     * Smooth Scroll for Anchor Links
     */
    function initSmoothScroll() {
        $('a[href*="#"]:not([href="#"])').on('click', function(e) {
            if (
                location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') &&
                location.hostname === this.hostname
            ) {
                var $target = $(this.hash);
                $target = $target.length ? $target : $('[name=' + this.hash.slice(1) + ']');
                
                if ($target.length) {
                    e.preventDefault();
                    
                    var headerHeight = $('.site-header').outerHeight() || 0;
                    var targetOffset = $target.offset().top - headerHeight - 20;

                    $('html, body').animate({
                        scrollTop: targetOffset
                    }, 500);

                    // Set focus for accessibility
                    $target.attr('tabindex', '-1').focus();
                }
            }
        });
    }

    /**
     * Schedule Tabs
     */
    function initScheduleTabs() {
        var $tabs = $('.schedule-tabs');
        
        if (!$tabs.length) {
            return;
        }

        $tabs.find('a').on('click', function(e) {
            e.preventDefault();
            
            var targetId = $(this).attr('href');
            var $target = $(targetId);
            
            if (!$target.length) {
                return;
            }

            // Update active states
            $tabs.find('li').removeClass('active');
            $(this).parent('li').addClass('active');

            // Show/hide content
            $('.schedule-tab-content').removeClass('active').hide();
            $target.addClass('active').show();
        });

        // Initialize first tab
        $tabs.find('li:first-child a').trigger('click');
    }

    /**
     * Expandable Content (for schedule sessions)
     */
    function initExpandableContent() {
        // Session expand/collapse
        $('.session-item.expandable').on('click', function() {
            var $extended = $(this).find('.session-extended');
            $extended.toggleClass('hidden');
            $(this).toggleClass('expanded');
        });

        // Expand All toggle
        $('.expand-all-toggle').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var isExpanded = $this.data('expanded') || false;
            var $sessions = $this.closest('.schedule-wrapper').find('.session-extended');

            if (isExpanded) {
                $sessions.addClass('hidden');
                $this.text(conferenceStarter.i18n.expandAll || 'Expand All');
            } else {
                $sessions.removeClass('hidden');
                $this.text(conferenceStarter.i18n.collapseAll || 'Collapse All');
            }

            $this.data('expanded', !isExpanded);
        });
    }

    /**
     * Utility: Throttle function
     */
    function throttle(func, wait) {
        var timeout = null;
        var previous = 0;
        
        return function() {
            var now = Date.now();
            var remaining = wait - (now - previous);
            var context = this;
            var args = arguments;
            
            if (remaining <= 0 || remaining > wait) {
                if (timeout) {
                    clearTimeout(timeout);
                    timeout = null;
                }
                previous = now;
                func.apply(context, args);
            } else if (!timeout) {
                timeout = setTimeout(function() {
                    previous = Date.now();
                    timeout = null;
                    func.apply(context, args);
                }, remaining);
            }
        };
    }

})(jQuery);
