<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 *
 * @since 2.0.0
 */
function conference_starter_pingback_header() {
    if ( is_singular() && pings_open() ) {
        printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
    }
}
add_action( 'wp_head', 'conference_starter_pingback_header' );

/**
 * Changes comment form default fields.
 *
 * @since 2.0.0
 * @param array $defaults The default comment form arguments.
 * @return array Modified arguments.
 */
function conference_starter_comment_form_defaults( $defaults ) {
    $defaults['comment_field'] = sprintf(
        '<p class="comment-form-comment"><label for="comment">%1$s <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" maxlength="65525" required="required" placeholder="%2$s"></textarea></p>',
        esc_html__( 'Comment', 'conference-starter' ),
        esc_attr__( 'Enter your comment here...', 'conference-starter' )
    );

    return $defaults;
}
add_filter( 'comment_form_defaults', 'conference_starter_comment_form_defaults' );

/**
 * Filters the default archive titles.
 *
 * @since 2.0.0
 * @param string $title Archive title.
 * @return string Modified archive title.
 */
function conference_starter_archive_title( $title ) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_author() ) {
        $title = get_the_author();
    } elseif ( is_year() ) {
        $title = get_the_date( 'Y' );
    } elseif ( is_month() ) {
        $title = get_the_date( 'F Y' );
    } elseif ( is_day() ) {
        $title = get_the_date();
    } elseif ( is_post_type_archive() ) {
        $title = post_type_archive_title( '', false );
    } elseif ( is_tax() ) {
        $title = single_term_title( '', false );
    }

    return $title;
}
add_filter( 'get_the_archive_title', 'conference_starter_archive_title' );

/**
 * Determines whether the post thumbnail can be displayed.
 *
 * @since 2.0.0
 * @return bool Whether the post thumbnail can be displayed.
 */
function conference_starter_can_show_post_thumbnail() {
    return apply_filters(
        'conference_starter_can_show_post_thumbnail',
        ! post_password_required() && ! is_attachment() && has_post_thumbnail()
    );
}

/**
 * Returns the size for avatars used in the theme.
 *
 * @since 2.0.0
 * @return int Avatar size.
 */
function conference_starter_get_avatar_size() {
    return 60;
}

/**
 * Add custom class to navigation links.
 *
 * @since 2.0.0
 * @param string $attr Current attributes.
 * @return string Modified attributes.
 */
function conference_starter_nav_menu_link_attributes( $attr ) {
    if ( isset( $attr['class'] ) ) {
        $attr['class'] .= ' nav-link';
    } else {
        $attr['class'] = 'nav-link';
    }
    return $attr;
}
add_filter( 'nav_menu_link_attributes', 'conference_starter_nav_menu_link_attributes' );

/**
 * Add dropdown class to menu items with children.
 *
 * @since 2.0.0
 * @param array    $classes Current classes.
 * @param WP_Post  $item    Menu item.
 * @param stdClass $args    Menu arguments.
 * @param int      $depth   Depth.
 * @return array Modified classes.
 */
function conference_starter_nav_menu_css_class( $classes, $item, $args, $depth ) {
    if ( in_array( 'menu-item-has-children', $classes, true ) ) {
        $classes[] = 'has-dropdown';
    }
    return $classes;
}
add_filter( 'nav_menu_css_class', 'conference_starter_nav_menu_css_class', 10, 4 );

/**
 * Add dropdown toggle button for menu items with children.
 *
 * @since 2.0.0
 * @param string   $item_output The menu item's starting HTML output.
 * @param WP_Post  $item        Menu item data object.
 * @param int      $depth       Depth of menu item.
 * @param stdClass $args        An object of wp_nav_menu() arguments.
 * @return string Modified menu item output.
 */
function conference_starter_nav_menu_item_output( $item_output, $item, $depth, $args ) {
    if ( in_array( 'menu-item-has-children', $item->classes, true ) && 'primary' === $args->theme_location ) {
        $toggle = sprintf(
            '<button class="dropdown-toggle" aria-expanded="false" aria-label="%s">%s</button>',
            esc_attr__( 'Toggle submenu', 'conference-starter' ),
            conference_starter_get_icon( 'chevron-down' )
        );
        $item_output .= $toggle;
    }
    return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'conference_starter_nav_menu_item_output', 10, 4 );

/**
 * Wrap oEmbed content with responsive container.
 *
 * @since 2.0.0
 * @param string $html    The returned oEmbed HTML.
 * @param string $url     URL of the content to be embedded.
 * @param array  $attr    An array of shortcode attributes.
 * @param int    $post_id Post ID.
 * @return string Modified HTML.
 */
function conference_starter_embed_wrapper( $html, $url, $attr, $post_id ) {
    // Only wrap video embeds
    $video_providers = array( 'youtube', 'vimeo', 'dailymotion', 'wistia', 'videopress' );
    $is_video = false;

    foreach ( $video_providers as $provider ) {
        if ( strpos( $url, $provider ) !== false ) {
            $is_video = true;
            break;
        }
    }

    if ( $is_video ) {
        return '<div class="embed-responsive embed-responsive--16by9">' . $html . '</div>';
    }

    return $html;
}
add_filter( 'embed_oembed_html', 'conference_starter_embed_wrapper', 10, 4 );

/**
 * Exclude sticky posts from main query.
 *
 * @since 2.0.0
 * @param WP_Query $query The WP_Query instance.
 */
function conference_starter_modify_main_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
        // Optionally exclude sticky posts from main loop
        // $query->set( 'ignore_sticky_posts', 1 );
    }
}
add_action( 'pre_get_posts', 'conference_starter_modify_main_query' );

/**
 * Add preconnect for Google Fonts.
 *
 * @since 2.0.0
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed.
 * @return array Modified URLs.
 */
function conference_starter_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href' => 'https://fonts.googleapis.com',
        );
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }

    return $urls;
}
add_filter( 'wp_resource_hints', 'conference_starter_resource_hints', 10, 2 );

// Note: conference_starter_content_width() is defined in functions.php

/**
 * Add custom image sizes to media library.
 *
 * @since 2.0.0
 * @param array $sizes Registered image sizes.
 * @return array Modified image sizes.
 */
function conference_starter_custom_image_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'hero'    => __( 'Hero Image', 'conference-starter' ),
        'card'    => __( 'Card Image', 'conference-starter' ),
        'speaker' => __( 'Speaker Image', 'conference-starter' ),
    ) );
}
add_filter( 'image_size_names_choose', 'conference_starter_custom_image_sizes' );

/**
 * Disable WP emoji scripts.
 *
 * @since 2.0.0
 */
function conference_starter_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'conference_starter_disable_emojis' );

/**
 * Remove WordPress version from header.
 *
 * @since 2.0.0
 * @return string Empty string.
 */
function conference_starter_remove_version() {
    return '';
}
add_filter( 'the_generator', 'conference_starter_remove_version' );

/**
 * Add schema.org markup to posts.
 *
 * @since 2.0.0
 * @param array $attr Post class attributes.
 * @return array Modified attributes.
 */
function conference_starter_schema_type() {
    $schema = 'https://schema.org/';

    if ( is_singular( 'post' ) ) {
        $type = 'BlogPosting';
    } elseif ( is_singular( array( 'ems_event', 'event' ) ) ) {
        $type = 'Event';
    } elseif ( is_author() ) {
        $type = 'ProfilePage';
    } elseif ( is_search() ) {
        $type = 'SearchResultsPage';
    } else {
        $type = 'WebPage';
    }

    echo 'itemtype="' . esc_url( $schema . $type ) . '" itemscope';
}

// Note: conference_starter_excerpt_more() is defined in functions.php

/**
 * Add custom query vars.
 *
 * @since 2.0.0
 * @param array $vars Query vars.
 * @return array Modified query vars.
 */
function conference_starter_query_vars( $vars ) {
    $vars[] = 'event_date';
    $vars[] = 'session_track';
    return $vars;
}
add_filter( 'query_vars', 'conference_starter_query_vars' );
