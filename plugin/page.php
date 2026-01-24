<?php
/**
 * The Page Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

get_header();

while ( have_posts() ) :
    the_post();
    
    // Display page header
    conference_starter_page_header();
?>

<main id="main-content" class="page-content full-width">
    <div class="container">
        <div class="inner">
            
            <div class="main-content">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    
                    <div class="entry-content">
                        <?php
                        the_content();

                        wp_link_pages( array(
                            'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'conference-starter' ),
                            'after'  => '</div>',
                        ) );
                        ?>
                    </div>

                </article>

                <?php
                // If comments are open or there are comments, load up the comment template.
                if ( comments_open() || get_comments_number() ) {
                    comments_template();
                }
                ?>
            </div>

        </div>
    </div>
</main>

<?php
endwhile;

get_footer();
?>
