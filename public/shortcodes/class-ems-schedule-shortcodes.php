<?php
/**
 * Schedule Shortcodes
 *
 * Provides shortcodes for displaying event schedules and managing
 * session registrations on the frontend.
 *
 * @package    EventManagementSystem
 * @subpackage Public/Shortcodes
 * @since      1.2.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Schedule Shortcodes Class
 *
 * Registers and handles schedule-related shortcodes:
 * - [ems_schedule] - Display event schedule
 * - [ems_my_schedule] - Display participant's registered sessions
 * - [ems_session] - Display single session details
 *
 * @since 1.2.0
 */
class EMS_Schedule_Shortcodes {
	
	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Schedule display instance
	 *
	 * @var EMS_Schedule_Display
	 */
	private $schedule_display;

	/**
	 * Session registration instance
	 *
	 * @var EMS_Session_Registration
	 */
	private $session_registration;

	/**
	 * Constructor
	 *
	 * Registers shortcodes and initializes dependencies.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->logger               = EMS_Logger::instance();
		$this->schedule_display     = new EMS_Schedule_Display();
		$this->session_registration = new EMS_Session_Registration();
		
		// Register shortcodes
		add_shortcode( 'ems_schedule', array( $this, 'schedule_shortcode' ) );
		add_shortcode( 'ems_my_schedule', array( $this, 'my_schedule_shortcode' ) );
		add_shortcode( 'ems_session', array( $this, 'session_shortcode' ) );
		
		$this->logger->debug( 'EMS_Schedule_Shortcodes initialized', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Schedule shortcode
	 *
	 * Displays the event schedule with filtering and registration options.
	 *
	 * Usage: [ems_schedule event_id="123" view="list" group_by="date"]
	 *
	 * @since 1.2.0
	 * @param array $atts {
	 *     Shortcode attributes.
	 *
	 *     @type int    $event_id      Event ID (required).
	 *     @type string $view          View type: 'list', 'grid', 'timeline' (default: 'list').
	 *     @type string $group_by      Group by: 'date', 'track', 'location' (default: 'date').
	 *     @type bool   $show_filters  Show filters (default: true).
	 *     @type bool   $show_search   Show search (default: true).
	 *     @type string $track         Filter by track.
	 *     @type string $session_type  Filter by type.
	 *     @type string $location      Filter by location.
	 * }
	 * @return string Shortcode output.
	 */
	public function schedule_shortcode( $atts ) {
		try {
			$atts = shortcode_atts(
				array(
					'event_id'     => 0,
					'view'         => 'list',
					'group_by'     => 'date',
					'show_filters' => true,
					'show_search'  => true,
					'track'        => '',
					'session_type' => '',
					'location'     => '',
				),
				$atts,
				'ems_schedule'
			);

			// Validate event ID
			if ( empty( $atts['event_id'] ) ) {
				return '<p class="ems-error">' . esc_html__( 'Event ID is required.', 'event-management-system' ) . '</p>';
			}

			$event = get_post( absint( $atts['event_id'] ) );
			if ( ! $event || $event->post_type !== 'ems_event' ) {
				return '<p class="ems-error">' . esc_html__( 'Invalid event.', 'event-management-system' ) . '</p>';
			}

			// Convert string booleans
			$atts['show_filters'] = filter_var( $atts['show_filters'], FILTER_VALIDATE_BOOLEAN );
			$atts['show_search']  = filter_var( $atts['show_search'], FILTER_VALIDATE_BOOLEAN );

			// Enqueue styles and scripts
			$this->enqueue_schedule_assets();

			// Get schedule HTML
			$html = $this->schedule_display->get_schedule_html( absint( $atts['event_id'] ), $atts );

			return $html;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in schedule_shortcode: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<p class="ems-error">' . esc_html__( 'Error loading schedule.', 'event-management-system' ) . '</p>';
		}
	}

	/**
	 * My schedule shortcode
	 *
	 * Displays the current participant's registered sessions.
	 * Requires user to be logged in with active registration.
	 * If no event_id is provided, shows schedules for all registered events.
	 *
	 * Usage: [ems_my_schedule event_id="123"] or [ems_my_schedule]
	 *
	 * @since 1.2.0
	 * @param array $atts {
	 *     Shortcode attributes.
	 *
	 *     @type int $event_id Event ID (optional - shows all if not provided).
	 * }
	 * @return string Shortcode output.
	 */
	public function my_schedule_shortcode( $atts ) {
		global $wpdb;

		try {
			$atts = shortcode_atts(
				array(
					'event_id' => 0,
				),
				$atts,
				'ems_my_schedule'
			);

			// Check if user is logged in
			if ( ! is_user_logged_in() ) {
				return '<div class="ems-notice ems-notice-warning">' . 
				       sprintf(
					       __( 'Please <a href="%s">log in</a> to view your schedule.', 'event-management-system' ),
					       esc_url( wp_login_url( get_permalink() ) )
				       ) . '</div>';
			}

			$user = wp_get_current_user();

			// Enqueue assets
			$this->enqueue_schedule_assets();

			// If event_id provided, show schedule for that specific event
			if ( ! empty( $atts['event_id'] ) ) {
				return $this->render_single_event_schedule( $user, absint( $atts['event_id'] ) );
			}

			// Otherwise, show schedules for all registered events
			return $this->render_all_events_schedule( $user );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in my_schedule_shortcode: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<p class="ems-error">' . esc_html__( 'Error loading your schedule.', 'event-management-system' ) . '</p>';
		}
	}

	/**
	 * Render schedule for a single event
	 *
	 * @since 1.2.0
	 * @param WP_User $user Current user.
	 * @param int     $event_id Event ID.
	 * @return string HTML output.
	 */
	private function render_single_event_schedule( $user, $event_id ) {
		global $wpdb;

		// Get user's registration for this event
		$registration = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ems_registrations 
				WHERE event_id = %d AND (user_id = %d OR email = %s) AND status = 'active'
				ORDER BY registration_date DESC LIMIT 1",
				$event_id,
				$user->ID,
				$user->user_email
			)
		);

		if ( ! $registration ) {
			return '<p class="ems-notice">' . esc_html__( 'You are not registered for this event.', 'event-management-system' ) . '</p>';
		}

		// Get participant's schedule
		$html = $this->schedule_display->get_participant_schedule( $registration->id );

		// Add download link
		$download_url = add_query_arg(
			array(
				'ems_action'       => 'export_my_ical',
				'registration_id'  => $registration->id,
			),
			home_url()
		);

		$html .= '<div class="ems-my-schedule-actions">';
		$html .= '<a href="' . esc_url( $download_url ) . '" class="ems-btn ems-btn-secondary">';
		$html .= esc_html__( 'Download My Schedule', 'event-management-system' );
		$html .= '</a>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render schedules for all registered events
	 *
	 * @since 1.2.0
	 * @param WP_User $user Current user.
	 * @return string HTML output.
	 */
	private function render_all_events_schedule( $user ) {
		global $wpdb;

		// Get all user's active registrations for upcoming events
		$registrations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, e.post_title as event_title 
				FROM {$wpdb->prefix}ems_registrations r
				LEFT JOIN {$wpdb->posts} e ON r.event_id = e.ID
				LEFT JOIN {$wpdb->postmeta} pm ON e.ID = pm.post_id AND pm.meta_key = 'event_start_date'
				WHERE (r.user_id = %d OR r.email = %s) 
				AND r.status = 'active'
				AND e.post_status = 'publish'
				ORDER BY pm.meta_value ASC",
				$user->ID,
				$user->user_email
			)
		);

		if ( empty( $registrations ) ) {
			ob_start();
			?>
			<div class="ems-no-schedule">
				<p><?php esc_html_e( 'You have not registered for any events yet.', 'event-management-system' ); ?></p>
				<?php 
				$events_page_id = get_option( 'ems_page_events' );
				$events_url = $events_page_id ? get_permalink( $events_page_id ) : get_post_type_archive_link( 'ems_event' );
				?>
				<a href="<?php echo esc_url( $events_url ); ?>" class="ems-btn ems-btn-primary">
					<?php esc_html_e( 'Browse Events', 'event-management-system' ); ?>
				</a>
			</div>
			<?php
			return ob_get_clean();
		}

		ob_start();
		?>
		<div class="ems-all-schedules">
			<?php foreach ( $registrations as $registration ) : 
				$event_date = get_post_meta( $registration->event_id, 'event_start_date', true );
				$event_end_date = get_post_meta( $registration->event_id, 'event_end_date', true );
				$event_location = get_post_meta( $registration->event_id, 'event_location', true );
				
				// Check if event has sessions
				$has_sessions = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
						WHERE p.post_type = 'ems_session' 
						AND p.post_status = 'publish'
						AND pm.meta_key = 'event_id' 
						AND pm.meta_value = %d",
						$registration->event_id
					)
				);
				
				// Get user's registered sessions count
				$session_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}ems_session_registrations 
						WHERE registration_id = %d AND attendance_status != 'cancelled'",
						$registration->id
					)
				);
			?>
				<div class="ems-event-schedule-block">
					<div class="ems-event-schedule-header">
						<h3>
							<a href="<?php echo esc_url( get_permalink( $registration->event_id ) ); ?>">
								<?php echo esc_html( $registration->event_title ?: __( 'Unknown Event', 'event-management-system' ) ); ?>
							</a>
						</h3>
						<div class="ems-event-meta-info">
							<?php if ( $event_date ) : ?>
								<span class="ems-event-date">
									<span class="dashicons dashicons-calendar-alt"></span>
									<?php 
									echo esc_html( EMS_Date_Helper::format_date( $event_date ) );
									if ( $event_end_date && $event_end_date !== $event_date ) {
										echo ' - ' . esc_html( EMS_Date_Helper::format_date( $event_end_date ) );
									}
									?>
								</span>
							<?php endif; ?>
							<?php if ( $event_location ) : ?>
								<span class="ems-event-location">
									<span class="dashicons dashicons-location"></span>
									<?php echo esc_html( $event_location ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
					
					<div class="ems-event-schedule-body">
						<?php if ( $has_sessions > 0 ) : ?>
							<?php if ( $session_count > 0 ) : ?>
								<?php echo $this->schedule_display->get_participant_schedule( $registration->id ); ?>
								
								<div class="ems-schedule-actions">
									<?php
									$download_url = add_query_arg(
										array(
											'ems_action'       => 'export_my_ical',
											'registration_id'  => $registration->id,
										),
										home_url()
									);
									?>
									<a href="<?php echo esc_url( $download_url ); ?>" class="ems-btn ems-btn-small ems-btn-secondary">
										<span class="dashicons dashicons-download"></span>
										<?php esc_html_e( 'Download iCal', 'event-management-system' ); ?>
									</a>
									<a href="<?php echo esc_url( get_permalink( $registration->event_id ) ); ?>" class="ems-btn ems-btn-small">
										<?php esc_html_e( 'View Full Schedule', 'event-management-system' ); ?>
									</a>
								</div>
							<?php else : ?>
								<p class="ems-no-sessions-registered">
									<?php esc_html_e( 'You haven\'t registered for any sessions yet.', 'event-management-system' ); ?>
								</p>
								<a href="<?php echo esc_url( get_permalink( $registration->event_id ) ); ?>" class="ems-btn ems-btn-small ems-btn-primary">
									<?php esc_html_e( 'Browse Sessions', 'event-management-system' ); ?>
								</a>
							<?php endif; ?>
						<?php else : ?>
							<p class="ems-no-sessions">
								<?php esc_html_e( 'This event does not have a session schedule.', 'event-management-system' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<style>
		.ems-all-schedules { display: flex; flex-direction: column; gap: 30px; }
		.ems-event-schedule-block { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
		.ems-event-schedule-header { background: #f5f5f5; padding: 20px; border-bottom: 1px solid #e0e0e0; }
		.ems-event-schedule-header h3 { margin: 0 0 10px; font-size: 1.25em; }
		.ems-event-schedule-header h3 a { color: #1d2327; text-decoration: none; }
		.ems-event-schedule-header h3 a:hover { color: #2271b1; }
		.ems-event-meta-info { display: flex; flex-wrap: wrap; gap: 15px; font-size: 14px; color: #666; }
		.ems-event-meta-info .dashicons { font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 4px; }
		.ems-event-schedule-body { padding: 20px; }
		.ems-schedule-actions { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
		.ems-no-sessions-registered, .ems-no-sessions { color: #666; font-style: italic; margin: 0 0 15px; }
		.ems-no-schedule { text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px; }
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Session shortcode
	 *
	 * Displays detailed information about a single session.
	 *
	 * Usage: [ems_session id="123"]
	 *
	 * @since 1.2.0
	 * @param array $atts {
	 *     Shortcode attributes.
	 *
	 *     @type int  $id           Session ID (required).
	 *     @type bool $show_register Show register button (default: true).
	 * }
	 * @return string Shortcode output.
	 */
	public function session_shortcode( $atts ) {
		try {
			$atts = shortcode_atts(
				array(
					'id'            => 0,
					'show_register' => true,
				),
				$atts,
				'ems_session'
			);

			// Validate session ID
			if ( empty( $atts['id'] ) ) {
				return '<p class="ems-error">' . esc_html__( 'Session ID is required.', 'event-management-system' ) . '</p>';
			}

			$session = get_post( absint( $atts['id'] ) );
			if ( ! $session || $session->post_type !== 'ems_session' ) {
				return '<p class="ems-error">' . esc_html__( 'Invalid session.', 'event-management-system' ) . '</p>';
			}

			// Get session data
			$schedule_builder = new EMS_Schedule_Builder();
			$session_data     = $schedule_builder->get_session( absint( $atts['id'] ) );

			if ( ! $session_data ) {
				return '<p class="ems-error">' . esc_html__( 'Session not found.', 'event-management-system' ) . '</p>';
			}

			// Enqueue assets
			$this->enqueue_schedule_assets();

			// Build output
			ob_start();
			?>
			<div class="ems-session-detail" data-session-id="<?php echo esc_attr( $session_data['id'] ); ?>">
				
				<h2 class="ems-session-title"><?php echo esc_html( $session_data['title'] ); ?></h2>

				<div class="ems-session-meta-detail">
					<div class="ems-meta-item">
						<span class="ems-meta-label"><?php esc_html_e( 'Date & Time:', 'event-management-system' ); ?></span>
						<span class="ems-meta-value">
							<?php
							echo esc_html(
								EMS_Date_Helper::format_date( $session_data['start_datetime'] ) . ' ' .
								EMS_Date_Helper::format_time( $session_data['start_datetime'] ) . ' - ' .
								EMS_Date_Helper::format_time( $session_data['end_datetime'] )
							);
							?>
						</span>
					</div>

					<div class="ems-meta-item">
						<span class="ems-meta-label"><?php esc_html_e( 'Location:', 'event-management-system' ); ?></span>
						<span class="ems-meta-value"><?php echo esc_html( $session_data['location'] ); ?></span>
					</div>

					<?php if ( ! empty( $session_data['track'] ) ) : ?>
						<div class="ems-meta-item">
							<span class="ems-meta-label"><?php esc_html_e( 'Track:', 'event-management-system' ); ?></span>
							<span class="ems-meta-value"><?php echo esc_html( $session_data['track'] ); ?></span>
						</div>
					<?php endif; ?>

					<div class="ems-meta-item">
						<span class="ems-meta-label"><?php esc_html_e( 'Type:', 'event-management-system' ); ?></span>
						<span class="ems-meta-value">
							<?php
							$types = $schedule_builder->get_session_types();
							echo esc_html( isset( $types[ $session_data['session_type'] ] ) ? $types[ $session_data['session_type'] ] : ucfirst( $session_data['session_type'] ) );
							?>
						</span>
					</div>

					<div class="ems-meta-item">
						<span class="ems-meta-label"><?php esc_html_e( 'Capacity:', 'event-management-system' ); ?></span>
						<span class="ems-meta-value">
							<?php
							if ( $session_data['capacity'] > 0 ) {
								echo esc_html( $session_data['current_registrations'] . ' / ' . $session_data['capacity'] );
							} else {
								echo esc_html__( 'Unlimited', 'event-management-system' );
							}
							?>
						</span>
					</div>
				</div>

				<?php if ( ! empty( $session_data['description'] ) ) : ?>
					<div class="ems-session-description-detail">
						<h3><?php esc_html_e( 'Description', 'event-management-system' ); ?></h3>
						<?php echo wp_kses_post( wpautop( $session_data['description'] ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( filter_var( $atts['show_register'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
					<div class="ems-session-actions">
						<?php
						// Check if session is full
						$is_full = $session_data['capacity'] > 0 && $session_data['current_registrations'] >= $session_data['capacity'];
						
						if ( $is_full ) :
							?>
							<button class="ems-btn ems-btn-secondary" disabled>
								<?php esc_html_e( 'Session Full', 'event-management-system' ); ?>
							</button>
						<?php else : ?>
							<button class="ems-btn ems-btn-primary ems-register-session" data-session-id="<?php echo esc_attr( $session_data['id'] ); ?>">
								<?php esc_html_e( 'Register for This Session', 'event-management-system' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>
			<?php
			return ob_get_clean();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in session_shortcode: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<p class="ems-error">' . esc_html__( 'Error loading session.', 'event-management-system' ) . '</p>';
		}
	}

	/**
	 * Enqueue schedule assets
	 *
	 * Enqueues CSS and JavaScript for schedule display.
	 *
	 * @since 1.2.0
	 */
	private function enqueue_schedule_assets() {
		// Enqueue styles
		wp_enqueue_style(
			'ems-schedule',
			plugin_dir_url( dirname( __FILE__, 2 ) ) . 'public/css/ems-schedule.css',
			array(),
			EMS_VERSION
		);

		// Enqueue scripts
		wp_enqueue_script(
			'ems-schedule',
			plugin_dir_url( dirname( __FILE__, 2 ) ) . 'public/js/ems-schedule.js',
			array( 'jquery' ),
			EMS_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'ems-schedule',
			'emsSchedule',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ems_schedule_nonce' ),
				'strings' => array(
					'registerSuccess'  => __( 'Successfully registered for session', 'event-management-system' ),
					'registerError'    => __( 'Failed to register for session', 'event-management-system' ),
					'cancelSuccess'    => __( 'Session registration cancelled', 'event-management-system' ),
					'cancelError'      => __( 'Failed to cancel registration', 'event-management-system' ),
					'confirmCancel'    => __( 'Are you sure you want to cancel this session registration?', 'event-management-system' ),
					'loading'          => __( 'Loading...', 'event-management-system' ),
					'conflict'         => __( 'This session conflicts with another session you are registered for', 'event-management-system' ),
					'loginRequired'    => __( 'Please log in to register for sessions', 'event-management-system' ),
				),
			)
		);
	}
}