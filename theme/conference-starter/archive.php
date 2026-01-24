<?php
/**
 * The template for displaying archive pages
 *
 * @package Conference_Starter
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">
    
    <!-- Archive Header -->
    <div class="hero hero-small section-primary">
        <div class="container">
            <div class="hero-content">
                <?php conference_starter_breadcrumbs(); ?>
                <?php the_archive_title('<h1 class="hero-title">', '</h1>'); ?>
                <?php the_archive_description('<div class="hero-description">', '</div>'); ?>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="container">
            <div class="content-area<?php echo is_active_sidebar('sidebar-1') ? ' has-sidebar' : ''; ?>">
                
                <div class="main-content">
                    <?php if (have_posts()) : ?>
                        
                        <div class="posts-grid grid grid-auto gap-8">
                            <?php
                            while (have_posts()) :
                                the_post();
                                get_template_part('template-parts/content', get_post_type());
                            endwhile;
                            ?>
                        </div>

                        <?php conference_starter_pagination(); ?>

                    <?php else : ?>
                        
                        <?php get_template_part('template-parts/content', 'none'); ?>

                    <?php endif; ?>
                </div>

                <?php if (is_active_sidebar('sidebar-1')) : ?>
                    <?php get_sidebar(); ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php
get_footer();
