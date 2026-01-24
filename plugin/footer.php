<?php
/**
 * The Footer Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */
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
