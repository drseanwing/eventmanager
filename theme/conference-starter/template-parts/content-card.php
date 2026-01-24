<?php
/**
 * Template part for displaying posts in a card layout
 *
 * Used in archive views, blog index, and related posts sections.
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'card card-compact' ); ?>>
	
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card-image">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php the_post_thumbnail( 'conference-card', array( 'loading' => 'lazy' ) ); ?>
			</a>
			<?php
			if ( 'post' === get_post_type() ) :
				$categories = get_the_category();
				if ( ! empty( $categories ) ) :
					?>
					<span class="card-badge">
						<?php echo esc_html( $categories[0]->name ); ?>
					</span>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="card-body">
		<?php
		the_title(
			'<h3 class="card-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
			'</a></h3>'
		);
		?>

		<div class="card-meta">
			<span class="card-date">
				<?php echo esc_html( get_the_date() ); ?>
			</span>
			<?php if ( function_exists( 'conference_starter_reading_time' ) ) : ?>
				<?php conference_starter_reading_time(); ?>
			<?php endif; ?>
		</div>

		<div class="card-excerpt">
			<?php echo wp_trim_words( get_the_excerpt(), 15, '...' ); ?>
		</div>

		<a href="<?php the_permalink(); ?>" class="card-link">
			<?php esc_html_e( 'Read More', 'conference-starter' ); ?>
			<svg class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="5" y1="12" x2="19" y2="12"></line>
				<polyline points="12 5 19 12 12 19"></polyline>
			</svg>
		</a>
	</div>

</article>
