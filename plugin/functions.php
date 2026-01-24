<?php
/**
 * Conference starter Theme Functions
 *
 * A clean, minimal theme based on the GrandConference design system.
 * No bloat, no required plugins, just solid foundations.
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme version and constants
 */
define( 'CONFERENCE_STARTER_VERSION', '1.0.0' );
define( 'CONFERENCE_STARTER_DIR', get_template_directory() );
define( 'CONFERENCE_STARTER_URI', get_template_directory_uri() );

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * @since 1.0.0
 */
function conference_starter_setup() {
    // Make theme available for translation
    load_theme_textdomain( 'conference-starter', CONFERENCE_STARTER_DIR . '/languages' );

    // Add default posts and comments RSS feed links to head
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails
    add_theme_support( 'post-thumbnails' );

    // Custom image sizes
    add_image_size( 'conference-featured', 1200, 600, true );
    add_image_size( 'conference-card', 400, 300, true );
    add_image_size( 'conference-speaker', 300, 300, true );

    // Register navigation menus
    register_nav_menus( array(
        'primary' => esc_html__( 'Primary Menu', 'conference-starter' ),
        'footer'  => esc_html__( 'Footer Menu', 'conference-starter' ),
    ) );

    // Switch default core markup to output valid HTML5
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Add support for custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Add support for custom background
    add_theme_support( 'custom-background', array(
        'default-color' => 'ffffff',
    ) );

    // Add support for editor styles
    add_theme_support( 'editor-styles' );

    // Add support for responsive embeds
    add_theme_support( 'responsive-embeds' );

    // Add support for full and wide align images
    add_theme_support( 'align-wide' );

    // Add support for custom line height
    add_theme_support( 'custom-line-height' );

    // Add support for custom spacing
    add_theme_support( 'custom-spacing' );

    // Set content width
    global $content_width;
    if ( ! isset( $content_width ) ) {
        $content_width = 1170;
    }
}
add_action( 'after_setup_theme', 'conference_starter_setup' );

/**
 * Register widget areas.
 *
 * @since 1.0.0
 */
function conference_starter_widgets_init() {
    // Main Sidebar
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar', 'conference-starter' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here to appear in your sidebar.', 'conference-starter' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer Widget Areas
    for ( $i = 1; $i <= 4; $i++ ) {
        register_sidebar( array(
            'name'          => sprintf( esc_html__( 'Footer %d', 'conference-starter' ), $i ),
            'id'            => 'footer-' . $i,
            'description'   => sprintf( esc_html__( 'Footer widget area %d.', 'conference-starter' ), $i ),
            'before_widget' => '<div id="%1$s" class="footer-widget widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ) );
    }
}
add_action( 'widgets_init', 'conference_starter_widgets_init' );

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0.0
 */
function conference_starter_scripts() {
    // Google Fonts - Work Sans and Poppins
    wp_enqueue_style(
        'conference-starter-fonts',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Work+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'conference-starter-style',
        get_stylesheet_uri(),
        array( 'conference-starter-fonts' ),
        CONFERENCE_STARTER_VERSION
    );

    // Main JavaScript
    wp_enqueue_script(
        'conference-starter-script',
        CONFERENCE_STARTER_URI . '/js/main.js',
        array( 'jquery' ),
        CONFERENCE_STARTER_VERSION,
        true
    );

    // Localize script for AJAX and other data
    wp_localize_script( 'conference-starter-script', 'conferenceStarter', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'conference_starter_nonce' ),
        'i18n'    => array(
            'menu'        => esc_html__( 'Menu', 'conference-starter' ),
            'close'       => esc_html__( 'Close', 'conference-starter' ),
            'expandAll'   => esc_html__( 'Expand All', 'conference-starter' ),
            'collapseAll' => esc_html__( 'Collapse All', 'conference-starter' ),
        ),
    ) );

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'conference_starter_scripts' );

/**
 * Add preconnect for Google Fonts.
 *
 * @since 1.0.0
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed.
 * @return array Modified URLs.
 */
function conference_starter_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href' => 'https://fonts.googleapis.com',
            'crossorigin',
        );
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'conference_starter_resource_hints', 10, 2 );

/**
 * Add custom body classes.
 *
 * @since 1.0.0
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function conference_starter_body_classes( $classes ) {
    // Add class if sidebar is active
    if ( is_active_sidebar( 'sidebar-1' ) && ! is_page_template( 'templates/full-width.php' ) ) {
        $classes[] = 'has-sidebar';
    } else {
        $classes[] = 'no-sidebar';
    }

    // Add class for singular pages
    if ( is_singular() ) {
        $classes[] = 'singular';
    }

    // Add class for sticky header option
    if ( get_theme_mod( 'conference_starter_sticky_header', true ) ) {
        $classes[] = 'sticky-header';
    }

    return $classes;
}
add_filter( 'body_class', 'conference_starter_body_classes' );

/**
 * Custom excerpt length.
 *
 * @since 1.0.0
 * @param int $length Default excerpt length.
 * @return int Modified excerpt length.
 */
function conference_starter_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'conference_starter_excerpt_length' );

/**
 * Custom excerpt more string.
 *
 * @since 1.0.0
 * @param string $more Default more string.
 * @return string Modified more string.
 */
function conference_starter_excerpt_more( $more ) {
    return '&hellip;';
}
add_filter( 'excerpt_more', 'conference_starter_excerpt_more' );

/**
 * Display custom logo or site title.
 *
 * @since 1.0.0
 */
function conference_starter_site_logo() {
    if ( has_custom_logo() ) {
        the_custom_logo();
    } else {
        ?>
        <div class="site-branding-text">
            <?php if ( is_front_page() && is_home() ) : ?>
                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
            <?php else : ?>
                <p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
            <?php endif; ?>

            <?php
            $description = get_bloginfo( 'description', 'display' );
            if ( $description || is_customize_preview() ) :
                ?>
                <p class="site-description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Display page header with optional background image.
 *
 * @since 1.0.0
 * @param string $title   Optional custom title.
 * @param string $tagline Optional tagline/description.
 */
function conference_starter_page_header( $title = '', $tagline = '' ) {
    // Get title if not provided
    if ( empty( $title ) ) {
        if ( is_archive() ) {
            $title = get_the_archive_title();
        } elseif ( is_search() ) {
            $title = sprintf( esc_html__( 'Search Results for: %s', 'conference-starter' ), get_search_query() );
        } elseif ( is_404() ) {
            $title = esc_html__( 'Page Not Found', 'conference-starter' );
        } else {
            $title = get_the_title();
        }
    }

    // Get tagline if not provided
    if ( empty( $tagline ) && is_archive() ) {
        $tagline = get_the_archive_description();
    }

    // Check for featured image
    $has_bg = false;
    $bg_style = '';
    
    if ( is_singular() && has_post_thumbnail() ) {
        $image_url = get_the_post_thumbnail_url( get_the_ID(), 'conference-featured' );
        if ( $image_url ) {
            $has_bg = true;
            $bg_style = 'style="background-image: url(' . esc_url( $image_url ) . ');"';
        }
    }

    $header_class = $has_bg ? 'page-header has-background' : 'page-header';
    ?>
    <div class="<?php echo esc_attr( $header_class ); ?>" <?php echo $bg_style; ?>>
        <div class="page-header-inner">
            <div class="container">
                <h1 class="page-title"><?php echo wp_kses_post( $title ); ?></h1>
                <?php if ( ! empty( $tagline ) ) : ?>
                    <div class="page-tagline"><?php echo wp_kses_post( $tagline ); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Display pagination.
 *
 * @since 1.0.0
 */
function conference_starter_pagination() {
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous', 'conference-starter' ) . '</span><span aria-hidden="true">&laquo;</span>',
        'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next', 'conference-starter' ) . '</span><span aria-hidden="true">&raquo;</span>',
    ) );
}

/**
 * Display post navigation.
 *
 * @since 1.0.0
 */
function conference_starter_post_navigation() {
    the_post_navigation( array(
        'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous Post', 'conference-starter' ) . '</span><span class="nav-title">%title</span>',
        'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next Post', 'conference-starter' ) . '</span><span class="nav-title">%title</span>',
    ) );
}

/**
 * Display entry meta.
 *
 * @since 1.0.0
 */
function conference_starter_entry_meta() {
    // Posted date
    $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
    
    $time_string = sprintf(
        $time_string,
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date() )
    );

    echo '<span class="posted-on">' . $time_string . '</span>';

    // Author
    echo '<span class="byline">';
    printf(
        esc_html__( 'by %s', 'conference-starter' ),
        '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>'
    );
    echo '</span>';

    // Categories
    $categories_list = get_the_category_list( ', ' );
    if ( $categories_list ) {
        echo '<span class="cat-links">' . esc_html__( 'in ', 'conference-starter' ) . $categories_list . '</span>';
    }

    // Comments
    if ( ! post_password_required() && comments_open() ) {
        echo '<span class="comments-link">';
        comments_popup_link(
            esc_html__( 'Leave a comment', 'conference-starter' ),
            esc_html__( '1 Comment', 'conference-starter' ),
            esc_html__( '% Comments', 'conference-starter' )
        );
        echo '</span>';
    }
}

/**
 * Display entry footer (tags).
 *
 * @since 1.0.0
 */
function conference_starter_entry_footer() {
    // Tags
    $tags_list = get_the_tag_list( '', '' );
    if ( $tags_list ) {
        echo '<div class="entry-tags">' . $tags_list . '</div>';
    }
}

/**
 * Check if post has a featured image, if not use a placeholder.
 *
 * @since 1.0.0
 * @param string $size Image size.
 */
function conference_starter_post_thumbnail( $size = 'conference-card' ) {
    if ( has_post_thumbnail() ) {
        the_post_thumbnail( $size, array( 'class' => 'entry-thumbnail-img' ) );
    }
}

/**
 * Include Customizer settings.
 */
require_once CONFERENCE_STARTER_DIR . '/inc/customizer.php';

/**
 * Check for EMS plugin and add comprehensive compatibility
 *
 * @since 1.0.0
 * @since 1.1.0 Added EMS integration styles and Gutenberg blocks
 */
function conference_starter_ems_compatibility() {
    if ( ! class_exists( 'EMS_Core' ) && ! defined( 'EMS_PLUGIN_DIR' ) ) {
        return;
    }
    
    // Add body class when EMS is active
    add_filter( 'body_class', 'conference_starter_ems_body_class' );
    
    // Enqueue EMS integration styles
    add_action( 'wp_enqueue_scripts', 'conference_starter_ems_styles', 20 );
    
    // Register EMS Gutenberg blocks
    add_action( 'init', 'conference_starter_register_ems_blocks' );
    
    // Add custom templates for EMS post types
    add_filter( 'single_template', 'conference_starter_ems_single_template' );
    add_filter( 'archive_template', 'conference_starter_ems_archive_template' );
}
add_action( 'plugins_loaded', 'conference_starter_ems_compatibility' );

/**
 * Add EMS-active body class
 *
 * @since 1.1.0
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function conference_starter_ems_body_class( $classes ) {
    $classes[] = 'ems-active';
    
    // Add specific classes for EMS post types
    if ( is_singular( 'ems_event' ) ) {
        $classes[] = 'ems-single-event';
    } elseif ( is_singular( 'ems_session' ) ) {
        $classes[] = 'ems-single-session';
    } elseif ( is_post_type_archive( 'ems_event' ) ) {
        $classes[] = 'ems-archive-events';
    }
    
    return $classes;
}

/**
 * Enqueue EMS integration styles
 *
 * @since 1.1.0
 */
function conference_starter_ems_styles() {
    wp_enqueue_style(
        'conference-starter-ems-integration',
        CONFERENCE_STARTER_URI . '/css/ems-integration.css',
        array( 'conference-starter-style' ),
        CONFERENCE_STARTER_VERSION
    );
}

/**
 * Register Gutenberg blocks for EMS shortcodes
 *
 * @since 1.1.0
 */
function conference_starter_register_ems_blocks() {
    // Only register if Gutenberg is available
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }
    
    // Register block category for EMS blocks
    add_filter( 'block_categories_all', function( $categories ) {
        return array_merge( $categories, array(
            array(
                'slug'  => 'ems-blocks',
                'title' => __( 'Event Management', 'conference-starter' ),
                'icon'  => 'calendar-alt',
            ),
        ) );
    } );
    
    // Enqueue block editor assets
    add_action( 'enqueue_block_editor_assets', 'conference_starter_ems_block_editor_assets' );
}

/**
 * Enqueue block editor assets for EMS blocks
 *
 * @since 1.1.0
 */
function conference_starter_ems_block_editor_assets() {
    wp_enqueue_script(
        'conference-starter-ems-blocks',
        CONFERENCE_STARTER_URI . '/js/ems-blocks.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
        CONFERENCE_STARTER_VERSION,
        true
    );
    
    // Pass available events to JavaScript
    $events = get_posts( array(
        'post_type'      => 'ems_event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    
    $events_data = array_map( function( $event ) {
        return array(
            'id'    => $event->ID,
            'title' => $event->post_title,
        );
    }, $events );
    
    wp_localize_script( 'conference-starter-ems-blocks', 'emsBlockData', array(
        'events' => $events_data,
        'i18n'   => array(
            'selectEvent'      => __( 'Select Event', 'conference-starter' ),
            'noEvents'         => __( 'No events found', 'conference-starter' ),
            'eventList'        => __( 'Event List', 'conference-starter' ),
            'eventSchedule'    => __( 'Event Schedule', 'conference-starter' ),
            'registrationForm' => __( 'Registration Form', 'conference-starter' ),
            'abstractForm'     => __( 'Abstract Submission', 'conference-starter' ),
            'mySchedule'       => __( 'My Schedule', 'conference-starter' ),
            'myRegistrations'  => __( 'My Registrations', 'conference-starter' ),
            'presenterDash'    => __( 'Presenter Dashboard', 'conference-starter' ),
        ),
    ) );
}

/**
 * Load custom single template for EMS post types
 *
 * @since 1.1.0
 * @param string $template Current template path.
 * @return string Modified template path.
 */
function conference_starter_ems_single_template( $template ) {
    global $post;
    
    if ( ! $post ) {
        return $template;
    }
    
    // Check for EMS post types
    $ems_post_types = array( 'ems_event', 'ems_session', 'ems_abstract', 'ems_sponsor' );
    
    if ( in_array( $post->post_type, $ems_post_types, true ) ) {
        // Look for theme template first
        $theme_template = locate_template( array(
            'single-' . $post->post_type . '.php',
            'ems/single-' . str_replace( 'ems_', '', $post->post_type ) . '.php',
        ) );
        
        if ( $theme_template ) {
            return $theme_template;
        }
    }
    
    return $template;
}

/**
 * Load custom archive template for EMS post types
 *
 * @since 1.1.0
 * @param string $template Current template path.
 * @return string Modified template path.
 */
function conference_starter_ems_archive_template( $template ) {
    // Check for EMS post type archives
    $ems_post_types = array( 'ems_event', 'ems_session', 'ems_sponsor' );
    
    foreach ( $ems_post_types as $post_type ) {
        if ( is_post_type_archive( $post_type ) ) {
            $theme_template = locate_template( array(
                'archive-' . $post_type . '.php',
                'ems/archive-' . str_replace( 'ems_', '', $post_type ) . '.php',
            ) );
            
            if ( $theme_template ) {
                return $theme_template;
            }
        }
    }
    
    return $template;
}

/**
 * Get event registration status helper
 *
 * @since 1.1.0
 * @param int $event_id Event post ID.
 * @return array Registration status information.
 */
function conference_starter_get_event_registration_status( $event_id ) {
    if ( ! function_exists( 'EMS_Date_Helper' ) && ! class_exists( 'EMS_Date_Helper' ) ) {
        return array(
            'available' => false,
            'message'   => '',
        );
    }
    
    $reg_deadline = get_post_meta( $event_id, 'event_registration_deadline', true );
    $reg_open     = get_post_meta( $event_id, 'registration_open_date', true );
    $max_capacity = get_post_meta( $event_id, 'event_max_capacity', true );
    $reg_enabled  = get_post_meta( $event_id, 'registration_enabled', true );
    
    // Check if registration is explicitly disabled
    if ( '0' === $reg_enabled || 'no' === $reg_enabled ) {
        return array(
            'available' => false,
            'message'   => __( 'Registration is not available for this event.', 'conference-starter' ),
        );
    }
    
    // Check if registration hasn't opened yet
    if ( $reg_open && class_exists( 'EMS_Date_Helper' ) && EMS_Date_Helper::is_future( $reg_open ) ) {
        return array(
            'available' => false,
            'message'   => sprintf(
                __( 'Registration opens on %s.', 'conference-starter' ),
                EMS_Date_Helper::format( $reg_open )
            ),
        );
    }
    
    // Check if registration deadline has passed
    if ( $reg_deadline && class_exists( 'EMS_Date_Helper' ) && EMS_Date_Helper::has_passed( $reg_deadline ) ) {
        return array(
            'available' => false,
            'message'   => __( 'Registration has closed for this event.', 'conference-starter' ),
        );
    }
    
    return array(
        'available' => true,
        'message'   => '',
    );
}

/**
 * Add skip link focus fix for accessibility.
 */
function conference_starter_skip_link_focus_fix() {
    ?>
    <script>
    /(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);
    </script>
    <?php
}
add_action( 'wp_print_footer_scripts', 'conference_starter_skip_link_focus_fix' );
