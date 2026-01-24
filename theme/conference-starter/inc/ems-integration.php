<?php
/**
 * Event Management System (EMS) Plugin Integration
 *
 * Provides theme support and styling integration for the EMS plugin.
 * Handles custom post type templates, shortcode styling, and helper functions.
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if EMS plugin is active.
 *
 * @since 2.0.0
 * @return bool True if EMS plugin is active.
 */
function conference_starter_is_ems_active() {
    return defined( 'EMS_VERSION' ) || class_exists( 'EMS_Core' );
}

/**
 * Initialize EMS integration.
 *
 * @since 2.0.0
 */
function conference_starter_ems_init() {
    if ( ! conference_starter_is_ems_active() ) {
        conference_starter_log( 'EMS plugin not detected - integration skipped', 'debug' );
        return;
    }

    conference_starter_log( 'EMS plugin detected - initializing integration', 'debug' );

    // Add theme support for EMS
    add_theme_support( 'ems-integration' );

    // Register EMS-specific sidebars
    conference_starter_register_ems_sidebars();

    // Enqueue EMS integration styles
    add_action( 'wp_enqueue_scripts', 'conference_starter_enqueue_ems_styles', 20 );

    // Add EMS body classes
    add_filter( 'body_class', 'conference_starter_ems_body_classes' );

    // Customize EMS shortcode output
    add_filter( 'ems_registration_form_classes', 'conference_starter_ems_form_classes' );
    add_filter( 'ems_schedule_classes', 'conference_starter_ems_schedule_classes' );
}
add_action( 'after_setup_theme', 'conference_starter_ems_init', 15 );

/**
 * Register EMS-specific widget areas.
 *
 * @since 2.0.0
 */
function conference_starter_register_ems_sidebars() {
    register_sidebar( array(
        'name'          => esc_html__( 'Event Sidebar', 'conference-starter' ),
        'id'            => 'sidebar-event',
        'description'   => esc_html__( 'Widgets displayed on event pages.', 'conference-starter' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Registration Sidebar', 'conference-starter' ),
        'id'            => 'sidebar-registration',
        'description'   => esc_html__( 'Widgets displayed on registration pages.', 'conference-starter' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}

/**
 * Enqueue EMS integration styles.
 *
 * @since 2.0.0
 */
function conference_starter_enqueue_ems_styles() {
    if ( ! conference_starter_is_ems_active() ) {
        return;
    }

    $css_file = get_template_directory() . '/assets/css/ems-integration.css';
    
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'conference-starter-ems',
            get_template_directory_uri() . '/assets/css/ems-integration.css',
            array( 'conference-starter-style' ),
            filemtime( $css_file )
        );
    }
}

/**
 * Add EMS-specific body classes.
 *
 * @since 2.0.0
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function conference_starter_ems_body_classes( $classes ) {
    if ( ! conference_starter_is_ems_active() ) {
        return $classes;
    }

    $classes[] = 'ems-active';

    // Check for EMS post types
    $ems_post_types = array( 'ems_event', 'ems_session', 'ems_speaker', 'ems_sponsor', 'ems_abstract' );
    
    if ( is_singular( $ems_post_types ) ) {
        $classes[] = 'ems-single';
        $classes[] = 'ems-single-' . get_post_type();
    }

    if ( is_post_type_archive( $ems_post_types ) ) {
        $classes[] = 'ems-archive';
        $classes[] = 'ems-archive-' . get_post_type();
    }

    // Check for EMS pages
    if ( conference_starter_is_ems_page() ) {
        $classes[] = 'ems-page';
    }

    return $classes;
}

/**
 * Check if current page is an EMS page.
 *
 * @since 2.0.0
 * @return bool True if on an EMS page.
 */
function conference_starter_is_ems_page() {
    if ( ! conference_starter_is_ems_active() ) {
        return false;
    }

    // Check for EMS shortcodes in content
    if ( is_page() || is_single() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'ems_registration' ) ) {
            return true;
        }
        if ( $post && has_shortcode( $post->post_content, 'ems_schedule' ) ) {
            return true;
        }
        if ( $post && has_shortcode( $post->post_content, 'ems_abstract_submission' ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Add theme classes to EMS registration forms.
 *
 * @since 2.0.0
 * @param array $classes Existing classes.
 * @return array Modified classes.
 */
function conference_starter_ems_form_classes( $classes ) {
    $classes[] = 'conference-starter-form';
    return $classes;
}

/**
 * Add theme classes to EMS schedule displays.
 *
 * @since 2.0.0
 * @param array $classes Existing classes.
 * @return array Modified classes.
 */
function conference_starter_ems_schedule_classes( $classes ) {
    $classes[] = 'conference-starter-schedule';
    return $classes;
}

/**
 * Get the registration URL.
 *
 * @since 2.0.0
 * @param int $event_id Optional event ID.
 * @return string Registration URL.
 */
function conference_starter_get_registration_url( $event_id = null ) {
    // Check if EMS provides a registration URL function
    if ( function_exists( 'ems_get_registration_url' ) ) {
        return ems_get_registration_url( $event_id );
    }

    // Fallback to searching for a registration page
    $registration_page = get_page_by_path( 'register' );
    if ( ! $registration_page ) {
        $registration_page = get_page_by_path( 'registration' );
    }

    if ( $registration_page ) {
        return get_permalink( $registration_page->ID );
    }

    return home_url( '/register/' );
}

/**
 * Get the schedule URL.
 *
 * @since 2.0.0
 * @param int $event_id Optional event ID.
 * @return string Schedule URL.
 */
function conference_starter_get_schedule_url( $event_id = null ) {
    // Check if EMS provides a schedule URL function
    if ( function_exists( 'ems_get_schedule_url' ) ) {
        return ems_get_schedule_url( $event_id );
    }

    // Check for session archive
    if ( post_type_exists( 'ems_session' ) ) {
        return get_post_type_archive_link( 'ems_session' );
    }

    // Fallback to searching for a schedule page
    $schedule_page = get_page_by_path( 'schedule' );
    if ( ! $schedule_page ) {
        $schedule_page = get_page_by_path( 'program' );
    }

    if ( $schedule_page ) {
        return get_permalink( $schedule_page->ID );
    }

    return home_url( '/schedule/' );
}

/**
 * Get the abstract submission URL.
 *
 * @since 2.0.0
 * @param int $event_id Optional event ID.
 * @return string Abstract submission URL.
 */
function conference_starter_get_abstract_url( $event_id = null ) {
    // Check if EMS provides an abstract URL function
    if ( function_exists( 'ems_get_abstract_submission_url' ) ) {
        return ems_get_abstract_submission_url( $event_id );
    }

    // Fallback to searching for an abstract submission page
    $abstract_page = get_page_by_path( 'submit-abstract' );
    if ( ! $abstract_page ) {
        $abstract_page = get_page_by_path( 'abstracts' );
    }

    if ( $abstract_page ) {
        return get_permalink( $abstract_page->ID );
    }

    return home_url( '/submit-abstract/' );
}

/**
 * Display event meta information.
 *
 * @since 2.0.0
 * @param int $event_id Optional event ID.
 */
function conference_starter_event_meta( $event_id = null ) {
    if ( ! $event_id ) {
        $event_id = get_the_ID();
    }

    $start_date = get_post_meta( $event_id, '_event_start_date', true );
    $end_date = get_post_meta( $event_id, '_event_end_date', true );
    $venue = get_post_meta( $event_id, '_event_venue', true );
    $location = get_post_meta( $event_id, '_event_location', true );
    ?>
    <div class="event-meta">
        <?php if ( $start_date ) : ?>
            <div class="event-meta__item event-meta__date">
                <?php echo conference_starter_get_icon( 'calendar' ); ?>
                <span>
                    <?php
                    echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) );
                    if ( $end_date && $end_date !== $start_date ) {
                        echo ' - ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $end_date ) ) );
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ( $venue || $location ) : ?>
            <div class="event-meta__item event-meta__location">
                <?php echo conference_starter_get_icon( 'map-pin' ); ?>
                <span>
                    <?php
                    if ( $venue ) {
                        echo esc_html( $venue );
                    }
                    if ( $venue && $location ) {
                        echo ', ';
                    }
                    if ( $location ) {
                        echo esc_html( $location );
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display session meta information.
 *
 * @since 2.0.0
 * @param int $session_id Optional session ID.
 */
function conference_starter_session_meta( $session_id = null ) {
    if ( ! $session_id ) {
        $session_id = get_the_ID();
    }

    $start_time = get_post_meta( $session_id, '_session_start_time', true );
    $end_time = get_post_meta( $session_id, '_session_end_time', true );
    $location = get_post_meta( $session_id, '_session_location', true );
    $track = get_post_meta( $session_id, '_session_track', true );
    ?>
    <div class="session-meta">
        <?php if ( $start_time ) : ?>
            <div class="session-meta__item session-meta__time">
                <?php echo conference_starter_get_icon( 'clock' ); ?>
                <span>
                    <?php
                    echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $start_time ) ) );
                    if ( $end_time ) {
                        echo ' - ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $end_time ) ) );
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ( $location ) : ?>
            <div class="session-meta__item session-meta__location">
                <?php echo conference_starter_get_icon( 'map-pin' ); ?>
                <span><?php echo esc_html( $location ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( $track ) : ?>
            <div class="session-meta__item session-meta__track">
                <?php echo conference_starter_get_icon( 'tag' ); ?>
                <span><?php echo esc_html( $track ); ?></span>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Get event status badge HTML.
 *
 * @since 2.0.0
 * @param int $event_id Optional event ID.
 * @return string Status badge HTML.
 */
function conference_starter_get_event_status_badge( $event_id = null ) {
    if ( ! $event_id ) {
        $event_id = get_the_ID();
    }

    $status = get_post_meta( $event_id, '_event_status', true );
    $start_date = get_post_meta( $event_id, '_event_start_date', true );
    $end_date = get_post_meta( $event_id, '_event_end_date', true );

    // Determine status based on dates if not explicitly set
    if ( ! $status && $start_date ) {
        $now = current_time( 'timestamp' );
        $start = strtotime( $start_date );
        $end = $end_date ? strtotime( $end_date ) : $start;

        if ( $now < $start ) {
            $status = 'upcoming';
        } elseif ( $now >= $start && $now <= $end ) {
            $status = 'ongoing';
        } else {
            $status = 'past';
        }
    }

    $labels = array(
        'upcoming' => __( 'Upcoming', 'conference-starter' ),
        'ongoing'  => __( 'Happening Now', 'conference-starter' ),
        'past'     => __( 'Past Event', 'conference-starter' ),
        'draft'    => __( 'Coming Soon', 'conference-starter' ),
    );

    $label = isset( $labels[ $status ] ) ? $labels[ $status ] : '';

    if ( empty( $label ) ) {
        return '';
    }

    return sprintf(
        '<span class="event-status event-status--%s">%s</span>',
        esc_attr( $status ),
        esc_html( $label )
    );
}

/**
 * Provide EMS helper function aliases for theme use.
 * These wrap EMS functions with fallbacks for when the plugin isn't active.
 *
 * @since 2.0.0
 */
if ( ! function_exists( 'ems_get_registration_url' ) ) {
    function ems_get_registration_url( $event_id = null ) {
        return conference_starter_get_registration_url( $event_id );
    }
}

/**
 * Add EMS template compatibility.
 * Allows theme to override EMS plugin templates.
 *
 * @since 2.0.0
 * @param string $template Template path.
 * @return string Modified template path.
 */
function conference_starter_ems_template_override( $template ) {
    if ( ! conference_starter_is_ems_active() ) {
        return $template;
    }

    $ems_post_types = array( 'ems_event', 'ems_session', 'ems_speaker', 'ems_sponsor', 'ems_abstract' );
    
    foreach ( $ems_post_types as $post_type ) {
        if ( is_singular( $post_type ) ) {
            $theme_template = locate_template( array(
                "single-{$post_type}.php",
                'single-ems.php',
            ) );
            
            if ( $theme_template ) {
                return $theme_template;
            }
        }
        
        if ( is_post_type_archive( $post_type ) ) {
            $theme_template = locate_template( array(
                "archive-{$post_type}.php",
                'archive-ems.php',
            ) );
            
            if ( $theme_template ) {
                return $theme_template;
            }
        }
    }

    return $template;
}
add_filter( 'template_include', 'conference_starter_ems_template_override', 99 );

/**
 * Log EMS integration events.
 *
 * @since 2.0.0
 * @param string $message Log message.
 * @param string $level   Log level (debug, info, warning, error).
 */
function conference_starter_ems_log( $message, $level = 'info' ) {
    if ( function_exists( 'conference_starter_log' ) ) {
        conference_starter_log( '[EMS Integration] ' . $message, $level );
    }
}
