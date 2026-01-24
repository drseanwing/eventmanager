<?php
/**
 * Event Registration Management
 *
 * Handles all event registration logic including capacity checks,
 * registration creation, status management, and waitlist integration.
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
 * Event Registration Management Class
 *
 * @since 1.0.0
 */
class EMS_Registration {

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
	 * Ticketing instance
	 *
	 * @var EMS_Ticketing
	 */
	private $ticketing;

	/**
	 * Waitlist instance
	 *
	 * @var EMS_Waitlist
	 */
	private $waitlist;

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
		$this->validator     = new EMS_Validator();
		$this->security      = new EMS_Security();
		$this->ticketing     = new EMS_Ticketing();
		$this->waitlist      = new EMS_Waitlist();
		$this->email_manager = new EMS_Email_Manager();
	}

	/**
	 * Register a participant for an event
	 *
	 * @since 1.0.0
	 * @param array $registration_data Registration data including event_id, user info, ticket type.
	 * @return array Result array with success status and registration_id or error message
	 */
	public function register_participant( $registration_data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		try {
			// Validate required fields
			$required_fields = array( 'event_id', 'email', 'first_name', 'last_name', 'discipline', 'specialty', 'seniority', 'ticket_type' );
			foreach ( $required_fields as $field ) {
				if ( empty( $registration_data[ $field ] ) ) {
					$this->logger->warning(
						"Registration validation failed: Missing required field '{$field}'",
						EMS_Logger::CONTEXT_REGISTRATION
					);
					return array(
						'success' => false,
						'message' => sprintf( __( 'Missing required field: %s', 'event-management-system' ), $field ),
					);
				}
			}

			$event_id = absint( $registration_data['event_id'] );

			// Verify event exists and is accepting registrations
			$event_check = $this->validate_event_registration( $event_id );
			if ( ! $event_check['valid'] ) {
				return array(
					'success' => false,
					'message' => $event_check['message'],
				);
			}

			// Validate email
			if ( ! $this->validator->validate_email( $registration_data['email'] ) ) {
				$this->logger->warning(
					"Invalid email address: {$registration_data['email']}",
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'Invalid email address', 'event-management-system' ),
				);
			}

			// Check for duplicate registration
			// Check by both email AND user_id to cover all cases
			$duplicate_query = "SELECT id FROM {$table_name} WHERE event_id = %d AND status != 'cancelled' AND (email = %s";
			$query_args = array( $event_id, sanitize_email( $registration_data['email'] ) );
			
			// Also check user_id if logged in
			$user_id = isset( $registration_data['user_id'] ) ? absint( $registration_data['user_id'] ) : 0;
			if ( $user_id > 0 ) {
				$duplicate_query .= " OR user_id = %d";
				$query_args[] = $user_id;
			}
			$duplicate_query .= ")";
			
			$existing = $wpdb->get_var(
				$wpdb->prepare( $duplicate_query, $query_args )
			);

			if ( $existing ) {
				$this->logger->info(
					sprintf( 
						'Duplicate registration attempt for event %d by email %s (user_id: %d)',
						$event_id,
						$registration_data['email'],
						$user_id
					),
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'You are already registered for this event', 'event-management-system' ),
				);
			}

			// Check capacity
			$capacity_check = $this->check_event_capacity( $event_id, $registration_data['ticket_quantity'] ?? 1 );
			
			if ( ! $capacity_check['available'] ) {
				// Offer waitlist
				$this->logger->info(
					"Event {$event_id} at capacity. Offering waitlist to {$registration_data['email']}",
					EMS_Logger::CONTEXT_REGISTRATION
				);
				
				$waitlist_result = $this->waitlist->add_to_waitlist( $registration_data );
				
				return array(
					'success'         => false,
					'message'         => __( 'Event is at capacity', 'event-management-system' ),
					'waitlist_offered' => true,
					'waitlist_result' => $waitlist_result,
				);
			}

			// Calculate ticket price
			$ticket_quantity = isset( $registration_data['ticket_quantity'] ) ? absint( $registration_data['ticket_quantity'] ) : 1;
			$price_calc = $this->ticketing->calculate_ticket_price(
				$event_id,
				$registration_data['ticket_type'],
				$ticket_quantity,
				$registration_data['promo_code'] ?? ''
			);

			if ( ! $price_calc['success'] ) {
				return $price_calc;
			}

			// Prepare registration data
			$user_id = get_current_user_id() ?: null;
			
			$insert_data = array(
				'event_id'            => $event_id,
				'user_id'             => $user_id,
				'email'               => sanitize_email( $registration_data['email'] ),
				'first_name'          => sanitize_text_field( $registration_data['first_name'] ),
				'last_name'           => sanitize_text_field( $registration_data['last_name'] ),
				'discipline'          => sanitize_text_field( $registration_data['discipline'] ),
				'specialty'           => sanitize_text_field( $registration_data['specialty'] ),
				'seniority'           => sanitize_text_field( $registration_data['seniority'] ),
				'ticket_type'         => sanitize_text_field( $registration_data['ticket_type'] ),
				'ticket_quantity'     => $ticket_quantity,
				'promo_code'          => ! empty( $registration_data['promo_code'] ) ? sanitize_text_field( $registration_data['promo_code'] ) : null,
				'amount_paid'         => $price_calc['total_price'],
				'payment_status'      => 'pending',
				'registration_date'   => current_time( 'mysql' ),
				'status'              => 'active',
				'special_requirements' => ! empty( $registration_data['special_requirements'] ) ? sanitize_textarea_field( $registration_data['special_requirements'] ) : null,
			);

			// Insert registration
			$inserted = $wpdb->insert(
				$table_name,
				$insert_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				$this->logger->error(
					"Database error creating registration for event {$event_id}: " . $wpdb->last_error,
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'Database error. Please try again.', 'event-management-system' ),
				);
			}

			$registration_id = $wpdb->insert_id;

			// Create or get user account for registrant
			$user_helper = new EMS_User_Helper();
			$user_result = $user_helper->create_or_get_participant(
				EMS_User_Helper::extract_user_data( $registration_data ),
				true // Send notification for new users
			);

			// Link registration to user if successful
			if ( $user_result['success'] && $user_result['user_id'] ) {
				$user_helper->link_registration_to_user( $registration_id, $user_result['user_id'] );
				
				// Update the insert_data user_id for the return
				$user_id = $user_result['user_id'];
				
				$this->logger->info(
					sprintf( 
						'Registration %d linked to user %d (created: %s)',
						$registration_id,
						$user_result['user_id'],
						$user_result['created'] ? 'yes' : 'no'
					),
					EMS_Logger::CONTEXT_REGISTRATION
				);
			}

			// Update event registration count
			$this->update_event_registration_count( $event_id );

			$this->logger->info(
				"Registration {$registration_id} created successfully for event {$event_id} by {$registration_data['email']}",
				EMS_Logger::CONTEXT_REGISTRATION
			);

			// Send confirmation email
			$this->email_manager->send_registration_pending_email( $registration_id );

			// Hook for extensions
			do_action( 'ems_after_registration', $registration_id, $registration_data );

			return array(
				'success'         => true,
				'registration_id' => $registration_id,
				'amount'          => $price_calc['total_price'],
				'payment_required' => $price_calc['total_price'] > 0,
				'message'         => __( 'Registration successful', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception during registration: " . $e->getMessage(),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred. Please try again.', 'event-management-system' ),
			);
		}
	}

	/**
	 * Validate that an event is accepting registrations
	 *
	 * Uses timezone-aware date comparisons to properly handle
	 * datetime-local input values.
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array Validation result with 'valid' boolean and 'message'
	 */
	private function validate_event_registration( $event_id ) {
		$event = get_post( $event_id );

		if ( ! $event || 'ems_event' !== $event->post_type ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid event', 'event-management-system' ),
			);
		}

		if ( 'publish' !== $event->post_status ) {
			return array(
				'valid'   => false,
				'message' => __( 'Event is not available for registration', 'event-management-system' ),
			);
		}

		// Check if registration is explicitly disabled
		$reg_enabled = get_post_meta( $event_id, 'registration_enabled', true );
		if ( '0' === $reg_enabled || 'no' === $reg_enabled ) {
			return array(
				'valid'   => false,
				'message' => __( 'Registration is not available for this event', 'event-management-system' ),
			);
		}

		$reg_open  = get_post_meta( $event_id, 'registration_open_date', true );
		$reg_close = get_post_meta( $event_id, 'registration_close_date', true );

		// Use timezone-aware date comparison
		if ( $reg_open && EMS_Date_Helper::is_future( $reg_open ) ) {
			$this->logger->debug(
				sprintf( 
					'Registration not yet open for event %d. Opens: %s, Current: %s',
					$event_id,
					$reg_open,
					current_time( 'Y-m-d H:i:s' )
				),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'Registration opens on %s', 'event-management-system' ),
					EMS_Date_Helper::format( $reg_open )
				),
			);
		}

		if ( $reg_close && EMS_Date_Helper::has_passed( $reg_close ) ) {
			$this->logger->debug(
				sprintf( 
					'Registration closed for event %d. Closed: %s, Current: %s',
					$event_id,
					$reg_close,
					current_time( 'Y-m-d H:i:s' )
				),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'valid'   => false,
				'message' => __( 'Registration has closed', 'event-management-system' ),
			);
		}

		return array(
			'valid'   => true,
			'message' => '',
		);
	}

	/**
	 * Check event capacity
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @param int $requested_quantity Number of tickets requested.
	 * @return array Capacity check result with 'available', 'remaining', 'max_capacity'
	 */
	public function check_event_capacity( $event_id, $requested_quantity = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$max_capacity = absint( get_post_meta( $event_id, 'max_capacity', true ) );

		// If no capacity limit set, always available
		if ( empty( $max_capacity ) ) {
			return array(
				'available'    => true,
				'remaining'    => -1, // Unlimited
				'max_capacity' => -1,
			);
		}

		// Get current registration count (sum of ticket quantities)
		$current_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(ticket_quantity) FROM {$table_name} WHERE event_id = %d AND status = 'active'",
				$event_id
			)
		);

		$current_count = $current_count ? absint( $current_count ) : 0;
		$remaining     = $max_capacity - $current_count;

		$this->logger->debug(
			"Capacity check for event {$event_id}: {$current_count}/{$max_capacity} ({$remaining} remaining), requesting {$requested_quantity}",
			EMS_Logger::CONTEXT_REGISTRATION
		);

		return array(
			'available'    => $remaining >= $requested_quantity,
			'remaining'    => $remaining,
			'max_capacity' => $max_capacity,
			'current_count' => $current_count,
		);
	}

	/**
	 * Update event's current registration count meta
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return void
	 */
	private function update_event_registration_count( $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(ticket_quantity) FROM {$table_name} WHERE event_id = %d AND status = 'active'",
				$event_id
			)
		);

		$count = $count ? absint( $count ) : 0;
		update_post_meta( $event_id, 'current_registrations', $count );

		$this->logger->debug(
			"Updated registration count for event {$event_id}: {$count}",
			EMS_Logger::CONTEXT_REGISTRATION
		);
	}

	/**
	 * Confirm payment for a registration
	 *
	 * @since 1.0.0
	 * @param int    $registration_id Registration ID.
	 * @param string $payment_reference Payment reference/transaction ID.
	 * @return array Result with success status and message
	 */
	public function confirm_payment( $registration_id, $payment_reference = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		try {
			$registration = $this->get_registration( $registration_id );

			if ( ! $registration ) {
				return array(
					'success' => false,
					'message' => __( 'Registration not found', 'event-management-system' ),
				);
			}

			$update_data = array(
				'payment_status'    => 'completed',
				'payment_reference' => sanitize_text_field( $payment_reference ),
			);

			$updated = $wpdb->update(
				$table_name,
				$update_data,
				array( 'id' => $registration_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				$this->logger->error(
					"Failed to confirm payment for registration {$registration_id}: " . $wpdb->last_error,
					EMS_Logger::CONTEXT_PAYMENT
				);
				return array(
					'success' => false,
					'message' => __( 'Database error', 'event-management-system' ),
				);
			}

			$this->logger->info(
				"Payment confirmed for registration {$registration_id}, reference: {$payment_reference}",
				EMS_Logger::CONTEXT_PAYMENT
			);

			// Send confirmation email
			$this->email_manager->send_registration_confirmed_email( $registration_id );

			// Hook for extensions
			do_action( 'ems_payment_completed', $registration_id, $registration->amount_paid );

			return array(
				'success' => true,
				'message' => __( 'Payment confirmed', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception confirming payment for registration {$registration_id}: " . $e->getMessage(),
				EMS_Logger::CONTEXT_PAYMENT
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred', 'event-management-system' ),
			);
		}
	}

	/**
	 * Get registration by ID
	 *
	 * @since 1.0.0
	 * @param int $registration_id Registration ID.
	 * @return object|null Registration object or null if not found
	 */
	public function get_registration( $registration_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$registration = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$registration_id
			)
		);

		return $registration;
	}

	/**
	 * Get all registrations for an event
	 *
	 * @since 1.0.0
	 * @param int   $event_id Event ID.
	 * @param array $args Query arguments (status, limit, offset, order_by).
	 * @return array Array of registration objects
	 */
	public function get_event_registrations( $event_id, $args = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$defaults = array(
			'status'   => 'active',
			'limit'    => 100,
			'offset'   => 0,
			'order_by' => 'registration_date DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = $wpdb->prepare( 'WHERE event_id = %d', $event_id );

		if ( ! empty( $args['status'] ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $args['status'] );
		}

		$query = "SELECT * FROM {$table_name} {$where} ORDER BY {$args['order_by']} LIMIT {$args['offset']}, {$args['limit']}";

		$registrations = $wpdb->get_results( $query );

		return $registrations;
	}

	/**
	 * Get registrations for a user
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Array of registration objects
	 */
	public function get_user_registrations( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}

		$registrations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d OR email = %s ORDER BY registration_date DESC",
				$user_id,
				$user->user_email
			)
		);

		return $registrations;
	}

	/**
	 * Export registrations to CSV
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return string|false CSV content or false on error
	 */
	public function export_registrations_csv( $event_id ) {
		$registrations = $this->get_event_registrations( $event_id, array( 'limit' => 9999 ) );

		if ( empty( $registrations ) ) {
			return false;
		}

		$csv_output = fopen( 'php://temp', 'r+' );

		// Headers
		$headers = array(
			'Registration ID',
			'First Name',
			'Last Name',
			'Email',
			'Discipline',
			'Specialty',
			'Seniority',
			'Ticket Type',
			'Quantity',
			'Promo Code',
			'Amount Paid',
			'Payment Status',
			'Payment Reference',
			'Registration Date',
			'Status',
			'Special Requirements',
		);

		fputcsv( $csv_output, $headers );

		// Data rows
		foreach ( $registrations as $reg ) {
			$row = array(
				$reg->id,
				$reg->first_name,
				$reg->last_name,
				$reg->email,
				$reg->discipline,
				$reg->specialty,
				$reg->seniority,
				$reg->ticket_type,
				$reg->ticket_quantity,
				$reg->promo_code,
				$reg->amount_paid,
				$reg->payment_status,
				$reg->payment_reference,
				$reg->registration_date,
				$reg->status,
				$reg->special_requirements,
			);
			fputcsv( $csv_output, $row );
		}

		rewind( $csv_output );
		$csv_content = stream_get_contents( $csv_output );
		fclose( $csv_output );

		$this->logger->info(
			"Exported " . count( $registrations ) . " registrations for event {$event_id}",
			EMS_Logger::CONTEXT_REGISTRATION
		);

		return $csv_content;
	}

	/**
	 * Get registration statistics for an event
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @return array Statistics array with counts by discipline, specialty, seniority
	 */
	public function get_registration_statistics( $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$stats = array(
			'total'       => 0,
			'by_discipline' => array(),
			'by_specialty'  => array(),
			'by_seniority'  => array(),
			'by_ticket_type' => array(),
			'payment_stats' => array(
				'total_revenue' => 0,
				'pending'       => 0,
				'completed'     => 0,
			),
		);

		// Get all active registrations
		$registrations = $this->get_event_registrations( $event_id, array( 'status' => 'active', 'limit' => 9999 ) );

		$stats['total'] = count( $registrations );

		foreach ( $registrations as $reg ) {
			// By discipline
			if ( ! isset( $stats['by_discipline'][ $reg->discipline ] ) ) {
				$stats['by_discipline'][ $reg->discipline ] = 0;
			}
			$stats['by_discipline'][ $reg->discipline ] += $reg->ticket_quantity;

			// By specialty
			if ( ! isset( $stats['by_specialty'][ $reg->specialty ] ) ) {
				$stats['by_specialty'][ $reg->specialty ] = 0;
			}
			$stats['by_specialty'][ $reg->specialty ] += $reg->ticket_quantity;

			// By seniority
			if ( ! isset( $stats['by_seniority'][ $reg->seniority ] ) ) {
				$stats['by_seniority'][ $reg->seniority ] = 0;
			}
			$stats['by_seniority'][ $reg->seniority ] += $reg->ticket_quantity;

			// By ticket type
			if ( ! isset( $stats['by_ticket_type'][ $reg->ticket_type ] ) ) {
				$stats['by_ticket_type'][ $reg->ticket_type ] = 0;
			}
			$stats['by_ticket_type'][ $reg->ticket_type ] += $reg->ticket_quantity;

			// Payment stats
			$stats['payment_stats']['total_revenue'] += floatval( $reg->amount_paid );
			
			if ( 'completed' === $reg->payment_status ) {
				$stats['payment_stats']['completed']++;
			} elseif ( 'pending' === $reg->payment_status ) {
				$stats['payment_stats']['pending']++;
			}
		}

		return $stats;
	}

	/**
	 * Cancel a registration
	 *
	 * Marks a registration as cancelled and sends notification email.
	 * Can be called by the user (frontend) or admin (backend).
	 *
	 * @since 1.3.0
	 * @param int    $registration_id Registration ID to cancel.
	 * @param string $cancelled_by    Who cancelled: 'user', 'admin', or 'system'.
	 * @param string $reason          Optional cancellation reason.
	 * @return array Result array with success status and message.
	 */
	public function cancel_registration( $registration_id, $cancelled_by = 'user', $reason = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		try {
			$registration = $this->get_registration( $registration_id );

			if ( ! $registration ) {
				return array(
					'success' => false,
					'message' => __( 'Registration not found', 'event-management-system' ),
				);
			}

			// Check if already cancelled
			if ( 'cancelled' === $registration->status ) {
				return array(
					'success' => false,
					'message' => __( 'Registration is already cancelled', 'event-management-system' ),
				);
			}

			// For user-initiated cancellations, verify ownership
			if ( 'user' === $cancelled_by ) {
				$current_user_id = get_current_user_id();
				$current_user = wp_get_current_user();
				
				// Check if user owns this registration (by user_id or email)
				$is_owner = false;
				if ( $registration->user_id && $registration->user_id == $current_user_id ) {
					$is_owner = true;
				} elseif ( $current_user && $registration->email === $current_user->user_email ) {
					$is_owner = true;
				}

				if ( ! $is_owner ) {
					$this->logger->warning(
						sprintf( 
							'Unauthorized cancellation attempt on registration %d by user %d',
							$registration_id,
							$current_user_id
						),
						EMS_Logger::CONTEXT_SECURITY
					);
					return array(
						'success' => false,
						'message' => __( 'You do not have permission to cancel this registration', 'event-management-system' ),
					);
				}
			}

			// Update registration status
			$update_data = array(
				'status'           => 'cancelled',
				'cancelled_at'     => current_time( 'mysql' ),
				'cancelled_by'     => $cancelled_by,
				'cancellation_reason' => sanitize_textarea_field( $reason ),
			);

			$updated = $wpdb->update(
				$table_name,
				$update_data,
				array( 'id' => $registration_id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				$this->logger->error(
					"Failed to cancel registration {$registration_id}: " . $wpdb->last_error,
					EMS_Logger::CONTEXT_REGISTRATION
				);
				return array(
					'success' => false,
					'message' => __( 'Database error. Please try again.', 'event-management-system' ),
				);
			}

			// Update event registration count
			$this->update_event_registration_count( $registration->event_id );

			$this->logger->info(
				sprintf(
					'Registration %d cancelled by %s. Reason: %s',
					$registration_id,
					$cancelled_by,
					$reason ?: 'Not provided'
				),
				EMS_Logger::CONTEXT_REGISTRATION
			);

			// Send cancellation notification email
			$this->email_manager->send_registration_cancelled_email( $registration_id, $cancelled_by, $reason );

			// Hook for extensions
			do_action( 'ems_registration_cancelled', $registration_id, $registration, $cancelled_by, $reason );

			// Check waitlist for promotion
			$this->waitlist->check_and_promote( $registration->event_id );

			return array(
				'success' => true,
				'message' => __( 'Registration cancelled successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception cancelling registration {$registration_id}: " . $e->getMessage(),
				EMS_Logger::CONTEXT_REGISTRATION
			);
			return array(
				'success' => false,
				'message' => __( 'An error occurred. Please try again.', 'event-management-system' ),
			);
		}
	}

	/**
	 * Check if a registration can be cancelled
	 *
	 * Checks various conditions like event start date, cancellation policy, etc.
	 *
	 * @since 1.3.0
	 * @param int $registration_id Registration ID.
	 * @return array Result with 'can_cancel' boolean and 'reason' if not.
	 */
	public function can_cancel_registration( $registration_id ) {
		$registration = $this->get_registration( $registration_id );

		if ( ! $registration ) {
			return array(
				'can_cancel' => false,
				'reason'     => __( 'Registration not found', 'event-management-system' ),
			);
		}

		// Already cancelled
		if ( 'cancelled' === $registration->status ) {
			return array(
				'can_cancel' => false,
				'reason'     => __( 'Registration is already cancelled', 'event-management-system' ),
			);
		}

		// Check if event has already started
		$event_start = get_post_meta( $registration->event_id, 'event_start_date', true );
		if ( $event_start && EMS_Date_Helper::has_passed( $event_start ) ) {
			return array(
				'can_cancel' => false,
				'reason'     => __( 'Cannot cancel after the event has started', 'event-management-system' ),
			);
		}

		// Check cancellation deadline if set (optional - could add event meta for this)
		$cancellation_deadline = get_post_meta( $registration->event_id, 'cancellation_deadline', true );
		if ( $cancellation_deadline && EMS_Date_Helper::has_passed( $cancellation_deadline ) ) {
			return array(
				'can_cancel' => false,
				'reason'     => __( 'Cancellation deadline has passed', 'event-management-system' ),
			);
		}

		return array(
			'can_cancel' => true,
			'reason'     => '',
		);
	}
}
