<?php
/**
 * Template Name: Full Width
 * Template Post Type: page
 *
 * A full-width page template without sidebar.
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main site-main--full-width">
	
	<?php while ( have_posts() ) : the_post(); ?>
	
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="hero hero-small" style="background-image: url('<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'conference-hero' ) ); ?>');">
				<div class="hero-overlay"></div>
				<div class="container">
					<div class="hero-content">
						<?php conference_starter_breadcrumbs(); ?>
						<h1 class="hero-title"><?php the_title(); ?></h1>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="container container-wide">
			<div class="section">
				
				<?php if ( ! has_post_thumbnail() ) : ?>
					<?php conference_starter_breadcrumbs(); ?>
					<header class="entry-header">
						<h1 class="entry-title"><?php the_title(); ?></h1>
					</header>
				<?php endif; ?>

				<div class="entry-content prose">
					<?php
					the_content();

					wp_link_pages( array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'conference-starter' ),
						'after'  => '</div>',
					) );
					?>
				</div>

				<?php if ( get_edit_post_link() ) : ?>
					<footer class="entry-footer">
						<?php
						edit_post_link(
							sprintf(
								wp_kses(
									/* translators: %s: Name of current page. */
									__( 'Edit <span class="screen-reader-text">%s</span>', 'conference-starter' ),
									array(
										'span' => array(
											'class' => array(),
										),
									)
								),
								wp_kses_post( get_the_title() )
							),
							'<span class="edit-link">',
							'</span>'
						);
						?>
					</footer>
				<?php endif; ?>

				<?php
				// Comments on pages (if enabled)
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;
				?>
			</div>
		</div>
	</article>

	<?php endwhile; ?>

</main>

<?php
get_footer();
