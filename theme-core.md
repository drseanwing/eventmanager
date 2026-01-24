# Conference Starter Theme - Core

**Document Purpose:** Core theme files for Conference Starter WordPress theme  
**Production Path:** `conference-starter/`  
**Theme Version:** 1.1.0

---

## Table of Contents

1. [Style.css](#stylecss) - Main stylesheet with design tokens
2. [Functions.php](#functionsphp) - Theme setup and features
3. [Template Files](#template-files) - Standard WordPress templates
4. [Customizer](#customizer) - Theme customization options

---

## Style.css

**File:** `style.css`  
**Production Path:** `conference-starter/style.css`

### Theme Header

```css
/*
Theme Name: Conference Starter
Theme URI: https://github.com/yourusername/conference-starter
Author: Royal Brisbane & Women's Hospital
Author URI: https://www.health.qld.gov.au/rbwh
Description: A modern, professional WordPress theme designed for conferences and events.
Version: 1.1.0
Requires at least: 5.9
Tested up to: 6.4
Requires PHP: 7.4
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: conference-starter
*/
```

### Design Tokens (CSS Custom Properties)

```css
:root {
    /* Typography */
    --font-body: 'Work Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-heading: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-mono: 'Fira Code', 'Consolas', monospace;
    
    /* Font Sizes */
    --text-xs: 0.75rem;
    --text-sm: 0.875rem;
    --text-base: 1rem;
    --text-lg: 1.125rem;
    --text-xl: 1.25rem;
    --text-2xl: 1.5rem;
    --text-3xl: 1.875rem;
    --text-4xl: 2.25rem;
    
    /* Colors - Primary */
    --color-primary: #222222;
    --color-primary-light: #444444;
    --color-primary-dark: #111111;
    
    /* Colors - Accent */
    --color-accent: #1EC6B6;
    --color-accent-light: #3DD9CA;
    --color-accent-dark: #18A89A;
    --color-accent-blue: #007bff;
    
    /* Colors - Neutral */
    --color-white: #ffffff;
    --color-gray-50: #f9fafb;
    --color-gray-100: #f3f4f6;
    --color-gray-200: #e5e7eb;
    --color-gray-300: #d1d5db;
    --color-gray-400: #9ca3af;
    --color-gray-500: #6b7280;
    --color-gray-600: #4b5563;
    --color-gray-700: #374151;
    --color-gray-800: #1f2937;
    --color-gray-900: #111827;
    
    /* Colors - Semantic */
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-error: #ef4444;
    --color-info: #3b82f6;
    
    /* Spacing */
    --spacing-xs: 5px;
    --spacing-sm: 10px;
    --spacing-md: 15px;
    --spacing-lg: 20px;
    --spacing-xl: 30px;
    --spacing-xxl: 40px;
    --spacing-3xl: 60px;
    
    /* Layout */
    --container-width: 1200px;
    --sidebar-width: 300px;
    --header-height: 80px;
    
    /* Components */
    --border-radius: 4px;
    --border-radius-lg: 8px;
    --border-radius-full: 9999px;
    --box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    --box-shadow-md: 0 4px 10px rgba(0,0,0,0.12);
    --box-shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
    --transition-base: 0.3s ease;
    --transition-fast: 0.15s ease;
}
```

### Base Styles

```css
/* Reset & Base */
*, *::before, *::after {
    box-sizing: border-box;
}

body {
    font-family: var(--font-body);
    font-size: var(--text-base);
    line-height: 1.6;
    color: var(--color-gray-800);
    background-color: var(--color-white);
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    font-weight: 600;
    line-height: 1.3;
    color: var(--color-primary);
}

a {
    color: var(--color-accent-blue);
    text-decoration: none;
    transition: color var(--transition-fast);
}

a:hover {
    color: var(--color-accent-dark);
}

/* Buttons */
.btn, button, input[type="submit"] {
    display: inline-block;
    padding: var(--spacing-sm) var(--spacing-lg);
    font-family: var(--font-body);
    font-size: var(--text-base);
    font-weight: 500;
    text-align: center;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-primary {
    background-color: var(--color-accent);
    color: var(--color-white);
}

.btn-primary:hover {
    background-color: var(--color-accent-dark);
}

.btn-secondary {
    background-color: var(--color-gray-200);
    color: var(--color-gray-800);
}

/* Container */
.container {
    max-width: var(--container-width);
    margin: 0 auto;
    padding: 0 var(--spacing-lg);
}
```

---

## Functions.php

**File:** `functions.php`  
**Production Path:** `conference-starter/functions.php`

### Theme Setup

```php
<?php
/**
 * Conference Starter Theme Functions
 *
 * @package ConferenceStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CONFERENCE_STARTER_VERSION', '1.1.0' );

/**
 * Theme setup
 */
function conference_starter_setup() {
    // Add default posts and comments RSS feed links
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'event-featured', 1200, 600, true );
    add_image_size( 'event-thumbnail', 400, 250, true );

    // Register menus
    register_nav_menus( array(
        'primary' => esc_html__( 'Primary Menu', 'conference-starter' ),
        'footer'  => esc_html__( 'Footer Menu', 'conference-starter' ),
    ) );

    // Switch default core markup to HTML5
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Custom logo support
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ) );

    // Wide and full alignment for Gutenberg
    add_theme_support( 'align-wide' );

    // Editor styles
    add_theme_support( 'editor-styles' );

    // Responsive embeds
    add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'conference_starter_setup' );
```

### Enqueue Scripts & Styles

```php
/**
 * Enqueue scripts and styles
 */
function conference_starter_scripts() {
    // Google Fonts
    wp_enqueue_style(
        'conference-starter-fonts',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Work+Sans:wght@300;400;500;600&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'conference-starter-style',
        get_stylesheet_uri(),
        array(),
        CONFERENCE_STARTER_VERSION
    );

    // Main JavaScript
    wp_enqueue_script(
        'conference-starter-main',
        get_template_directory_uri() . '/js/main.js',
        array( 'jquery' ),
        CONFERENCE_STARTER_VERSION,
        true
    );

    // EMS Integration styles (if plugin active)
    if ( class_exists( 'EMS_Core' ) ) {
        wp_enqueue_style(
            'conference-starter-ems',
            get_template_directory_uri() . '/css/ems-integration.css',
            array( 'conference-starter-style' ),
            CONFERENCE_STARTER_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'conference_starter_scripts' );
```

### Widget Areas

```php
/**
 * Register widget areas
 */
function conference_starter_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar', 'conference-starter' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here.', 'conference-starter' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer 1', 'conference-starter' ),
        'id'            => 'footer-1',
        'description'   => esc_html__( 'First footer widget area.', 'conference-starter' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer 2', 'conference-starter' ),
        'id'            => 'footer-2',
        'description'   => esc_html__( 'Second footer widget area.', 'conference-starter' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer 3', 'conference-starter' ),
        'id'            => 'footer-3',
        'description'   => esc_html__( 'Third footer widget area.', 'conference-starter' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'conference_starter_widgets_init' );
```

### EMS Body Classes

```php
/**
 * Add EMS-specific body classes
 */
function conference_starter_body_classes( $classes ) {
    // Add class for EMS plugin active
    if ( class_exists( 'EMS_Core' ) ) {
        $classes[] = 'ems-active';
    }

    // Add class for single event
    if ( is_singular( 'ems_event' ) ) {
        $classes[] = 'ems-single-event';
    }

    // Add class for event archive
    if ( is_post_type_archive( 'ems_event' ) ) {
        $classes[] = 'ems-event-archive';
    }

    // Add class for pages with EMS shortcodes
    global $post;
    if ( $post && has_shortcode( $post->post_content, 'ems_event_schedule' ) ) {
        $classes[] = 'ems-schedule-page';
    }
    if ( $post && has_shortcode( $post->post_content, 'ems_registration_form' ) ) {
        $classes[] = 'ems-registration-page';
    }

    return $classes;
}
add_filter( 'body_class', 'conference_starter_body_classes' );
```

---

## Template Files

### header.php

```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e( 'Skip to content', 'conference-starter' ); ?>
    </a>

    <header id="masthead" class="site-header">
        <div class="container header-inner">
            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                    </h1>
                <?php endif; ?>
            </div>

            <nav id="site-navigation" class="main-navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="menu-toggle-icon"></span>
                </button>
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                ) );
                ?>
            </nav>
        </div>
    </header>

    <main id="primary" class="site-main">
```

### footer.php

```php
    </main><!-- #primary -->

    <footer id="colophon" class="site-footer">
        <div class="container">
            <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
            <div class="footer-widgets">
                <div class="footer-widget-area">
                    <?php dynamic_sidebar( 'footer-1' ); ?>
                </div>
                <div class="footer-widget-area">
                    <?php dynamic_sidebar( 'footer-2' ); ?>
                </div>
                <div class="footer-widget-area">
                    <?php dynamic_sidebar( 'footer-3' ); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="site-info">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'footer',
                    'menu_id'        => 'footer-menu',
                    'container'      => false,
                    'depth'          => 1,
                ) );
                ?>
                <p class="copyright">
                    &copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>
                </p>
            </div>
        </div>
    </footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
```

### index.php

```php
<?php get_header(); ?>

<div class="container">
    <div class="content-area">
        <?php if ( have_posts() ) : ?>
            
            <?php if ( is_home() && ! is_front_page() ) : ?>
                <header class="page-header">
                    <h1 class="page-title"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>

            <div class="posts-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="post-thumbnail">
                                <?php the_post_thumbnail( 'medium' ); ?>
                            </a>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h2 class="entry-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="entry-meta">
                                <?php echo get_the_date(); ?>
                            </div>
                            <div class="entry-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination(); ?>

        <?php else : ?>
            <p><?php esc_html_e( 'No posts found.', 'conference-starter' ); ?></p>
        <?php endif; ?>
    </div>

    <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>
```

---

## Customizer

**File:** `customizer.php`  
**Production Path:** `conference-starter/inc/customizer.php`

### Customizer Sections

```php
function conference_starter_customize_register( $wp_customize ) {
    
    // Theme Colors Section
    $wp_customize->add_section( 'conference_starter_colors', array(
        'title'    => __( 'Theme Colors', 'conference-starter' ),
        'priority' => 30,
    ) );

    // Primary Color
    $wp_customize->add_setting( 'primary_color', array(
        'default'           => '#222222',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primary_color', array(
        'label'   => __( 'Primary Color', 'conference-starter' ),
        'section' => 'conference_starter_colors',
    ) ) );

    // Accent Color
    $wp_customize->add_setting( 'accent_color', array(
        'default'           => '#1EC6B6',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'accent_color', array(
        'label'   => __( 'Accent Color', 'conference-starter' ),
        'section' => 'conference_starter_colors',
    ) ) );
    
    // Footer Section
    $wp_customize->add_section( 'conference_starter_footer', array(
        'title'    => __( 'Footer Settings', 'conference-starter' ),
        'priority' => 90,
    ) );

    // Copyright Text
    $wp_customize->add_setting( 'footer_copyright', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ) );

    $wp_customize->add_control( 'footer_copyright', array(
        'label'   => __( 'Copyright Text', 'conference-starter' ),
        'section' => 'conference_starter_footer',
        'type'    => 'textarea',
    ) );
}
add_action( 'customize_register', 'conference_starter_customize_register' );
```
