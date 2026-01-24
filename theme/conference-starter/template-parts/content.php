<?php
/**
 * Template part for displaying posts
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
	
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card-image">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php the_post_thumbnail( 'conference-card', array( 'loading' => 'lazy' ) ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="card-body">
		<header class="entry-header">
			<?php
			if ( 'post' === get_post_type() ) :
				$categories = get_the_category();
				if ( ! empty( $categories ) ) :
					?>
					<div class="entry-categories">
						<a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>" class="badge badge-primary">
							<?php echo esc_html( $categories[0]->name ); ?>
						</a>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php
			the_title(
				'<h2 class="entry-title card-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
				'</a></h2>'
			);
			?>

			<div class="entry-meta card-meta">
				<?php
				conference_starter_posted_on();
				?>
			</div>
		</header>

		<div class="entry-summary card-content">
			<?php the_excerpt(); ?>
		</div>

		<footer class="entry-footer card-footer">
			<a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm">
				<?php esc_html_e( 'Read More', 'conference-starter' ); ?>
				<svg class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="5" y1="12" x2="19" y2="12"></line>
					<polyline points="12 5 19 12 12 19"></polyline>
				</svg>
			</a>
		</footer>
	</div>

</article>
