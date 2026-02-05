<?php
/**
 * Single Sponsor Template
 *
 * Template for displaying a single ems_sponsor post on the front end.
 * Loaded via the single_template filter in EMS_Public.
 *
 * Sections:
 * - Logo (featured image)
 * - Organisation name
 * - Description (post_content)
 * - Website link (button)
 * - Events sponsored (list with level pills)
 * - Contact info (if public)
 *
 * @package    Event_Management_System
 * @subpackage Templates
 * @since      1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ems_get_contrast_colour' ) ) {
	/**
	 * Get contrasting text colour for a hex background.
	 *
	 * @since 1.5.0
	 * @param string $hex Hex colour string.
	 * @return string '#ffffff' or '#333333'.
	 */
	function ems_get_contrast_colour( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
		return $luminance > 0.5 ? '#333333' : '#ffffff';
	}
}

get_header();
?>

<div class="ems-single-sponsor">

	<?php while ( have_posts() ) : the_post(); ?>

		<?php
		$sponsor_id  = get_the_ID();
		$website_url = get_post_meta( $sponsor_id, '_ems_sponsor_website_url', true );
		$contact_name  = get_post_meta( $sponsor_id, '_ems_sponsor_contact_name', true );
		$contact_email = get_post_meta( $sponsor_id, '_ems_sponsor_contact_email', true );
		$contact_phone = get_post_meta( $sponsor_id, '_ems_sponsor_contact_phone', true );

		// Get linked events from reverse reference.
		$linked_events = get_post_meta( $sponsor_id, '_ems_linked_events', true );
		if ( ! is_array( $linked_events ) ) {
			$linked_events = array();
		}
		?>

		<article id="sponsor-<?php echo esc_attr( $sponsor_id ); ?>" <?php post_class( 'ems-single-sponsor__article' ); ?>>

			<!-- Sponsor Header: Logo + Title -->
			<header class="ems-single-sponsor__header">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="ems-single-sponsor__logo">
						<?php the_post_thumbnail( 'large', array(
							'alt'   => get_the_title(),
							'class' => 'ems-single-sponsor__logo-img',
						) ); ?>
					</div>
				<?php endif; ?>

				<div class="ems-single-sponsor__title-wrap">
					<h1 class="ems-single-sponsor__title"><?php the_title(); ?></h1>

					<?php if ( $website_url ) : ?>
						<a href="<?php echo esc_url( $website_url ); ?>"
						   class="ems-btn ems-btn-primary ems-single-sponsor__website-link"
						   target="_blank"
						   rel="noopener noreferrer"
						   aria-label="<?php echo esc_attr( sprintf(
							   /* translators: %s: sponsor name */
							   __( 'Visit %s website', 'event-management-system' ),
							   get_the_title()
						   ) ); ?>">
							<?php esc_html_e( 'Visit Website', 'event-management-system' ); ?>
							<span aria-hidden="true">&rarr;</span>
						</a>
					<?php endif; ?>
				</div>
			</header>

			<!-- Sponsor Description -->
			<?php if ( get_the_content() ) : ?>
				<section class="ems-single-sponsor__description" aria-label="<?php esc_attr_e( 'About', 'event-management-system' ); ?>">
					<h2><?php esc_html_e( 'About', 'event-management-system' ); ?></h2>
					<div class="ems-single-sponsor__content">
						<?php the_content(); ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- Events Sponsored -->
			<?php if ( ! empty( $linked_events ) ) : ?>
				<section class="ems-single-sponsor__events" aria-label="<?php esc_attr_e( 'Events Sponsored', 'event-management-system' ); ?>">
					<h2><?php esc_html_e( 'Events Sponsored', 'event-management-system' ); ?></h2>
					<ul class="ems-single-sponsor__events-list">
						<?php
						$levels_handler = new EMS_Sponsorship_Levels();

						foreach ( $linked_events as $link ) :
							$event_id = is_array( $link ) ? absint( $link['event_id'] ?? 0 ) : absint( $link );
							$level_id = is_array( $link ) ? absint( $link['level_id'] ?? 0 ) : 0;

							if ( ! $event_id ) {
								continue;
							}

							$event = get_post( $event_id );
							if ( ! $event || 'ems_event' !== $event->post_type || 'publish' !== $event->post_status ) {
								continue;
							}

							$level_obj = $level_id ? $levels_handler->get_level( $level_id ) : null;
							?>
							<li class="ems-single-sponsor__event-item">
								<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
									<?php echo esc_html( $event->post_title ); ?>
								</a>
								<?php
								if ( $level_obj ) {
									$colour      = ! empty( $level_obj->colour ) ? $level_obj->colour : '#666666';
									$text_colour = ems_get_contrast_colour( $colour );
									$slug        = sanitize_title( $level_obj->level_name );
									printf(
										'<span class="ems-sponsor-level-pill ems-sponsor-level-pill--%s" style="background-color: %s; color: %s;" aria-label="%s">%s</span>',
										esc_attr( $slug ),
										esc_attr( $colour ),
										esc_attr( $text_colour ),
										/* translators: %s: sponsor level name */
										esc_attr( sprintf( __( 'Sponsorship level: %s', 'event-management-system' ), $level_obj->level_name ) ),
										esc_html( $level_obj->level_name )
									);
								}
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<!-- Contact Information (public-facing subset) -->
			<?php
			$show_contact = get_post_meta( $sponsor_id, '_ems_sponsor_show_contact_public', true );
			if ( $show_contact && ( $contact_name || $contact_email || $contact_phone ) ) :
			?>
				<section class="ems-single-sponsor__contact" aria-label="<?php esc_attr_e( 'Contact Information', 'event-management-system' ); ?>">
					<h2><?php esc_html_e( 'Contact Information', 'event-management-system' ); ?></h2>
					<div class="ems-single-sponsor__contact-details">
						<?php if ( $contact_name ) : ?>
							<div class="ems-single-sponsor__contact-item">
								<strong><?php esc_html_e( 'Contact:', 'event-management-system' ); ?></strong>
								<span><?php echo esc_html( $contact_name ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $contact_email ) : ?>
							<div class="ems-single-sponsor__contact-item">
								<strong><?php esc_html_e( 'Email:', 'event-management-system' ); ?></strong>
								<a href="mailto:<?php echo esc_attr( $contact_email ); ?>"><?php echo esc_html( $contact_email ); ?></a>
							</div>
						<?php endif; ?>
						<?php if ( $contact_phone ) : ?>
							<div class="ems-single-sponsor__contact-item">
								<strong><?php esc_html_e( 'Phone:', 'event-management-system' ); ?></strong>
								<a href="tel:<?php echo esc_attr( $contact_phone ); ?>"><?php echo esc_html( $contact_phone ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

		</article>

	<?php endwhile; ?>

</div>

<?php
get_footer();
