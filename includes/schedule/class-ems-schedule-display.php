<?php
/**
 * Schedule Display
 *
 * Handles public schedule display, filtering, search, and iCal export.
 * Provides comprehensive tools for viewing and interacting with event schedules.
 *
 * @package    EventManagementSystem
 * @subpackage Schedule
 * @since      1.2.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Schedule Display Class
 *
 * Manages public-facing schedule display including:
 * - Schedule rendering (list, grid, calendar views)
 * - Filtering by track, type, date, location
 * - Search functionality
 * - iCal/ICS export
 * - Participant's personal schedule
 *
 * @since 1.2.0
 */
class EMS_Schedule_Display {
	
	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Schedule builder instance
	 *
	 * @var EMS_Schedule_Builder
	 */
	private $schedule_builder;

	/**
	 * Constructor
	 *
	 * Initializes dependencies for schedule display.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->logger           = EMS_Logger::instance();
		$this->schedule_builder = new EMS_Schedule_Builder();
		
		$this->logger->debug( 'EMS_Schedule_Display initialized', EMS_Logger::CONTEXT_GENERAL );
	}

	// ==========================================
	// SCHEDULE RENDERING
	// ==========================================

	/**
	 * Get event schedule HTML
	 *
	 * Generates complete schedule HTML for an event with filtering options.
	 *
	 * @since 1.2.0
	 * @param int   $event_id Event ID.
	 * @param array $args {
	 *     Display options.
	 *
	 *     @type string $view         View type: 'list', 'grid', 'timeline' (default: 'list').
	 *     @type string $group_by     Group sessions by: 'date', 'track', 'location' (default: 'date').
	 *     @type bool   $show_filters Show filter controls (default: true).
	 *     @type bool   $show_search  Show search box (default: true).
	 *     @type string $date         Filter by specific date.
	 *     @type string $track        Filter by track.
	 *     @type string $session_type Filter by type.
	 *     @type string $location     Filter by location.
	 * }
	 * @return string HTML output.
	 */
	public function get_schedule_html( $event_id, $args = array() ) {
		try {
			// Default arguments
			$defaults = array(
				'view'         => 'list',
				'group_by'     => 'date',
				'show_filters' => true,
				'show_search'  => true,
				'date'         => '',
				'track'        => '',
				'session_type' => '',
				'location'     => '',
			);

			$args = wp_parse_args( $args, $defaults );

			// Get sessions
			$result = $this->schedule_builder->get_event_sessions( $event_id, $args );

			if ( ! $result['success'] || empty( $result['sessions'] ) ) {
				return '<p class="ems-no-sessions">' . esc_html__( 'No sessions found.', 'event-management-system' ) . '</p>';
			}

			$sessions = $result['sessions'];

			// Build HTML
			ob_start();
			?>
			<div class="ems-schedule-wrapper" data-event-id="<?php echo esc_attr( $event_id ); ?>">
				
				<?php if ( $args['show_filters'] ) : ?>
					<?php echo $this->render_filters( $event_id, $args ); ?>
				<?php endif; ?>

				<?php if ( $args['show_search'] ) : ?>
					<?php echo $this->render_search_box(); ?>
				<?php endif; ?>

				<div class="ems-schedule-content">
					<?php
					switch ( $args['view'] ) {
						case 'grid':
							echo $this->render_grid_view( $sessions, $args['group_by'] );
							break;
						case 'timeline':
							echo $this->render_timeline_view( $sessions );
							break;
						default:
							echo $this->render_list_view( $sessions, $args['group_by'] );
					}
					?>
				</div>

				<div class="ems-schedule-actions">
					<a href="<?php echo esc_url( $this->get_ical_export_url( $event_id ) ); ?>" class="ems-btn ems-btn-secondary">
						<?php esc_html_e( 'Download iCal', 'event-management-system' ); ?>
					</a>
				</div>

			</div>
			<?php
			return ob_get_clean();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_schedule_html: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<p class="ems-error">' . esc_html__( 'Error loading schedule.', 'event-management-system' ) . '</p>';
		}
	}

	/**
	 * Render list view
	 *
	 * Renders sessions in a list format grouped by specified field.
	 *
	 * @since 1.2.0
	 * @param array  $sessions  Sessions array.
	 * @param string $group_by  Group by field.
	 * @return string HTML output.
	 */
	private function render_list_view( $sessions, $group_by = 'date' ) {
		$grouped = $this->group_sessions( $sessions, $group_by );

		ob_start();
		?>
		<div class="ems-schedule-list">
			<?php foreach ( $grouped as $group_key => $group_sessions ) : ?>
				<div class="ems-schedule-group">
					<h3 class="ems-schedule-group-title">
						<?php echo esc_html( $this->format_group_title( $group_key, $group_by ) ); ?>
					</h3>
					
					<div class="ems-sessions-list">
						<?php foreach ( $group_sessions as $session ) : ?>
							<?php echo $this->render_session_card( $session ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render grid view
	 *
	 * Renders sessions in a grid format.
	 *
	 * @since 1.2.0
	 * @param array  $sessions Sessions array.
	 * @param string $group_by Group by field.
	 * @return string HTML output.
	 */
	private function render_grid_view( $sessions, $group_by = 'date' ) {
		$grouped = $this->group_sessions( $sessions, $group_by );

		ob_start();
		?>
		<div class="ems-schedule-grid">
			<?php foreach ( $grouped as $group_key => $group_sessions ) : ?>
				<div class="ems-schedule-group">
					<h3 class="ems-schedule-group-title">
						<?php echo esc_html( $this->format_group_title( $group_key, $group_by ) ); ?>
					</h3>
					
					<div class="ems-sessions-grid">
						<?php foreach ( $group_sessions as $session ) : ?>
							<?php echo $this->render_session_card( $session, 'grid' ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render timeline view
	 *
	 * Renders sessions in a timeline format showing concurrent sessions.
	 *
	 * @since 1.2.0
	 * @param array $sessions Sessions array.
	 * @return string HTML output.
	 */
	private function render_timeline_view( $sessions ) {
		// Group by date first
		$by_date = $this->group_sessions( $sessions, 'date' );

		ob_start();
		?>
		<div class="ems-schedule-timeline">
			<?php foreach ( $by_date as $date => $date_sessions ) : ?>
				<div class="ems-timeline-day">
					<h3 class="ems-timeline-date">
						<?php echo esc_html( date_i18n( 'l, F j, Y', strtotime( $date ) ) ); ?>
					</h3>
					
					<div class="ems-timeline-sessions">
						<?php
						// Sort by start time
						usort( $date_sessions, function( $a, $b ) {
							return strtotime( $a['start_datetime'] ) - strtotime( $b['start_datetime'] );
						});

						foreach ( $date_sessions as $session ) :
							$start_time = date( 'H:i', strtotime( $session['start_datetime'] ) );
							?>
							<div class="ems-timeline-slot" data-time="<?php echo esc_attr( $start_time ); ?>">
								<div class="ems-timeline-time">
									<?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $session['start_datetime'] ) ) ); ?>
								</div>
								<div class="ems-timeline-content">
									<?php echo $this->render_session_card( $session, 'timeline' ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render session card
	 *
	 * Renders an individual session card.
	 *
	 * @since 1.2.0
	 * @param array  $session Session data.
	 * @param string $variant Display variant: 'list', 'grid', 'timeline'.
	 * @return string HTML output.
	 */
	private function render_session_card( $session, $variant = 'list' ) {
		$capacity_info  = $this->get_capacity_info( $session );
		$session_types  = $this->schedule_builder->get_session_types();
		$session_type   = isset( $session_types[ $session['session_type'] ] ) ? $session_types[ $session['session_type'] ] : ucfirst( $session['session_type'] );

		ob_start();
		?>
		<div class="ems-session-card ems-session-<?php echo esc_attr( $variant ); ?> ems-session-type-<?php echo esc_attr( $session['session_type'] ); ?>" data-session-id="<?php echo esc_attr( $session['id'] ); ?>">
			
			<div class="ems-session-header">
				<h4 class="ems-session-title"><?php echo esc_html( $session['title'] ); ?></h4>
				<span class="ems-session-type"><?php echo esc_html( $session_type ); ?></span>
			</div>

			<div class="ems-session-meta">
				<div class="ems-session-time">
					<span class="dashicons dashicons-clock"></span>
					<?php
					echo esc_html(
						date_i18n( get_option( 'time_format' ), strtotime( $session['start_datetime'] ) ) . ' - ' .
						date_i18n( get_option( 'time_format' ), strtotime( $session['end_datetime'] ) )
					);
					?>
				</div>
				
				<div class="ems-session-location">
					<span class="dashicons dashicons-location"></span>
					<?php echo esc_html( $session['location'] ); ?>
				</div>

				<?php if ( ! empty( $session['track'] ) ) : ?>
					<div class="ems-session-track">
						<span class="dashicons dashicons-tag"></span>
						<?php echo esc_html( $session['track'] ); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $session['description'] ) ) : ?>
				<div class="ems-session-description">
					<?php echo wp_kses_post( wp_trim_words( $session['description'], 30 ) ); ?>
				</div>
			<?php endif; ?>

			<div class="ems-session-footer">
				<div class="ems-session-capacity <?php echo esc_attr( $capacity_info['class'] ); ?>">
					<?php echo esc_html( $capacity_info['text'] ); ?>
				</div>

				<?php if ( $capacity_info['can_register'] ) : ?>
					<button class="ems-btn ems-btn-primary ems-register-session" data-session-id="<?php echo esc_attr( $session['id'] ); ?>">
						<?php esc_html_e( 'Register', 'event-management-system' ); ?>
					</button>
				<?php elseif ( $capacity_info['is_full'] ) : ?>
					<button class="ems-btn ems-btn-secondary" disabled>
						<?php esc_html_e( 'Full', 'event-management-system' ); ?>
					</button>
				<?php endif; ?>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render filters
	 *
	 * Renders filter controls for schedule.
	 *
	 * @since 1.2.0
	 * @param int   $event_id Event ID.
	 * @param array $args     Current filter values.
	 * @return string HTML output.
	 */
	private function render_filters( $event_id, $args ) {
		$tracks        = $this->schedule_builder->get_event_tracks( $event_id );
		$locations     = $this->schedule_builder->get_event_locations( $event_id );
		$session_types = $this->schedule_builder->get_session_types();

		ob_start();
		?>
		<div class="ems-schedule-filters">
			
			<select name="track" class="ems-filter" data-filter="track">
				<option value=""><?php esc_html_e( 'All Tracks', 'event-management-system' ); ?></option>
				<?php foreach ( $tracks as $track ) : ?>
					<option value="<?php echo esc_attr( $track ); ?>" <?php selected( $args['track'], $track ); ?>>
						<?php echo esc_html( $track ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="session_type" class="ems-filter" data-filter="session_type">
				<option value=""><?php esc_html_e( 'All Types', 'event-management-system' ); ?></option>
				<?php foreach ( $session_types as $type => $label ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $args['session_type'], $type ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="location" class="ems-filter" data-filter="location">
				<option value=""><?php esc_html_e( 'All Locations', 'event-management-system' ); ?></option>
				<?php foreach ( $locations as $location ) : ?>
					<option value="<?php echo esc_attr( $location ); ?>" <?php selected( $args['location'], $location ); ?>>
						<?php echo esc_html( $location ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="button" class="ems-btn ems-btn-secondary ems-clear-filters">
				<?php esc_html_e( 'Clear Filters', 'event-management-system' ); ?>
			</button>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render search box
	 *
	 * Renders search input for filtering sessions.
	 *
	 * @since 1.2.0
	 * @return string HTML output.
	 */
	private function render_search_box() {
		ob_start();
		?>
		<div class="ems-schedule-search">
			<input type="text" 
			       class="ems-search-input" 
			       placeholder="<?php esc_attr_e( 'Search sessions...', 'event-management-system' ); ?>"
			       aria-label="<?php esc_attr_e( 'Search sessions', 'event-management-system' ); ?>">
			<span class="dashicons dashicons-search"></span>
		</div>
		<?php
		return ob_get_clean();
	}

	// ==========================================
	// UTILITY METHODS
	// ==========================================

	/**
	 * Group sessions
	 *
	 * Groups sessions by specified field.
	 *
	 * @since 1.2.0
	 * @param array  $sessions Sessions array.
	 * @param string $group_by Group by field.
	 * @return array Grouped sessions.
	 */
	private function group_sessions( $sessions, $group_by ) {
		$grouped = array();

		foreach ( $sessions as $session ) {
			switch ( $group_by ) {
				case 'date':
					$key = date( 'Y-m-d', strtotime( $session['start_datetime'] ) );
					break;
				case 'track':
					$key = ! empty( $session['track'] ) ? $session['track'] : __( 'No Track', 'event-management-system' );
					break;
				case 'location':
					$key = $session['location'];
					break;
				default:
					$key = 'all';
			}

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}

			$grouped[ $key ][] = $session;
		}

		// Sort groups
		ksort( $grouped );

		return $grouped;
	}

	/**
	 * Format group title
	 *
	 * Formats the group title based on grouping method.
	 *
	 * @since 1.2.0
	 * @param string $group_key Group key.
	 * @param string $group_by  Group by field.
	 * @return string Formatted title.
	 */
	private function format_group_title( $group_key, $group_by ) {
		switch ( $group_by ) {
			case 'date':
				return date_i18n( 'l, F j, Y', strtotime( $group_key ) );
			case 'track':
			case 'location':
			default:
				return $group_key;
		}
	}

	/**
	 * Get capacity info
	 *
	 * Gets capacity information and registration status for a session.
	 *
	 * @since 1.2.0
	 * @param array $session Session data.
	 * @return array Capacity information.
	 */
	private function get_capacity_info( $session ) {
		$capacity   = absint( $session['capacity'] );
		$registered = absint( $session['current_registrations'] );

		if ( $capacity === 0 ) {
			return array(
				'text'         => sprintf( __( '%d registered', 'event-management-system' ), $registered ),
				'class'        => 'capacity-unlimited',
				'can_register' => true,
				'is_full'      => false,
			);
		}

		$remaining  = $capacity - $registered;
		$percentage = ( $registered / $capacity ) * 100;

		if ( $remaining <= 0 ) {
			return array(
				'text'         => __( 'Full', 'event-management-system' ),
				'class'        => 'capacity-full',
				'can_register' => false,
				'is_full'      => true,
			);
		}

		if ( $percentage >= 90 ) {
			$class = 'capacity-almost-full';
			$text  = sprintf( __( '%d spots left', 'event-management-system' ), $remaining );
		} elseif ( $percentage >= 75 ) {
			$class = 'capacity-filling';
			$text  = sprintf( __( '%d spots left', 'event-management-system' ), $remaining );
		} else {
			$class = 'capacity-available';
			$text  = sprintf( __( '%d / %d', 'event-management-system' ), $registered, $capacity );
		}

		return array(
			'text'         => $text,
			'class'        => $class,
			'can_register' => true,
			'is_full'      => false,
		);
	}

	// ==========================================
	// ICAL EXPORT
	// ==========================================

	/**
	 * Get iCal export URL
	 *
	 * Returns URL for downloading event schedule as iCal file.
	 *
	 * @since 1.2.0
	 * @param int $event_id Event ID.
	 * @return string Export URL.
	 */
	public function get_ical_export_url( $event_id ) {
		return add_query_arg(
			array(
				'ems_action' => 'export_ical',
				'event_id'   => $event_id,
			),
			home_url()
		);
	}

	/**
	 * Generate iCal file
	 *
	 * Generates and outputs an iCal file for event sessions.
	 *
	 * @since 1.2.0
	 * @param int $event_id Event ID.
	 */
	public function generate_ical( $event_id ) {
		try {
			$event = get_post( $event_id );
			if ( ! $event ) {
				return;
			}

			// Get all sessions
			$result = $this->schedule_builder->get_event_sessions( $event_id );
			if ( ! $result['success'] || empty( $result['sessions'] ) ) {
				return;
			}

			$sessions = $result['sessions'];

			// Build iCal content
			$ical = "BEGIN:VCALENDAR\r\n";
			$ical .= "VERSION:2.0\r\n";
			$ical .= "PRODID:-//Event Management System//EN\r\n";
			$ical .= "CALSCALE:GREGORIAN\r\n";
			$ical .= "METHOD:PUBLISH\r\n";
			$ical .= "X-WR-CALNAME:" . $this->ical_escape( $event->post_title ) . "\r\n";
			$ical .= "X-WR-TIMEZONE:UTC\r\n";

			foreach ( $sessions as $session ) {
				$ical .= "BEGIN:VEVENT\r\n";
				$ical .= "UID:session-" . $session['id'] . "@" . parse_url( home_url(), PHP_URL_HOST ) . "\r\n";
				$ical .= "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
				$ical .= "DTSTART:" . gmdate( 'Ymd\THis\Z', strtotime( $session['start_datetime'] ) ) . "\r\n";
				$ical .= "DTEND:" . gmdate( 'Ymd\THis\Z', strtotime( $session['end_datetime'] ) ) . "\r\n";
				$ical .= "SUMMARY:" . $this->ical_escape( $session['title'] ) . "\r\n";
				
				if ( ! empty( $session['description'] ) ) {
					$ical .= "DESCRIPTION:" . $this->ical_escape( wp_strip_all_tags( $session['description'] ) ) . "\r\n";
				}
				
				$ical .= "LOCATION:" . $this->ical_escape( $session['location'] ) . "\r\n";
				$ical .= "STATUS:CONFIRMED\r\n";
				$ical .= "END:VEVENT\r\n";
			}

			$ical .= "END:VCALENDAR\r\n";

			// Output headers and content
			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $event->post_title ) . '-schedule.ics"' );
			header( 'Content-Length: ' . strlen( $ical ) );
			header( 'Cache-Control: no-cache' );

			echo $ical;
			exit;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in generate_ical: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
		}
	}

	/**
	 * Escape text for iCal
	 *
	 * Escapes special characters for iCal format.
	 *
	 * @since 1.2.0
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	private function ical_escape( $text ) {
		$text = str_replace( array( "\\", ",", ";", "\n" ), array( "\\\\", "\\,", "\\;", "\\n" ), $text );
		return $text;
	}

	// ==========================================
	// PARTICIPANT SCHEDULE
	// ==========================================

	/**
	 * Get participant schedule
	 *
	 * Gets all sessions a participant is registered for.
	 *
	 * @since 1.2.0
	 * @param int $registration_id Event registration ID.
	 * @return string HTML output of participant's schedule.
	 */
	public function get_participant_schedule( $registration_id ) {
		try {
			$session_reg = new EMS_Session_Registration();
			$sessions    = $session_reg->get_participant_sessions( $registration_id );

			if ( empty( $sessions ) ) {
				return '<p class="ems-no-sessions">' . esc_html__( 'You have not registered for any sessions yet.', 'event-management-system' ) . '</p>';
			}

			ob_start();
			?>
			<div class="ems-participant-schedule">
				<h3><?php esc_html_e( 'Your Schedule', 'event-management-system' ); ?></h3>
				
				<div class="ems-participant-sessions">
					<?php foreach ( $sessions as $session ) : ?>
						<div class="ems-participant-session" data-session-id="<?php echo esc_attr( $session['id'] ); ?>">
							<div class="ems-session-info">
								<h4><?php echo esc_html( $session['title'] ); ?></h4>
								<div class="ems-session-details">
									<span class="ems-session-time">
										<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $session['start_datetime'] ) ) ); ?>
									</span>
									<span class="ems-session-location"><?php echo esc_html( $session['location'] ); ?></span>
								</div>
							</div>
							<button class="ems-btn ems-btn-danger ems-cancel-session" data-session-id="<?php echo esc_attr( $session['id'] ); ?>">
								<?php esc_html_e( 'Cancel', 'event-management-system' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
			return ob_get_clean();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_participant_schedule: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return '<p class="ems-error">' . esc_html__( 'Error loading your schedule.', 'event-management-system' ) . '</p>';
		}
	}

	/**
	 * Generate personal iCal for a participant
	 *
	 * Creates an iCal file containing only the sessions the participant has registered for.
	 *
	 * @since 1.2.0
	 * @param array  $session_ids  Array of session IDs the user is registered for.
	 * @param object $registration Registration data object.
	 */
	public function generate_personal_ical( $session_ids, $registration ) {
		try {
			if ( empty( $session_ids ) ) {
				return;
			}

			$sessions = array();
			foreach ( $session_ids as $session_id ) {
				$session = get_post( $session_id );
				if ( $session ) {
					$sessions[] = array(
						'id'             => $session->ID,
						'title'          => $session->post_title,
						'description'    => $session->post_content,
						'start_datetime' => get_post_meta( $session_id, 'start_datetime', true ),
						'end_datetime'   => get_post_meta( $session_id, 'end_datetime', true ),
						'location'       => get_post_meta( $session_id, 'location', true ),
					);
				}
			}

			if ( empty( $sessions ) ) {
				return;
			}

			// Get event name for calendar title
			$event_id   = get_post_meta( $sessions[0]['id'], 'event_id', true );
			$event      = get_post( $event_id );
			$event_name = $event ? $event->post_title : __( 'My Event Schedule', 'event-management-system' );

			// Build iCal content
			$ical = "BEGIN:VCALENDAR\r\n";
			$ical .= "VERSION:2.0\r\n";
			$ical .= "PRODID:-//Event Management System//EN\r\n";
			$ical .= "CALSCALE:GREGORIAN\r\n";
			$ical .= "METHOD:PUBLISH\r\n";
			$ical .= "X-WR-CALNAME:" . $this->ical_escape( $event_name . ' - ' . $registration->first_name . "'s Schedule" ) . "\r\n";
			$ical .= "X-WR-TIMEZONE:UTC\r\n";

			foreach ( $sessions as $session ) {
				$ical .= "BEGIN:VEVENT\r\n";
				$ical .= "UID:session-" . $session['id'] . "-reg-" . $registration->id . "@" . parse_url( home_url(), PHP_URL_HOST ) . "\r\n";
				$ical .= "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
				$ical .= "DTSTART:" . gmdate( 'Ymd\THis\Z', strtotime( $session['start_datetime'] ) ) . "\r\n";
				$ical .= "DTEND:" . gmdate( 'Ymd\THis\Z', strtotime( $session['end_datetime'] ) ) . "\r\n";
				$ical .= "SUMMARY:" . $this->ical_escape( $session['title'] ) . "\r\n";
				
				if ( ! empty( $session['description'] ) ) {
					$ical .= "DESCRIPTION:" . $this->ical_escape( wp_strip_all_tags( $session['description'] ) ) . "\r\n";
				}
				
				$ical .= "LOCATION:" . $this->ical_escape( $session['location'] ) . "\r\n";
				$ical .= "STATUS:CONFIRMED\r\n";
				
				// Add alarm 15 minutes before
				$ical .= "BEGIN:VALARM\r\n";
				$ical .= "TRIGGER:-PT15M\r\n";
				$ical .= "ACTION:DISPLAY\r\n";
				$ical .= "DESCRIPTION:" . $this->ical_escape( __( 'Session starting soon: ', 'event-management-system' ) . $session['title'] ) . "\r\n";
				$ical .= "END:VALARM\r\n";
				
				$ical .= "END:VEVENT\r\n";
			}

			$ical .= "END:VCALENDAR\r\n";

			// Output headers and content
			$filename = sanitize_file_name( $event_name . '-' . $registration->first_name . '-schedule' );
			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.ics"' );
			header( 'Content-Length: ' . strlen( $ical ) );
			header( 'Cache-Control: no-cache' );

			echo $ical;
			exit;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in generate_personal_ical: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
		}
	}
}
