<?php
/**
 * The header template
 *
 * @package Conference_Starter
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">
    <?php esc_html_e('Skip to content', 'conference-starter'); ?>
</a>

<header id="masthead" class="site-header" role="banner">
    <div class="container">
        <div class="header-inner">
            
            <!-- Site Branding -->
            <div class="site-branding">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="site-branding" rel="home">
                        <?php if (is_front_page() && is_home()) : ?>
                            <h1 class="site-title"><?php bloginfo('name'); ?></h1>
                        <?php else : ?>
                            <p class="site-title"><?php bloginfo('name'); ?></p>
                        <?php endif; ?>
                        
                        <?php
                        $description = get_bloginfo('description', 'display');
                        if ($description || is_customize_preview()) :
                        ?>
                            <p class="site-description"><?php echo $description; ?></p>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Primary Navigation (Desktop) -->
            <nav id="site-navigation" class="primary-navigation" role="navigation" aria-label="<?php esc_attr_e('Primary Menu', 'conference-starter'); ?>">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'menu_class'     => 'nav-menu',
                    'container'      => false,
                    'depth'          => 2,
                    'fallback_cb'    => false,
                ));
                ?>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <?php conference_starter_header_cta(); ?>
                
                <!-- Mobile Menu Toggle -->
                <button 
                    type="button" 
                    class="mobile-menu-toggle" 
                    aria-controls="mobile-navigation" 
                    aria-expanded="false"
                    aria-label="<?php esc_attr_e('Toggle mobile menu', 'conference-starter'); ?>"
                >
                    <span class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</header>

<!-- Mobile Navigation -->
<div id="mobile-navigation" class="mobile-navigation" aria-hidden="true">
    <nav role="navigation" aria-label="<?php esc_attr_e('Mobile Menu', 'conference-starter'); ?>">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'mobile',
            'menu_id'        => 'mobile-menu',
            'menu_class'     => 'mobile-nav-menu',
            'container'      => false,
            'depth'          => 2,
            'fallback_cb'    => function() {
                // Fall back to primary menu if mobile menu not set
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'mobile-menu',
                    'menu_class'     => 'mobile-nav-menu',
                    'container'      => false,
                    'depth'          => 2,
                    'fallback_cb'    => false,
                ));
            },
        ));
        ?>
        
        <?php 
        // Show CTA in mobile menu as well
        $cta_text = get_theme_mod('conference_header_cta_text', __('Register Now', 'conference-starter'));
        $cta_url = get_theme_mod('conference_header_cta_url', '#');
        
        if (!empty($cta_text) && !empty($cta_url)) :
        ?>
            <div class="mobile-nav-cta" style="padding: var(--space-4);">
                <a href="<?php echo esc_url($cta_url); ?>" class="btn btn-primary btn-block">
                    <?php echo esc_html($cta_text); ?>
                </a>
            </div>
        <?php endif; ?>
    </nav>
</div>

<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay" aria-hidden="true"></div>

<div id="main-content" class="site-content">
