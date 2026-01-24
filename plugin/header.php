<?php
/**
 * The Header Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link sr-only" href="#main-content"><?php esc_html_e( 'Skip to content', 'conference-starter' ); ?></a>

<div class="site-wrapper">

    <header class="site-header" id="site-header">
        <div class="container">
            <div class="header-inner">
                
                <div class="site-branding">
                    <?php conference_starter_site_logo(); ?>
                </div>

                <button class="menu-toggle" id="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span class="sr-only"><?php esc_html_e( 'Menu', 'conference-starter' ); ?></span>
                </button>

                <nav class="main-nav" id="primary-menu" aria-label="<?php esc_attr_e( 'Primary Menu', 'conference-starter' ); ?>">
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu-list',
                        'container'      => false,
                        'fallback_cb'    => false,
                        'depth'          => 3,
                    ) );
                    ?>
                </nav>

            </div>
        </div>
    </header>
