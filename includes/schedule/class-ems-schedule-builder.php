<?php
/**
 * Schedule Builder
 *
 * Handles schedule creation, session management, conflict detection,
 * and abstract-to-session assignment. Provides comprehensive tools for
 * building and managing event schedules.
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
 * Schedule Builder Class
 *
 * Manages all aspects of schedule creation including:
 * - Session creation and modification
 * - Time slot management
 * - Room/location assignment
 * - Conflict detection
 * - Abstract-to-session linking
 * - Capacity management
 *
 * @since 1.2.0
 */
class EMS_Schedule_Builder {
	
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
	 * Session types
	 *
	 * @var array
	 */
	const SESSION_TYPES = array(
		'keynote'    => 'Keynote',
		'oral'       => 'Oral Presentation',
		'poster'     => 'Poster Session',
		'workshop'   => 'Workshop',
		'panel'      => 'Panel Discussion',
		'breakout'   => 'Breakout Session',
		'meal'       => 'Meal Break',
		'coffee'     => 'Coffee Break',
		'networking' => 'Networking',
		'other'      => 'Other',
	);

	/**
	 * Constructor
	 *
	 * Initializes dependencies and sets up the schedule builder.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->logger    = EMS_Logger::instance();
		$this->validator = new EMS_Validator();
		$this->security  = new EMS_Security();
		
		$this->logger->debug( 'EMS_Schedule_Builder initialized', EMS_Logger::CONTEXT_GENERAL );
	}

	// ==========================================
	// SESSION CREATION AND MANAGEMENT
	// ==========================================

	/**
	 * Create a new session
	 *
	 * Creates a session with all necessary metadata including time,
	 * location, capacity, and optional abstract linkage.
	 *
	 * @since 1.2.0
	 * @param array $session_data {
	 *     Session data.
	 *
	 *     @type int    $event_id         Event ID (required).
	 *     @type string $title            Session title (required).
	 *     @type string $description      Session description.
	 *     @type string $session_type     Type of session (required).
	 *     @type string $start_datetime   Start date/time (required).
	 *     @type string $end_datetime     End date/time (required).
	 *     @type string $location         Room/location (required).
	 *     @type int    $capacity         Max capacity.
	 *     @type int    $abstract_id      Linked abstract ID.
	 *     @type array  $presenters       Array of presenter IDs.
	 *     @type string $track            Track/stream name.
	 *     @type array  $custom_fields    Additional custom data.
	 * }
	 * @return array {
	 *     @type bool   $success     Success status.
	 *     @type int    $session_id  Created session ID.
	 *     @type string $message     Result message.
	 * }
	 */
	public function create_session( $session_data ) {
		try {
			// Validate required fields
			$required_fields = array( 'event_id', 'title', 'session_type', 'start_datetime', 'end_datetime', 'location' );
			foreach ( $required_fields as $field ) {
				if ( empty( $session_data[ $field ] ) ) {
					$this->logger->warning(
						"Missing required field for session creation: {$field}",
						EMS_Logger::CONTEXT_GENERAL
					);
					return array(
						'success' => false,
						'message' => sprintf( __( 'Missing required field: %s', 'event-management-system' ), $field ),
					);
				}
			}

			// Validate session type
			if ( ! array_key_exists( $session_data['session_type'], self::SESSION_TYPES ) ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid session type', 'event-management-system' ),
				);
			}

			// Validate event exists
			$event = get_post( $session_data['event_id'] );
			if ( ! $event || $event->post_type !== 'ems_event' ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid event ID', 'event-management-system' ),
				);
			}

			// Validate datetime format and logic
			$validation = $this->validate_session_times(
				$session_data['start_datetime'],
				$session_data['end_datetime']
			);
			if ( ! $validation['valid'] ) {
				return array(
					'success' => false,
					'message' => $validation['message'],
				);
			}

			// Check for time conflicts
			$conflict_check = $this->check_time_conflicts(
				$session_data['event_id'],
				$session_data['start_datetime'],
				$session_data['end_datetime'],
				$session_data['location']
			);

			if ( $conflict_check['has_conflict'] ) {
				$this->logger->warning(
					'Session creation blocked due to time conflict',
					EMS_Logger::CONTEXT_GENERAL,
					$conflict_check
				);
				return array(
					'success' => false,
					'message' => $conflict_check['message'],
				);
			}

			// Create session post
			$post_data = array(
				'post_title'   => sanitize_text_field( $session_data['title'] ),
				'post_content' => ! empty( $session_data['description'] ) ? wp_kses_post( $session_data['description'] ) : '',
				'post_status'  => 'publish',
				'post_type'    => 'ems_session',
				'post_author'  => get_current_user_id(),
			);

			$session_id = wp_insert_post( $post_data );

			if ( is_wp_error( $session_id ) ) {
				$this->logger->error(
					'Failed to create session post: ' . $session_id->get_error_message(),
					EMS_Logger::CONTEXT_GENERAL
				);
				return array(
					'success' => false,
					'message' => __( 'Failed to create session', 'event-management-system' ),
				);
			}

			// Save session metadata
			update_post_meta( $session_id, 'event_id', absint( $session_data['event_id'] ) );
			update_post_meta( $session_id, 'session_type', sanitize_text_field( $session_data['session_type'] ) );
			update_post_meta( $session_id, 'start_datetime', sanitize_text_field( $session_data['start_datetime'] ) );
			update_post_meta( $session_id, 'end_datetime', sanitize_text_field( $session_data['end_datetime'] ) );
			update_post_meta( $session_id, 'location', sanitize_text_field( $session_data['location'] ) );
			update_post_meta( $session_id, 'capacity', ! empty( $session_data['capacity'] ) ? absint( $session_data['capacity'] ) : 0 );
			update_post_meta( $session_id, 'current_registrations', 0 );

			// Optional fields
			if ( ! empty( $session_data['abstract_id'] ) ) {
				update_post_meta( $session_id, 'linked_abstract_id', absint( $session_data['abstract_id'] ) );
			}

			if ( ! empty( $session_data['presenters'] ) && is_array( $session_data['presenters'] ) ) {
				update_post_meta( $session_id, 'presenters', array_map( 'absint', $session_data['presenters'] ) );
			}

			if ( ! empty( $session_data['track'] ) ) {
				update_post_meta( $session_id, 'track', sanitize_text_field( $session_data['track'] ) );
			}

			if ( ! empty( $session_data['custom_fields'] ) && is_array( $session_data['custom_fields'] ) ) {
				foreach ( $session_data['custom_fields'] as $key => $value ) {
					update_post_meta( $session_id, 'custom_' . sanitize_key( $key ), sanitize_text_field( $value ) );
				}
			}

			// Fire action hook
			do_action( 'ems_session_created', $session_id, $session_data );

			$this->logger->info(
				"Session created successfully: ID {$session_id}",
				EMS_Logger::CONTEXT_GENERAL,
				array( 'session_id' => $session_id )
			);

			return array(
				'success'    => true,
				'session_id' => $session_id,
				'message'    => __( 'Session created successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in create_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL,
				array( 'trace' => $e->getTraceAsString() )
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while creating the session', 'event-management-system' ),
			);
		}
	}

	/**
	 * Update an existing session
	 *
	 * Updates session details while maintaining data integrity and
	 * checking for conflicts.
	 *
	 * @since 1.2.0
	 * @param int   $session_id   Session ID.
	 * @param array $session_data Updated session data.
	 * @return array Result array with success status and message.
	 */
	public function update_session( $session_id, $session_data ) {
		try {
			// Verify session exists
			$session = get_post( $session_id );
			if ( ! $session || $session->post_type !== 'ems_session' ) {
				return array(
					'success' => false,
					'message' => __( 'Session not found', 'event-management-system' ),
				);
			}

			// Update post data if provided
			$update_data = array(
				'ID' => $session_id,
			);

			if ( ! empty( $session_data['title'] ) ) {
				$update_data['post_title'] = sanitize_text_field( $session_data['title'] );
			}

			if ( isset( $session_data['description'] ) ) {
				$update_data['post_content'] = wp_kses_post( $session_data['description'] );
			}

			// Update post if there are changes
			if ( count( $update_data ) > 1 ) {
				wp_update_post( $update_data );
			}

			// Update metadata
			$meta_fields = array(
				'session_type',
				'start_datetime',
				'end_datetime',
				'location',
				'capacity',
				'track',
				'linked_abstract_id',
			);

			foreach ( $meta_fields as $field ) {
				if ( isset( $session_data[ $field ] ) ) {
					// Validate times if being updated
					if ( in_array( $field, array( 'start_datetime', 'end_datetime' ), true ) ) {
						$start = isset( $session_data['start_datetime'] ) ? $session_data['start_datetime'] : get_post_meta( $session_id, 'start_datetime', true );
						$end   = isset( $session_data['end_datetime'] ) ? $session_data['end_datetime'] : get_post_meta( $session_id, 'end_datetime', true );

						$validation = $this->validate_session_times( $start, $end );
						if ( ! $validation['valid'] ) {
							return array(
								'success' => false,
								'message' => $validation['message'],
							);
						}

						// Check conflicts (excluding current session)
						$event_id = get_post_meta( $session_id, 'event_id', true );
						$location = isset( $session_data['location'] ) ? $session_data['location'] : get_post_meta( $session_id, 'location', true );

						$conflict_check = $this->check_time_conflicts( $event_id, $start, $end, $location, $session_id );
						if ( $conflict_check['has_conflict'] ) {
							return array(
								'success' => false,
								'message' => $conflict_check['message'],
							);
						}
					}

					update_post_meta( $session_id, $field, sanitize_text_field( $session_data[ $field ] ) );
				}
			}

			// Update presenters array if provided
			if ( isset( $session_data['presenters'] ) && is_array( $session_data['presenters'] ) ) {
				update_post_meta( $session_id, 'presenters', array_map( 'absint', $session_data['presenters'] ) );
			}

			// Fire action hook
			do_action( 'ems_session_updated', $session_id, $session_data );

			$this->logger->info(
				"Session updated successfully: ID {$session_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Session updated successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in update_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while updating the session', 'event-management-system' ),
			);
		}
	}

	/**
	 * Delete a session
	 *
	 * Deletes a session and handles cleanup of related data.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array Result array with success status and message.
	 */
	public function delete_session( $session_id ) {
		try {
			// Verify session exists
			$session = get_post( $session_id );
			if ( ! $session || $session->post_type !== 'ems_session' ) {
				return array(
					'success' => false,
					'message' => __( 'Session not found', 'event-management-system' ),
				);
			}

			// Check if session has registrations
			$registration_count = absint( get_post_meta( $session_id, 'current_registrations', true ) );
			if ( $registration_count > 0 ) {
				$this->logger->warning(
					"Cannot delete session {$session_id} - has {$registration_count} registrations",
					EMS_Logger::CONTEXT_GENERAL
				);
				return array(
					'success' => false,
					'message' => sprintf(
						__( 'Cannot delete session with %d registrations. Cancel registrations first.', 'event-management-system' ),
						$registration_count
					),
				);
			}

			// Fire action before deletion
			do_action( 'ems_before_session_deleted', $session_id );

			// Delete the post
			$result = wp_delete_post( $session_id, true );

			if ( ! $result ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to delete session', 'event-management-system' ),
				);
			}

			// Fire action after deletion
			do_action( 'ems_session_deleted', $session_id );

			$this->logger->info(
				"Session deleted successfully: ID {$session_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Session deleted successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in delete_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while deleting the session', 'event-management-system' ),
			);
		}
	}

	// ==========================================
	// SESSION RETRIEVAL AND QUERIES
	// ==========================================

	/**
	 * Get event sessions
	 *
	 * Retrieves all sessions for a specific event with optional filtering.
	 *
	 * @since 1.2.0
	 * @param int   $event_id Event ID.
	 * @param array $args {
	 *     Optional query arguments.
	 *
	 *     @type string $session_type Filter by session type.
	 *     @type string $track        Filter by track.
	 *     @type string $location     Filter by location.
	 *     @type string $date         Filter by date (Y-m-d format).
	 *     @type string $order        Sort order (ASC or DESC).
	 *     @type string $orderby      Sort field (default: start_datetime).
	 * }
	 * @return array {
	 *     @type bool  $success  Success status.
	 *     @type array $sessions Array of session data.
	 *     @type int   $count    Total count.
	 * }
	 */
	public function get_event_sessions( $event_id, $args = array() ) {
		try {
			// Validate event
			$event = get_post( $event_id );
			if ( ! $event || $event->post_type !== 'ems_event' ) {
				return array(
					'success'  => false,
					'sessions' => array(),
					'count'    => 0,
					'message'  => __( 'Invalid event ID', 'event-management-system' ),
				);
			}

			// Build meta query
			$meta_query = array(
				array(
					'key'   => 'event_id',
					'value' => $event_id,
					'type'  => 'NUMERIC',
				),
			);

			// Add filters if provided
			if ( ! empty( $args['session_type'] ) ) {
				$meta_query[] = array(
					'key'   => 'session_type',
					'value' => sanitize_text_field( $args['session_type'] ),
				);
			}

			if ( ! empty( $args['track'] ) ) {
				$meta_query[] = array(
					'key'   => 'track',
					'value' => sanitize_text_field( $args['track'] ),
				);
			}

			if ( ! empty( $args['location'] ) ) {
				$meta_query[] = array(
					'key'   => 'location',
					'value' => sanitize_text_field( $args['location'] ),
				);
			}

			// Date filtering
			if ( ! empty( $args['date'] ) ) {
				$meta_query[] = array(
					'key'     => 'start_datetime',
					'value'   => array(
						$args['date'] . ' 00:00:00',
						$args['date'] . ' 23:59:59',
					),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				);
			}

			// Query sessions
			$query_args = array(
				'post_type'      => 'ems_session',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => $meta_query,
				'meta_key'       => ! empty( $args['orderby'] ) ? sanitize_text_field( $args['orderby'] ) : 'start_datetime',
				'orderby'        => 'meta_value',
				'order'          => ! empty( $args['order'] ) ? sanitize_text_field( $args['order'] ) : 'ASC',
			);

			$query    = new WP_Query( $query_args );
			$sessions = array();

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$session_id = get_the_ID();

					$sessions[] = array(
						'id'                    => $session_id,
						'title'                 => get_the_title(),
						'description'           => get_the_content(),
						'session_type'          => get_post_meta( $session_id, 'session_type', true ),
						'start_datetime'        => get_post_meta( $session_id, 'start_datetime', true ),
						'end_datetime'          => get_post_meta( $session_id, 'end_datetime', true ),
						'location'              => get_post_meta( $session_id, 'location', true ),
						'capacity'              => get_post_meta( $session_id, 'capacity', true ),
						'current_registrations' => get_post_meta( $session_id, 'current_registrations', true ),
						'track'                 => get_post_meta( $session_id, 'track', true ),
						'linked_abstract_id'    => get_post_meta( $session_id, 'linked_abstract_id', true ),
						'presenters'            => get_post_meta( $session_id, 'presenters', true ),
					);
				}
				wp_reset_postdata();
			}

			return array(
				'success'  => true,
				'sessions' => $sessions,
				'count'    => count( $sessions ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_event_sessions: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success'  => false,
				'sessions' => array(),
				'count'    => 0,
				'message'  => __( 'An error occurred while retrieving sessions', 'event-management-system' ),
			);
		}
	}

	/**
	 * Get session by ID
	 *
	 * Retrieves complete session data including all metadata.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array|false Session data or false if not found.
	 */
	public function get_session( $session_id ) {
		$session = get_post( $session_id );
		
		if ( ! $session || $session->post_type !== 'ems_session' ) {
			return false;
		}

		return array(
			'id'                    => $session_id,
			'title'                 => $session->post_title,
			'description'           => $session->post_content,
			'session_type'          => get_post_meta( $session_id, 'session_type', true ),
			'start_datetime'        => get_post_meta( $session_id, 'start_datetime', true ),
			'end_datetime'          => get_post_meta( $session_id, 'end_datetime', true ),
			'location'              => get_post_meta( $session_id, 'location', true ),
			'capacity'              => get_post_meta( $session_id, 'capacity', true ),
			'current_registrations' => get_post_meta( $session_id, 'current_registrations', true ),
			'track'                 => get_post_meta( $session_id, 'track', true ),
			'linked_abstract_id'    => get_post_meta( $session_id, 'linked_abstract_id', true ),
			'presenters'            => get_post_meta( $session_id, 'presenters', true ),
			'event_id'              => get_post_meta( $session_id, 'event_id', true ),
		);
	}

	// ==========================================
	// TIME VALIDATION AND CONFLICT DETECTION
	// ==========================================

	/**
	 * Validate session times
	 *
	 * Checks that session times are valid and end time is after start time.
	 *
	 * @since 1.2.0
	 * @param string $start_datetime Start datetime.
	 * @param string $end_datetime   End datetime.
	 * @return array Validation result with valid flag and message.
	 */
	private function validate_session_times( $start_datetime, $end_datetime ) {
		// Validate datetime format
		$start = strtotime( $start_datetime );
		$end   = strtotime( $end_datetime );

		if ( ! $start || ! $end ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid datetime format', 'event-management-system' ),
			);
		}

		// Check end is after start
		if ( $end <= $start ) {
			return array(
				'valid'   => false,
				'message' => __( 'End time must be after start time', 'event-management-system' ),
			);
		}

		// Check session duration is reasonable (not more than 24 hours)
		$duration_hours = ( $end - $start ) / 3600;
		if ( $duration_hours > 24 ) {
			return array(
				'valid'   => false,
				'message' => __( 'Session duration cannot exceed 24 hours', 'event-management-system' ),
			);
		}

		return array(
			'valid'   => true,
			'message' => __( 'Times are valid', 'event-management-system' ),
		);
	}

	/**
	 * Check time conflicts
	 *
	 * Checks if a session conflicts with existing sessions in the same location.
	 *
	 * @since 1.2.0
	 * @param int    $event_id       Event ID.
	 * @param string $start_datetime Start datetime.
	 * @param string $end_datetime   End datetime.
	 * @param string $location       Location/room.
	 * @param int    $exclude_id     Session ID to exclude from check (for updates).
	 * @return array Conflict check result.
	 */
	public function check_time_conflicts( $event_id, $start_datetime, $end_datetime, $location, $exclude_id = 0 ) {
		try {
			// Get all sessions for this event at this location
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'   => 'event_id',
					'value' => $event_id,
					'type'  => 'NUMERIC',
				),
				array(
					'key'   => 'location',
					'value' => $location,
				),
			);

			$query_args = array(
				'post_type'      => 'ems_session',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => $meta_query,
				'post__not_in'   => $exclude_id ? array( $exclude_id ) : array(),
			);

			$query = new WP_Query( $query_args );

			if ( ! $query->have_posts() ) {
				return array(
					'has_conflict' => false,
					'message'      => __( 'No conflicts found', 'event-management-system' ),
				);
			}

			// Check each existing session for overlap
			$start = strtotime( $start_datetime );
			$end   = strtotime( $end_datetime );

			while ( $query->have_posts() ) {
				$query->the_post();
				$session_id = get_the_ID();

				$existing_start = strtotime( get_post_meta( $session_id, 'start_datetime', true ) );
				$existing_end   = strtotime( get_post_meta( $session_id, 'end_datetime', true ) );

				// Check for overlap
				// Overlap occurs if: (start1 < end2) AND (end1 > start2)
				if ( $start < $existing_end && $end > $existing_start ) {
					wp_reset_postdata();
					return array(
						'has_conflict'    => true,
						'conflicting_id'  => $session_id,
						'conflicting_title' => get_the_title( $session_id ),
						'message'         => sprintf(
							__( 'Time conflict with session: %s', 'event-management-system' ),
							get_the_title( $session_id )
						),
					);
				}
			}

			wp_reset_postdata();

			return array(
				'has_conflict' => false,
				'message'      => __( 'No conflicts found', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in check_time_conflicts: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'has_conflict' => false,
				'message'      => __( 'Could not check conflicts', 'event-management-system' ),
			);
		}
	}

	// ==========================================
	// ABSTRACT ASSIGNMENT
	// ==========================================

	/**
	 * Assign abstract to session
	 *
	 * Links an approved abstract to a session slot.
	 *
	 * @since 1.2.0
	 * @param int $session_id  Session ID.
	 * @param int $abstract_id Abstract ID.
	 * @return array Result array.
	 */
	public function assign_abstract_to_session( $session_id, $abstract_id ) {
		try {
			// Validate session
			$session = get_post( $session_id );
			if ( ! $session || $session->post_type !== 'ems_session' ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid session ID', 'event-management-system' ),
				);
			}

			// Validate abstract
			$abstract = get_post( $abstract_id );
			if ( ! $abstract || $abstract->post_type !== 'ems_abstract' ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid abstract ID', 'event-management-system' ),
				);
			}

			// Check abstract is approved
			$review_status = get_post_meta( $abstract_id, 'review_status', true );
			if ( $review_status !== 'approved' ) {
				return array(
					'success' => false,
					'message' => __( 'Only approved abstracts can be assigned to sessions', 'event-management-system' ),
				);
			}

			// Check if abstract is already assigned
			$existing_assignment = get_post_meta( $abstract_id, 'assigned_session_id', true );
			if ( ! empty( $existing_assignment ) && $existing_assignment != $session_id ) {
				return array(
					'success' => false,
					'message' => __( 'Abstract is already assigned to another session', 'event-management-system' ),
				);
			}

			// Assign abstract to session
			update_post_meta( $session_id, 'linked_abstract_id', $abstract_id );
			update_post_meta( $abstract_id, 'assigned_session_id', $session_id );

			// Get abstract presenter and assign to session
			$primary_author = get_post_meta( $abstract_id, 'primary_author_id', true );
			if ( $primary_author ) {
				$presenters   = get_post_meta( $session_id, 'presenters', true );
				$presenters   = is_array( $presenters ) ? $presenters : array();
				$presenters[] = absint( $primary_author );
				update_post_meta( $session_id, 'presenters', array_unique( $presenters ) );
			}

			// Fire action hook
			do_action( 'ems_abstract_assigned_to_session', $session_id, $abstract_id );

			$this->logger->info(
				"Abstract {$abstract_id} assigned to session {$session_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Abstract assigned successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in assign_abstract_to_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while assigning the abstract', 'event-management-system' ),
			);
		}
	}

	/**
	 * Unassign abstract from session
	 *
	 * Removes the link between an abstract and session.
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array Result array.
	 */
	public function unassign_abstract_from_session( $session_id ) {
		try {
			$abstract_id = get_post_meta( $session_id, 'linked_abstract_id', true );
			
			if ( empty( $abstract_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'No abstract assigned to this session', 'event-management-system' ),
				);
			}

			delete_post_meta( $session_id, 'linked_abstract_id' );
			delete_post_meta( $abstract_id, 'assigned_session_id' );

			// Fire action hook
			do_action( 'ems_abstract_unassigned_from_session', $session_id, $abstract_id );

			$this->logger->info(
				"Abstract {$abstract_id} unassigned from session {$session_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Abstract unassigned successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in unassign_abstract_from_session: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred while unassigning the abstract', 'event-management-system' ),
			);
		}
	}

	// ==========================================
	// UTILITY METHODS
	// ==========================================

	/**
	 * Get available session types
	 *
	 * Returns array of session types for use in forms and filters.
	 *
	 * @since 1.2.0
	 * @return array Session types.
	 */
	public function get_session_types() {
		return apply_filters( 'ems_session_types', self::SESSION_TYPES );
	}

	/**
	 * Get event tracks
	 *
	 * Retrieves unique tracks for an event.
	 *
	 * @since 1.2.0
	 * @param int $event_id Event ID.
	 * @return array Unique track names.
	 */
	public function get_event_tracks( $event_id ) {
		global $wpdb;

		$tracks = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value 
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
				WHERE pm.meta_key = 'track'
				AND pm2.meta_key = 'event_id'
				AND pm2.meta_value = %d
				AND pm.meta_value != ''
				ORDER BY pm.meta_value ASC",
				$event_id
			)
		);

		return $tracks ? $tracks : array();
	}

	/**
	 * Get event locations
	 *
	 * Retrieves unique locations/rooms for an event.
	 *
	 * @since 1.2.0
	 * @param int $event_id Event ID.
	 * @return array Unique location names.
	 */
	public function get_event_locations( $event_id ) {
		global $wpdb;

		$locations = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value 
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
				WHERE pm.meta_key = 'location'
				AND pm2.meta_key = 'event_id'
				AND pm2.meta_value = %d
				AND pm.meta_value != ''
				ORDER BY pm.meta_value ASC",
				$event_id
			)
		);

		return $locations ? $locations : array();
	}
}
