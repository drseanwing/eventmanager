/**
 * Theme Customizer Live Preview
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Site title
    wp.customize('blogname', function(value) {
        value.bind(function(to) {
            $('.site-title a').text(to);
        });
    });

    // Site description
    wp.customize('blogdescription', function(value) {
        value.bind(function(to) {
            $('.site-description').text(to);
        });
    });

    // Accent color
    wp.customize('conference_starter_accent_color', function(value) {
        value.bind(function(to) {
            document.documentElement.style.setProperty('--color-accent', to);
        });
    });

    // Secondary accent color
    wp.customize('conference_starter_secondary_accent_color', function(value) {
        value.bind(function(to) {
            document.documentElement.style.setProperty('--color-accent-blue', to);
        });
    });

    // Copyright text
    wp.customize('conference_starter_copyright', function(value) {
        value.bind(function(to) {
            if (to) {
                $('.copyright').html(to);
            } else {
                var year = new Date().getFullYear();
                var siteName = wp.customize('blogname').get();
                $('.copyright').html('&copy; ' + year + ' ' + siteName + '. All rights reserved.');
            }
        });
    });

})(jQuery);
