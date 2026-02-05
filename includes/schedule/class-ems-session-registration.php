<?php
/**
 * Session Registration
 *
 * Handles participant registration for individual sessions including
 * capacity management, conflict detection, and waitlist integration.
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
 * Session Registration Class
 *
 * Manages all aspects of session registration including:
 * - Individual session registration
 * - Capacity checking and enforcement
 * - Registration conflicts (time overlaps)
 * - Waitlist management for full sessions
 * - Registration cancellation
 *
 * @since 1.2.0
 */
class EMS_Session_Registration {
	
	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Validator instance
	 *
	 * @var EMS_Validator
	 */
	private $validator;

	/**
	 * Security instance
	 *
	 * @var EMS_Security
	 */
	private $security;

	/**
	 * Waitlist handler
	 *
	 * @var EMS_Waitlist
	 */
	private $waitlist;

	/**
	 * Constructor
	 *
	 * Initializes dependencies for session registration management.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->logger    = EMS_Logger::instance();
		$this->validator = new EMS_Validator();
		$this->security  = new EMS_Security();
		$this->waitlist  = new EMS_Waitlist();
		
		$this->logger->debug( 'EMS_Session_Registration initialized', EMS_Logger::CONTEXT_GENERAL );
	}

	// ==========================================
	// SESSION REGISTRATION
	// ==========================================

	/**
	 * Register for session
	 *
	 * Registers a participant for a specific session with capacity
	 * and conflict checking.
	 *
	 * @since 1.2.0
	 * @param int $session_id      Session ID.
	 * @param int $registration_id Event registration ID.
	 * @return array {
	 *     @type bool   $success Success status.
	 *     @type string $message Result message.
	 *     @type int    $id      Session registration ID (if successful).
	 * }
	 */
	public function register_for_session( $session_id, $registration_id ) {
		global $wpdb;
		
		try {
			// Validate session exists
			$session = get_post( $session_id );
			if ( ! $session || $session->post_type !== 'ems_session' ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid session ID', 'event-management-system' ),
				);
			}

			// Validate event registration exists
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d AND status = 'active'",
					$registration_id
				)
			);

			if ( ! $registration ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid or inactive event registration', 'event-management-system' ),
				);
			}

			// Check event match
			$session_event_id = get_post_meta( $session_id, 'event_id', true );
			if ( $session_event_id != $registration->event_id ) {
				return array(
					'success' => false,
					'message' => __( 'Session does not belong to your registered event', 'event-management-system' ),
				);
			}

			// Check if already registered
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}ems_session_registrations 
					WHERE session_id = %d AND registration_id = %d",
					$session_id,
					$registration_id
				)
			);

			if ( $existing ) {
				return array(
					'success' => false,
					'message' => __( 'Already registered for this session', 'event-management-system' ),
				);
			}

			// Check for time conflicts
			$conflict_check = $this->check_registration_conflicts( $registration_id, $session_id );
			if ( $conflict_check['has_conflict'] ) {
				$this->logger->warning(
					"Session registration blocked due to conflict for registration {$registration_id}",
					EMS_Logger::CONTEXT_GENERAL,
					$conflict_check
				);
				return array(
					'success' => false,
					'message' => $conflict_check['message'],
				);
			}

			// Check capacity
			$capacity_check = $this->check_session_capacity( $session_id );
			if ( ! $capacity_check['available'] ) {
				// Add to waitlist if capacity full
				$waitlist_result = $this->waitlist->add_to_waitlist(
					$registration->event_id,
					$registration->email,
					$registration->first_name,
					$registration->last_name,
					$registration->discipline,
					$registration->specialty,
					$registration->seniority,
					$session_id
				);

				if ( $waitlist_result['success'] ) {
					return array(
						'success'  => false,
						'waitlist' => true,
						'position' => $waitlist_result['position'],
						'message'  => sprintf(
							__( 'Session is full. You have been added to the waitlist at position %d', 'event-management-system' ),
							$waitlist_result['position']
						),
					);
				} else {
					return array(
						'success' => false,
						'message' => __( 'Session is full and waitlist could not be added', 'event-management-system' ),
					);
				}
			}

			// Register for session
			$result = $wpdb->insert(
				$wpdb->prefix . 'ems_session_registrations',
				array(
					'session_id'        => $session_id,
					'registration_id'   => $registration_id,
					'registered_date'   => current_time( 'mysql' ),
					'attendance_status' => 'registered',
				),
				array( '%d', '%d', '%s', '%s' )
			);

			if ( ! $result ) {
				$this->logger->error(
					'Failed to insert session registration',
					EMS_Logger::CONTEXT_GENERAL,
					array( 'db_error' => $wpdb->last_error )
				);
				return array(
					'success' => false,
					'message' => __( 'Failed to register for session', 'event-management-system' ),
				);
			}

			$session_registration_id = $wpdb->insert_id;

			// Update session registration count
			$this->update_session_count( $session_id );

			// Fire action hook
			do_action( 'ems_session_registered', $session_id, $registration_id, $session_registration_id );

			$this->logger->info(
				"Session registration successful: Session {$session_id}, Registration {$registration_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'id'      => $session_registration_id,
				'message' => __( 'Successfully registered for session', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in register_for_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL,
				array( 'trace' => $e->getTraceAsString() )
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while registering for the session', 'event-management-system' ),
			);
		}
	}

	/**
	 * Cancel session registration
	 *
	 * Cancels a participant's registration for a session and
	 * processes waitlist if applicable.
	 *
	 * @since 1.2.0
	 * @param int $session_id      Session ID.
	 * @param int $registration_id Event registration ID.
	 * @return array Result array with success status and message.
	 */
	public function cancel_session_registration( $session_id, $registration_id ) {
		global $wpdb;
		
		try {
			// Verify registration exists
			$session_reg = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_session_registrations 
					WHERE session_id = %d AND registration_id = %d",
					$session_id,
					$registration_id
				)
			);

			if ( ! $session_reg ) {
				return array(
					'success' => false,
					'message' => __( 'Session registration not found', 'event-management-system' ),
				);
			}

			// Delete the registration
			$deleted = $wpdb->delete(
				$wpdb->prefix . 'ems_session_registrations',
				array(
					'session_id'      => $session_id,
					'registration_id' => $registration_id,
				),
				array( '%d', '%d' )
			);

			if ( ! $deleted ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to cancel session registration', 'event-management-system' ),
				);
			}

			// Update session count
			$this->update_session_count( $session_id );

			// Check waitlist and notify next person
			$event_id = get_post_meta( $session_id, 'event_id', true );
			$this->waitlist->process_session_waitlist( $event_id, $session_id );

			// Fire action hook
			do_action( 'ems_session_registration_cancelled', $session_id, $registration_id );

			$this->logger->info(
				"Session registration cancelled: Session {$session_id}, Registration {$registration_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Session registration cancelled successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in cancel_session_registration: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while cancelling the registration', 'event-management-system' ),
			);
		}
	}

	/**
	 * Bulk register for sessions
	 *
	 * Registers a participant for multiple sessions at once.
	 *
	 * @since 1.2.0
	 * @param array $session_ids     Array of session IDs.
	 * @param int   $registration_id Event registration ID.
	 * @return array {
	 *     @type bool  $success      Overall success status.
	 *     @type array $results      Individual results for each session.
	 *     @type int   $registered   Count of successful registrations.
	 *     @type int   $failed       Count of failed registrations.
	 * }
	 */
	public function bulk_register_sessions( $session_ids, $registration_id ) {
		$results    = array();
		$registered = 0;
		$failed     = 0;

		foreach ( $session_ids as $session_id ) {
			$result = $this->register_for_session( $session_id, $registration_id );
			
			$results[ $session_id ] = $result;
			
			if ( $result['success'] ) {
				$registered++;
			} else {
				$failed++;
			}
		}

		return array(
			'success'    => $registered > 0,
			'results'    => $results,
			'registered' => $registered,
			'failed'     => $failed,
			'message'    => sprintf(
				__( 'Registered for %d sessions. %d failed.', 'event-management-system' ),
				$registered,
				$failed
			),
		);
	}

	// ==========================================
	// CAPACITY MANAGEMENT
	// ==========================================

	/**
	 * Check session capacity
	 *
	 * Checks if a session has available capacity.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array {
	 *     @type bool $available Whether capacity is available.
	 *     @type int  $capacity  Total capacity (0 = unlimited).
	 *     @type int  $registered Current registration count.
	 *     @type int  $remaining  Remaining spots.
	 * }
	 */
	public function check_session_capacity( $session_id ) {
		$capacity   = absint( get_post_meta( $session_id, 'capacity', true ) );
		$registered = absint( get_post_meta( $session_id, 'current_registrations', true ) );

		// 0 capacity means unlimited
		if ( $capacity === 0 ) {
			return array(
				'available'  => true,
				'capacity'   => 0,
				'registered' => $registered,
				'remaining'  => -1, // Unlimited
			);
		}

		$remaining = $capacity - $registered;

		return array(
			'available'  => $remaining > 0,
			'capacity'   => $capacity,
			'registered' => $registered,
			'remaining'  => max( 0, $remaining ),
		);
	}

	/**
	 * Update session registration count
	 *
	 * Recalculates and updates the current registration count for a session.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return int Updated count.
	 */
	private function update_session_count( $session_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_session_registrations WHERE session_id = %d",
				$session_id
			)
		);

		update_post_meta( $session_id, 'current_registrations', absint( $count ) );

		return absint( $count );
	}

	// ==========================================
	// CONFLICT DETECTION
	// ==========================================

	/**
	 * Check registration conflicts
	 *
	 * Checks if a participant is already registered for another session
	 * that conflicts with the requested session time.
	 *
	 * @since 1.2.0
	 * @param int $registration_id Event registration ID.
	 * @param int $session_id      Session ID to check.
	 * @return array {
	 *     @type bool   $has_conflict     Whether a conflict exists.
	 *     @type string $message          Conflict message.
	 *     @type int    $conflicting_id   Conflicting session ID (if any).
	 *     @type string $conflicting_title Conflicting session title (if any).
	 * }
	 */
	public function check_registration_conflicts( $registration_id, $session_id ) {
		global $wpdb;

		try {
			// Get session times
			$session       = get_post( $session_id );
			$start_time    = get_post_meta( $session_id, 'start_datetime', true );
			$end_time      = get_post_meta( $session_id, 'end_datetime', true );
			$session_event = get_post_meta( $session_id, 'event_id', true );

			if ( ! $start_time || ! $end_time ) {
				return array(
					'has_conflict' => false,
					'message'      => __( 'Could not check conflicts - missing session times', 'event-management-system' ),
				);
			}

			// Get all other sessions this participant is registered for
			$registered_sessions = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT session_id FROM {$wpdb->prefix}ems_session_registrations 
					WHERE registration_id = %d AND session_id != %d",
					$registration_id,
					$session_id
				)
			);

			if ( empty( $registered_sessions ) ) {
				return array(
					'has_conflict' => false,
					'message'      => __( 'No conflicts', 'event-management-system' ),
				);
			}

			// Check each registered session for time overlap
			foreach ( $registered_sessions as $reg_session_id ) {
				$reg_start = get_post_meta( $reg_session_id, 'start_datetime', true );
				$reg_end   = get_post_meta( $reg_session_id, 'end_datetime', true );
				
				if ( ! $reg_start || ! $reg_end ) {
					continue;
				}

				// Convert to timestamps for comparison
				$start_ts     = strtotime( $start_time );
				$end_ts       = strtotime( $end_time );
				$reg_start_ts = strtotime( $reg_start );
				$reg_end_ts   = strtotime( $reg_end );

				// Check for overlap: (start1 < end2) AND (end1 > start2)
				if ( $start_ts < $reg_end_ts && $end_ts > $reg_start_ts ) {
					$conflicting_session = get_post( $reg_session_id );
					
					return array(
						'has_conflict'       => true,
						'conflicting_id'     => $reg_session_id,
						'conflicting_title'  => $conflicting_session->post_title,
						'message'            => sprintf(
							__( 'Time conflict with: %s', 'event-management-system' ),
							$conflicting_session->post_title
						),
					);
				}
			}

			return array(
				'has_conflict' => false,
				'message'      => __( 'No conflicts', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in check_registration_conflicts: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'has_conflict' => false,
				'message'      => __( 'Could not check conflicts', 'event-management-system' ),
			);
		}
	}

	// ==========================================
	// REGISTRATION QUERIES
	// ==========================================

	/**
	 * Get participant's session registrations
	 *
	 * Retrieves all sessions a participant is registered for.
	 *
	 * @since 1.2.0
	 * @param int $registration_id Event registration ID.
	 * @return array Array of session data.
	 */
	public function get_participant_sessions( $registration_id ) {
		global $wpdb;

		$session_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT session_id FROM {$wpdb->prefix}ems_session_registrations WHERE registration_id = %d",
				$registration_id
			)
		);

		if ( empty( $session_ids ) ) {
			return array();
		}

		$sessions = array();
		foreach ( $session_ids as $session_id ) {
			$session = get_post( $session_id );
			if ( ! $session ) {
				continue;
			}

			$sessions[] = array(
				'id'             => $session_id,
				'title'          => $session->post_title,
				'session_type'   => get_post_meta( $session_id, 'session_type', true ),
				'start_datetime' => get_post_meta( $session_id, 'start_datetime', true ),
				'end_datetime'   => get_post_meta( $session_id, 'end_datetime', true ),
				'location'       => get_post_meta( $session_id, 'location', true ),
				'track'          => get_post_meta( $session_id, 'track', true ),
			);
		}

		// Sort by start time
		usort( $sessions, function( $a, $b ) {
			return strtotime( $a['start_datetime'] ) - strtotime( $b['start_datetime'] );
		});

		return $sessions;
	}

	/**
	 * Get session registrants
	 *
	 * Retrieves all participants registered for a specific session.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array Array of registration data.
	 */
	public function get_session_registrants( $session_id ) {
		global $wpdb;

		$registrations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sr.*, r.first_name, r.last_name, r.email, r.discipline, r.specialty 
				FROM {$wpdb->prefix}ems_session_registrations sr
				INNER JOIN {$wpdb->prefix}ems_registrations r ON sr.registration_id = r.id
				WHERE sr.session_id = %d
				ORDER BY sr.registered_date ASC",
				$session_id
			)
		);

		return $registrations ? $registrations : array();
	}

	/**
	 * Get session registration statistics
	 *
	 * Returns statistical data about session registrations.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array Statistics array.
	 */
	public function get_session_statistics( $session_id ) {
		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_session_registrations WHERE session_id = %d",
				$session_id
			)
		);

		$by_discipline = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.discipline, COUNT(*) as count
				FROM {$wpdb->prefix}ems_session_registrations sr
				INNER JOIN {$wpdb->prefix}ems_registrations r ON sr.registration_id = r.id
				WHERE sr.session_id = %d
				GROUP BY r.discipline",
				$session_id
			),
			OBJECT_K
		);

		return array(
			'total'         => absint( $total ),
			'by_discipline' => $by_discipline,
			'capacity'      => get_post_meta( $session_id, 'capacity', true ),
			'remaining'     => max( 0, get_post_meta( $session_id, 'capacity', true ) - $total ),
		);
	}

	// ==========================================
	// ATTENDANCE TRACKING
	// ==========================================

	/**
	 * Mark attendance
	 *
	 * Updates attendance status for a session registration.
	 *
	 * @since 1.2.0
	 * @param int    $session_id      Session ID.
	 * @param int    $registration_id Event registration ID.
	 * @param string $status          Attendance status (registered|attended|no_show).
	 * @return array Result array.
	 */
	public function mark_attendance( $session_id, $registration_id, $status ) {
		global $wpdb;

		$valid_statuses = array( 'registered', 'attended', 'no_show' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid attendance status', 'event-management-system' ),
			);
		}

		$updated = $wpdb->update(
			$wpdb->prefix . 'ems_session_registrations',
			array( 'attendance_status' => $status ),
			array(
				'session_id'      => $session_id,
				'registration_id' => $registration_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);

		if ( $updated === false ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to update attendance', 'event-management-system' ),
			);
		}

		// Fire action hook
		do_action( 'ems_attendance_marked', $session_id, $registration_id, $status );

		return array(
			'success' => true,
			'message' => __( 'Attendance updated successfully', 'event-management-system' ),
		);
	}
}