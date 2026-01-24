<?php
/**
 * The Archive Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

get_header();

// Display archive header
conference_starter_page_header();
?>

<main id="main-content" class="page-content <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : 'full-width'; ?>">
    <div class="container">
        <div class="inner">
            
            <div class="main-content">
                <?php if ( have_posts() ) : ?>
                    
                    <div class="posts-list">
                        <?php while ( have_posts() ) : the_post(); ?>
                            
                            <article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
                                
                                <?php if ( has_post_thumbnail() ) : ?>
                                <div class="entry-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php conference_starter_post_thumbnail( 'conference-card' ); ?>
                                    </a>
                                </div>
                                <?php endif; ?>

                                <div class="card-header entry-header">
                                    <?php the_title( '<h2 class="entry-title card-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' ); ?>
                                    <div class="entry-meta card-meta">
                                        <?php conference_starter_entry_meta(); ?>
                                    </div>
                                </div>

                                <div class="card-content entry-summary">
                                    <?php the_excerpt(); ?>
                                </div>

                                <div class="card-footer">
                                    <a href="<?php the_permalink(); ?>" class="button small">
                                        <?php esc_html_e( 'Read More', 'conference-starter' ); ?>
                                    </a>
                                </div>

                            </article>

                        <?php endwhile; ?>
                    </div>

                    <?php conference_starter_pagination(); ?>

                <?php else : ?>

                    <div class="no-results">
                        <h2><?php esc_html_e( 'Nothing Found', 'conference-starter' ); ?></h2>
                        <p><?php esc_html_e( 'It seems we can\'t find what you\'re looking for.', 'conference-starter' ); ?></p>
                    </div>

                <?php endif; ?>
            </div>

            <?php get_sidebar(); ?>

        </div>
    </div>
</main>

<?php get_footer(); ?>
