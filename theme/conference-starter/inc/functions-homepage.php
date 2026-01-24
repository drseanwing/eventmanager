<?php
/**
 * Homepage Template Functions
 *
 * Additional functions for the Conference Homepage template.
 * Include this file from functions.php: require_once get_template_directory() . '/inc/functions-homepage.php';
 *
 * @package Conference_Starter
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if current page is using the Conference Homepage template.
 *
 * @since 1.2.0
 * @return bool True if using homepage template.
 */
function conference_starter_is_homepage_template() {
    return is_page_template( 'homepage-template.php' ) 
        || is_page_template( 'templates/homepage.php' );
}

/**
 * Enqueue homepage-specific styles and scripts.
 *
 * @since 1.2.0
 */
function conference_starter_homepage_assets() {
    // Only load on pages using the Conference Homepage template
    if ( ! conference_starter_is_homepage_template() ) {
        return;
    }

    // Google Fonts - Plus Jakarta Sans and Inter for homepage
    wp_enqueue_style(
        'conference-homepage-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap',
        array(),
        null
    );

    // Homepage CSS
    wp_enqueue_style(
        'conference-homepage-style',
        CONFERENCE_STARTER_URI . '/assets/css/homepage.css',
        array( 'conference-starter-style' ),
        CONFERENCE_STARTER_VERSION
    );

    // Homepage JavaScript
    wp_enqueue_script(
        'conference-homepage-script',
        CONFERENCE_STARTER_URI . '/assets/js/homepage.js',
        array(),
        CONFERENCE_STARTER_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'conference_starter_homepage_assets' );

/**
 * Add preconnect for Google Fonts on homepage.
 *
 * @since 1.2.0
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed.
 * @return array Modified URLs.
 */
function conference_starter_homepage_resource_hints( $urls, $relation_type ) {
    if ( conference_starter_is_homepage_template() && 'preconnect' === $relation_type ) {
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
add_filter( 'wp_resource_hints', 'conference_starter_homepage_resource_hints', 10, 2 );

/**
 * Add body classes for homepage template.
 *
 * @since 1.2.0
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function conference_starter_homepage_body_class( $classes ) {
    if ( conference_starter_is_homepage_template() ) {
        $classes[] = 'conference-homepage';
        $classes[] = 'no-sidebar';
    }
    return $classes;
}
add_filter( 'body_class', 'conference_starter_homepage_body_class' );

/**
 * Remove default page header on homepage template.
 *
 * @since 1.2.0
 */
function conference_starter_homepage_remove_header() {
    if ( conference_starter_is_homepage_template() ) {
        remove_action( 'conference_starter_before_content', 'conference_starter_page_header' );
    }
}
add_action( 'wp', 'conference_starter_homepage_remove_header' );

/**
 * Make header transparent on homepage.
 *
 * @since 1.2.0
 * @param array $classes Existing header classes.
 * @return array Modified header classes.
 */
function conference_starter_homepage_header_class( $classes ) {
    if ( conference_starter_is_homepage_template() ) {
        $classes[] = 'header--transparent';
    }
    return $classes;
}

/**
 * Add SEO meta tags for homepage.
 *
 * @since 1.2.0
 */
function conference_starter_homepage_meta() {
    if ( ! conference_starter_is_homepage_template() ) {
        return;
    }

    // Meta description
    $description = 'REdI Program delivers resuscitation and patient deterioration education at Royal Brisbane & Women\'s Hospital. Courses include ALS, BLS, simulation training, and annual conferences. Register now.';
    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";

    // Open Graph tags
    echo '<meta property="og:title" content="' . esc_attr( get_bloginfo( 'name' ) ) . ' - Resuscitation Education">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . '">' . "\n";
    
    // Featured image as OG image
    if ( has_post_thumbnail() ) {
        $og_image = get_the_post_thumbnail_url( get_the_ID(), 'large' );
        echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
    }

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
add_action( 'wp_head', 'conference_starter_homepage_meta', 5 );
