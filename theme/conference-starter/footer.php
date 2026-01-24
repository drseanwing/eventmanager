<?php
/**
 * The Footer Template
 *
 * Enhanced with Conference Settings integration.
 *
 * @package Conference_Starter
 * @since 1.0.0
 * @since 1.2.0 Added social links and footer description from customizer
 */

// Get customizer settings
$footer_description = get_theme_mod( 'conference_footer_description', 'Resuscitation education for Queensland healthcare professionals' );
$social_linkedin    = get_theme_mod( 'conference_social_linkedin', '' );
$social_twitter     = get_theme_mod( 'conference_social_twitter', '' );
$social_youtube     = get_theme_mod( 'conference_social_youtube', '' );
$has_social         = $social_linkedin || $social_twitter || $social_youtube;
?>

    <footer class="site-footer" id="site-footer">
        <div class="container">
            
            <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) || is_active_sidebar( 'footer-4' ) ) : ?>
            <div class="footer-widgets">
                <?php
                for ( $i = 1; $i <= 4; $i++ ) {
                    if ( is_active_sidebar( 'footer-' . $i ) ) {
                        echo '<div class="footer-widget-area footer-' . esc_attr( $i ) . '">';
                        dynamic_sidebar( 'footer-' . $i );
                        echo '</div>';
                    }
                }
                ?>
            </div>
            <?php else : ?>
            <!-- Default footer content when no widgets -->
            <div class="footer-content">
                <div class="footer-brand">
                    <?php 
                    if ( has_custom_logo() ) {
                        the_custom_logo();
                    } else {
                        echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="footer-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
                    }
                    ?>
                    <?php if ( $footer_description ) : ?>
                    <p class="footer-tagline"><?php echo esc_html( $footer_description ); ?></p>
                    <?php endif; ?>
                    
                    <?php if ( $has_social ) : ?>
                    <div class="footer-social">
                        <?php if ( $social_linkedin ) : ?>
                        <a href="<?php echo esc_url( $social_linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ( $social_twitter ) : ?>
                        <a href="<?php echo esc_url( $social_twitter ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter/X">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ( $social_youtube ) : ?>
                        <a href="<?php echo esc_url( $social_youtube ); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">Upcoming Events</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About REdI</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/submit-abstract/' ) ); ?>">Submit Abstract</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4>Contact</h4>
                    <address>
                        <p>REdI Program<br>
                        Royal Brisbane & Women's Hospital<br>
                        Butterfield Street<br>
                        Herston QLD 4029</p>
                    </address>
                </div>
            </div>
            <?php endif; ?>

            <div class="footer-bottom">
                <div class="copyright">
                    <?php
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
                    ?>
                </div>

                <?php if ( has_nav_menu( 'footer' ) ) : ?>
                <nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer Menu', 'conference-starter' ); ?>">
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer',
                        'menu_id'        => 'footer-menu',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ) );
                    ?>
                </nav>
                <?php endif; ?>
            </div>

        </div>
    </footer>

</div><!-- .site-wrapper -->

<?php wp_footer(); ?>

</body>
</html>
