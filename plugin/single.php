<?php
/**
 * The Single Post Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

get_header();

while ( have_posts() ) :
    the_post();
    
    // Display page header with featured image
    conference_starter_page_header();
?>

<main id="main-content" class="page-content <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : 'full-width'; ?>">
    <div class="container">
        <div class="inner">
            
            <div class="main-content">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    
                    <header class="entry-header">
                        <div class="entry-meta">
                            <?php conference_starter_entry_meta(); ?>
                        </div>
                    </header>

                    <div class="entry-content">
                        <?php
                        the_content();

                        wp_link_pages( array(
                            'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'conference-starter' ),
                            'after'  => '</div>',
                        ) );
                        ?>
                    </div>

                    <footer class="entry-footer">
                        <?php conference_starter_entry_footer(); ?>
                    </footer>

                </article>

                <?php conference_starter_post_navigation(); ?>

                <?php
                // If comments are open or there are comments, load up the comment template.
                if ( comments_open() || get_comments_number() ) {
                    comments_template();
                }
                ?>
            </div>

            <?php get_sidebar(); ?>

        </div>
    </div>
</main>

<?php
endwhile;

get_footer();
?>
