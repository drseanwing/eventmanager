<?php
/**
 * The main template file
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
    <div class="container">
        
        <?php if (!is_front_page()) : ?>
            <?php conference_starter_breadcrumbs(); ?>
        <?php endif; ?>

        <div class="content-area<?php echo is_active_sidebar('sidebar-1') ? ' has-sidebar' : ''; ?>">
            
            <div class="main-content">
                <?php if (have_posts()) : ?>
                    
                    <?php if (is_home() && !is_front_page()) : ?>
                        <header class="page-header">
                            <h1 class="page-title"><?php single_post_title(); ?></h1>
                        </header>
                    <?php endif; ?>

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
</main>

<?php
get_footer();
