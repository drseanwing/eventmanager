<?php
/**
 * Ticketing System
 *
 * Manages ticket pricing, promo codes, and ticket type configurations.
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
 * Ticketing System Class
 *
 * @since 1.0.0
 */
class EMS_Ticketing {

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Calculate ticket price with quantity and promo code
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event ID.
	 * @param string $ticket_type Ticket type identifier.
	 * @param int    $quantity Number of tickets.
	 * @param string $promo_code Optional promo code.
	 * @return array Result with success, total_price, original_price, discount_applied
	 */
	public function calculate_ticket_price( $event_id, $ticket_type, $quantity = 1, $promo_code = '' ) {
		try {
			// Get base ticket price
			$base_price = $this->get_ticket_base_price( $event_id, $ticket_type );

			if ( false === $base_price ) {
				$this->logger->warning(
					"Invalid ticket type '{$ticket_type}' for event {$event_id}",
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'Invalid ticket type', 'event-management-system' ),
				);
			}

			$original_price = $base_price * $quantity;
			$discount = 0;
			$promo_applied = false;

			// Apply promo code if provided
			if ( ! empty( $promo_code ) ) {
				$promo_result = $this->apply_promo_code( $event_id, $promo_code, $original_price );
				if ( $promo_result['valid'] ) {
					$discount = $promo_result['discount_amount'];
					$promo_applied = true;
					$this->logger->info(
						"Promo code '{$promo_code}' applied to event {$event_id}, discount: {$discount}",
						EMS_Logger::CONTEXT_REGISTRATION
					);
				} else {
					$this->logger->warning(
						"Invalid promo code '{$promo_code}' for event {$event_id}: {$promo_result['message']}",
						EMS_Logger::CONTEXT_REGISTRATION
					);
					return array(
						'success' => false,
						'message' => $promo_result['message'],
					);
				}
			}

			$total_price = max( 0, $original_price - $discount );

			// Allow filtering of final price
			$total_price = apply_filters( 'ems_ticket_final_price', $total_price, $event_id, $ticket_type, $quantity );

			return array(
				'success'         => true,
				'total_price'     => $total_price,
				'original_price'  => $original_price,
				'discount'        => $discount,
				'promo_applied'   => $promo_applied,
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception calculating ticket price: " . $e->getMessage(),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'success' => false,
				'message' => __( 'Error calculating price', 'event-management-system' ),
			);
		}
	}

	/**
	 * Get base price for a ticket type
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event ID.
	 * @param string $ticket_type Ticket type identifier.
	 * @return float|false Base price or false if invalid
	 */
	private function get_ticket_base_price( $event_id, $ticket_type ) {
		$ticket_config = $this->get_event_ticket_config( $event_id );

		if ( isset( $ticket_config[ $ticket_type ] ) ) {
			return floatval( $ticket_config[ $ticket_type ]['price'] );
		}

		return false;
	}

	/**
	 * Get ticket configuration for an event
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array Ticket configuration array
	 */
	public function get_event_ticket_config( $event_id ) {
		$saved_config = get_post_meta( $event_id, 'ticket_configuration', true );

		if ( ! empty( $saved_config ) && is_array( $saved_config ) ) {
			return $saved_config;
		}

		// Default ticket types if none configured
		return array(
			'standard' => array(
				'name'        => __( 'Standard', 'event-management-system' ),
				'price'       => 0,
				'description' => __( 'Standard registration', 'event-management-system' ),
			),
		);
	}

	/**
	 * Save ticket configuration for an event
	 *
	 * @since 1.0.0
	 * @param int   $event_id Event ID.
	 * @param array $config Ticket configuration array.
	 * @return bool Success status
	 */
	public function save_ticket_configuration( $event_id, $config ) {
		if ( ! is_array( $config ) ) {
			return false;
		}

		$sanitized_config = array();

		foreach ( $config as $type_key => $type_data ) {
			$type_key = sanitize_key( $type_key );
			$sanitized_config[ $type_key ] = array(
				'name'        => sanitize_text_field( $type_data['name'] ?? '' ),
				'price'       => floatval( $type_data['price'] ?? 0 ),
				'description' => sanitize_textarea_field( $type_data['description'] ?? '' ),
			);
		}

		$updated = update_post_meta( $event_id, 'ticket_configuration', $sanitized_config );

		$this->logger->info(
			"Ticket configuration saved for event {$event_id}",
			EMS_Logger::CONTEXT_GENERAL
		);

		return false !== $updated;
	}

	/**
	 * Apply promo code to calculate discount
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event ID.
	 * @param string $promo_code Promo code.
	 * @param float  $original_amount Original amount before discount.
	 * @return array Result with valid, discount_amount, message
	 */
	private function apply_promo_code( $event_id, $promo_code, $original_amount ) {
		$promo_config = $this->get_event_promo_codes( $event_id );
		$promo_code = strtoupper( trim( $promo_code ) );

		if ( ! isset( $promo_config[ $promo_code ] ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid promo code', 'event-management-system' ),
			);
		}

		$promo = $promo_config[ $promo_code ];

		// Check if promo code is active
		if ( ! $promo['active'] ) {
			return array(
				'valid'   => false,
				'message' => __( 'This promo code is no longer active', 'event-management-system' ),
			);
		}

		// Check usage limit
		if ( isset( $promo['usage_limit'] ) && $promo['usage_limit'] > 0 ) {
			$current_usage = $this->get_promo_code_usage_count( $event_id, $promo_code );
			if ( $current_usage >= $promo['usage_limit'] ) {
				return array(
					'valid'   => false,
					'message' => __( 'This promo code has reached its usage limit', 'event-management-system' ),
				);
			}
		}

		// Check expiry date
		if ( ! empty( $promo['expiry_date'] ) ) {
			$expiry_timestamp = strtotime( $promo['expiry_date'] );
			if ( $expiry_timestamp < current_time( 'timestamp' ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'This promo code has expired', 'event-management-system' ),
				);
			}
		}

		// Calculate discount
		$discount_amount = 0;

		if ( 'percentage' === $promo['discount_type'] ) {
			$discount_amount = ( $original_amount * $promo['discount_value'] ) / 100;
		} elseif ( 'fixed' === $promo['discount_type'] ) {
			$discount_amount = $promo['discount_value'];
		}

		// Ensure discount doesn't exceed original amount
		$discount_amount = min( $discount_amount, $original_amount );

		return array(
			'valid'           => true,
			'discount_amount' => $discount_amount,
			'promo_code'      => $promo_code,
			'message'         => __( 'Promo code applied', 'event-management-system' ),
		);
	}

	/**
	 * Get promo code configuration for an event
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array Promo code configuration
	 */
	public function get_event_promo_codes( $event_id ) {
		$saved_codes = get_post_meta( $event_id, 'promo_codes', true );

		if ( ! empty( $saved_codes ) && is_array( $saved_codes ) ) {
			return $saved_codes;
		}

		return array();
	}

	/**
	 * Save promo codes for an event
	 *
	 * @since 1.0.0
	 * @param int   $event_id Event ID.
	 * @param array $promo_codes Promo code configuration.
	 * @return bool Success status
	 */
	public function save_promo_codes( $event_id, $promo_codes ) {
		if ( ! is_array( $promo_codes ) ) {
			return false;
		}

		$sanitized_codes = array();

		foreach ( $promo_codes as $code => $data ) {
			$code = strtoupper( sanitize_text_field( $code ) );
			$sanitized_codes[ $code ] = array(
				'discount_type'  => sanitize_text_field( $data['discount_type'] ?? 'percentage' ),
				'discount_value' => floatval( $data['discount_value'] ?? 0 ),
				'active'         => (bool) ( $data['active'] ?? true ),
				'usage_limit'    => isset( $data['usage_limit'] ) ? absint( $data['usage_limit'] ) : 0,
				'expiry_date'    => ! empty( $data['expiry_date'] ) ? sanitize_text_field( $data['expiry_date'] ) : '',
				'description'    => sanitize_text_field( $data['description'] ?? '' ),
			);
		}

		$updated = update_post_meta( $event_id, 'promo_codes', $sanitized_codes );

		$this->logger->info(
			"Promo codes saved for event {$event_id}",
			EMS_Logger::CONTEXT_GENERAL
		);

		return false !== $updated;
	}

	/**
	 * Get usage count for a promo code
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event ID.
	 * @param string $promo_code Promo code.
	 * @return int Usage count
	 */
	public function get_promo_code_usage_count( $event_id, $promo_code ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE event_id = %d AND promo_code = %s AND status = 'active'",
				$event_id,
				strtoupper( $promo_code )
			)
		);

		return absint( $count );
	}

	/**
	 * Validate promo code without applying
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event ID.
	 * @param string $promo_code Promo code.
	 * @return array Validation result with valid boolean and message
	 */
	public function validate_promo_code( $event_id, $promo_code ) {
		$result = $this->apply_promo_code( $event_id, $promo_code, 100 ); // Test with dummy amount
		
		return array(
			'valid'   => $result['valid'],
			'message' => $result['message'],
		);
	}

	/**
	 * Get available ticket types for display
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array Array of ticket types with labels for display
	 */
	public function get_available_ticket_types( $event_id ) {
		$config = $this->get_event_ticket_config( $event_id );
		$available = array();

		foreach ( $config as $type_key => $type_data ) {
			$available[ $type_key ] = sprintf(
				'%s - $%s',
				$type_data['name'],
				number_format( $type_data['price'], 2 )
			);
		}

		return $available;
	}
}
