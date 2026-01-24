<?php
/**
 * Conference Starter Theme Functions
 *
 * @package Conference_Starter
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme version and constants
define('CONFERENCE_STARTER_VERSION', '2.3.0');
define('CONFERENCE_STARTER_DIR', get_template_directory());
define('CONFERENCE_STARTER_URI', get_template_directory_uri());

/**
 * ==========================================================================
 * LOGGING SYSTEM
 * ==========================================================================
 */

if (!function_exists('conference_starter_log')) {
    /**
     * Log messages to theme-specific log file
     *
     * @param string $message  Log message
     * @param string $level    Log level: debug, info, warning, error
     * @param mixed  $context  Additional context data
     */
    function conference_starter_log($message, $level = 'info', $context = null) {
        // Only log in debug mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log_file = WP_CONTENT_DIR . '/conference-starter-debug.log';
        $timestamp = current_time('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        $log_entry = "[{$timestamp}] [{$level}] {$message}";
        
        if ($context !== null) {
            $log_entry .= ' | Context: ' . print_r($context, true);
        }
        
        $log_entry .= PHP_EOL;
        
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * ==========================================================================
 * THEME SETUP
 * ==========================================================================
 */

if (!function_exists('conference_starter_setup')) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     */
    function conference_starter_setup() {
        conference_starter_log('Theme setup initiated', 'debug');

        // Load text domain for translations
        load_theme_textdomain('conference-starter', CONFERENCE_STARTER_DIR . '/languages');

        // Add default posts and comments RSS feed links
        add_theme_support('automatic-feed-links');

        // Let WordPress manage the document title
        add_theme_support('title-tag');

        // Enable support for Post Thumbnails
        add_theme_support('post-thumbnails');

        // Custom image sizes
        add_image_size('conference-hero', 1920, 800, true);
        add_image_size('conference-featured', 1200, 630, true);
        add_image_size('conference-card', 600, 400, true);
        add_image_size('conference-speaker', 400, 400, true);
        add_image_size('conference-thumbnail', 150, 150, true);

        // Register navigation menus
        register_nav_menus(array(
            'primary'      => esc_html__('Primary Menu', 'conference-starter'),
            'footer'       => esc_html__('Footer Menu', 'conference-starter'),
            'footer-legal' => esc_html__('Footer Legal Menu', 'conference-starter'),
            'mobile'       => esc_html__('Mobile Menu', 'conference-starter'),
        ));

        // Switch default core markup to output valid HTML5
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
            'navigation-widgets',
        ));

        // Custom logo support
        add_theme_support('custom-logo', array(
            'height'      => 100,
            'width'       => 300,
            'flex-width'  => true,
            'flex-height' => true,
        ));

        // Custom header support
        add_theme_support('custom-header', array(
            'default-image'      => '',
            'default-text-color' => '1a365d',
            'width'              => 1920,
            'height'             => 800,
            'flex-width'         => true,
            'flex-height'        => true,
        ));

        // Custom background support
        add_theme_support('custom-background', array(
            'default-color' => 'ffffff',
        ));

        // Add support for Block Styles
        add_theme_support('wp-block-styles');

        // Add support for full and wide align images
        add_theme_support('align-wide');

        // Add support for responsive embedded content
        add_theme_support('responsive-embeds');

        // Add support for editor styles
        add_theme_support('editor-styles');
        add_editor_style('assets/css/editor-style.css');
        add_editor_style('assets/css/block-patterns.css');

        // Add MNResus color palette for block editor
        add_theme_support('editor-color-palette', array(
            array(
                'name'  => __('Primary (Navy)', 'conference-starter'),
                'slug'  => 'primary',
                'color' => '#1a365d',
            ),
            array(
                'name'  => __('Primary Dark', 'conference-starter'),
                'slug'  => 'primary-dark',
                'color' => '#0f1f35',
            ),
            array(
                'name'  => __('Secondary (Teal)', 'conference-starter'),
                'slug'  => 'secondary',
                'color' => '#0d9488',
            ),
            array(
                'name'  => __('Accent (Orange)', 'conference-starter'),
                'slug'  => 'accent',
                'color' => '#ea580c',
            ),
            array(
                'name'  => __('Light Gray', 'conference-starter'),
                'slug'  => 'light-gray',
                'color' => '#f8fafc',
            ),
            array(
                'name'  => __('Medium Gray', 'conference-starter'),
                'slug'  => 'medium-gray',
                'color' => '#64748b',
            ),
            array(
                'name'  => __('White', 'conference-starter'),
                'slug'  => 'white',
                'color' => '#ffffff',
            ),
            array(
                'name'  => __('Black', 'conference-starter'),
                'slug'  => 'black',
                'color' => '#0f172a',
            ),
        ));

        // Add support for custom spacing
        add_theme_support('custom-spacing');

        // Add support for custom units
        add_theme_support('custom-units');

        // Selective refresh for widgets
        add_theme_support('customize-selective-refresh-widgets');

        conference_starter_log('Theme setup completed', 'debug');
    }
}
add_action('after_setup_theme', 'conference_starter_setup');

/**
 * ==========================================================================
 * CONTENT WIDTH
 * ==========================================================================
 */

if (!function_exists('conference_starter_content_width')) {
    /**
     * Set the content width in pixels
     */
    function conference_starter_content_width() {
        $GLOBALS['content_width'] = apply_filters('conference_starter_content_width', 1200);
    }
}
add_action('after_setup_theme', 'conference_starter_content_width', 0);

/**
 * ==========================================================================
 * WIDGET AREAS
 * ==========================================================================
 */

if (!function_exists('conference_starter_widgets_init')) {
    /**
     * Register widget areas
     */
    function conference_starter_widgets_init() {
        // Main Sidebar
        register_sidebar(array(
            'name'          => esc_html__('Sidebar', 'conference-starter'),
            'id'            => 'sidebar-1',
            'description'   => esc_html__('Add widgets here to appear in your sidebar.', 'conference-starter'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));

        // Footer Columns
        for ($i = 1; $i <= 4; $i++) {
            register_sidebar(array(
                'name'          => sprintf(esc_html__('Footer Column %d', 'conference-starter'), $i),
                'id'            => 'footer-' . $i,
                'description'   => sprintf(esc_html__('Add widgets here to appear in footer column %d.', 'conference-starter'), $i),
                'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="footer-widget-title">',
                'after_title'   => '</h4>',
            ));
        }

        // Homepage Widget Areas
        register_sidebar(array(
            'name'          => esc_html__('Homepage Before Content', 'conference-starter'),
            'id'            => 'homepage-before',
            'description'   => esc_html__('Widgets displayed before homepage content.', 'conference-starter'),
            'before_widget' => '<section id="%1$s" class="homepage-widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="section-title">',
            'after_title'   => '</h2>',
        ));

        register_sidebar(array(
            'name'          => esc_html__('Homepage After Content', 'conference-starter'),
            'id'            => 'homepage-after',
            'description'   => esc_html__('Widgets displayed after homepage content.', 'conference-starter'),
            'before_widget' => '<section id="%1$s" class="homepage-widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="section-title">',
            'after_title'   => '</h2>',
        ));

        conference_starter_log('Widget areas registered', 'debug');
    }
}
add_action('widgets_init', 'conference_starter_widgets_init');

/**
 * ==========================================================================
 * ENQUEUE SCRIPTS AND STYLES
 * ==========================================================================
 */

if (!function_exists('conference_starter_scripts')) {
    /**
     * Enqueue scripts and styles
     */
    function conference_starter_scripts() {
        // Google Fonts
        $google_fonts_url = add_query_arg(array(
            'family' => 'Inter:wght@400;500;600;700|Plus+Jakarta+Sans:wght@600;700;800|JetBrains+Mono:wght@400;500',
            'display' => 'swap',
        ), 'https://fonts.googleapis.com/css2');
        
        wp_enqueue_style(
            'conference-starter-fonts',
            $google_fonts_url,
            array(),
            null
        );

        // Main stylesheet
        wp_enqueue_style(
            'conference-starter-style',
            get_stylesheet_uri(),
            array('conference-starter-fonts'),
            CONFERENCE_STARTER_VERSION
        );

        // EMS Integration styles (if EMS plugin active)
        if (class_exists('EMS_Core')) {
            wp_enqueue_style(
                'conference-starter-ems',
                CONFERENCE_STARTER_URI . '/assets/css/ems-integration.css',
                array('conference-starter-style'),
                CONFERENCE_STARTER_VERSION
            );
        }

        // Block Patterns styles
        wp_enqueue_style(
            'conference-starter-block-patterns',
            CONFERENCE_STARTER_URI . '/assets/css/block-patterns.css',
            array('conference-starter-style'),
            CONFERENCE_STARTER_VERSION
        );

        // Main JavaScript
        wp_enqueue_script(
            'conference-starter-main',
            CONFERENCE_STARTER_URI . '/assets/js/main.js',
            array(),
            CONFERENCE_STARTER_VERSION,
            true
        );

        // Localize script with theme data
        wp_localize_script('conference-starter-main', 'conferenceStarterData', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('conference_starter_nonce'),
            'siteUrl'       => home_url(),
            'themeUrl'      => CONFERENCE_STARTER_URI,
            'eventDate'     => get_theme_mod('conference_event_date', ''),
            'isDebug'       => (defined('WP_DEBUG') && WP_DEBUG),
            'i18n'          => array(
                'days'    => esc_html__('Days', 'conference-starter'),
                'hours'   => esc_html__('Hours', 'conference-starter'),
                'minutes' => esc_html__('Minutes', 'conference-starter'),
                'seconds' => esc_html__('Seconds', 'conference-starter'),
                'loading' => esc_html__('Loading...', 'conference-starter'),
                'error'   => esc_html__('An error occurred.', 'conference-starter'),
            ),
        ));

        // Comment reply script
        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }

        conference_starter_log('Scripts and styles enqueued', 'debug');
    }
}
add_action('wp_enqueue_scripts', 'conference_starter_scripts');

/**
 * ==========================================================================
 * HELPER FUNCTIONS
 * ==========================================================================
 */

if (!function_exists('conference_starter_get_svg_icon')) {
    /**
     * Get SVG icon markup
     *
     * @param string $icon   Icon name
     * @param int    $size   Icon size in pixels
     * @param string $class  Additional CSS classes
     * @return string SVG markup
     */
    function conference_starter_get_svg_icon($icon, $size = 24, $class = '') {
        $icons = array(
            'calendar' => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>',
            'clock' => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>',
            'location' => '<path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>',
            'map-pin' => '<path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>',
            'user' => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"/>',
            'users' => '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/>',
            'mail' => '<path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>',
            'phone' => '<path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round"/>',
            'arrow-right' => '<path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"/>',
            'arrow-left' => '<path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round"/>',
            'check' => '<path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>',
            'x' => '<path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/>',
            'chevron-down' => '<path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>',
            'chevron-up' => '<path d="M5 15l7-7 7 7" stroke-linecap="round" stroke-linejoin="round"/>',
            'chevron-right' => '<path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>',
            'chevron-left' => '<path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>',
            'search' => '<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round"/>',
            'home' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round"/>',
            'edit' => '<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/>',
            'folder' => '<path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-linecap="round" stroke-linejoin="round"/>',
            'tag' => '<path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"/>',
            'message-circle' => '<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" stroke-linecap="round" stroke-linejoin="round"/>',
            'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8.5" cy="8.5" r="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 15l-5-5L5 21" stroke-linecap="round" stroke-linejoin="round"/>',
            'twitter' => '<path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>',
            'linkedin' => '<path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
            'facebook' => '<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>',
            'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
            'youtube' => '<path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>',
            'external-link' => '<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3" stroke-linecap="round" stroke-linejoin="round"/>',
            'download' => '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>',
            'plus' => '<path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>',
            'minus' => '<path d="M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>',
        );

        if (!isset($icons[$icon])) {
            conference_starter_log("SVG icon not found: {$icon}", 'warning');
            return '';
        }

        $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';
        
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"%s aria-hidden="true">%s</svg>',
            absint($size),
            absint($size),
            $class_attr,
            $icons[$icon]
        );
    }
}

if (!function_exists('conference_starter_get_icon')) {
    /**
     * Alias for conference_starter_get_svg_icon
     * 
     * This wrapper ensures backward compatibility with templates
     * that use the shorter function name.
     *
     * @param string $icon   Icon name
     * @param int    $size   Icon size in pixels
     * @param string $class  Additional CSS classes
     * @return string SVG markup
     */
    function conference_starter_get_icon($icon, $size = 24, $class = '') {
        return conference_starter_get_svg_icon($icon, $size, $class);
    }
}

if (!function_exists('conference_starter_breadcrumbs')) {
    /**
     * Display breadcrumbs
     */
    function conference_starter_breadcrumbs() {
        if (is_front_page()) {
            return;
        }

        $separator = '<span class="separator" aria-hidden="true">/</span>';
        
        echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'conference-starter') . '">';
        echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'conference-starter') . '</a>';
        echo $separator;

        if (is_single()) {
            $categories = get_the_category();
            if ($categories) {
                echo '<a href="' . esc_url(get_category_link($categories[0]->term_id)) . '">' . esc_html($categories[0]->name) . '</a>';
                echo $separator;
            }
            echo '<span class="current">' . esc_html(get_the_title()) . '</span>';
        } elseif (is_page()) {
            global $post;
            if ($post->post_parent) {
                $ancestors = get_post_ancestors($post->ID);
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor) {
                    echo '<a href="' . esc_url(get_permalink($ancestor)) . '">' . esc_html(get_the_title($ancestor)) . '</a>';
                    echo $separator;
                }
            }
            echo '<span class="current">' . esc_html(get_the_title()) . '</span>';
        } elseif (is_category()) {
            echo '<span class="current">' . esc_html(single_cat_title('', false)) . '</span>';
        } elseif (is_tag()) {
            echo '<span class="current">' . esc_html(single_tag_title('', false)) . '</span>';
        } elseif (is_author()) {
            echo '<span class="current">' . esc_html(get_the_author()) . '</span>';
        } elseif (is_search()) {
            echo '<span class="current">' . esc_html__('Search Results', 'conference-starter') . '</span>';
        } elseif (is_404()) {
            echo '<span class="current">' . esc_html__('Page Not Found', 'conference-starter') . '</span>';
        } elseif (is_archive()) {
            echo '<span class="current">' . esc_html(get_the_archive_title()) . '</span>';
        }

        echo '</nav>';
    }
}

if (!function_exists('conference_starter_posted_on')) {
    /**
     * Prints HTML with meta information for the current post-date/time
     */
    function conference_starter_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        if (get_the_time('U') !== get_the_modified_time('U')) {
            $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated sr-only" datetime="%3$s">%4$s</time>';
        }

        $time_string = sprintf(
            $time_string,
            esc_attr(get_the_date(DATE_W3C)),
            esc_html(get_the_date()),
            esc_attr(get_the_modified_date(DATE_W3C)),
            esc_html(get_the_modified_date())
        );

        echo '<span class="card-meta-item">';
        echo conference_starter_get_svg_icon('calendar', 16);
        echo '<a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $time_string . '</a>';
        echo '</span>';
    }
}

if (!function_exists('conference_starter_posted_by')) {
    /**
     * Prints HTML with meta information for the current author
     */
    function conference_starter_posted_by() {
        echo '<span class="card-meta-item">';
        echo conference_starter_get_svg_icon('user', 16);
        echo '<a href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a>';
        echo '</span>';
    }
}

if (!function_exists('conference_starter_comment_count')) {
    /**
     * Prints HTML with comment count
     */
    function conference_starter_comment_count() {
        if (!post_password_required() && (comments_open() || get_comments_number())) {
            echo '<span class="card-meta-item">';
            comments_popup_link(
                esc_html__('No comments', 'conference-starter'),
                esc_html__('1 comment', 'conference-starter'),
                esc_html__('% comments', 'conference-starter')
            );
            echo '</span>';
        }
    }
}

if (!function_exists('conference_starter_pagination')) {
    /**
     * Display pagination
     */
    function conference_starter_pagination() {
        $args = array(
            'mid_size'  => 2,
            'prev_text' => conference_starter_get_svg_icon('arrow-left', 20) . '<span class="sr-only">' . esc_html__('Previous', 'conference-starter') . '</span>',
            'next_text' => '<span class="sr-only">' . esc_html__('Next', 'conference-starter') . '</span>' . conference_starter_get_svg_icon('arrow-right', 20),
        );

        echo '<nav class="pagination" aria-label="' . esc_attr__('Posts navigation', 'conference-starter') . '">';
        echo paginate_links($args);
        echo '</nav>';
    }
}

if (!function_exists('conference_starter_social_links')) {
    /**
     * Get social links HTML
     *
     * @return string Social links HTML
     */
    function conference_starter_social_links() {
        $social_networks = array(
            'twitter'   => get_theme_mod('conference_social_twitter', ''),
            'linkedin'  => get_theme_mod('conference_social_linkedin', ''),
            'facebook'  => get_theme_mod('conference_social_facebook', ''),
            'instagram' => get_theme_mod('conference_social_instagram', ''),
        );

        $output = '';
        
        foreach ($social_networks as $network => $url) {
            if (!empty($url)) {
                $output .= sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
                    esc_url($url),
                    /* translators: %s: social network name */
                    sprintf(esc_attr__('Follow us on %s', 'conference-starter'), ucfirst($network)),
                    conference_starter_get_svg_icon($network, 20)
                );
            }
        }

        return $output;
    }
}

/**
 * ==========================================================================
 * EXCERPT CUSTOMIZATION
 * ==========================================================================
 */

if (!function_exists('conference_starter_excerpt_length')) {
    /**
     * Filter the excerpt length
     */
    function conference_starter_excerpt_length($length) {
        return 25;
    }
}
add_filter('excerpt_length', 'conference_starter_excerpt_length');

if (!function_exists('conference_starter_excerpt_more')) {
    /**
     * Filter the excerpt "read more" string
     */
    function conference_starter_excerpt_more($more) {
        return '&hellip;';
    }
}
add_filter('excerpt_more', 'conference_starter_excerpt_more');

/**
 * ==========================================================================
 * BODY CLASSES
 * ==========================================================================
 */

if (!function_exists('conference_starter_body_classes')) {
    /**
     * Adds custom classes to the array of body classes
     */
    function conference_starter_body_classes($classes) {
        // Adds a class of hfeed to non-singular pages
        if (!is_singular()) {
            $classes[] = 'hfeed';
        }

        // Adds a class for sidebar
        if (is_active_sidebar('sidebar-1') && !is_page_template('templates/full-width.php')) {
            $classes[] = 'has-sidebar';
        }

        // Adds a class if no featured image
        if (!has_post_thumbnail()) {
            $classes[] = 'no-featured-image';
        }

        // Add class if EMS plugin is active
        if (class_exists('EMS_Core')) {
            $classes[] = 'ems-active';
        }

        return $classes;
    }
}
add_filter('body_class', 'conference_starter_body_classes');

/**
 * ==========================================================================
 * CUSTOM TEMPLATE FUNCTIONS
 * ==========================================================================
 */

if (!function_exists('conference_starter_header_cta')) {
    /**
     * Display header CTA button
     */
    function conference_starter_header_cta() {
        $cta_text = get_theme_mod('conference_header_cta_text', __('Register Now', 'conference-starter'));
        $cta_url = get_theme_mod('conference_header_cta_url', '#');

        if (!empty($cta_text) && !empty($cta_url)) {
            printf(
                '<a href="%s" class="btn btn-primary header-cta">%s</a>',
                esc_url($cta_url),
                esc_html($cta_text)
            );
        }
    }
}

/**
 * ==========================================================================
 * INCLUDE ADDITIONAL FILES
 * ==========================================================================
 */

// Customizer options
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/customizer.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/customizer.php';
} else {
    conference_starter_log('CRITICAL: customizer.php not found', 'error');
}

// Template tags
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/template-tags.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/template-tags.php';
}

// Template functions
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/template-functions.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/template-functions.php';
}

// EMS Integration
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/ems-integration.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/ems-integration.php';
}

// Homepage Template Functions
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/functions-homepage.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/functions-homepage.php';
}

// Block Patterns
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/block-patterns.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/block-patterns.php';
}

// Page Display Options
if (file_exists(CONFERENCE_STARTER_DIR . '/inc/page-options.php')) {
    require_once CONFERENCE_STARTER_DIR . '/inc/page-options.php';
}

conference_starter_log('Theme functions loaded successfully', 'info');
