<?php
/**
 * Theme Customizer Settings
 *
 * Enhanced with Conference Homepage settings for MNResus.
 *
 * @package Conference_Starter
 * @since 1.0.0
 * @since 1.2.0 Added Conference Homepage settings
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add customizer settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function conference_starter_customize_register( $wp_customize ) {

    // Add selective refresh for site title and description
    $wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
    $wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

    if ( isset( $wp_customize->selective_refresh ) ) {
        $wp_customize->selective_refresh->add_partial( 'blogname', array(
            'selector'        => '.site-title a',
            'render_callback' => function() {
                bloginfo( 'name' );
            },
        ) );
        $wp_customize->selective_refresh->add_partial( 'blogdescription', array(
            'selector'        => '.site-description',
            'render_callback' => function() {
                bloginfo( 'description' );
            },
        ) );
    }

    /*
     * =========================================================================
     * Conference Settings Panel
     * =========================================================================
     */
    $wp_customize->add_panel( 'conference_settings_panel', array(
        'title'       => esc_html__( 'Conference Settings', 'conference-starter' ),
        'description' => esc_html__( 'Configure your conference/event homepage settings.', 'conference-starter' ),
        'priority'    => 25,
    ) );

    /*
     * -------------------------------------------------------------------------
     * Event Details Section
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_event_details', array(
        'title'    => esc_html__( 'Event Details', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 10,
    ) );

    // Event Date
    $wp_customize->add_setting( 'conference_event_date', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'conference_event_date', array(
        'label'       => esc_html__( 'Event Date', 'conference-starter' ),
        'description' => esc_html__( 'Date for the next major event (YYYY-MM-DD format). Used for countdown timer.', 'conference-starter' ),
        'section'     => 'conference_event_details',
        'type'        => 'date',
    ) );

    // Event Venue
    $wp_customize->add_setting( 'conference_event_venue', array(
        'default'           => 'Royal Brisbane & Women\'s Hospital',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'conference_event_venue', array(
        'label'   => esc_html__( 'Event Venue', 'conference-starter' ),
        'section' => 'conference_event_details',
        'type'    => 'text',
    ) );

    // Event Location
    $wp_customize->add_setting( 'conference_event_location', array(
        'default'           => 'Herston, Brisbane',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'conference_event_location', array(
        'label'   => esc_html__( 'Event Location', 'conference-starter' ),
        'section' => 'conference_event_details',
        'type'    => 'text',
    ) );

    // Header CTA Text
    $wp_customize->add_setting( 'conference_cta_text', array(
        'default'           => 'Register Now',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'conference_cta_text', array(
        'label'   => esc_html__( 'Header CTA Button Text', 'conference-starter' ),
        'section' => 'conference_event_details',
        'type'    => 'text',
    ) );

    // Header CTA URL
    $wp_customize->add_setting( 'conference_cta_url', array(
        'default'           => '/events/',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'conference_cta_url', array(
        'label'   => esc_html__( 'Header CTA Button URL', 'conference-starter' ),
        'section' => 'conference_event_details',
        'type'    => 'url',
    ) );

    /*
     * -------------------------------------------------------------------------
     * Colors Section
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_colors', array(
        'title'    => esc_html__( 'Brand Colors', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 20,
    ) );

    // Primary Color (Navy)
    $wp_customize->add_setting( 'conference_color_primary', array(
        'default'           => '#1a365d',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'conference_color_primary', array(
        'label'       => esc_html__( 'Primary Color (Navy)', 'conference-starter' ),
        'description' => esc_html__( 'Main brand color for headings and text.', 'conference-starter' ),
        'section'     => 'conference_colors',
    ) ) );

    // Secondary Color (Teal)
    $wp_customize->add_setting( 'conference_color_secondary', array(
        'default'           => '#0d9488',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'conference_color_secondary', array(
        'label'       => esc_html__( 'Secondary Color (Teal)', 'conference-starter' ),
        'description' => esc_html__( 'Accent color for success states and health associations.', 'conference-starter' ),
        'section'     => 'conference_colors',
    ) ) );

    // Accent Color (Orange)
    $wp_customize->add_setting( 'conference_color_accent', array(
        'default'           => '#ea580c',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'conference_color_accent', array(
        'label'       => esc_html__( 'Accent Color (Orange)', 'conference-starter' ),
        'description' => esc_html__( 'Call-to-action buttons and urgent elements.', 'conference-starter' ),
        'section'     => 'conference_colors',
    ) ) );

    /*
     * -------------------------------------------------------------------------
     * Statistics Section
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_statistics', array(
        'title'    => esc_html__( 'Statistics Bar', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 30,
    ) );

    // Stat 1
    $wp_customize->add_setting( 'conference_stat_1_value', array(
        'default'           => '2,500+',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_1_value', array(
        'label'   => esc_html__( 'Statistic 1 - Value', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_stat_1_label', array(
        'default'           => 'Healthcare professionals trained annually',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_1_label', array(
        'label'   => esc_html__( 'Statistic 1 - Label', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    // Stat 2
    $wp_customize->add_setting( 'conference_stat_2_value', array(
        'default'           => '15+',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_2_value', array(
        'label'   => esc_html__( 'Statistic 2 - Value', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_stat_2_label', array(
        'default'           => 'Years of education excellence',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_2_label', array(
        'label'   => esc_html__( 'Statistic 2 - Label', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    // Stat 3
    $wp_customize->add_setting( 'conference_stat_3_value', array(
        'default'           => '50+',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_3_value', array(
        'label'   => esc_html__( 'Statistic 3 - Value', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_stat_3_label', array(
        'default'           => 'Expert faculty members',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_3_label', array(
        'label'   => esc_html__( 'Statistic 3 - Label', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    // Stat 4
    $wp_customize->add_setting( 'conference_stat_4_value', array(
        'default'           => '98%',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_4_value', array(
        'label'   => esc_html__( 'Statistic 4 - Value', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_stat_4_label', array(
        'default'           => 'Participant satisfaction rate',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_stat_4_label', array(
        'label'   => esc_html__( 'Statistic 4 - Label', 'conference-starter' ),
        'section' => 'conference_statistics',
        'type'    => 'text',
    ) );

    /*
     * -------------------------------------------------------------------------
     * Testimonials Section
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_testimonials', array(
        'title'    => esc_html__( 'Testimonials', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 40,
    ) );

    // Testimonial 1
    $wp_customize->add_setting( 'conference_testimonial_1_quote', array(
        'default'           => 'The ALS course was the most practical and well-delivered resuscitation training I\'ve attended. The faculty created a safe learning environment while pushing us to perform at our best.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'conference_testimonial_1_quote', array(
        'label'   => esc_html__( 'Testimonial 1 - Quote', 'conference-starter' ),
        'section' => 'conference_testimonials',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'conference_testimonial_1_author', array(
        'default'           => 'Dr. Sarah Chen',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_testimonial_1_author', array(
        'label'   => esc_html__( 'Testimonial 1 - Author Name', 'conference-starter' ),
        'section' => 'conference_testimonials',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_testimonial_1_role', array(
        'default'           => 'Emergency Medicine Registrar, Princess Alexandra Hospital',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_testimonial_1_role', array(
        'label'   => esc_html__( 'Testimonial 1 - Author Role', 'conference-starter' ),
        'section' => 'conference_testimonials',
        'type'    => 'text',
    ) );

    // Testimonial 2
    $wp_customize->add_setting( 'conference_testimonial_2_quote', array(
        'default'           => 'REdI\'s simulation program transformed how our ward responds to deteriorating patients. We\'ve seen real improvements in our escalation times and team communication.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'conference_testimonial_2_quote', array(
        'label'   => esc_html__( 'Testimonial 2 - Quote', 'conference-starter' ),
        'section' => 'conference_testimonials',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'conference_testimonial_2_author', array(
        'default'           => 'Amanda Torres',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_testimonial_2_author', array(
        'label'   => esc_html__( 'Testimonial 2 - Author Name', 'conference-starter' ),
        'section' => 'conference_testimonials',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_testimonial_2_role', array(
        'default'           => 'Nurse Unit Manager, RBWH Medical Ward 7A',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_testimonial_2_role', array(
        'label'   => esc_html__( 'Testimonial 2 - Author Role', 'conference-starter' ),
        'section' => 'conference_testimonials',
        'type'    => 'text',
    ) );

    /*
     * -------------------------------------------------------------------------
     * Call to Action Section
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_cta_section', array(
        'title'    => esc_html__( 'Call to Action', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 50,
    ) );

    $wp_customize->add_setting( 'conference_cta_headline', array(
        'default'           => 'Ready to Advance Your Skills?',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_cta_headline', array(
        'label'   => esc_html__( 'CTA Headline', 'conference-starter' ),
        'section' => 'conference_cta_section',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'conference_cta_subheadline', array(
        'default'           => 'Browse our upcoming events and register today. Early registration discounts available for most programs.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'conference_cta_subheadline', array(
        'label'   => esc_html__( 'CTA Subheadline', 'conference-starter' ),
        'section' => 'conference_cta_section',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'conference_newsletter_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'conference_newsletter_url', array(
        'label'       => esc_html__( 'Newsletter Signup URL', 'conference-starter' ),
        'description' => esc_html__( 'Leave empty to hide the newsletter link.', 'conference-starter' ),
        'section'     => 'conference_cta_section',
        'type'        => 'url',
    ) );

    /*
     * -------------------------------------------------------------------------
     * About Section
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_about_section', array(
        'title'    => esc_html__( 'About Section', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 25,
    ) );

    $wp_customize->add_setting( 'conference_about_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'conference_about_image', array(
        'label'       => esc_html__( 'About Section Image', 'conference-starter' ),
        'description' => esc_html__( 'Image displayed in the About section. Recommended size: 600x400px', 'conference-starter' ),
        'section'     => 'conference_about_section',
    ) ) );

    /*
     * -------------------------------------------------------------------------
     * Footer Section (from original)
     * -------------------------------------------------------------------------
     */
    $wp_customize->add_section( 'conference_footer', array(
        'title'    => esc_html__( 'Footer', 'conference-starter' ),
        'panel'    => 'conference_settings_panel',
        'priority' => 90,
    ) );

    $wp_customize->add_setting( 'conference_footer_description', array(
        'default'           => 'Resuscitation education for Queensland healthcare professionals',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'conference_footer_description', array(
        'label'   => esc_html__( 'Footer Tagline', 'conference-starter' ),
        'section' => 'conference_footer',
        'type'    => 'text',
    ) );

    // Social Media URLs
    $wp_customize->add_setting( 'conference_social_linkedin', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'conference_social_linkedin', array(
        'label'   => esc_html__( 'LinkedIn URL', 'conference-starter' ),
        'section' => 'conference_footer',
        'type'    => 'url',
    ) );

    $wp_customize->add_setting( 'conference_social_twitter', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'conference_social_twitter', array(
        'label'   => esc_html__( 'Twitter/X URL', 'conference-starter' ),
        'section' => 'conference_footer',
        'type'    => 'url',
    ) );

    $wp_customize->add_setting( 'conference_social_youtube', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'conference_social_youtube', array(
        'label'   => esc_html__( 'YouTube URL', 'conference-starter' ),
        'section' => 'conference_footer',
        'type'    => 'url',
    ) );

    // Copyright Text
    $wp_customize->add_setting( 'conference_starter_copyright', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'conference_starter_copyright', array(
        'label'       => esc_html__( 'Copyright Text', 'conference-starter' ),
        'description' => esc_html__( 'Custom copyright text. Leave empty for default.', 'conference-starter' ),
        'section'     => 'conference_footer',
        'type'        => 'textarea',
    ) );

    if ( isset( $wp_customize->selective_refresh ) ) {
        $wp_customize->selective_refresh->add_partial( 'conference_starter_copyright', array(
            'selector'        => '.copyright',
            'render_callback' => 'conference_starter_copyright_partial',
        ) );
    }

    /*
     * =========================================================================
     * Theme Options Section (from original)
     * =========================================================================
     */
    $wp_customize->add_section( 'conference_starter_theme_options', array(
        'title'    => esc_html__( 'Theme Options', 'conference-starter' ),
        'priority' => 30,
    ) );

    // Sticky Header
    $wp_customize->add_setting( 'conference_starter_sticky_header', array(
        'default'           => true,
        'sanitize_callback' => 'conference_starter_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'conference_starter_sticky_header', array(
        'label'   => esc_html__( 'Enable Sticky Header', 'conference-starter' ),
        'section' => 'conference_starter_theme_options',
        'type'    => 'checkbox',
    ) );
}
add_action( 'customize_register', 'conference_starter_customize_register' );

/**
 * Render the copyright for selective refresh.
 */
function conference_starter_copyright_partial() {
    $copyright = get_theme_mod( 'conference_starter_copyright', '' );
    if ( ! empty( $copyright ) ) {
        echo wp_kses_post( $copyright );
    } else {
        printf(
            esc_html__( '&copy; %1$s %2$s. All rights reserved.', 'conference-starter' ),
            date( 'Y' ),
            get_bloginfo( 'name' )
        );
    }
}

/**
 * Sanitize checkbox values.
 *
 * @param bool $checked Whether the checkbox is checked.
 * @return bool
 */
function conference_starter_sanitize_checkbox( $checked ) {
    return ( ( isset( $checked ) && true === $checked ) ? true : false );
}

/**
 * Output custom CSS from customizer settings.
 */
function conference_starter_customizer_css() {
    $primary   = get_theme_mod( 'conference_color_primary', '#1a365d' );
    $secondary = get_theme_mod( 'conference_color_secondary', '#0d9488' );
    $accent    = get_theme_mod( 'conference_color_accent', '#ea580c' );

    $css = ':root {';
    
    if ( '#1a365d' !== $primary ) {
        $css .= '--color-primary: ' . esc_attr( $primary ) . ';';
    }
    
    if ( '#0d9488' !== $secondary ) {
        $css .= '--color-secondary: ' . esc_attr( $secondary ) . ';';
    }
    
    if ( '#ea580c' !== $accent ) {
        $css .= '--color-accent: ' . esc_attr( $accent ) . ';';
    }
    
    $css .= '}';

    // Only output if we have custom values
    if ( ':root {}' !== $css ) {
        wp_add_inline_style( 'conference-starter-style', $css );
    }
}
add_action( 'wp_enqueue_scripts', 'conference_starter_customizer_css', 20 );

/**
 * Enqueue customizer preview scripts.
 */
function conference_starter_customize_preview_js() {
    wp_enqueue_script(
        'conference-starter-customizer',
        CONFERENCE_STARTER_URI . '/js/customizer.js',
        array( 'customize-preview' ),
        CONFERENCE_STARTER_VERSION,
        true
    );
}
add_action( 'customize_preview_init', 'conference_starter_customize_preview_js' );
