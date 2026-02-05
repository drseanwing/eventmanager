<?php
/**
 * Sponsorship Levels CRUD
 *
 * Handles all database operations for event sponsorship levels
 * stored in the wp_ems_sponsorship_levels table.
 *
 * @package    EventManagementSystem
 * @subpackage Sponsor
 * @since      1.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sponsorship Levels class.
 *
 * @since 1.5.0
 */
class EMS_Sponsorship_Levels {

	/**
	 * Table name (without prefix)
	 *
	 * @var string
	 */
	const TABLE = 'ems_sponsorship_levels';

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Get the full table name with prefix.
	 *
	 * @since 1.5.0
	 * @return string Full table name.
	 */
	private function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Get all sponsorship levels for an event.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return array Array of level objects ordered by sort_order.
	 */
	public function get_event_levels( $event_id ) {
		global $wpdb;

		$event_id = absint( $event_id );
		if ( ! $event_id ) {
			return array();
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE event_id = %d ORDER BY sort_order ASC, id ASC",
				$event_id
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get a single sponsorship level by ID.
	 *
	 * @since 1.5.0
	 * @param int $level_id Level ID.
	 * @return object|null Level object or null if not found.
	 */
	public function get_level( $level_id ) {
		global $wpdb;

		$level_id = absint( $level_id );
		if ( ! $level_id ) {
			return null;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE id = %d",
				$level_id
			)
		);
	}

	/**
	 * Add a new sponsorship level.
	 *
	 * @since 1.5.0
	 * @param int   $event_id Event post ID.
	 * @param array $data     Level data array.
	 * @return int|false Inserted level ID or false on failure.
	 */
	public function add_level( $event_id, $data ) {
		global $wpdb;

		$event_id = absint( $event_id );
		if ( ! $event_id ) {
			return false;
		}

		$level_name = sanitize_text_field( $data['level_name'] ?? '' );
		if ( empty( $level_name ) ) {
			return false;
		}

		$level_slug = sanitize_title( $level_name );

		// Ensure unique slug within event
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table()} WHERE event_id = %d AND level_slug = %s",
				$event_id,
				$level_slug
			)
		);

		if ( $existing > 0 ) {
			$level_slug .= '-' . wp_rand( 100, 999 );
		}

		$now = current_time( 'mysql' );

		$insert_data = array(
			'event_id'         => $event_id,
			'level_name'       => $level_name,
			'level_slug'       => $level_slug,
			'colour'           => sanitize_hex_color( $data['colour'] ?? '#CD7F32' ) ?: '#CD7F32',
			'value_aud'        => isset( $data['value_aud'] ) ? floatval( $data['value_aud'] ) : null,
			'slots_total'      => isset( $data['slots_total'] ) ? absint( $data['slots_total'] ) : null,
			'slots_filled'     => 0,
			'recognition_text' => sanitize_textarea_field( $data['recognition_text'] ?? '' ),
			'sort_order'       => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
			'enabled'          => 1,
			'created_at'       => $now,
			'updated_at'       => $now,
		);

		$format = array(
			'%d', // event_id
			'%s', // level_name
			'%s', // level_slug
			'%s', // colour
			'%f', // value_aud
			'%d', // slots_total
			'%d', // slots_filled
			'%s', // recognition_text
			'%d', // sort_order
			'%d', // enabled
			'%s', // created_at
			'%s', // updated_at
		);

		$result = $wpdb->insert( $this->table(), $insert_data, $format );

		if ( false === $result ) {
			$this->logger->error(
				sprintf( 'Failed to add sponsorship level for event %d: %s', $event_id, $wpdb->last_error ),
				EMS_Logger::CONTEXT_GENERAL
			);
			return false;
		}

		$level_id = $wpdb->insert_id;

		$this->logger->info(
			sprintf( 'Sponsorship level "%s" (ID: %d) added to event %d', $level_name, $level_id, $event_id ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return $level_id;
	}

	/**
	 * Update an existing sponsorship level.
	 *
	 * @since 1.5.0
	 * @param int   $level_id Level ID.
	 * @param array $data     Data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update_level( $level_id, $data ) {
		global $wpdb;

		$level_id = absint( $level_id );
		if ( ! $level_id ) {
			return false;
		}

		$update_data = array();
		$format      = array();

		if ( isset( $data['level_name'] ) ) {
			$update_data['level_name'] = sanitize_text_field( $data['level_name'] );
			$format[]                  = '%s';
		}

		if ( isset( $data['colour'] ) ) {
			$update_data['colour'] = sanitize_hex_color( $data['colour'] ) ?: '#CD7F32';
			$format[]              = '%s';
		}

		if ( isset( $data['value_aud'] ) ) {
			$update_data['value_aud'] = floatval( $data['value_aud'] );
			$format[]                 = '%f';
		}

		if ( isset( $data['slots_total'] ) ) {
			$update_data['slots_total'] = absint( $data['slots_total'] );
			$format[]                   = '%d';
		}

		if ( isset( $data['recognition_text'] ) ) {
			$update_data['recognition_text'] = sanitize_textarea_field( $data['recognition_text'] );
			$format[]                        = '%s';
		}

		if ( isset( $data['sort_order'] ) ) {
			$update_data['sort_order'] = absint( $data['sort_order'] );
			$format[]                  = '%d';
		}

		if ( isset( $data['enabled'] ) ) {
			$update_data['enabled'] = absint( $data['enabled'] ) ? 1 : 0;
			$format[]               = '%d';
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$update_data['updated_at'] = current_time( 'mysql' );
		$format[]                  = '%s';

		$result = $wpdb->update(
			$this->table(),
			$update_data,
			array( 'id' => $level_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			$this->logger->error(
				sprintf( 'Failed to update sponsorship level %d: %s', $level_id, $wpdb->last_error ),
				EMS_Logger::CONTEXT_GENERAL
			);
			return false;
		}

		$this->logger->info(
			sprintf( 'Sponsorship level %d updated', $level_id ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return true;
	}

	/**
	 * Delete a sponsorship level.
	 *
	 * Only deletes if slots_filled is 0 (no sponsors currently linked).
	 *
	 * @since 1.5.0
	 * @param int $level_id Level ID.
	 * @return bool|WP_Error True on success, WP_Error if level has filled slots.
	 */
	public function delete_level( $level_id ) {
		global $wpdb;

		$level_id = absint( $level_id );
		if ( ! $level_id ) {
			return new WP_Error( 'invalid_id', __( 'Invalid level ID.', 'event-management-system' ) );
		}

		$level = $this->get_level( $level_id );
		if ( ! $level ) {
			return new WP_Error( 'not_found', __( 'Level not found.', 'event-management-system' ) );
		}

		if ( intval( $level->slots_filled ) > 0 ) {
			return new WP_Error(
				'has_sponsors',
				__( 'Cannot delete a level that has linked sponsors. Unlink all sponsors first.', 'event-management-system' )
			);
		}

		$result = $wpdb->delete(
			$this->table(),
			array( 'id' => $level_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			$this->logger->error(
				sprintf( 'Failed to delete sponsorship level %d: %s', $level_id, $wpdb->last_error ),
				EMS_Logger::CONTEXT_GENERAL
			);
			return new WP_Error( 'delete_failed', __( 'Failed to delete level.', 'event-management-system' ) );
		}

		$this->logger->info(
			sprintf( 'Sponsorship level %d deleted from event %d', $level_id, $level->event_id ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return true;
	}

	/**
	 * Populate default Bronze / Silver / Gold levels for an event.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return array Array of created level IDs.
	 */
	public function populate_defaults( $event_id ) {
		$event_id = absint( $event_id );
		if ( ! $event_id ) {
			return array();
		}

		$defaults = array(
			array(
				'level_name'       => __( 'Bronze', 'event-management-system' ),
				'colour'           => '#CD7F32',
				'value_aud'        => 1000,
				'slots_total'      => 10,
				'recognition_text' => __( 'Logo on event website, acknowledgement in program', 'event-management-system' ),
				'sort_order'       => 1,
			),
			array(
				'level_name'       => __( 'Silver', 'event-management-system' ),
				'colour'           => '#C0C0C0',
				'value_aud'        => 2500,
				'slots_total'      => 5,
				'recognition_text' => __( 'All Bronze benefits + logo on signage, trade display space', 'event-management-system' ),
				'sort_order'       => 2,
			),
			array(
				'level_name'       => __( 'Gold', 'event-management-system' ),
				'colour'           => '#FFD700',
				'value_aud'        => 5000,
				'slots_total'      => 3,
				'recognition_text' => __( 'All Silver benefits + named session sponsorship, prime booth location', 'event-management-system' ),
				'sort_order'       => 3,
			),
		);

		$created_ids = array();

		foreach ( $defaults as $level_data ) {
			$id = $this->add_level( $event_id, $level_data );
			if ( $id ) {
				$created_ids[] = $id;
			}
		}

		$this->logger->info(
			sprintf( 'Default sponsorship levels populated for event %d (%d levels created)', $event_id, count( $created_ids ) ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return $created_ids;
	}

	/**
	 * Get available slots for a level (slots_total - slots_filled).
	 *
	 * @since 1.5.0
	 * @param int $level_id Level ID.
	 * @return int|false Available slots or false if level not found. Returns -1 for unlimited (null slots_total).
	 */
	public function get_available_slots( $level_id ) {
		$level = $this->get_level( $level_id );
		if ( ! $level ) {
			return false;
		}

		// Null slots_total means unlimited
		if ( is_null( $level->slots_total ) ) {
			return -1;
		}

		return max( 0, intval( $level->slots_total ) - intval( $level->slots_filled ) );
	}

	/**
	 * Increment slots_filled when a sponsor is linked.
	 *
	 * @since 1.5.0
	 * @param int $level_id Level ID.
	 * @return bool True on success, false on failure.
	 */
	public function increment_slots_filled( $level_id ) {
		global $wpdb;

		$level_id = absint( $level_id );
		if ( ! $level_id ) {
			return false;
		}

		$level = $this->get_level( $level_id );
		if ( ! $level ) {
			return false;
		}

		// Check slot availability before incrementing
		if ( ! is_null( $level->slots_total ) && intval( $level->slots_filled ) >= intval( $level->slots_total ) ) {
			$this->logger->warning(
				sprintf( 'Cannot increment slots_filled for level %d: all slots are full', $level_id ),
				EMS_Logger::CONTEXT_GENERAL
			);
			return false;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table()} SET slots_filled = slots_filled + 1, updated_at = %s WHERE id = %d",
				current_time( 'mysql' ),
				$level_id
			)
		);

		return false !== $result;
	}

	/**
	 * Decrement slots_filled when a sponsor is unlinked.
	 *
	 * @since 1.5.0
	 * @param int $level_id Level ID.
	 * @return bool True on success, false on failure.
	 */
	public function decrement_slots_filled( $level_id ) {
		global $wpdb;

		$level_id = absint( $level_id );
		if ( ! $level_id ) {
			return false;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table()} SET slots_filled = GREATEST(0, slots_filled - 1), updated_at = %s WHERE id = %d",
				current_time( 'mysql' ),
				$level_id
			)
		);

		return false !== $result;
	}
}
