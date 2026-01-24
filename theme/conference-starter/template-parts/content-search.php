<?php
/**
 * Template part for displaying results in search pages
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'card card-horizontal' ); ?>>
	
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card-image card-image-small">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="card-body">
		<header class="entry-header">
			<div class="entry-meta card-meta">
				<span class="post-type badge badge-secondary">
					<?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?>
				</span>
			</div>

			<?php the_title( sprintf( '<h2 class="entry-title card-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
		</header>

		<div class="entry-summary card-content">
			<?php the_excerpt(); ?>
		</div>

		<footer class="entry-footer card-footer">
			<a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm">
				<?php esc_html_e( 'View', 'conference-starter' ); ?>
			</a>
		</footer>
	</div>

</article>
