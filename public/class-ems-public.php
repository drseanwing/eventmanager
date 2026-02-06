<?php
/**
 * Public-facing functionality
 *
 * @package    EventManagementSystem
 * @subpackage Public
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Public {
	
	private $plugin_name;
	private $version;
	private $logger;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->logger      = EMS_Logger::instance();
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, EMS_PLUGIN_URL . 'public/css/ems-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, EMS_PLUGIN_URL . 'public/js/ems-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ems_public', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ems_public_nonce' ),
		) );

		// Multi-step form engine script
		wp_enqueue_script( 'ems-forms', EMS_PLUGIN_URL . 'public/js/ems-forms.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'ems-forms', 'ems_forms', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'i18n'     => array(
				'confirm_required' => __( 'Please confirm before submitting.', 'event-management-system' ),
				'submit_success'   => __( 'Form submitted successfully!', 'event-management-system' ),
				'submit_error'     => __( 'An error occurred. Please try again.', 'event-management-system' ),
				'network_error'    => __( 'A network error occurred. Please try again.', 'event-management-system' ),
			),
		) );
	}

	public function register_shortcodes() {
		// Register basic shortcodes
		add_shortcode( 'ems_event_list', array( $this, 'shortcode_event_list' ) );
		add_shortcode( 'ems_upcoming_events', array( $this, 'shortcode_upcoming_events' ) );
		add_shortcode( 'ems_event_details', array( $this, 'shortcode_event_details' ) );
		add_shortcode( 'ems_registration_form', array( $this, 'shortcode_registration_form' ) );
		add_shortcode( 'ems_abstract_submission', array( $this, 'shortcode_abstract_submission' ) );
		add_shortcode( 'ems_my_registrations', array( $this, 'shortcode_my_registrations' ) );
		
		// Load abstract shortcodes
		require_once EMS_PLUGIN_DIR . 'public/shortcodes/class-ems-abstract-shortcodes.php';
		new EMS_Abstract_Shortcodes();
		
		// Load schedule shortcodes (Phase 3)
		require_once EMS_PLUGIN_DIR . 'public/shortcodes/class-ems-schedule-shortcodes.php';
		new EMS_Schedule_Shortcodes();

		// Load sponsor portal shortcodes (Phase 4)
		require_once EMS_PLUGIN_DIR . 'public/shortcodes/class-ems-sponsor-shortcodes.php';
		new EMS_Sponsor_Shortcodes();

		// Load sponsor onboarding shortcode (Phase 5)
		require_once EMS_PLUGIN_DIR . 'public/shortcodes/class-ems-sponsor-onboarding-shortcode.php';
		new EMS_Sponsor_Onboarding_Shortcode();

		// Load sponsor EOI shortcode (Phase 6)
		require_once EMS_PLUGIN_DIR . 'public/shortcodes/class-ems-sponsor-eoi-shortcode.php';
		new EMS_Sponsor_EOI_Shortcode();

		// Load sponsor public display shortcodes (Phase 7)
		require_once EMS_PLUGIN_DIR . 'public/shortcodes/class-ems-sponsor-display-shortcodes.php';
		new EMS_Sponsor_Display_Shortcodes();

		// Add single sponsor template filter (Phase 7)
		add_filter( 'single_template', array( $this, 'sponsor_single_template' ) );

		// Add single event content filter
		add_filter( 'the_content', array( $this, 'filter_single_event_content' ) );
		
		$this->logger->debug( 'Shortcodes registered', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Filter single event content to add schedule, registration, and abstract sections.
	 *
	 * Sections are only displayed if:
	 * - Schedule: Event has at least one session
	 * - Registration: Registration is configured and not expired
	 * - Abstract Submission: Abstract dates are configured
	 *
	 * @since 1.1.0
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function filter_single_event_content( $content ) {
		// Only modify single event pages
		if ( ! is_singular( 'ems_event' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		try {
			global $post, $wpdb;
			$event_id = $post->ID;

			// Get event metadata
			$start_date       = get_post_meta( $event_id, 'event_start_date', true );
			$end_date         = get_post_meta( $event_id, 'event_end_date', true );
			$location         = get_post_meta( $event_id, 'event_location', true );
			$max_capacity     = get_post_meta( $event_id, 'event_max_capacity', true );
			$reg_open_date    = get_post_meta( $event_id, 'registration_open_date', true );
			$reg_deadline     = get_post_meta( $event_id, 'event_registration_deadline', true );
			$abstract_open    = get_post_meta( $event_id, 'abstract_submission_open', true );
			$abstract_close   = get_post_meta( $event_id, 'abstract_submission_close', true );
			$reg_enabled      = get_post_meta( $event_id, 'registration_enabled', true );
			
			// Get registration count
			$reg_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations WHERE event_id = %d AND status = 'active'",
				$event_id
			) );
			
			// Check if event has sessions
			$session_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'ems_session' 
				AND p.post_status = 'publish'
				AND pm.meta_key = 'event_id' 
				AND pm.meta_value = %d",
				$event_id
			) );
			$has_sessions = ( $session_count > 0 );

			// Determine registration availability
			$registration_available = false;
			$registration_status = '';
			$show_registration_section = false;
			
			// Registration is available if it's enabled (or not explicitly disabled) and dates allow
			$reg_explicitly_disabled = ( '0' === $reg_enabled || 'no' === $reg_enabled );
			
			if ( ! $reg_explicitly_disabled ) {
				// Check if we should show the registration section at all
				// Show if: there's a deadline set, or capacity set, or registration is explicitly enabled, or event has a start date
				if ( $reg_deadline || $max_capacity || $reg_enabled === '1' || $start_date ) {
					$show_registration_section = true;
					$registration_available = true;
					
					// Check if registration hasn't opened yet (using timezone-aware comparison)
					if ( $reg_open_date && EMS_Date_Helper::is_future( $reg_open_date ) ) {
						$registration_available = false;
						$registration_status = sprintf(
							__( 'Registration opens on %s.', 'event-management-system' ),
							EMS_Date_Helper::format( $reg_open_date )
						);
					}
					// Check if registration deadline has passed
					elseif ( $reg_deadline && EMS_Date_Helper::has_passed( $reg_deadline ) ) {
						$registration_available = false;
						$registration_status = __( 'Registration has closed for this event.', 'event-management-system' );
					}
					// Check if event has already started/passed
					elseif ( $start_date && EMS_Date_Helper::has_passed( $start_date ) ) {
						$registration_available = false;
						$registration_status = __( 'This event has already started.', 'event-management-system' );
					}
					// Check capacity
					elseif ( $max_capacity && $reg_count >= $max_capacity ) {
						$registration_available = false;
						$registration_status = __( 'This event is at full capacity.', 'event-management-system' );
						// Waitlist option is available in registration form
					}
				}
			}
			
			// Determine abstract submission availability
			$show_abstract_section = false;
			$abstract_status = '';
			$abstract_available = false;
			
			if ( $abstract_open || $abstract_close ) {
				$show_abstract_section = true;
				
				// Use timezone-aware comparison
				if ( $abstract_open && EMS_Date_Helper::is_future( $abstract_open ) ) {
					$abstract_status = sprintf(
						__( 'Abstract submissions will open on %s.', 'event-management-system' ),
						EMS_Date_Helper::format( $abstract_open )
					);
				} elseif ( $abstract_close && EMS_Date_Helper::has_passed( $abstract_close ) ) {
					$abstract_status = __( 'Abstract submissions are now closed.', 'event-management-system' );
				} else {
					$abstract_available = true;
				}
			}

			ob_start();
			?>
			
			<!-- Event Details Section -->
			<div class="ems-event-details-section" style="margin-bottom: 30px;">
				<?php if ( $start_date || $location || $max_capacity || $reg_deadline ) : ?>
				<div class="ems-event-meta" style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
						<?php if ( $start_date ) : ?>
							<div>
								<strong><?php esc_html_e( 'Date:', 'event-management-system' ); ?></strong><br>
								<?php 
								echo esc_html( EMS_Date_Helper::format_date( $start_date ) );
								if ( $end_date && $end_date !== $start_date ) {
									echo ' - ' . esc_html( EMS_Date_Helper::format_date( $end_date ) );
								}
								?>
							</div>
						<?php endif; ?>
						
						<?php if ( $location ) : ?>
							<div>
								<strong><?php esc_html_e( 'Location:', 'event-management-system' ); ?></strong><br>
								<?php echo esc_html( $location ); ?>
							</div>
						<?php endif; ?>
						
						<?php if ( $max_capacity ) : ?>
							<div>
								<strong><?php esc_html_e( 'Capacity:', 'event-management-system' ); ?></strong><br>
								<?php echo esc_html( $reg_count . ' / ' . $max_capacity ); ?>
								<?php if ( $reg_count >= $max_capacity ) : ?>
									<span style="color: #d63638;"><?php esc_html_e( '(Full)', 'event-management-system' ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						
						<?php if ( $reg_deadline ) : ?>
							<div>
								<strong><?php esc_html_e( 'Registration Deadline:', 'event-management-system' ); ?></strong><br>
								<?php echo esc_html( EMS_Date_Helper::format_date( $reg_deadline ) ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Original Content -->
				<div class="ems-event-description">
					<?php echo $content; ?>
				</div>
			</div>

			<?php
			// Schedule Section - only show if event has sessions
			if ( $has_sessions ) :
			?>
			<div class="ems-event-schedule-section" style="margin-bottom: 30px;">
				<h2><?php esc_html_e( 'Event Schedule', 'event-management-system' ); ?></h2>
				<?php 
				$schedule_shortcodes = new EMS_Schedule_Shortcodes();
				echo $schedule_shortcodes->schedule_shortcode( array( 'event_id' => $event_id ) );
				?>
			</div>
			<?php endif; ?>

			<?php
			// Registration Section - only show if registration is configured
			if ( $show_registration_section ) :
			?>
			<div class="ems-event-registration-section" style="margin-bottom: 30px;">
				<h2><?php esc_html_e( 'Register for This Event', 'event-management-system' ); ?></h2>
				<?php
				if ( $registration_available ) {
					echo $this->shortcode_registration_form( array( 'event_id' => $event_id ) );
				} else {
					echo '<div class="ems-notice ems-notice-info"><p>' . esc_html( $registration_status ) . '</p></div>';
				}
				?>
			</div>
			<?php endif; ?>

			<?php
			// Abstract Submission Section - only show if abstract dates are configured
			if ( $show_abstract_section ) :
			?>
			<div class="ems-abstract-submission-section" style="margin-bottom: 30px;">
				<h2><?php esc_html_e( 'Abstract Submission', 'event-management-system' ); ?></h2>
				
				<?php if ( $abstract_available ) : ?>
					<?php if ( is_user_logged_in() ) : ?>
						<p><?php esc_html_e( 'Abstract submissions are currently open.', 'event-management-system' ); ?></p>
						<?php 
						$submit_page_id = get_option( 'ems_page_submit_abstract' );
						$submit_url = $submit_page_id ? add_query_arg( 'event_id', $event_id, get_permalink( $submit_page_id ) ) : add_query_arg( 'event_id', $event_id, home_url( '/submit-abstract/' ) );
						?>
						<a href="<?php echo esc_url( $submit_url ); ?>" class="ems-btn ems-btn-primary">
							<?php esc_html_e( 'Submit an Abstract', 'event-management-system' ); ?>
						</a>
						<?php if ( $abstract_close ) : ?>
							<p style="margin-top: 10px; color: #666;">
								<small><?php printf( esc_html__( 'Deadline: %s', 'event-management-system' ), esc_html( EMS_Date_Helper::format( $abstract_close ) ) ); ?></small>
							</p>
						<?php endif; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'Please log in to submit an abstract.', 'event-management-system' ); ?></p>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="ems-btn ems-btn-secondary">
							<?php esc_html_e( 'Log In', 'event-management-system' ); ?>
						</a>
					<?php endif; ?>
				<?php else : ?>
					<p><?php echo esc_html( $abstract_status ); ?></p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php
			return ob_get_clean();
		} catch ( Exception $e ) {
			// Log error and return original content
			$this->logger->error( 'Error in filter_single_event_content: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			return $content;
		}
	}

	public function shortcode_event_list( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'count'    => 10,
			'status'   => 'publish',
		), $atts, 'ems_event_list' );

		$args = array(
			'post_type'      => 'ems_event',
			'post_status'    => $atts['status'],
			'posts_per_page' => intval( $atts['count'] ),
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'ems_event_category',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		$events = get_posts( $args );
		ob_start();
		echo '<div class="ems-event-list">';
		foreach ( $events as $event ) {
			echo '<div class="ems-event-item">';
			echo '<h3>' . esc_html( $event->post_title ) . '</h3>';
			echo '<div class="ems-event-excerpt">' . wp_kses_post( get_the_excerpt( $event ) ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Upcoming events shortcode with enhanced display.
	 *
	 * Shows events with dates, location, and registration status.
	 * Usage: [ems_upcoming_events count="10" category="conference" show_past="no"]
	 *
	 * @since 1.1.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_upcoming_events( $atts ) {
		$atts = shortcode_atts( array(
			'count'     => 10,
			'category'  => '',
			'show_past' => 'no',
		), $atts, 'ems_upcoming_events' );

		global $wpdb;

		// Build query
		$args = array(
			'post_type'      => 'ems_event',
			'post_status'    => 'publish',
			'posts_per_page' => intval( $atts['count'] ),
			'meta_key'       => 'event_start_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		// Filter by category
		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'ems_event_category',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		// Filter past events
		if ( 'no' === $atts['show_past'] ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);
		}

		$events = get_posts( $args );

		if ( empty( $events ) ) {
			return '<div class="ems-no-events"><p>' . esc_html__( 'No upcoming events found.', 'event-management-system' ) . '</p></div>';
		}

		ob_start();
		?>
		<div class="ems-upcoming-events">
			<?php foreach ( $events as $event ) : 
				$start_date   = get_post_meta( $event->ID, 'event_start_date', true );
				$end_date     = get_post_meta( $event->ID, 'event_end_date', true );
				$location     = get_post_meta( $event->ID, 'event_location', true );
				$max_capacity = get_post_meta( $event->ID, 'event_max_capacity', true );
				$reg_deadline = get_post_meta( $event->ID, 'event_registration_deadline', true );
				
				// Get registration count
				$reg_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations WHERE event_id = %d AND status = 'active'",
					$event->ID
				) );

				// Determine registration status
				$reg_open = true;
				$reg_status_text = __( 'Registration Open', 'event-management-system' );
				$reg_status_class = 'ems-status-open';

				// Check registration open date first
				$reg_open_date = get_post_meta( $event->ID, 'registration_open_date', true );
				if ( $reg_open_date && EMS_Date_Helper::is_future( $reg_open_date ) ) {
					$reg_open = false;
					$reg_status_text = __( 'Opens Soon', 'event-management-system' );
					$reg_status_class = 'ems-status-closed';
				} elseif ( $reg_deadline && EMS_Date_Helper::has_passed( $reg_deadline ) ) {
					$reg_open = false;
					$reg_status_text = __( 'Registration Closed', 'event-management-system' );
					$reg_status_class = 'ems-status-closed';
				} elseif ( $max_capacity && $reg_count >= $max_capacity ) {
					$reg_open = false;
					$reg_status_text = __( 'Fully Booked', 'event-management-system' );
					$reg_status_class = 'ems-status-full';
				}

				// Get categories
				$categories = get_the_terms( $event->ID, 'ems_event_category' );
			?>
				<div class="ems-event-card">
					<?php if ( has_post_thumbnail( $event->ID ) ) : ?>
						<div class="ems-event-image">
							<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
								<?php echo get_the_post_thumbnail( $event->ID, 'medium' ); ?>
							</a>
						</div>
					<?php endif; ?>
					
					<div class="ems-event-content">
						<div class="ems-event-meta-top">
							<?php if ( $start_date ) : ?>
								<span class="ems-event-date">
									<strong><?php echo esc_html( EMS_Date_Helper::format( $start_date, 'M j, Y' ) ); ?></strong>
									<?php if ( $end_date && $end_date !== $start_date ) : ?>
										- <?php echo esc_html( EMS_Date_Helper::format( $end_date, 'M j, Y' ) ); ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
							
							<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
								<span class="ems-event-category">
									<?php echo esc_html( $categories[0]->name ); ?>
								</span>
							<?php endif; ?>
						</div>
						
						<h3 class="ems-event-title">
							<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
								<?php echo esc_html( $event->post_title ); ?>
							</a>
						</h3>
						
						<?php if ( $location ) : ?>
							<p class="ems-event-location">
								<span class="dashicons dashicons-location"></span>
								<?php echo esc_html( $location ); ?>
							</p>
						<?php endif; ?>
						
						<div class="ems-event-excerpt">
							<?php echo wp_kses_post( wp_trim_words( $event->post_content, 25 ) ); ?>
						</div>
						
						<div class="ems-event-footer">
							<span class="ems-registration-status <?php echo esc_attr( $reg_status_class ); ?>">
								<?php echo esc_html( $reg_status_text ); ?>
								<?php if ( $max_capacity ) : ?>
									(<?php echo esc_html( $reg_count . '/' . $max_capacity ); ?>)
								<?php endif; ?>
							</span>
							
							<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" class="ems-btn ems-btn-small">
								<?php echo $reg_open ? esc_html__( 'Register Now', 'event-management-system' ) : esc_html__( 'View Details', 'event-management-system' ); ?>
							</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<style>
		.ems-upcoming-events { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
		.ems-event-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; transition: box-shadow 0.2s; }
		.ems-event-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
		.ems-event-image img { width: 100%; height: 180px; object-fit: cover; }
		.ems-event-content { padding: 20px; }
		.ems-event-meta-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 13px; }
		.ems-event-date { color: #2271b1; font-weight: 500; }
		.ems-event-category { background: #f0f0f0; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
		.ems-event-title { margin: 0 0 10px; font-size: 18px; }
		.ems-event-title a { color: #1d2327; text-decoration: none; }
		.ems-event-title a:hover { color: #2271b1; }
		.ems-event-location { color: #666; font-size: 14px; margin: 0 0 10px; }
		.ems-event-location .dashicons { font-size: 16px; vertical-align: middle; }
		.ems-event-excerpt { color: #555; font-size: 14px; line-height: 1.5; margin-bottom: 15px; }
		.ems-event-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; }
		.ems-registration-status { font-size: 12px; font-weight: 500; }
		.ems-status-open { color: #00a32a; }
		.ems-status-closed { color: #d63638; }
		.ems-status-full { color: #dba617; }
		.ems-btn-small { padding: 6px 12px; font-size: 13px; }
		.ems-no-events { padding: 40px; text-align: center; background: #f9f9f9; border-radius: 8px; }
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * My registrations shortcode.
	 *
	 * Shows the current user's event registrations.
	 * Usage: [ems_my_registrations]
	 *
	 * @since 1.1.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_my_registrations( $atts ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return '<div class="ems-notice ems-notice-warning">' . 
			       sprintf(
				       __( 'Please <a href="%s">log in</a> to view your registrations.', 'event-management-system' ),
				       esc_url( wp_login_url( get_permalink() ) )
			       ) . '</div>';
		}

		global $wpdb;
		$user = wp_get_current_user();
		$registration_handler = new EMS_Registration();

		// Check for cancellation message
		$cancellation_message = '';
		if ( isset( $_GET['ems_cancelled'] ) && $_GET['ems_cancelled'] === 'success' ) {
			$cancellation_message = '<div class="ems-notice ems-notice-success" style="margin-bottom: 20px;">' . 
				__( 'Your registration has been cancelled successfully.', 'event-management-system' ) . '</div>';
		}

		// Get user's registrations (including cancelled for history)
		$show_cancelled = isset( $_GET['show_cancelled'] ) && $_GET['show_cancelled'] === '1';
		$status_filter = $show_cancelled ? '' : "AND r.status != 'cancelled'";
		
		$registrations = $wpdb->get_results( $wpdb->prepare(
			"SELECT r.*, e.post_title as event_title 
			FROM {$wpdb->prefix}ems_registrations r
			LEFT JOIN {$wpdb->posts} e ON r.event_id = e.ID
			WHERE (r.user_id = %d OR r.email = %s)
			{$status_filter}
			ORDER BY r.registration_date DESC",
			$user->ID,
			$user->user_email
		) );

		if ( empty( $registrations ) ) {
			ob_start();
			echo $cancellation_message;
			?>
			<div class="ems-no-registrations">
				<p><?php esc_html_e( 'You have not registered for any events yet.', 'event-management-system' ); ?></p>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'ems_event' ) ); ?>" class="ems-btn ems-btn-primary">
					<?php esc_html_e( 'Browse Events', 'event-management-system' ); ?>
				</a>
			</div>
			<?php
			return ob_get_clean();
		}

		ob_start();
		echo $cancellation_message;
		?>
		<div class="ems-my-registrations">
			<div class="ems-registrations-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
				<h3 style="margin: 0;"><?php esc_html_e( 'My Event Registrations', 'event-management-system' ); ?></h3>
				<?php if ( ! $show_cancelled ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'show_cancelled', '1' ) ); ?>" class="ems-link-muted" style="font-size: 13px; color: #666;">
						<?php esc_html_e( 'Show cancelled registrations', 'event-management-system' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( remove_query_arg( 'show_cancelled' ) ); ?>" class="ems-link-muted" style="font-size: 13px; color: #666;">
						<?php esc_html_e( 'Hide cancelled registrations', 'event-management-system' ); ?>
					</a>
				<?php endif; ?>
			</div>
			
			<table class="ems-registrations-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Date', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Ticket Type', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Status', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $registrations as $reg ) : 
						$event_date = get_post_meta( $reg->event_id, 'event_start_date', true );
						$event_url  = get_permalink( $reg->event_id );
						$is_upcoming = $event_date && ! EMS_Date_Helper::has_passed( $event_date );
						$is_cancelled = 'cancelled' === $reg->status;
						$can_cancel = ! $is_cancelled && $is_upcoming ? $registration_handler->can_cancel_registration( $reg->id ) : array( 'can_cancel' => false );
					?>
						<tr class="<?php echo $is_cancelled ? 'ems-cancelled' : ( $is_upcoming ? 'ems-upcoming' : 'ems-past' ); ?>">
							<td>
								<a href="<?php echo esc_url( $event_url ); ?>">
									<?php echo esc_html( $reg->event_title ?: __( 'Unknown Event', 'event-management-system' ) ); ?>
								</a>
							</td>
							<td>
								<?php 
								if ( $event_date ) {
									echo esc_html( EMS_Date_Helper::format_date( $event_date ) );
								} else {
									echo '—';
								}
								?>
							</td>
							<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $reg->ticket_type ) ) ); ?></td>
							<td>
								<span class="ems-status-badge ems-status-<?php echo esc_attr( $reg->status ); ?>">
									<?php echo esc_html( ucfirst( $reg->status ) ); ?>
								</span>
								<?php if ( 'pending' === $reg->payment_status && $reg->amount_paid > 0 && ! $is_cancelled ) : ?>
									<br><small class="ems-payment-pending"><?php esc_html_e( 'Payment Pending', 'event-management-system' ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $is_cancelled ) : ?>
									<span class="ems-cancelled-label"><?php esc_html_e( 'Cancelled', 'event-management-system' ); ?></span>
								<?php elseif ( $is_upcoming ) : ?>
									<a href="<?php echo esc_url( $event_url ); ?>" class="ems-btn ems-btn-small">
										<?php esc_html_e( 'View Event', 'event-management-system' ); ?>
									</a>
									<?php
									// Check if event has sessions
									$has_sessions = get_posts( array(
										'post_type'   => 'ems_session',
										'meta_key'    => 'event_id',
										'meta_value'  => $reg->event_id,
										'numberposts' => 1,
									) );
									if ( $has_sessions ) :
									?>
										<a href="<?php echo esc_url( add_query_arg( 'registration_id', $reg->id, home_url( '/my-schedule/' ) ) ); ?>" class="ems-btn ems-btn-small ems-btn-secondary">
											<?php esc_html_e( 'My Schedule', 'event-management-system' ); ?>
										</a>
									<?php endif; ?>
									
									<?php if ( $can_cancel['can_cancel'] ) : ?>
										<button type="button" 
											class="ems-btn ems-btn-small ems-btn-danger ems-cancel-registration" 
											data-registration-id="<?php echo esc_attr( $reg->id ); ?>"
											data-event-name="<?php echo esc_attr( $reg->event_title ); ?>">
											<?php esc_html_e( 'Cancel', 'event-management-system' ); ?>
										</button>
									<?php endif; ?>
								<?php else : ?>
									<span class="ems-past-event"><?php esc_html_e( 'Event Completed', 'event-management-system' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		
		<!-- Cancellation Confirmation Modal -->
		<div id="ems-cancel-modal" class="ems-modal" style="display: none;">
			<div class="ems-modal-content">
				<span class="ems-modal-close">&times;</span>
				<h3><?php esc_html_e( 'Cancel Registration', 'event-management-system' ); ?></h3>
				<p><?php esc_html_e( 'Are you sure you want to cancel your registration for:', 'event-management-system' ); ?></p>
				<p><strong id="ems-cancel-event-name"></strong></p>
				
				<div class="ems-form-group">
					<label for="ems-cancel-reason"><?php esc_html_e( 'Reason for cancellation (optional):', 'event-management-system' ); ?></label>
					<textarea id="ems-cancel-reason" rows="3" class="ems-form-control" placeholder="<?php esc_attr_e( 'Please let us know why you are cancelling...', 'event-management-system' ); ?>"></textarea>
				</div>
				
				<div class="ems-modal-actions">
					<button type="button" class="ems-btn ems-btn-secondary ems-modal-close-btn">
						<?php esc_html_e( 'Keep Registration', 'event-management-system' ); ?>
					</button>
					<button type="button" id="ems-confirm-cancel" class="ems-btn ems-btn-danger">
						<?php esc_html_e( 'Yes, Cancel Registration', 'event-management-system' ); ?>
					</button>
				</div>
			</div>
		</div>
		
		<style>
		.ems-my-registrations { overflow-x: auto; }
		.ems-registrations-table { width: 100%; border-collapse: collapse; background: #fff; }
		.ems-registrations-table th, .ems-registrations-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
		.ems-registrations-table th { background: #f5f5f5; font-weight: 600; }
		.ems-registrations-table tr:hover { background: #fafafa; }
		.ems-registrations-table a { color: #2271b1; text-decoration: none; }
		.ems-registrations-table a:hover { text-decoration: underline; }
		.ems-status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 500; }
		.ems-status-active { background: #d4edda; color: #155724; }
		.ems-status-pending { background: #fff3cd; color: #856404; }
		.ems-status-waitlist { background: #cce5ff; color: #004085; }
		.ems-status-cancelled { background: #f8d7da; color: #721c24; }
		.ems-payment-pending { color: #856404; }
		.ems-past-event { color: #666; font-style: italic; font-size: 13px; }
		.ems-cancelled-label { color: #721c24; font-style: italic; font-size: 13px; }
		.ems-past td { opacity: 0.7; }
		.ems-cancelled td { opacity: 0.6; background: #fdf2f2; }
		.ems-btn-secondary { background: #f0f0f0; color: #1d2327; }
		.ems-btn-secondary:hover { background: #e0e0e0; }
		.ems-btn-danger { background: #dc3545; color: #fff; }
		.ems-btn-danger:hover { background: #c82333; }
		.ems-no-registrations { text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px; }
		
		/* Modal styles */
		.ems-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
		.ems-modal-content { background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; position: relative; }
		.ems-modal-close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #666; }
		.ems-modal-close:hover { color: #333; }
		.ems-modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
		.ems-form-group { margin: 15px 0; }
		.ems-form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
		.ems-form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			var currentRegistrationId = null;
			
			// Open modal
			$('.ems-cancel-registration').on('click', function() {
				currentRegistrationId = $(this).data('registration-id');
				var eventName = $(this).data('event-name');
				$('#ems-cancel-event-name').text(eventName);
				$('#ems-cancel-reason').val('');
				$('#ems-cancel-modal').show();
			});
			
			// Close modal
			$('.ems-modal-close, .ems-modal-close-btn').on('click', function() {
				$('#ems-cancel-modal').hide();
				currentRegistrationId = null;
			});
			
			// Close on outside click
			$('#ems-cancel-modal').on('click', function(e) {
				if (e.target === this) {
					$(this).hide();
					currentRegistrationId = null;
				}
			});
			
			// Confirm cancellation
			$('#ems-confirm-cancel').on('click', function() {
				if (!currentRegistrationId) return;
				
				var $btn = $(this);
				var reason = $('#ems-cancel-reason').val();
				
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Cancelling...', 'event-management-system' ) ); ?>');
				
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_cancel_registration',
						nonce: ems_public.nonce,
						registration_id: currentRegistrationId,
						reason: reason
					},
					success: function(response) {
						if (response.success) {
							// Redirect with success message
							window.location.href = '<?php echo esc_url( add_query_arg( 'ems_cancelled', 'success', get_permalink() ) ); ?>';
						} else {
							alert(response.data.message || '<?php echo esc_js( __( 'An error occurred. Please try again.', 'event-management-system' ) ); ?>');
							$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Yes, Cancel Registration', 'event-management-system' ) ); ?>');
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'event-management-system' ) ); ?>');
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Yes, Cancel Registration', 'event-management-system' ) ); ?>');
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	public function shortcode_event_details( $atts ) {
		$atts = shortcode_atts( array( 'event_id' => 0 ), $atts, 'ems_event_details' );
		if ( ! $atts['event_id'] ) {
			return '<p>' . esc_html__( 'Please specify an event ID.', 'event-management-system' ) . '</p>';
		}
		$event = get_post( $atts['event_id'] );
		if ( ! $event ) {
			return '<p>' . esc_html__( 'Event not found.', 'event-management-system' ) . '</p>';
		}
		ob_start();
		echo '<div class="ems-event-details">';
		echo '<h2>' . esc_html( $event->post_title ) . '</h2>';
		echo '<div class="ems-event-content">' . wp_kses_post( apply_filters( 'the_content', $event->post_content ) ) . '</div>';
		echo '</div>';
		return ob_get_clean();
	}

	public function shortcode_registration_form( $atts ) {
		$atts = shortcode_atts( array( 'event_id' => 0 ), $atts, 'ems_registration_form' );
		
		$event_id = absint( $atts['event_id'] );
		
		// If no event_id provided and we're on a single event page, get from post
		if ( ! $event_id && is_singular( 'ems_event' ) ) {
			global $post;
			$event_id = $post->ID;
		}
		
		// Validate event
		if ( ! $event_id ) {
			return '<div class="ems-notice ems-notice-error">' . 
			       __( 'Please specify an event ID.', 'event-management-system' ) . '</div>';
		}
		
		$event = get_post( $event_id );
		if ( ! $event || 'ems_event' !== $event->post_type ) {
			return '<div class="ems-notice ems-notice-error">' . 
			       __( 'Event not found.', 'event-management-system' ) . '</div>';
		}

		// Check event capacity
		global $wpdb;
		$max_capacity = get_post_meta( $event_id, 'event_max_capacity', true );
		$reg_count = 0;
		$at_capacity = false;

		if ( $max_capacity ) {
			$reg_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations WHERE event_id = %d AND status = 'active'",
				$event_id
			) );
			$at_capacity = ( $reg_count >= $max_capacity );
		}

		// Check if logged-in user is already registered
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$existing_registration = $this->get_user_event_registration( $current_user->ID, $current_user->user_email, $event_id );
			
			if ( $existing_registration ) {
				$my_registrations_page = get_option( 'ems_page_my_registrations' );
				$my_registrations_url = $my_registrations_page ? get_permalink( $my_registrations_page ) : '';
				
				ob_start();
				?>
				<div class="ems-notice ems-notice-info" style="padding: 20px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px;">
					<h3 style="margin-top: 0; color: #0c5460;">
						<?php esc_html_e( 'You\'re Already Registered!', 'event-management-system' ); ?>
					</h3>
					<p><?php esc_html_e( 'You have already registered for this event.', 'event-management-system' ); ?></p>
					<p>
						<strong><?php esc_html_e( 'Registration Date:', 'event-management-system' ); ?></strong> 
						<?php echo esc_html( EMS_Date_Helper::format( $existing_registration->registration_date ) ); ?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Status:', 'event-management-system' ); ?></strong> 
						<?php echo esc_html( ucfirst( $existing_registration->status ) ); ?>
						<?php if ( 'pending' === $existing_registration->payment_status && $existing_registration->amount_paid > 0 ) : ?>
							<span style="color: #856404;">(<?php esc_html_e( 'Payment Pending', 'event-management-system' ); ?>)</span>
						<?php endif; ?>
					</p>
					<?php if ( $my_registrations_url ) : ?>
						<p style="margin-bottom: 0;">
							<a href="<?php echo esc_url( $my_registrations_url ); ?>" class="ems-btn ems-btn-secondary" style="display: inline-block; padding: 8px 16px; background: #6c757d; color: #fff; text-decoration: none; border-radius: 4px;">
								<?php esc_html_e( 'View My Registrations', 'event-management-system' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>
				<?php
				return ob_get_clean();
			}
		}

		// Get ticket types if available
		$ticket_types = get_post_meta( $event_id, 'ticket_types', true );
		if ( ! is_array( $ticket_types ) ) {
			$ticket_types = array(
				array( 'name' => 'Standard', 'price' => 0 ),
			);
		}

		// Pre-fill if user is logged in
		$current_user = wp_get_current_user();
		$user_email = is_user_logged_in() ? $current_user->user_email : '';
		$user_first = is_user_logged_in() ? $current_user->first_name : '';
		$user_last = is_user_logged_in() ? $current_user->last_name : '';

		ob_start();
		?>
		<div class="ems-registration-form">
			<form id="ems-registration-form" method="post" class="ems-form">
				<?php wp_nonce_field( 'ems_public_nonce', 'ems_nonce' ); ?>
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
				
				<div class="ems-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
					<div class="ems-form-group">
						<label for="ems_first_name"><?php esc_html_e( 'First Name *', 'event-management-system' ); ?></label>
						<input type="text" id="ems_first_name" name="first_name" value="<?php echo esc_attr( $user_first ); ?>" required class="ems-form-control">
					</div>
					<div class="ems-form-group">
						<label for="ems_last_name"><?php esc_html_e( 'Last Name *', 'event-management-system' ); ?></label>
						<input type="text" id="ems_last_name" name="last_name" value="<?php echo esc_attr( $user_last ); ?>" required class="ems-form-control">
					</div>
				</div>
				
				<div class="ems-form-group">
					<label for="ems_email"><?php esc_html_e( 'Email Address *', 'event-management-system' ); ?></label>
					<input type="email" id="ems_email" name="email" value="<?php echo esc_attr( $user_email ); ?>" required class="ems-form-control" <?php echo is_user_logged_in() ? 'readonly' : ''; ?>>
					<?php if ( is_user_logged_in() ) : ?>
						<p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">
							<?php esc_html_e( 'Email is linked to your account and cannot be changed.', 'event-management-system' ); ?>
						</p>
					<?php endif; ?>
				</div>
				
				<div class="ems-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
					<div class="ems-form-group">
						<label for="ems_discipline"><?php esc_html_e( 'Discipline/Department', 'event-management-system' ); ?></label>
						<input type="text" id="ems_discipline" name="discipline" class="ems-form-control">
					</div>
					<div class="ems-form-group">
						<label for="ems_specialty"><?php esc_html_e( 'Specialty', 'event-management-system' ); ?></label>
						<input type="text" id="ems_specialty" name="specialty" class="ems-form-control">
					</div>
				</div>
				
				<div class="ems-form-group">
					<label for="ems_seniority"><?php esc_html_e( 'Position/Seniority', 'event-management-system' ); ?></label>
					<select id="ems_seniority" name="seniority" class="ems-form-control">
						<option value=""><?php esc_html_e( 'Select...', 'event-management-system' ); ?></option>
						<option value="student"><?php esc_html_e( 'Student', 'event-management-system' ); ?></option>
						<option value="intern"><?php esc_html_e( 'Intern/Resident', 'event-management-system' ); ?></option>
						<option value="junior"><?php esc_html_e( 'Junior/Early Career', 'event-management-system' ); ?></option>
						<option value="senior"><?php esc_html_e( 'Senior', 'event-management-system' ); ?></option>
						<option value="consultant"><?php esc_html_e( 'Consultant/Specialist', 'event-management-system' ); ?></option>
						<option value="other"><?php esc_html_e( 'Other', 'event-management-system' ); ?></option>
					</select>
				</div>

				<?php if ( count( $ticket_types ) > 1 ) : ?>
				<div class="ems-form-group">
					<label for="ems_ticket_type"><?php esc_html_e( 'Registration Type *', 'event-management-system' ); ?></label>
					<select id="ems_ticket_type" name="ticket_type" required class="ems-form-control">
						<?php foreach ( $ticket_types as $ticket ) : ?>
							<option value="<?php echo esc_attr( sanitize_title( $ticket['name'] ) ); ?>">
								<?php 
								echo esc_html( $ticket['name'] );
								if ( ! empty( $ticket['price'] ) && $ticket['price'] > 0 ) {
									echo ' - $' . esc_html( number_format( $ticket['price'], 2 ) );
								} elseif ( isset( $ticket['price'] ) && $ticket['price'] == 0 ) {
									echo ' - ' . esc_html__( 'Free', 'event-management-system' );
								}
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php else : ?>
					<input type="hidden" name="ticket_type" value="standard">
				<?php endif; ?>
				
				<?php if ( $at_capacity ) : ?>
				<div class="ems-form-group" style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin-top: 20px;">
					<div style="display: flex; align-items: flex-start; margin-bottom: 10px;">
						<span style="font-size: 20px; color: #856404; margin-right: 10px;">⚠️</span>
						<div>
							<strong style="color: #856404;"><?php esc_html_e( 'Event at Full Capacity', 'event-management-system' ); ?></strong>
							<p style="margin: 5px 0 0 0; color: #856404;">
								<?php 
								echo esc_html( sprintf(
									__( 'This event has reached maximum capacity (%d/%d registrations).', 'event-management-system' ),
									$reg_count,
									$max_capacity
								) );
								?>
							</p>
						</div>
					</div>
					<div class="ems-waitlist-option">
						<label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
							<input type="checkbox" name="join_waitlist" id="ems_join_waitlist" value="1" style="margin-right: 8px;">
							<span><?php esc_html_e( 'Join the waitlist to be notified if a spot becomes available', 'event-management-system' ); ?></span>
						</label>
						<p class="description" style="margin: 8px 0 0 0; font-size: 12px; color: #856404;">
							<?php esc_html_e( 'You will receive an email notification if a spot opens up. You can then complete your registration at that time.', 'event-management-system' ); ?>
						</p>
					</div>
				</div>
				<?php endif; ?>
				
				<div class="ems-form-actions" style="margin-top: 20px;">
					<button type="submit" class="ems-btn ems-btn-primary">
						<?php 
						if ( $at_capacity ) {
							esc_html_e( 'Join Waitlist', 'event-management-system' );
						} else {
							esc_html_e( 'Complete Registration', 'event-management-system' );
						}
						?>
					</button>
				</div>
			</form>
		</div>
		
		<?php if ( $at_capacity ) : ?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var $form = $('#ems-registration-form');
			var $submitBtn = $form.find('button[type="submit"]');
			var $waitlistCheckbox = $('#ems_join_waitlist');
			
			// Initially disable submit if at capacity and waitlist not checked
			$submitBtn.prop('disabled', true);
			
			// Enable submit when waitlist checkbox is checked
			$waitlistCheckbox.on('change', function() {
				$submitBtn.prop('disabled', !$(this).is(':checked'));
			});
			
			// Update button text based on waitlist checkbox
			$waitlistCheckbox.on('change', function() {
				if ($(this).is(':checked')) {
					$submitBtn.text('<?php echo esc_js( __( 'Join Waitlist', 'event-management-system' ) ); ?>');
					$submitBtn.prop('disabled', false);
				} else {
					$submitBtn.text('<?php echo esc_js( __( 'Complete Registration', 'event-management-system' ) ); ?>');
					$submitBtn.prop('disabled', true);
				}
			});
		});
		</script>
		<?php endif; ?>
		
		<style>
		.ems-form-group { margin-bottom: 15px; }
		.ems-form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
		.ems-form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
		.ems-form-control[readonly] { background-color: #f5f5f5; cursor: not-allowed; }
		.ems-btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
		.ems-btn-primary { background: #2271b1; color: #fff; }
		.ems-btn-primary:hover { background: #135e96; }
		.ems-notice { padding: 15px; border-radius: 4px; margin-bottom: 15px; }
		.ems-notice-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
		.ems-notice-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
		.ems-notice-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if user is already registered for an event
	 *
	 * @since 1.3.0
	 * @param int    $user_id   User ID.
	 * @param string $email     User email.
	 * @param int    $event_id  Event ID.
	 * @return object|null Registration object or null if not registered.
	 */
	private function get_user_event_registration( $user_id, $email, $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';
		
		// Check by both user_id and email to catch all cases
		$registration = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} 
				WHERE event_id = %d 
				AND (user_id = %d OR email = %s) 
				AND status != 'cancelled'
				ORDER BY registration_date DESC
				LIMIT 1",
				$event_id,
				$user_id,
				$email
			)
		);
		
		return $registration;
	}

	public function shortcode_abstract_submission( $atts ) {
		// This shortcode is handled by EMS_Abstract_Shortcodes class
		// which is loaded in register_shortcodes() and overrides this registration
		// This method serves as a fallback
		if ( ! is_user_logged_in() ) {
			return '<div class="ems-notice ems-notice-warning">' . 
			       __( 'Please log in to submit an abstract.', 'event-management-system' ) . 
			       ' <a href="' . wp_login_url( get_permalink() ) . '">' . __( 'Log in', 'event-management-system' ) . '</a></div>';
		}
		
		// The EMS_Abstract_Shortcodes class handles the actual form rendering
		// If this is reached, the class may not have loaded properly
		$abstract_shortcodes = new EMS_Abstract_Shortcodes();
		return $abstract_shortcodes->abstract_submission_shortcode( $atts );
	}

	public function ajax_submit_registration() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			// Validate required fields
			$required_fields = array( 'event_id', 'email', 'first_name', 'last_name' );
			foreach ( $required_fields as $field ) {
				if ( empty( $_POST[ $field ] ) ) {
					wp_send_json_error( array(
						'message' => sprintf( __( 'The %s field is required.', 'event-management-system' ), str_replace( '_', ' ', $field ) ),
					) );
				}
			}

			$event_id = absint( $_POST['event_id'] );
			$email = sanitize_email( $_POST['email'] );

			// Validate email format
			if ( ! is_email( $email ) ) {
				wp_send_json_error( array(
					'message' => __( 'Please enter a valid email address.', 'event-management-system' ),
				) );
			}

			// For logged-in users, force email to match their account
			// This prevents users from registering with different emails
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$email = $current_user->user_email;
				
				// Check if already registered (pre-emptive check before hitting the registration class)
				$existing = $this->get_user_event_registration( $current_user->ID, $email, $event_id );
				if ( $existing ) {
					wp_send_json_error( array(
						'message' => __( 'You are already registered for this event.', 'event-management-system' ),
					) );
				}
			}

			// Sanitize input data
			$registration_data = array(
				'event_id'    => $event_id,
				'email'       => $email,
				'first_name'  => sanitize_text_field( $_POST['first_name'] ),
				'last_name'   => sanitize_text_field( $_POST['last_name'] ),
				'discipline'  => isset( $_POST['discipline'] ) ? sanitize_text_field( $_POST['discipline'] ) : '',
				'specialty'   => isset( $_POST['specialty'] ) ? sanitize_text_field( $_POST['specialty'] ) : '',
				'seniority'   => isset( $_POST['seniority'] ) ? sanitize_text_field( $_POST['seniority'] ) : '',
				'ticket_type' => isset( $_POST['ticket_type'] ) ? sanitize_text_field( $_POST['ticket_type'] ) : 'standard',
				'user_id'     => is_user_logged_in() ? get_current_user_id() : 0,
			);

			// Process registration
			$registration_handler = new EMS_Registration();
			$result = $registration_handler->register_participant( $registration_data );

			if ( $result['success'] ) {
				wp_send_json_success( array(
					'message'         => $result['message'],
					'registration_id' => $result['registration_id'],
					'redirect_url'    => isset( $result['redirect_url'] ) ? $result['redirect_url'] : '',
				) );
			} else {
				wp_send_json_error( array(
					'message' => $result['message'],
				) );
			}

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_submit_registration error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array(
				'message' => __( 'An error occurred during registration. Please try again.', 'event-management-system' ),
			) );
		}
	}

	/**
	 * Handle AJAX registration cancellation
	 *
	 * @since 1.3.0
	 */
	public function ajax_cancel_registration() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in to cancel a registration.', 'event-management-system' ),
			) );
		}

		try {
			$registration_id = isset( $_POST['registration_id'] ) ? absint( $_POST['registration_id'] ) : 0;
			$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

			if ( ! $registration_id ) {
				wp_send_json_error( array(
					'message' => __( 'Invalid registration ID.', 'event-management-system' ),
				) );
			}

			// Check if cancellation is allowed
			$registration_handler = new EMS_Registration();
			$can_cancel = $registration_handler->can_cancel_registration( $registration_id );
			
			if ( ! $can_cancel['can_cancel'] ) {
				wp_send_json_error( array(
					'message' => $can_cancel['reason'],
				) );
			}

			// Process cancellation
			$result = $registration_handler->cancel_registration( $registration_id, 'user', $reason );

			if ( $result['success'] ) {
				wp_send_json_success( array(
					'message' => $result['message'],
				) );
			} else {
				wp_send_json_error( array(
					'message' => $result['message'],
				) );
			}

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_cancel_registration error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array(
				'message' => __( 'An error occurred. Please try again.', 'event-management-system' ),
			) );
		}
	}

	public function ajax_submit_abstract() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in to submit an abstract.', 'event-management-system' ),
			) );
		}

		try {
			// Validate required fields
			$required_fields = array( 'event_id', 'title', 'content', 'abstract_type' );
			foreach ( $required_fields as $field ) {
				if ( empty( $_POST[ $field ] ) ) {
					wp_send_json_error( array(
						'message' => sprintf( __( 'The %s field is required.', 'event-management-system' ), str_replace( '_', ' ', $field ) ),
					) );
				}
			}

			// Sanitize input data
			$abstract_data = array(
				'event_id'            => absint( $_POST['event_id'] ),
				'user_id'             => get_current_user_id(),
				'title'               => sanitize_text_field( $_POST['title'] ),
				'content'             => wp_kses_post( $_POST['content'] ),
				'abstract_type'       => sanitize_text_field( $_POST['abstract_type'] ),
				'keywords'            => isset( $_POST['keywords'] ) ? sanitize_text_field( $_POST['keywords'] ) : '',
				'learning_objectives' => isset( $_POST['learning_objectives'] ) ? wp_kses_post( $_POST['learning_objectives'] ) : '',
				'presenter_bio'       => isset( $_POST['presenter_bio'] ) ? wp_kses_post( $_POST['presenter_bio'] ) : '',
			);

			// Handle co-authors
			if ( ! empty( $_POST['coauthors'] ) && is_array( $_POST['coauthors'] ) ) {
				$coauthors = array();
				foreach ( $_POST['coauthors'] as $coauthor ) {
					if ( ! empty( $coauthor['name'] ) ) {
						$coauthors[] = array(
							'name'        => sanitize_text_field( $coauthor['name'] ),
							'email'       => sanitize_email( $coauthor['email'] ),
							'affiliation' => sanitize_text_field( $coauthor['affiliation'] ?? '' ),
						);
					}
				}
				$abstract_data['co_authors'] = $coauthors;
			}

			// Process abstract submission
			$submission_handler = new EMS_Abstract_Submission();
			$result = $submission_handler->submit_abstract( $abstract_data );

			if ( $result['success'] ) {
				// Handle file uploads if present
				if ( ! empty( $_FILES['files'] ) ) {
					$file_manager = new EMS_File_Manager();
					$file_manager->process_abstract_files( $result['abstract_id'], $_FILES['files'] );
				}

				wp_send_json_success( array(
					'message'      => $result['message'],
					'abstract_id'  => $result['abstract_id'],
					'redirect_url' => home_url( '/presenter-dashboard/' ),
				) );
			} else {
				wp_send_json_error( array(
					'message' => $result['message'],
				) );
			}

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_submit_abstract error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array(
				'message' => __( 'An error occurred during submission. Please try again.', 'event-management-system' ),
			) );
		}
	}

	public function ajax_register_session() {
		try {
			// Verify nonce
			check_ajax_referer( 'ems_schedule_nonce', 'nonce' );

			// Check if user is logged in
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array(
					'message' => __( 'Please log in to register for sessions', 'event-management-system' ),
				) );
			}

			// Get session ID
			$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
			if ( ! $session_id ) {
				wp_send_json_error( array(
					'message' => __( 'Invalid session ID', 'event-management-system' ),
				) );
			}

			// Get user's event registration
			global $wpdb;
			$user = wp_get_current_user();
			
			$session_event_id = get_post_meta( $session_id, 'event_id', true );
			
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations 
					WHERE event_id = %d AND (user_id = %d OR email = %s) AND status = 'active'
					ORDER BY registration_date DESC LIMIT 1",
					$session_event_id,
					$user->ID,
					$user->user_email
				)
			);

			if ( ! $registration ) {
				wp_send_json_error( array(
					'message' => __( 'You must be registered for the event to register for sessions', 'event-management-system' ),
				) );
			}

			// Register for session
			$session_registration = new EMS_Session_Registration();
			$result = $session_registration->register_for_session( $session_id, $registration->id );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}

		} catch ( Exception $e ) {
			$logger = EMS_Logger::instance();
			$logger->error(
				'Exception in ajax_register_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			wp_send_json_error( array(
				'message' => __( 'An error occurred while registering for the session', 'event-management-system' ),
			) );
		}
	}

	public function ajax_cancel_session() {
		try {
			// Verify nonce
			check_ajax_referer( 'ems_schedule_nonce', 'nonce' );

			// Check if user is logged in
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array(
					'message' => __( 'Please log in to manage your sessions', 'event-management-system' ),
				) );
			}

			// Get session ID
			$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
			if ( ! $session_id ) {
				wp_send_json_error( array(
					'message' => __( 'Invalid session ID', 'event-management-system' ),
				) );
			}

			// Get user's event registration
			global $wpdb;
			$user = wp_get_current_user();
			
			$session_event_id = get_post_meta( $session_id, 'event_id', true );
			
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations 
					WHERE event_id = %d AND (user_id = %d OR email = %s) AND status = 'active'
					ORDER BY registration_date DESC LIMIT 1",
					$session_event_id,
					$user->ID,
					$user->user_email
				)
			);

			if ( ! $registration ) {
				wp_send_json_error( array(
					'message' => __( 'Registration not found', 'event-management-system' ),
				) );
			}

			// Cancel session registration
			$session_registration = new EMS_Session_Registration();
			$result = $session_registration->cancel_session_registration( $session_id, $registration->id );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}

		} catch ( Exception $e ) {
			$logger = EMS_Logger::instance();
			$logger->error(
				'Exception in ajax_cancel_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			wp_send_json_error( array(
				'message' => __( 'An error occurred while cancelling the session', 'event-management-system' ),
			) );
		}
	}

	public function register_rest_routes() {
		register_rest_route( 'ems/v1', '/events', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'rest_get_events' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function rest_get_events( $request ) {
		$per_page = min( absint( $request->get_param( 'per_page' ) ?: 20 ), 100 );
		$page     = max( absint( $request->get_param( 'page' ) ?: 1 ), 1 );

		$events = get_posts( array(
			'post_type'      => 'ems_event',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		) );

		$safe = array_map( function( $event ) {
			return array(
				'id'    => $event->ID,
				'title' => $event->post_title,
				'date'  => $event->post_date,
				'link'  => get_permalink( $event->ID ),
			);
		}, $events );

		return rest_ensure_response( $safe );
	}

	/**
	 * Handle iCal export requests
	 *
	 * @since 1.2.0
	 */
	public function handle_ical_export() {
		// Check if this is an iCal export request
		if ( ! isset( $_GET['ems_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['ems_action'] );

		if ( $action === 'export_ical' && isset( $_GET['event_id'] ) ) {
			$event_id = absint( $_GET['event_id'] );
			$schedule_display = new EMS_Schedule_Display();
			$schedule_display->generate_ical( $event_id );
			exit;
		}

		if ( $action === 'export_my_ical' && isset( $_GET['registration_id'] ) ) {
			$registration_id = absint( $_GET['registration_id'] );
			
			// Verify user owns this registration
			if ( ! is_user_logged_in() ) {
				wp_die( __( 'Please log in to export your schedule', 'event-management-system' ) );
			}
			
			global $wpdb;
			$user = wp_get_current_user();
			
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations 
					WHERE id = %d AND (user_id = %d OR email = %s)",
					$registration_id,
					$user->ID,
					$user->user_email
				)
			);
			
			if ( ! $registration ) {
				wp_die( __( 'Registration not found or access denied', 'event-management-system' ) );
			}
			
			// Get user's session registrations
			$session_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT session_id FROM {$wpdb->prefix}ems_session_registrations 
					WHERE registration_id = %d AND attendance_status != 'cancelled'",
					$registration_id
				)
			);
			
			if ( empty( $session_ids ) ) {
				wp_die( __( 'No sessions registered', 'event-management-system' ) );
			}
			
			$schedule_display = new EMS_Schedule_Display();
			$schedule_display->generate_personal_ical( $session_ids, $registration );
			exit;
		}
	}

	/**
	 * Handle sponsor file downloads
	 *
	 * Processes download requests for sponsor files with access control.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function handle_sponsor_file_download() {
		// Check if download request
		if ( ! isset( $_GET['ems_download_sponsor_file'] ) ) {
			return;
		}

		$file_id = absint( $_GET['ems_download_sponsor_file'] );
		if ( ! $file_id ) {
			wp_die( esc_html__( 'Invalid file ID', 'event-management-system' ) );
		}

		// Verify nonce to prevent CSRF.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ems_download_file_' . $file_id ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'event-management-system' ) );
		}

		// Load sponsor portal
		require_once EMS_PLUGIN_DIR . 'includes/collaboration/class-ems-sponsor-portal.php';
		$sponsor_portal = new EMS_Sponsor_Portal();

		// Trigger download
		$sponsor_portal->download_sponsor_file( $file_id );
		exit;
	}

	// =========================================================================
	// Multi-Step Form AJAX Handlers (Phase 4 - Form Framework)
	// =========================================================================

	/**
	 * AJAX handler: Validate a single form step.
	 *
	 * Expects POST data: ems_form_id, current_step, form_data, nonce.
	 * Returns success or error with field-specific messages.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function ajax_form_validate_step() {
		// Verify nonce
		$form_id = isset( $_POST['ems_form_id'] ) ? sanitize_key( $_POST['ems_form_id'] ) : '';
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form.', 'event-management-system' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ems_form_' . $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh and try again.', 'event-management-system' ) ) );
		}

		try {
			$current_step = isset( $_POST['current_step'] ) ? absint( $_POST['current_step'] ) : 0;

			/**
			 * Filters the multi-step form instance used for AJAX validation.
			 *
			 * Consuming code (e.g. Onboarding, EOI) must hook here to provide
			 * a fully-configured EMS_Multi_Step_Form with registered steps.
			 *
			 * @since 1.5.0
			 * @param EMS_Multi_Step_Form|null $form    The form instance (null by default).
			 * @param string                   $form_id The form identifier.
			 */
			$form = apply_filters( 'ems_get_multi_step_form', null, $form_id );

			if ( ! $form instanceof EMS_Multi_Step_Form ) {
				wp_send_json_error( array( 'message' => __( 'Form configuration not found.', 'event-management-system' ) ) );
			}

			$steps = $form->get_steps();
			if ( ! isset( $steps[ $current_step ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid step.', 'event-management-system' ) ) );
			}

			$step_slug = $steps[ $current_step ]['slug'];

			// Parse form data from serialised string
			$raw_data = array();
			if ( isset( $_POST['form_data'] ) ) {
				parse_str( $_POST['form_data'], $raw_data );
			}

			// Merge direct POST fields as well
			$step_data = $this->sanitize_form_step_data( $raw_data, $steps[ $current_step ] );

			// Validate
			$result = $form->validate_step( $step_slug, $step_data );

			if ( true === $result ) {
				wp_send_json_success( array( 'message' => __( 'Step validated.', 'event-management-system' ) ) );
			}

			// Build field-specific errors
			$errors = array();
			if ( is_wp_error( $result ) ) {
				foreach ( $result->get_error_codes() as $code ) {
					$errors[ $code ] = $result->get_error_messages( $code );
				}
			}

			wp_send_json_error( array(
				'message' => __( 'Please correct the errors below.', 'event-management-system' ),
				'errors'  => $errors,
			) );

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_form_validate_step error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred. Please try again.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX handler: Save form progress (state persistence).
	 *
	 * Saves current step data to a transient so users can resume later.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function ajax_form_save_progress() {
		$form_id = isset( $_POST['ems_form_id'] ) ? sanitize_key( $_POST['ems_form_id'] ) : '';
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form.', 'event-management-system' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ems_form_' . $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'event-management-system' ) ) );
		}

		try {
			$current_step = isset( $_POST['current_step'] ) ? absint( $_POST['current_step'] ) : 0;

			/** @see ems_get_multi_step_form filter */
			$form = apply_filters( 'ems_get_multi_step_form', null, $form_id );

			if ( ! $form instanceof EMS_Multi_Step_Form ) {
				wp_send_json_error( array( 'message' => __( 'Form configuration not found.', 'event-management-system' ) ) );
			}

			// Load existing state first
			$form->load_state();

			$steps = $form->get_steps();

			// Parse the serialised form data
			$raw_data = array();
			if ( isset( $_POST['form_data'] ) ) {
				parse_str( $_POST['form_data'], $raw_data );
			}

			// Save data for the current step (the step we are leaving)
			$leaving_step = isset( $raw_data['ems_current_step'] ) ? absint( $raw_data['ems_current_step'] ) : $current_step;
			if ( isset( $steps[ $leaving_step ] ) ) {
				$step_data = $this->sanitize_form_step_data( $raw_data, $steps[ $leaving_step ] );
				$form->set_form_data( $steps[ $leaving_step ]['slug'], $step_data );
			}

			$form->set_current_step( $current_step );
			$form->save_state();

			wp_send_json_success( array(
				'message'      => __( 'Progress saved.', 'event-management-system' ),
				'redirect_url' => '',
			) );

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_form_save_progress error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred while saving progress.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX handler: Submit the complete multi-step form.
	 *
	 * Validates all steps, then fires the `ems_form_submitted_{form_id}` action
	 * so that form-specific handlers can process the data.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function ajax_form_submit() {
		$form_id = isset( $_POST['ems_form_id'] ) ? sanitize_key( $_POST['ems_form_id'] ) : '';
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form.', 'event-management-system' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ems_form_' . $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh and try again.', 'event-management-system' ) ) );
		}

		try {
			/** @see ems_get_multi_step_form filter */
			$form = apply_filters( 'ems_get_multi_step_form', null, $form_id );

			if ( ! $form instanceof EMS_Multi_Step_Form ) {
				wp_send_json_error( array( 'message' => __( 'Form configuration not found.', 'event-management-system' ) ) );
			}

			// Load saved state (has data from all previous steps)
			$form->load_state();

			// Parse the final POST data for the last step
			$raw_data = array();
			if ( isset( $_POST['form_data'] ) ) {
				parse_str( $_POST['form_data'], $raw_data );
			}

			$steps = $form->get_steps();

			// Save the last step's data
			$last_step_index = isset( $raw_data['ems_current_step'] ) ? absint( $raw_data['ems_current_step'] ) : ( count( $steps ) - 1 );
			if ( isset( $steps[ $last_step_index ] ) ) {
				$step_data = $this->sanitize_form_step_data( $raw_data, $steps[ $last_step_index ] );
				$form->set_form_data( $steps[ $last_step_index ]['slug'], $step_data );
			}

			// Validate all steps
			$all_errors = array();
			foreach ( $steps as $index => $step ) {
				$step_data = $form->get_step_data( $step['slug'] );
				$result    = $form->validate_step( $step['slug'], $step_data );

				if ( is_wp_error( $result ) ) {
					foreach ( $result->get_error_codes() as $code ) {
						$all_errors[ $code ] = $result->get_error_messages( $code );
					}
				}
			}

			if ( ! empty( $all_errors ) ) {
				wp_send_json_error( array(
					'message' => __( 'Some fields have errors. Please review and correct.', 'event-management-system' ),
					'errors'  => $all_errors,
				) );
			}

			/**
			 * Fires when a multi-step form is successfully submitted.
			 *
			 * Form-specific handlers should hook into this action using the form_id suffix:
			 *   add_action( 'ems_form_submitted_my_form_id', 'my_handler', 10, 2 );
			 *
			 * The handler receives the form instance and should call wp_send_json_success()
			 * or wp_send_json_error() to complete the response.
			 *
			 * @since 1.5.0
			 * @param EMS_Multi_Step_Form $form      The form instance with all collected data.
			 * @param array               $form_data Complete form data keyed by step slug.
			 */
			do_action( 'ems_form_submitted_' . $form_id, $form, $form->get_form_data() );

			// If no handler has sent a response yet, send a generic success
			$form->clear_state();

			wp_send_json_success( array(
				'message' => __( 'Form submitted successfully.', 'event-management-system' ),
			) );

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_form_submit error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred during submission. Please try again.', 'event-management-system' ) ) );
		}
	}

	/**
	 * Sanitize form step data based on field definitions.
	 *
	 * Extracts only fields defined in the step and applies appropriate sanitization.
	 *
	 * @since 1.5.0
	 * @param array $raw_data Raw POST data (parsed from serialised string).
	 * @param array $step     Step definition with 'fields' key.
	 * @return array Sanitized step data.
	 */
	private function sanitize_form_step_data( $raw_data, $step ) {
		$sanitized = array();

		if ( empty( $step['fields'] ) || ! is_array( $step['fields'] ) ) {
			return $sanitized;
		}

		foreach ( $step['fields'] as $field ) {
			$name = $field['name'];
			$type = isset( $field['type'] ) ? $field['type'] : 'text';

			if ( ! isset( $raw_data[ $name ] ) ) {
				// Handle checkboxes that are unchecked (not in POST)
				if ( 'checkbox' === $type ) {
					$sanitized[ $name ] = '';
				}
				continue;
			}

			$value = $raw_data[ $name ];

			switch ( $type ) {
				case 'email':
					$sanitized[ $name ] = sanitize_email( $value );
					break;

				case 'url':
					$sanitized[ $name ] = esc_url_raw( $value );
					break;

				case 'textarea':
					$sanitized[ $name ] = sanitize_textarea_field( $value );
					break;

				case 'number':
					$sanitized[ $name ] = is_numeric( $value ) ? $value : '';
					break;

				case 'multiselect':
					if ( is_array( $value ) ) {
						$sanitized[ $name ] = array_map( 'sanitize_text_field', $value );
					} else {
						$sanitized[ $name ] = sanitize_text_field( $value );
					}
					break;

				case 'checkbox':
					$sanitized[ $name ] = ( '1' === $value || 'yes' === $value ) ? '1' : '';
					break;

				case 'conditional_group':
					// Conditional groups don't have their own value
					break;

				default:
					$sanitized[ $name ] = sanitize_text_field( $value );
					break;
			}

			// Also recurse into conditional group child fields
			if ( 'conditional_group' === $type && ! empty( $field['fields'] ) ) {
				$child_step = array( 'fields' => $field['fields'] );
				$child_data = $this->sanitize_form_step_data( $raw_data, $child_step );
				$sanitized  = array_merge( $sanitized, $child_data );
			}
		}

		return $sanitized;
	}

	/**
	 * Load custom single template for the ems_sponsor post type.
	 *
	 * Uses the plugin's templates/single-ems_sponsor.php if the theme
	 * does not provide its own override.
	 *
	 * @since 1.5.0
	 * @param string $template The path to the current template.
	 * @return string Modified template path.
	 */
	public function sponsor_single_template( $template ) {
		global $post;

		if ( ! $post || 'ems_sponsor' !== $post->post_type ) {
			return $template;
		}

		// Allow theme override: look for single-ems_sponsor.php in theme.
		$theme_template = locate_template( 'single-ems_sponsor.php' );
		if ( $theme_template ) {
			return $theme_template;
		}

		$plugin_template = EMS_PLUGIN_DIR . 'templates/single-ems_sponsor.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}
