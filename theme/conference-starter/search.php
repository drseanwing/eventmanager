<?php
/**
 * The Search Results Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

get_header();

// Display search header
conference_starter_page_header();
?>

<main id="main-content" class="page-content <?php echo is_active_sidebar( 'sidebar-1' ) ? 'has-sidebar' : 'full-width'; ?>">
    <div class="container">
        <div class="inner">
            
            <div class="main-content">
                
                <div class="search-form-wrapper mb-lg">
                    <?php get_search_form(); ?>
                </div>

                <?php if ( have_posts() ) : ?>
                    
                    <p class="search-results-count">
                        <?php
                        printf(
                            esc_html( _n( '%d result found', '%d results found', $wp_query->found_posts, 'conference-starter' ) ),
                            $wp_query->found_posts
                        );
                        ?>
                    </p>

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
                                        <span class="post-type"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></span>
                                    </div>
                                </div>

                                <div class="card-content entry-summary">
                                    <?php the_excerpt(); ?>
                                </div>

                                <div class="card-footer">
                                    <a href="<?php the_permalink(); ?>" class="button small">
                                        <?php esc_html_e( 'View', 'conference-starter' ); ?>
                                    </a>
                                </div>

                            </article>

                        <?php endwhile; ?>
                    </div>

                    <?php conference_starter_pagination(); ?>

                <?php else : ?>

                    <div class="no-results">
                        <h2><?php esc_html_e( 'No Results Found', 'conference-starter' ); ?></h2>
                        <p><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'conference-starter' ); ?></p>
                    </div>

                <?php endif; ?>
            </div>

            <?php get_sidebar(); ?>

        </div>
    </div>
</main>

<?php get_footer(); ?>
