<?php
/**
 * The 404 Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

get_header();

// Display 404 header
conference_starter_page_header();
?>

<main id="main-content" class="page-content full-width">
    <div class="container">
        <div class="inner">
            
            <div class="main-content">
                <div class="error-404 not-found text-center">
                    
                    <div class="error-code">
                        <span style="font-size: 120px; font-weight: 700; color: var(--color-border); line-height: 1;">404</span>
                    </div>

                    <h2><?php esc_html_e( 'Oops! That page can\'t be found.', 'conference-starter' ); ?></h2>
                    
                    <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'conference-starter' ); ?></p>

                    <div class="search-form-wrapper" style="max-width: 500px; margin: var(--spacing-xl) auto;">
                        <?php get_search_form(); ?>
                    </div>

                    <p>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
                            <?php esc_html_e( 'Back to Homepage', 'conference-starter' ); ?>
                        </a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</main>

<?php get_footer(); ?>
