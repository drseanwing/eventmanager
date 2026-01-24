<?php
/**
 * Theme Customizer Settings
 *
 * @package Conference_Starter
 * @since 1.0.0
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
     * Theme Options Section
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

    /*
     * Colors Section
     */
    $wp_customize->add_setting( 'conference_starter_accent_color', array(
        'default'           => '#1EC6B6',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'conference_starter_accent_color', array(
        'label'   => esc_html__( 'Accent Color', 'conference-starter' ),
        'section' => 'colors',
    ) ) );

    $wp_customize->add_setting( 'conference_starter_secondary_accent_color', array(
        'default'           => '#007bff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'conference_starter_secondary_accent_color', array(
        'label'       => esc_html__( 'Secondary Accent Color', 'conference-starter' ),
        'description' => esc_html__( 'Used for schedule headers and highlights.', 'conference-starter' ),
        'section'     => 'colors',
    ) ) );

    /*
     * Footer Section
     */
    $wp_customize->add_section( 'conference_starter_footer', array(
        'title'    => esc_html__( 'Footer', 'conference-starter' ),
        'priority' => 90,
    ) );

    // Copyright Text
    $wp_customize->add_setting( 'conference_starter_copyright', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'conference_starter_copyright', array(
        'label'       => esc_html__( 'Copyright Text', 'conference-starter' ),
        'description' => esc_html__( 'Custom copyright text for the footer. Leave empty for default.', 'conference-starter' ),
        'section'     => 'conference_starter_footer',
        'type'        => 'textarea',
    ) );

    if ( isset( $wp_customize->selective_refresh ) ) {
        $wp_customize->selective_refresh->add_partial( 'conference_starter_copyright', array(
            'selector'        => '.copyright',
            'render_callback' => 'conference_starter_copyright_partial',
        ) );
    }
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
    $accent_color = get_theme_mod( 'conference_starter_accent_color', '#1EC6B6' );
    $secondary_color = get_theme_mod( 'conference_starter_secondary_accent_color', '#007bff' );

    // Only output if colors have been changed
    if ( '#1EC6B6' === $accent_color && '#007bff' === $secondary_color ) {
        return;
    }

    $css = ':root {';
    
    if ( '#1EC6B6' !== $accent_color ) {
        $css .= '--color-accent: ' . esc_attr( $accent_color ) . ';';
    }
    
    if ( '#007bff' !== $secondary_color ) {
        $css .= '--color-accent-blue: ' . esc_attr( $secondary_color ) . ';';
    }
    
    $css .= '}';

    wp_add_inline_style( 'conference-starter-style', $css );
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
