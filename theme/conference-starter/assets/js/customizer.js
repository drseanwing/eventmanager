/**
 * Conference Starter Customizer Preview
 *
 * Handles real-time preview updates in the WordPress Customizer.
 * Uses postMessage transport for instant feedback without page reload.
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Bail if wp.customize is not available
    if (typeof wp === 'undefined' || typeof wp.customize === 'undefined') {
        console.warn('Conference Starter: wp.customize not available');
        return;
    }

    /**
     * Logger for development debugging
     */
    var Logger = {
        enabled: false, // Set to true for development
        
        log: function(message, data) {
            if (this.enabled && console && console.log) {
                console.log('[Customizer Preview] ' + message, data || '');
            }
        }
    };

    /**
     * Helper to update CSS custom property
     * 
     * @param {string} property - CSS custom property name (without --)
     * @param {string} value - New value
     */
    function updateCSSProperty(property, value) {
        document.documentElement.style.setProperty('--' + property, value);
        Logger.log('Updated CSS property: --' + property, value);
    }

    /**
     * Helper to update element text content
     * 
     * @param {string} selector - CSS selector
     * @param {string} text - New text content
     */
    function updateText(selector, text) {
        var elements = document.querySelectorAll(selector);
        elements.forEach(function(el) {
            el.textContent = text;
        });
        Logger.log('Updated text for: ' + selector, text);
    }

    /**
     * Helper to update element HTML content
     * 
     * @param {string} selector - CSS selector
     * @param {string} html - New HTML content
     */
    function updateHTML(selector, html) {
        var elements = document.querySelectorAll(selector);
        elements.forEach(function(el) {
            el.innerHTML = html;
        });
        Logger.log('Updated HTML for: ' + selector);
    }

    /**
     * Helper to update link href
     * 
     * @param {string} selector - CSS selector
     * @param {string} url - New URL
     */
    function updateLink(selector, url) {
        var elements = document.querySelectorAll(selector);
        elements.forEach(function(el) {
            el.setAttribute('href', url);
        });
        Logger.log('Updated link for: ' + selector, url);
    }

    /**
     * Helper to toggle element visibility
     * 
     * @param {string} selector - CSS selector
     * @param {boolean} visible - Show or hide
     */
    function toggleVisibility(selector, visible) {
        var elements = document.querySelectorAll(selector);
        elements.forEach(function(el) {
            el.style.display = visible ? '' : 'none';
        });
        Logger.log('Toggle visibility for: ' + selector, visible);
    }

    // =========================================================================
    // Site Identity
    // =========================================================================

    // Site title
    wp.customize('blogname', function(value) {
        value.bind(function(newval) {
            updateText('.site-title a', newval);
            updateText('.site-branding__title a', newval);
        });
    });

    // Site tagline/description
    wp.customize('blogdescription', function(value) {
        value.bind(function(newval) {
            updateText('.site-description', newval);
            updateText('.site-branding__description', newval);
        });
    });

    // =========================================================================
    // Header Settings
    // =========================================================================

    // Header CTA text
    wp.customize('conference_starter_header_cta_text', function(value) {
        value.bind(function(newval) {
            updateText('.header__cta .btn__text', newval);
            updateText('.header-cta-text', newval);
            // Toggle visibility if empty
            toggleVisibility('.header__cta', newval.length > 0);
        });
    });

    // Header CTA URL
    wp.customize('conference_starter_header_cta_url', function(value) {
        value.bind(function(newval) {
            updateLink('.header__cta', newval);
        });
    });

    // =========================================================================
    // Color Settings
    // =========================================================================

    // Primary color
    wp.customize('conference_starter_primary_color', function(value) {
        value.bind(function(newval) {
            updateCSSProperty('color-primary', newval);
            // Also update related shades (simplified)
            updateCSSProperty('color-primary-600', newval);
        });
    });

    // Secondary color
    wp.customize('conference_starter_secondary_color', function(value) {
        value.bind(function(newval) {
            updateCSSProperty('color-secondary', newval);
            updateCSSProperty('color-secondary-600', newval);
        });
    });

    // Accent color
    wp.customize('conference_starter_accent_color', function(value) {
        value.bind(function(newval) {
            updateCSSProperty('color-accent', newval);
            updateCSSProperty('color-accent-600', newval);
        });
    });

    // =========================================================================
    // Event Settings
    // =========================================================================

    // Event date (for countdown)
    wp.customize('conference_starter_event_date', function(value) {
        value.bind(function(newval) {
            var countdownEl = document.querySelector('[data-countdown]');
            if (countdownEl) {
                countdownEl.setAttribute('data-countdown', newval);
                // Trigger countdown reinitialize if available
                if (window.ConferenceStarter && window.ConferenceStarter.CountdownTimer) {
                    window.ConferenceStarter.CountdownTimer.init();
                }
            }
            Logger.log('Updated event date', newval);
        });
    });

    // Event venue
    wp.customize('conference_starter_event_venue', function(value) {
        value.bind(function(newval) {
            updateText('.event-venue', newval);
            updateText('.hero__venue', newval);
        });
    });

    // Event location
    wp.customize('conference_starter_event_location', function(value) {
        value.bind(function(newval) {
            updateText('.event-location', newval);
            updateText('.hero__location', newval);
        });
    });

    // =========================================================================
    // Social Media Links
    // =========================================================================

    var socialPlatforms = ['twitter', 'linkedin', 'facebook', 'instagram', 'youtube'];

    socialPlatforms.forEach(function(platform) {
        wp.customize('conference_starter_social_' + platform, function(value) {
            value.bind(function(newval) {
                var selector = '.social-link--' + platform;
                updateLink(selector, newval);
                toggleVisibility(selector, newval.length > 0);
            });
        });
    });

    // =========================================================================
    // Footer Settings
    // =========================================================================

    // Footer description
    wp.customize('conference_starter_footer_description', function(value) {
        value.bind(function(newval) {
            updateText('.footer__description', newval);
            updateText('.site-info__description', newval);
        });
    });

    // Copyright text
    wp.customize('conference_starter_copyright_text', function(value) {
        value.bind(function(newval) {
            // Replace {{year}} with current year
            var processedText = newval.replace('{{year}}', new Date().getFullYear());
            updateHTML('.footer__copyright', processedText);
            updateHTML('.site-info__copyright', processedText);
        });
    });

    // =========================================================================
    // Typography Settings (if implemented)
    // =========================================================================

    // Body font family
    wp.customize('conference_starter_body_font', function(value) {
        value.bind(function(newval) {
            updateCSSProperty('font-family-base', newval);
        });
    });

    // Heading font family
    wp.customize('conference_starter_heading_font', function(value) {
        value.bind(function(newval) {
            updateCSSProperty('font-family-heading', newval);
        });
    });

    // Base font size
    wp.customize('conference_starter_base_font_size', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.fontSize = newval;
        });
    });

    // =========================================================================
    // Layout Settings (if implemented)
    // =========================================================================

    // Container max width
    wp.customize('conference_starter_container_width', function(value) {
        value.bind(function(newval) {
            updateCSSProperty('container-max-width', newval + 'px');
        });
    });

    // =========================================================================
    // Initialize
    // =========================================================================

    Logger.log('Customizer preview script loaded');

})(jQuery);
