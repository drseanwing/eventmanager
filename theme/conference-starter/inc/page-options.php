<?php
/**
 * Page Display Options
 *
 * Adds meta boxes for controlling page-level display options
 * like hiding the title, footer, or header.
 *
 * @package Conference_Starter
 * @since 2.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Page Display Options meta box.
 */
function conference_starter_register_page_options_meta_box() {
    add_meta_box(
        'conference_starter_page_options',
        __( 'Page Display Options', 'conference-starter' ),
        'conference_starter_page_options_callback',
        'page',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'conference_starter_register_page_options_meta_box' );

/**
 * Render the Page Display Options meta box.
 *
 * @param WP_Post $post Current post object.
 */
function conference_starter_page_options_callback( $post ) {
    // Add nonce for security
    wp_nonce_field( 'conference_starter_page_options', 'conference_starter_page_options_nonce' );

    // Get saved values
    $hide_title     = get_post_meta( $post->ID, '_conference_starter_hide_title', true );
    $hide_footer    = get_post_meta( $post->ID, '_conference_starter_hide_footer', true );
    $hide_header    = get_post_meta( $post->ID, '_conference_starter_hide_header', true );
    $transparent_header = get_post_meta( $post->ID, '_conference_starter_transparent_header', true );
    ?>
    
    <p>
        <label>
            <input type="checkbox" name="conference_starter_hide_title" value="1" <?php checked( $hide_title, '1' ); ?> />
            <?php esc_html_e( 'Hide page title', 'conference-starter' ); ?>
        </label>
    </p>
    
    <p>
        <label>
            <input type="checkbox" name="conference_starter_hide_footer" value="1" <?php checked( $hide_footer, '1' ); ?> />
            <?php esc_html_e( 'Hide footer', 'conference-starter' ); ?>
        </label>
    </p>
    
    <p>
        <label>
            <input type="checkbox" name="conference_starter_hide_header" value="1" <?php checked( $hide_header, '1' ); ?> />
            <?php esc_html_e( 'Hide header', 'conference-starter' ); ?>
        </label>
    </p>
    
    <p>
        <label>
            <input type="checkbox" name="conference_starter_transparent_header" value="1" <?php checked( $transparent_header, '1' ); ?> />
            <?php esc_html_e( 'Transparent header (overlays content)', 'conference-starter' ); ?>
        </label>
    </p>
    
    <p class="description" style="margin-top: 12px;">
        <?php esc_html_e( 'These options control how this specific page is displayed.', 'conference-starter' ); ?>
    </p>
    
    <?php
}

/**
 * Save the Page Display Options meta box data.
 *
 * @param int $post_id Post ID.
 */
function conference_starter_save_page_options( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['conference_starter_page_options_nonce'] ) ||
         ! wp_verify_nonce( $_POST['conference_starter_page_options_nonce'], 'conference_starter_page_options' ) ) {
        return;
    }

    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check permissions
    if ( ! current_user_can( 'edit_page', $post_id ) ) {
        return;
    }

    // Save/delete hide title option
    if ( isset( $_POST['conference_starter_hide_title'] ) ) {
        update_post_meta( $post_id, '_conference_starter_hide_title', '1' );
    } else {
        delete_post_meta( $post_id, '_conference_starter_hide_title' );
    }

    // Save/delete hide footer option
    if ( isset( $_POST['conference_starter_hide_footer'] ) ) {
        update_post_meta( $post_id, '_conference_starter_hide_footer', '1' );
    } else {
        delete_post_meta( $post_id, '_conference_starter_hide_footer' );
    }

    // Save/delete hide header option
    if ( isset( $_POST['conference_starter_hide_header'] ) ) {
        update_post_meta( $post_id, '_conference_starter_hide_header', '1' );
    } else {
        delete_post_meta( $post_id, '_conference_starter_hide_header' );
    }

    // Save/delete transparent header option
    if ( isset( $_POST['conference_starter_transparent_header'] ) ) {
        update_post_meta( $post_id, '_conference_starter_transparent_header', '1' );
    } else {
        delete_post_meta( $post_id, '_conference_starter_transparent_header' );
    }
}
add_action( 'save_post_page', 'conference_starter_save_page_options' );

/**
 * Check if page title should be hidden.
 *
 * @param int|null $post_id Optional post ID. Defaults to current post.
 * @return bool True if title should be hidden.
 */
function conference_starter_hide_title( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }
    }
    if ( ! $post_id ) {
        return false;
    }
    return (bool) get_post_meta( $post_id, '_conference_starter_hide_title', true );
}

/**
 * Check if footer should be hidden.
 *
 * @param int|null $post_id Optional post ID. Defaults to current post.
 * @return bool True if footer should be hidden.
 */
function conference_starter_hide_footer( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }
    }
    if ( ! $post_id ) {
        return false;
    }
    return (bool) get_post_meta( $post_id, '_conference_starter_hide_footer', true );
}

/**
 * Check if header should be hidden.
 *
 * @param int|null $post_id Optional post ID. Defaults to current post.
 * @return bool True if header should be hidden.
 */
function conference_starter_hide_header( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }
    }
    if ( ! $post_id ) {
        return false;
    }
    return (bool) get_post_meta( $post_id, '_conference_starter_hide_header', true );
}

/**
 * Check if header should be transparent.
 *
 * @param int|null $post_id Optional post ID. Defaults to current post.
 * @return bool True if header should be transparent.
 */
function conference_starter_transparent_header( $post_id = null ) {
    if ( ! $post_id ) {
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }
    }
    if ( ! $post_id ) {
        return false;
    }
    return (bool) get_post_meta( $post_id, '_conference_starter_transparent_header', true );
}

/**
 * Add body classes based on page options.
 *
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function conference_starter_page_options_body_class( $classes ) {
    if ( is_page() ) {
        if ( conference_starter_hide_title() ) {
            $classes[] = 'hide-page-title';
        }
        if ( conference_starter_hide_footer() ) {
            $classes[] = 'hide-footer';
        }
        if ( conference_starter_hide_header() ) {
            $classes[] = 'hide-header';
        }
        if ( conference_starter_transparent_header() ) {
            $classes[] = 'transparent-header';
        }
    }
    return $classes;
}
add_filter( 'body_class', 'conference_starter_page_options_body_class' );

/**
 * Add inline CSS for hiding elements.
 * Using wp_head for more reliable output.
 */
function conference_starter_page_options_inline_css() {
    if ( ! is_page() ) {
        return;
    }
    
    $css = '';
    
    if ( conference_starter_hide_title() ) {
        // Hide the hero section (when page has featured image)
        $css .= 'body.page .hero.hero-small { display: none !important; }';
        // Hide the entry header (when no featured image)  
        $css .= 'body.page .entry-header { display: none !important; }';
        // Hide breadcrumbs
        $css .= 'body.page .breadcrumbs { display: none !important; }';
        // Also hide any nav.breadcrumbs
        $css .= 'body.page nav.breadcrumbs { display: none !important; }';
    }
    
    if ( conference_starter_hide_footer() ) {
        $css .= 'body.page .site-footer, body.page #site-footer, body.page footer.site-footer { display: none !important; }';
    }
    
    if ( conference_starter_hide_header() ) {
        // Hide main header
        $css .= 'body.page .site-header, body.page #masthead { display: none !important; }';
        // Hide mobile navigation elements too
        $css .= 'body.page .mobile-navigation, body.page #mobile-navigation { display: none !important; }';
        $css .= 'body.page .mobile-nav-overlay { display: none !important; }';
        // Remove top padding that was reserved for header
        $css .= 'body.page .site-content, body.page #main-content { padding-top: 0 !important; margin-top: 0 !important; }';
    }
    
    if ( conference_starter_transparent_header() ) {
        $css .= '
            body.page .site-header,
            body.page #masthead {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1000 !important;
                background: transparent !important;
                border-bottom: none !important;
                box-shadow: none !important;
            }
            body.page .site-header .site-branding,
            body.page .site-header .site-branding a,
            body.page .site-header .site-title,
            body.page .site-header .site-title a,
            body.page .site-header .primary-navigation a,
            body.page .site-header .nav-menu a,
            body.page .site-header .nav-menu > li > a {
                color: #fff !important;
            }
            body.page .site-header .header-cta,
            body.page .site-header .btn {
                background: rgba(255,255,255,0.2) !important;
                border-color: rgba(255,255,255,0.4) !important;
                color: #fff !important;
            }
            body.page .site-header .header-cta:hover {
                background: rgba(255,255,255,0.3) !important;
            }
            body.page .site-header .hamburger span {
                background-color: #fff !important;
            }
            body.transparent-header .site-content,
            body.transparent-header #main-content {
                padding-top: 0 !important;
            }
        ';
    }
    
    if ( ! empty( $css ) ) {
        echo '<style id="conference-starter-page-options">' . $css . '</style>' . "\n";
    }
}
add_action( 'wp_head', 'conference_starter_page_options_inline_css', 999 );
