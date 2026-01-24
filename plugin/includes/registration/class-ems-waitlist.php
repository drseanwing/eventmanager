<?php
/**
 * Waitlist Management
 *
 * Manages event and session waitlists with queue processing.
 *
 * @package    EventManagementSystem
 * @subpackage Registration
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Waitlist Management Class
 *
 * @since 1.0.0
 */
class EMS_Waitlist {

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Email manager instance
	 *
	 * @var EMS_Email_Manager
	 */
	private $email_manager;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger        = EMS_Logger::instance();
		$this->email_manager = new EMS_Email_Manager();
	}

	/**
	 * Add participant to waitlist
	 *
	 * @since 1.0.0
	 * @param array $waitlist_data Waitlist data including event_id, participant info.
	 * @return array Result with success status and waitlist_id or message
	 */
	public function add_to_waitlist( $waitlist_data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		try {
			$event_id = absint( $waitlist_data['event_id'] );
			$session_id = isset( $waitlist_data['session_id'] ) ? absint( $waitlist_data['session_id'] ) : null;

			// Check if already on waitlist
			$where = array( 'event_id' => $event_id, 'email' => sanitize_email( $waitlist_data['email'] ) );
			if ( $session_id ) {
				$where['session_id'] = $session_id;
			}

			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} WHERE event_id = %d AND email = %s" . ( $session_id ? " AND session_id = %d" : " AND session_id IS NULL" ),
					...array_values( $where )
				)
			);

			if ( $existing ) {
				$this->logger->info(
					"Already on waitlist: {$waitlist_data['email']} for event {$event_id}",
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'You are already on the waitlist', 'event-management-system' ),
				);
			}

			// Get next position in queue
			$position = $this->get_next_position( $event_id, $session_id );

			$insert_data = array(
				'event_id'   => $event_id,
				'session_id' => $session_id,
				'email'      => sanitize_email( $waitlist_data['email'] ),
				'first_name' => sanitize_text_field( $waitlist_data['first_name'] ),
				'last_name'  => sanitize_text_field( $waitlist_data['last_name'] ),
				'discipline' => sanitize_text_field( $waitlist_data['discipline'] ),
				'specialty'  => sanitize_text_field( $waitlist_data['specialty'] ),
				'seniority'  => sanitize_text_field( $waitlist_data['seniority'] ),
				'added_date' => current_time( 'mysql' ),
				'notified'   => 0,
				'position'   => $position,
			);

			$inserted = $wpdb->insert(
				$table_name,
				$insert_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
			);

			if ( false === $inserted ) {
				$this->logger->error(
					"Database error adding to waitlist: " . $wpdb->last_error,
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'Database error', 'event-management-system' ),
				);
			}

			$waitlist_id = $wpdb->insert_id;

			$this->logger->info(
				"Added to waitlist (ID: {$waitlist_id}, Position: {$position}) for event {$event_id}: {$waitlist_data['email']}",
				EMS_Logger::CONTEXT_REGISTRATION
			);

			// Send waitlist confirmation email
			$this->email_manager->send_waitlist_confirmation_email( $waitlist_id );

			return array(
				'success'     => true,
				'waitlist_id' => $waitlist_id,
				'position'    => $position,
				'message'     => sprintf(
					__( 'You have been added to the waitlist (Position: %d)', 'event-management-system' ),
					$position
				),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception adding to waitlist: " . $e->getMessage(),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred', 'event-management-system' ),
			);
		}
	}

	/**
	 * Get next position in waitlist queue
	 *
	 * @since 1.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $session_id Session ID (null for event-level waitlist).
	 * @return int Next position number
	 */
	private function get_next_position( $event_id, $session_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		if ( $session_id ) {
			$max_position = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(position) FROM {$table_name} WHERE event_id = %d AND session_id = %d",
					$event_id,
					$session_id
				)
			);
		} else {
			$max_position = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(position) FROM {$table_name} WHERE event_id = %d AND session_id IS NULL",
					$event_id
				)
			);
		}

		return absint( $max_position ) + 1;
	}

	/**
	 * Process waitlist when spots become available
	 *
	 * Automatically notifies next person(s) on waitlist when capacity opens.
	 *
	 * @since 1.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $session_id Session ID (null for event-level).
	 * @param int      $spots_available Number of spots that opened up.
	 * @return array Result with number of people notified
	 */
	public function process_waitlist( $event_id, $session_id = null, $spots_available = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		try {
			// Get next people on waitlist who haven't been notified
			if ( $session_id ) {
				$waitlist = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$table_name} WHERE event_id = %d AND session_id = %d AND notified = 0 ORDER BY position ASC LIMIT %d",
						$event_id,
						$session_id,
						$spots_available
					)
				);
			} else {
				$waitlist = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$table_name} WHERE event_id = %d AND session_id IS NULL AND notified = 0 ORDER BY position ASC LIMIT %d",
						$event_id,
						$spots_available
					)
				);
			}

			$notified_count = 0;

			foreach ( $waitlist as $entry ) {
				// Send notification email
				$email_sent = $this->email_manager->send_waitlist_spot_available_email( $entry->id );

				if ( $email_sent ) {
					// Mark as notified
					$wpdb->update(
						$table_name,
						array( 'notified' => 1 ),
						array( 'id' => $entry->id ),
						array( '%d' ),
						array( '%d' )
					);

					$notified_count++;

					$this->logger->info(
						"Waitlist spot available notification sent to {$entry->email} for event {$event_id}",
						EMS_Logger::CONTEXT_REGISTRATION
					);
				}
			}

			return array(
				'success' => true,
				'notified' => $notified_count,
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception processing waitlist: " . $e->getMessage(),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'success' => false,
				'notified' => 0,
			);
		}
	}

	/**
	 * Remove from waitlist
	 *
	 * @since 1.0.0
	 * @param int $waitlist_id Waitlist entry ID.
	 * @return array Result with success status
	 */
	public function remove_from_waitlist( $waitlist_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		try {
			$entry = $this->get_waitlist_entry( $waitlist_id );

			if ( ! $entry ) {
				return array(
					'success' => false,
					'message' => __( 'Waitlist entry not found', 'event-management-system' ),
				);
			}

			$deleted = $wpdb->delete(
				$table_name,
				array( 'id' => $waitlist_id ),
				array( '%d' )
			);

			if ( false === $deleted ) {
				$this->logger->error(
					"Failed to remove waitlist entry {$waitlist_id}: " . $wpdb->last_error,
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'Database error', 'event-management-system' ),
				);
			}

			// Reorder remaining positions
			$this->reorder_waitlist_positions( $entry->event_id, $entry->session_id );

			$this->logger->info(
				"Removed from waitlist: {$entry->email} (ID: {$waitlist_id})",
				EMS_Logger::CONTEXT_REGISTRATION
			);

			return array(
				'success' => true,
				'message' => __( 'Removed from waitlist', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception removing from waitlist: " . $e->getMessage(),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred', 'event-management-system' ),
			);
		}
	}

	/**
	 * Reorder waitlist positions after removal
	 *
	 * @since 1.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $session_id Session ID.
	 * @return void
	 */
	private function reorder_waitlist_positions( $event_id, $session_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		if ( $session_id ) {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} WHERE event_id = %d AND session_id = %d ORDER BY position ASC",
					$event_id,
					$session_id
				)
			);
		} else {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} WHERE event_id = %d AND session_id IS NULL ORDER BY position ASC",
					$event_id
				)
			);
		}

		$position = 1;
		foreach ( $entries as $entry ) {
			$wpdb->update(
				$table_name,
				array( 'position' => $position ),
				array( 'id' => $entry->id ),
				array( '%d' ),
				array( '%d' )
			);
			$position++;
		}

		$this->logger->debug(
			"Reordered waitlist positions for event {$event_id}",
			EMS_Logger::CONTEXT_REGISTRATION
		);
	}

	/**
	 * Get waitlist entry by ID
	 *
	 * @since 1.0.0
	 * @param int $waitlist_id Waitlist entry ID.
	 * @return object|null Waitlist entry or null
	 */
	public function get_waitlist_entry( $waitlist_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$waitlist_id
			)
		);

		return $entry;
	}

	/**
	 * Get all waitlist entries for an event
	 *
	 * @since 1.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $session_id Session ID (null for event-level).
	 * @return array Array of waitlist entries
	 */
	public function get_event_waitlist( $event_id, $session_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		if ( $session_id ) {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} WHERE event_id = %d AND session_id = %d ORDER BY position ASC",
					$event_id,
					$session_id
				)
			);
		} else {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} WHERE event_id = %d AND session_id IS NULL ORDER BY position ASC",
					$event_id
				)
			);
		}

		return $entries;
	}

	/**
	 * Get waitlist position for a specific email
	 *
	 * @since 1.0.0
	 * @param int      $event_id Event ID.
	 * @param string   $email Email address.
	 * @param int|null $session_id Session ID.
	 * @return int|false Position or false if not on waitlist
	 */
	public function get_user_position( $event_id, $email, $session_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		if ( $session_id ) {
			$position = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT position FROM {$table_name} WHERE event_id = %d AND session_id = %d AND email = %s",
					$event_id,
					$session_id,
					sanitize_email( $email )
				)
			);
		} else {
			$position = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT position FROM {$table_name} WHERE event_id = %d AND session_id IS NULL AND email = %s",
					$event_id,
					sanitize_email( $email )
				)
			);
		}

		return $position ? absint( $position ) : false;
	}

	/**
	 * Get total waitlist count
	 *
	 * @since 1.0.0
	 * @param int      $event_id Event ID.
	 * @param int|null $session_id Session ID.
	 * @return int Count of people on waitlist
	 */
	public function get_waitlist_count( $event_id, $session_id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_waitlist';

		if ( $session_id ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE event_id = %d AND session_id = %d",
					$event_id,
					$session_id
				)
			);
		} else {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE event_id = %d AND session_id IS NULL",
					$event_id
				)
			);
		}

		return absint( $count );
	}
}
