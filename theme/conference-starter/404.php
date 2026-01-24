<?php
/**
 * The template for displaying 404 pages (not found)
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
    
    <div class="hero hero-medium section-primary">
        <div class="container">
            <div class="hero-content hero-centered">
                <span class="hero-eyebrow"><?php esc_html_e('Error 404', 'conference-starter'); ?></span>
                <h1 class="hero-title"><?php esc_html_e('Page Not Found', 'conference-starter'); ?></h1>
                <p class="hero-description">
                    <?php esc_html_e('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'conference-starter'); ?>
                </p>
                <div class="hero-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline-light btn-lg">
                        <?php echo conference_starter_get_svg_icon('arrow-left', 20); ?>
                        <?php esc_html_e('Back to Home', 'conference-starter'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="container container-narrow">
            <div class="text-center">
                <h2><?php esc_html_e('Try searching for what you need', 'conference-starter'); ?></h2>
                <p class="text-muted mb-8"><?php esc_html_e('Use the search form below to find what you\'re looking for.', 'conference-starter'); ?></p>
                
                <?php get_search_form(); ?>
                
                <div class="mt-12">
                    <h3><?php esc_html_e('Popular Pages', 'conference-starter'); ?></h3>
                    <ul class="popular-pages" style="list-style: none; padding: 0;">
                        <?php
                        // Get popular pages
                        $popular_pages = get_pages(array(
                            'sort_column'  => 'menu_order',
                            'sort_order'   => 'ASC',
                            'number'       => 5,
                            'hierarchical' => 0,
                        ));
                        
                        foreach ($popular_pages as $page) :
                        ?>
                            <li style="margin-bottom: var(--space-2);">
                                <a href="<?php echo esc_url(get_permalink($page->ID)); ?>">
                                    <?php echo esc_html($page->post_title); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</main>

<?php
get_footer();
