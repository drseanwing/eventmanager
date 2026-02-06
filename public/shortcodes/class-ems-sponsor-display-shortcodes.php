<?php
/**
 * Sponsor Public Display Shortcodes
 *
 * Provides shortcodes for public-facing sponsor displays:
 * - [ems_event_sponsors]   - Event sponsors grouped by level with pills
 * - [ems_sponsor_carousel] - Auto-scrolling sponsor logo carousel (multi or single mode)
 * - [ems_sponsor_gallery]  - CSS Grid sponsor logo gallery
 * - [ems_sponsor_levels]   - Sponsorship levels card display
 * - [ems_sponsor_grid]     - Responsive grid of sponsor logos linked to sponsor pages
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/public/shortcodes
 * @since      1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EMS_Sponsor_Display_Shortcodes
 *
 * Registers and renders shortcodes for public-facing sponsor displays.
 *
 * @since 1.5.0
 */
class EMS_Sponsor_Display_Shortcodes {

	/**
	 * Whether the carousel script has been enqueued for the current page.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	private $carousel_enqueued = false;

	/**
	 * Constructor
	 *
	 * Register all display shortcodes.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		add_shortcode( 'ems_event_sponsors', array( $this, 'event_sponsors_shortcode' ) );
		add_shortcode( 'ems_sponsor_carousel', array( $this, 'sponsor_carousel_shortcode' ) );
		add_shortcode( 'ems_sponsor_gallery', array( $this, 'sponsor_gallery_shortcode' ) );
		add_shortcode( 'ems_sponsor_levels', array( $this, 'sponsor_levels_shortcode' ) );
		add_shortcode( 'ems_sponsor_grid', array( $this, 'sponsor_grid_shortcode' ) );
	}

	// =========================================================================
	// HELPER: Validation & Data Retrieval
	// =========================================================================

	/**
	 * Validate an event ID and check sponsorship is enabled.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return bool True if valid and enabled, false otherwise.
	 */
	private function validate_event( $event_id ) {
		if ( ! $event_id ) {
			return false;
		}

		$post = get_post( $event_id );
		if ( ! $post || 'ems_event' !== $post->post_type || 'publish' !== $post->post_status ) {
			return false;
		}

		// Check if sponsorship is enabled for this event.
		$enabled = get_post_meta( $event_id, '_ems_sponsorship_enabled', true );
		if ( ! $enabled ) {
			return false;
		}

		return true;
	}

	/**
	 * Get linked sponsors for an event, grouped by sponsorship level.
	 *
	 * Returns an array keyed by level_id (0 for unassigned) with sponsor data.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return array Array of levels, each containing 'level' object and 'sponsors' array.
	 */
	private function get_sponsors_grouped_by_level( $event_id ) {
		$linked = get_post_meta( $event_id, '_ems_linked_sponsors', true );
		if ( ! is_array( $linked ) || empty( $linked ) ) {
			return array();
		}

		$levels_handler = new EMS_Sponsorship_Levels();
		$levels         = $levels_handler->get_event_levels( $event_id );

		// Build level lookup by ID.
		$level_map = array();
		foreach ( $levels as $level ) {
			$level_map[ intval( $level->id ) ] = $level;
		}

		// Group sponsors by level.
		$grouped = array();
		foreach ( $linked as $link ) {
			// Support both array and legacy formats.
			if ( is_array( $link ) ) {
				$sponsor_id = absint( $link['sponsor_id'] ?? 0 );
				$level_id   = absint( $link['level_id'] ?? 0 );
			} else {
				$sponsor_id = absint( $link );
				$level_id   = 0;
			}

			if ( ! $sponsor_id ) {
				continue;
			}

			$sponsor = get_post( $sponsor_id );
			if ( ! $sponsor || 'ems_sponsor' !== $sponsor->post_type || 'publish' !== $sponsor->post_status ) {
				continue;
			}

			if ( ! isset( $grouped[ $level_id ] ) ) {
				$grouped[ $level_id ] = array(
					'level'    => isset( $level_map[ $level_id ] ) ? $level_map[ $level_id ] : null,
					'sponsors' => array(),
				);
			}

			$grouped[ $level_id ]['sponsors'][] = $sponsor;
		}

		// Sort groups by level sort_order (levels with objects first, unassigned last).
		uasort( $grouped, function ( $a, $b ) {
			$a_order = $a['level'] ? intval( $a['level']->sort_order ) : PHP_INT_MAX;
			$b_order = $b['level'] ? intval( $b['level']->sort_order ) : PHP_INT_MAX;
			return $a_order - $b_order;
		} );

		return $grouped;
	}

	/**
	 * Get a flat list of linked sponsor posts for an event.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return array Array of WP_Post objects.
	 */
	private function get_sponsors_flat( $event_id ) {
		$linked = get_post_meta( $event_id, '_ems_linked_sponsors', true );
		if ( ! is_array( $linked ) || empty( $linked ) ) {
			return array();
		}

		$sponsors = array();
		foreach ( $linked as $link ) {
			$sponsor_id = is_array( $link ) ? absint( $link['sponsor_id'] ?? 0 ) : absint( $link );
			if ( ! $sponsor_id ) {
				continue;
			}
			$sponsor = get_post( $sponsor_id );
			if ( $sponsor && 'ems_sponsor' === $sponsor->post_type && 'publish' === $sponsor->post_status ) {
				$sponsors[] = $sponsor;
			}
		}

		return $sponsors;
	}

	/**
	 * Render a sponsorship level pill.
	 *
	 * @since 1.5.0
	 * @param object|null $level Level object from wp_ems_sponsorship_levels.
	 * @return string HTML for the level pill.
	 */
	private function render_level_pill( $level ) {
		if ( ! $level ) {
			return '';
		}

		$colour = ! empty( $level->colour ) ? $level->colour : '#666666';
		$slug   = sanitize_title( $level->level_name );

		// Determine text colour based on luminance.
		$text_colour = $this->get_contrast_colour( $colour );

		$modifier_class = 'ems-sponsor-level-pill--' . esc_attr( $slug );

		return sprintf(
			'<span class="ems-sponsor-level-pill %s" style="background-color: %s; color: %s;" aria-label="%s">%s</span>',
			$modifier_class,
			esc_attr( $colour ),
			esc_attr( $text_colour ),
			/* translators: %s: sponsor level name */
			esc_attr( sprintf( __( 'Sponsorship level: %s', 'event-management-system' ), $level->level_name ) ),
			esc_html( $level->level_name )
		);
	}

	/**
	 * Get a contrasting text colour (black or white) for a given background hex.
	 *
	 * @since 1.5.0
	 * @param string $hex Background hex colour.
	 * @return string '#ffffff' or '#333333'.
	 */
	private function get_contrast_colour( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		// Relative luminance.
		$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;

		return $luminance > 0.5 ? '#333333' : '#ffffff';
	}

	// =========================================================================
	// 7.1 - Event Sponsors Shortcode
	// =========================================================================

	/**
	 * Render the event sponsors shortcode.
	 *
	 * Displays sponsors grouped by sponsorship level with coloured pills.
	 *
	 * Usage: [ems_event_sponsors event_id="123"]
	 *
	 * @since 1.5.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function event_sponsors_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'event_id'   => 0,
			'show_logos' => 'true',
			'show_names' => 'true',
		), $atts, 'ems_event_sponsors' );

		$event_id   = absint( $atts['event_id'] );
		$show_logos = filter_var( $atts['show_logos'], FILTER_VALIDATE_BOOLEAN );
		$show_names = filter_var( $atts['show_names'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $this->validate_event( $event_id ) ) {
			return '';
		}

		$grouped = $this->get_sponsors_grouped_by_level( $event_id );
		if ( empty( $grouped ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="ems-event-sponsors" aria-label="<?php esc_attr_e( 'Event Sponsors', 'event-management-system' ); ?>">
			<?php foreach ( $grouped as $group ) : ?>
				<div class="ems-event-sponsors__level-group">
					<?php if ( $group['level'] ) : ?>
						<div class="ems-event-sponsors__level-header">
							<?php echo $this->render_level_pill( $group['level'] ); ?>
						</div>
					<?php endif; ?>

					<div class="ems-event-sponsors__list">
						<?php foreach ( $group['sponsors'] as $sponsor ) : ?>
							<a href="<?php echo esc_url( get_permalink( $sponsor->ID ) ); ?>"
							   class="ems-event-sponsors__item no-lightbox"
							   data-no-lightbox="true"
							   title="<?php echo esc_attr( $sponsor->post_title ); ?>">
								<?php if ( $show_logos && has_post_thumbnail( $sponsor->ID ) ) : ?>
									<div class="ems-event-sponsors__logo">
										<?php echo get_the_post_thumbnail(
											$sponsor->ID,
											'medium',
											array(
												'alt'     => $sponsor->post_title,
												'loading' => 'lazy',
											)
										); ?>
									</div>
								<?php endif; ?>
								<?php if ( $show_names ) : ?>
									<span class="ems-event-sponsors__name"><?php echo esc_html( $sponsor->post_title ); ?></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// 7.2 - Sponsor Logo Carousel Shortcode
	// =========================================================================

	/**
	 * Render the sponsor logo carousel shortcode.
	 *
	 * Auto-scrolling carousel with pause-on-hover, keyboard nav, and responsive.
	 *
	 * Usage: [ems_sponsor_carousel event_id="123"]
	 *
	 * @since 1.5.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function sponsor_carousel_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'event_id' => 0,
			'speed'    => '3000',
			'mode'     => 'multi',
		), $atts, 'ems_sponsor_carousel' );

		$event_id = absint( $atts['event_id'] );
		$speed    = absint( $atts['speed'] );
		$mode     = in_array( $atts['mode'], array( 'single', 'multi' ), true ) ? $atts['mode'] : 'multi';

		if ( ! $this->validate_event( $event_id ) ) {
			return '';
		}

		$sponsors = $this->get_sponsors_flat( $event_id );
		if ( empty( $sponsors ) ) {
			return '';
		}

		// Enqueue carousel JS only when shortcode is used.
		if ( ! $this->carousel_enqueued ) {
			wp_enqueue_script(
				'ems-carousel',
				EMS_PLUGIN_URL . 'public/js/ems-carousel.js',
				array( 'jquery' ),
				defined( 'EMS_VERSION' ) ? EMS_VERSION : '1.5.0',
				true
			);
			$this->carousel_enqueued = true;
		}

		$carousel_id = 'ems-carousel-' . $event_id . '-' . wp_rand( 100, 999 );
		$is_single   = ( 'single' === $mode );
		$image_size  = $is_single ? 'large' : 'medium';
		$wrapper_class = 'ems-sponsor-carousel' . ( $is_single ? ' ems-sponsor-carousel--single' : '' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>"
			 id="<?php echo esc_attr( $carousel_id ); ?>"
			 data-speed="<?php echo esc_attr( $speed ); ?>"
			 data-mode="<?php echo esc_attr( $mode ); ?>"
			 role="region"
			 aria-label="<?php esc_attr_e( 'Sponsor Logo Carousel', 'event-management-system' ); ?>"
			 aria-roledescription="<?php esc_attr_e( 'carousel', 'event-management-system' ); ?>">

			<!-- Navigation Arrows -->
			<button class="ems-sponsor-carousel__prev" aria-label="<?php esc_attr_e( 'Previous sponsor', 'event-management-system' ); ?>" type="button">
				<span aria-hidden="true">&lsaquo;</span>
			</button>

			<div class="ems-sponsor-carousel__track-wrapper">
				<div class="ems-sponsor-carousel__track" aria-live="off">
					<?php foreach ( $sponsors as $index => $sponsor ) : ?>
						<div class="ems-sponsor-carousel__slide"
							 role="group"
							 aria-roledescription="<?php esc_attr_e( 'slide', 'event-management-system' ); ?>"
							 aria-label="<?php echo esc_attr( sprintf(
								 /* translators: 1: current slide number, 2: total slides */
								 __( '%1$d of %2$d', 'event-management-system' ),
								 $index + 1,
								 count( $sponsors )
							 ) ); ?>">
							<a href="<?php echo esc_url( get_permalink( $sponsor->ID ) ); ?>"
							   class="no-lightbox"
							   data-no-lightbox="true"
							   title="<?php echo esc_attr( $sponsor->post_title ); ?>">
								<?php if ( has_post_thumbnail( $sponsor->ID ) ) : ?>
									<?php echo get_the_post_thumbnail(
										$sponsor->ID,
										$image_size,
										array(
											'alt'     => $sponsor->post_title,
											'loading' => 'lazy',
										)
									); ?>
								<?php else : ?>
									<span class="ems-sponsor-carousel__placeholder">
										<?php echo esc_html( $sponsor->post_title ); ?>
									</span>
								<?php endif; ?>
							</a>
							<?php if ( $is_single ) : ?>
								<span class="ems-sponsor-carousel__sponsor-name"><?php echo esc_html( $sponsor->post_title ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<button class="ems-sponsor-carousel__next" aria-label="<?php esc_attr_e( 'Next sponsor', 'event-management-system' ); ?>" type="button">
				<span aria-hidden="true">&rsaquo;</span>
			</button>

			<!-- Dot Indicators -->
			<div class="ems-sponsor-carousel__dots" role="group" aria-label="<?php esc_attr_e( 'Carousel page indicators', 'event-management-system' ); ?>">
				<?php /* Dots will be generated by JS based on visible count */ ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// 7.3 - Sponsor Logo Gallery Shortcode
	// =========================================================================

	/**
	 * Render the sponsor logo gallery shortcode.
	 *
	 * CSS Grid of sponsor logos with optional level grouping.
	 *
	 * Usage: [ems_sponsor_gallery event_id="123" columns="4" group_by_level="true" show_names="false"]
	 *
	 * @since 1.5.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function sponsor_gallery_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'event_id'       => 0,
			'columns'        => '4',
			'group_by_level' => 'true',
			'show_names'     => 'false',
		), $atts, 'ems_sponsor_gallery' );

		$event_id       = absint( $atts['event_id'] );
		$columns        = max( 1, min( 8, absint( $atts['columns'] ) ) );
		$group_by_level = filter_var( $atts['group_by_level'], FILTER_VALIDATE_BOOLEAN );
		$show_names     = filter_var( $atts['show_names'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $this->validate_event( $event_id ) ) {
			return '';
		}

		ob_start();

		if ( $group_by_level ) {
			$grouped = $this->get_sponsors_grouped_by_level( $event_id );
			if ( empty( $grouped ) ) {
				return '';
			}
			?>
			<div class="ems-sponsor-gallery ems-sponsor-gallery--grouped"
				 aria-label="<?php esc_attr_e( 'Sponsor Gallery', 'event-management-system' ); ?>">
				<?php foreach ( $grouped as $group ) : ?>
					<div class="ems-sponsor-gallery__level-section">
						<?php if ( $group['level'] ) : ?>
							<div class="ems-sponsor-gallery__level-header">
								<?php echo $this->render_level_pill( $group['level'] ); ?>
							</div>
						<?php endif; ?>
						<div class="ems-sponsor-gallery__grid" style="--ems-gallery-columns: <?php echo esc_attr( $columns ); ?>;">
							<?php $this->render_gallery_items( $group['sponsors'], $show_names ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		} else {
			$sponsors = $this->get_sponsors_flat( $event_id );
			if ( empty( $sponsors ) ) {
				return '';
			}
			?>
			<div class="ems-sponsor-gallery"
				 aria-label="<?php esc_attr_e( 'Sponsor Gallery', 'event-management-system' ); ?>">
				<div class="ems-sponsor-gallery__grid" style="--ems-gallery-columns: <?php echo esc_attr( $columns ); ?>;">
					<?php $this->render_gallery_items( $sponsors, $show_names ); ?>
				</div>
			</div>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Render gallery items (shared between grouped and flat modes).
	 *
	 * @since 1.5.0
	 * @param array $sponsors Array of WP_Post sponsor objects.
	 * @param bool  $show_names Whether to show sponsor names.
	 */
	private function render_gallery_items( $sponsors, $show_names ) {
		foreach ( $sponsors as $sponsor ) :
			?>
			<a href="<?php echo esc_url( get_permalink( $sponsor->ID ) ); ?>"
			   class="ems-sponsor-gallery__item no-lightbox"
			   data-no-lightbox="true"
			   title="<?php echo esc_attr( $sponsor->post_title ); ?>">
				<?php if ( has_post_thumbnail( $sponsor->ID ) ) : ?>
					<div class="ems-sponsor-gallery__logo">
						<?php echo get_the_post_thumbnail(
							$sponsor->ID,
							'medium',
							array(
								'alt'     => $sponsor->post_title,
								'loading' => 'lazy',
							)
						); ?>
					</div>
				<?php else : ?>
					<div class="ems-sponsor-gallery__logo ems-sponsor-gallery__logo--placeholder">
						<span><?php echo esc_html( mb_substr( $sponsor->post_title, 0, 1 ) ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $show_names ) : ?>
					<span class="ems-sponsor-gallery__name"><?php echo esc_html( $sponsor->post_title ); ?></span>
				<?php endif; ?>
			</a>
			<?php
		endforeach;
	}

	// =========================================================================
	// 7.7 - Sponsorship Levels Public Display Shortcode
	// =========================================================================

	/**
	 * Render the sponsorship levels shortcode.
	 *
	 * Displays cards for each sponsorship level with value, slots, and EOI button.
	 *
	 * Usage: [ems_sponsor_levels event_id="123"]
	 *
	 * @since 1.5.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function sponsor_levels_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'event_id'     => 0,
			'show_value'   => 'true',
			'show_slots'   => 'true',
			'currency'     => 'AUD',
		), $atts, 'ems_sponsor_levels' );

		$event_id   = absint( $atts['event_id'] );
		$show_value = filter_var( $atts['show_value'], FILTER_VALIDATE_BOOLEAN );
		$show_slots = filter_var( $atts['show_slots'], FILTER_VALIDATE_BOOLEAN );
		$currency   = sanitize_text_field( $atts['currency'] );

		if ( ! $this->validate_event( $event_id ) ) {
			return '';
		}

		$levels_handler = new EMS_Sponsorship_Levels();
		$levels         = $levels_handler->get_event_levels( $event_id );

		if ( empty( $levels ) ) {
			return '';
		}

		// Build EOI page link if available.
		$eoi_page_id  = get_option( 'ems_page_sponsor_eoi' );
		$eoi_base_url = $eoi_page_id ? get_permalink( $eoi_page_id ) : '';

		ob_start();
		?>
		<div class="ems-sponsor-levels" aria-label="<?php esc_attr_e( 'Sponsorship Levels', 'event-management-system' ); ?>">
			<div class="ems-sponsor-levels__grid">
				<?php foreach ( $levels as $level ) :
					if ( ! $level->enabled ) {
						continue;
					}

					$available   = $levels_handler->get_available_slots( $level->id );
					$is_sold_out = ( 0 === $available );
					$is_unlimited = ( -1 === $available );
					$colour      = ! empty( $level->colour ) ? $level->colour : '#666666';
					$text_colour = $this->get_contrast_colour( $colour );
					?>
					<div class="ems-sponsor-levels__card<?php echo $is_sold_out ? ' ems-sponsor-levels__card--sold-out' : ''; ?>">
						<!-- Level Header -->
						<div class="ems-sponsor-levels__header" style="background-color: <?php echo esc_attr( $colour ); ?>; color: <?php echo esc_attr( $text_colour ); ?>;">
							<h3 class="ems-sponsor-levels__name"><?php echo esc_html( $level->level_name ); ?></h3>
						</div>

						<div class="ems-sponsor-levels__body">
							<?php if ( $show_value && ! is_null( $level->value_aud ) ) : ?>
								<div class="ems-sponsor-levels__value">
									<span class="ems-sponsor-levels__currency"><?php echo esc_html( $currency ); ?></span>
									<span class="ems-sponsor-levels__amount"><?php echo esc_html( number_format( floatval( $level->value_aud ), 0 ) ); ?></span>
								</div>
							<?php endif; ?>

							<?php if ( $show_slots && ! is_null( $level->slots_total ) ) : ?>
								<div class="ems-sponsor-levels__slots">
									<?php if ( $is_sold_out ) : ?>
										<span class="ems-sponsor-levels__sold-out-text">
											<?php esc_html_e( 'All slots filled', 'event-management-system' ); ?>
										</span>
									<?php else : ?>
										<span class="ems-sponsor-levels__slots-text">
											<?php echo esc_html( sprintf(
												/* translators: 1: number of slots remaining, 2: total slots */
												_n(
													'%1$d of %2$d slot remaining',
													'%1$d of %2$d slots remaining',
													$available,
													'event-management-system'
												),
												$available,
												intval( $level->slots_total )
											) ); ?>
										</span>
									<?php endif; ?>
								</div>
							<?php elseif ( $show_slots && $is_unlimited ) : ?>
								<div class="ems-sponsor-levels__slots">
									<span class="ems-sponsor-levels__slots-text">
										<?php esc_html_e( 'Unlimited slots available', 'event-management-system' ); ?>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $level->recognition_text ) ) : ?>
								<div class="ems-sponsor-levels__recognition">
									<p><?php echo esc_html( $level->recognition_text ); ?></p>
								</div>
							<?php endif; ?>

							<!-- Action -->
							<div class="ems-sponsor-levels__action">
								<?php if ( $is_sold_out ) : ?>
									<span class="ems-badge ems-badge-error ems-sponsor-levels__sold-out-badge">
										<?php esc_html_e( 'Sold Out', 'event-management-system' ); ?>
									</span>
								<?php elseif ( $eoi_base_url ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array(
										'event_id' => $event_id,
										'level'    => $level->level_slug,
									), $eoi_base_url ) ); ?>"
									   class="ems-btn ems-btn-primary">
										<?php esc_html_e( 'Express Interest', 'event-management-system' ); ?>
									</a>
								<?php else : ?>
									<span class="ems-badge ems-badge-success">
										<?php esc_html_e( 'Available', 'event-management-system' ); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// 7.8 - Sponsor Grid Shortcode
	// =========================================================================

	/**
	 * Render the sponsor grid shortcode.
	 *
	 * Displays all sponsors for an event in a responsive grid, showing their
	 * logo and linking each to their sponsor page.
	 *
	 * Usage: [ems_sponsor_grid event_id="123" columns="4"]
	 *
	 * @since 1.6.0
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function sponsor_grid_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'event_id' => 0,
			'columns'  => '4',
		), $atts, 'ems_sponsor_grid' );

		$event_id = absint( $atts['event_id'] );
		$columns  = max( 1, min( 8, absint( $atts['columns'] ) ) );

		if ( ! $this->validate_event( $event_id ) ) {
			return '';
		}

		$grouped = $this->get_sponsors_grouped_by_level( $event_id );
		if ( empty( $grouped ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="ems-sponsor-grid" aria-label="<?php esc_attr_e( 'Event Sponsors', 'event-management-system' ); ?>">
			<?php foreach ( $grouped as $group ) : ?>
				<?php if ( $group['level'] ) : ?>
					<div class="ems-sponsor-grid__level-section">
						<div class="ems-sponsor-grid__level-header">
							<?php echo $this->render_level_pill( $group['level'] ); ?>
						</div>
				<?php endif; ?>

				<div class="ems-sponsor-grid__items" style="--ems-grid-columns: <?php echo esc_attr( $columns ); ?>;">
					<?php foreach ( $group['sponsors'] as $sponsor ) : ?>
						<a href="<?php echo esc_url( get_permalink( $sponsor->ID ) ); ?>"
						   class="ems-sponsor-grid__item no-lightbox"
						   data-no-lightbox="true"
						   title="<?php echo esc_attr( $sponsor->post_title ); ?>">
							<div class="ems-sponsor-grid__logo">
								<?php if ( has_post_thumbnail( $sponsor->ID ) ) : ?>
									<?php echo get_the_post_thumbnail(
										$sponsor->ID,
										'medium',
										array(
											'alt'     => $sponsor->post_title,
											'loading' => 'lazy',
										)
									); ?>
								<?php else : ?>
									<span class="ems-sponsor-grid__logo-placeholder">
										<?php echo esc_html( mb_substr( $sponsor->post_title, 0, 1 ) ); ?>
									</span>
								<?php endif; ?>
							</div>
							<span class="ems-sponsor-grid__name"><?php echo esc_html( $sponsor->post_title ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>

				<?php if ( $group['level'] ) : ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
