<?php
/**
 * Email Manager
 *
 * Handles all email notifications for the Event Management System including
 * registration, abstract submission, review assignments, and event reminders.
 *
 * @package    EventManagementSystem
 * @subpackage Notifications
 * @since      1.0.0
 * @version    1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Email Manager Class
 *
 * Centralized email notification system with support for:
 * - Registration and waitlist emails
 * - Abstract submission workflow emails
 * - Review assignment and decision notifications
 * - Event and session reminders
 *
 * @since 1.0.0
 */
class EMS_Email_Manager {
	
	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * From name for emails
	 *
	 * @var string
	 */
	private $from_name;

	/**
	 * From email address
	 *
	 * @var string
	 */
	private $from_email;

	/**
	 * Constructor
	 *
	 * Initializes email manager with logger and email settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger     = EMS_Logger::instance();
		$this->from_name  = get_option( 'ems_email_from_name', get_bloginfo( 'name' ) );
		$this->from_email = get_option( 'ems_email_from_address', get_option( 'admin_email' ) );
		
		$this->logger->debug( 'Email Manager initialized', EMS_Logger::CONTEXT_EMAIL );
	}

	// ==========================================
	// CORE EMAIL SENDING METHODS
	// ==========================================

	/**
	 * Send email
	 *
	 * Core email sending method with logging and tracking.
	 *
	 * @since 1.0.0
	 * @param string $to Email recipient.
	 * @param string $subject Email subject.
	 * @param string $message Email message (HTML).
	 * @param string $type Email type for logging.
	 * @param int    $entity_id Optional entity ID (event, abstract, session).
	 * @param string $entity_type Optional entity type.
	 * @return bool True if email sent successfully.
	 */
	public function send_email( $to, $subject, $message, $type = 'general', $entity_id = null, $entity_type = null ) {
		try {
			// Prepare headers
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				"From: {$this->from_name} <{$this->from_email}>",
			);

			// Allow filtering of email content
			$to      = apply_filters( 'ems_email_recipient', $to, $type );
			$subject = apply_filters( 'ems_email_subject', $subject, $type );
			$message = apply_filters( 'ems_email_message', $message, $type );
			$headers = apply_filters( 'ems_email_headers', $headers, $type );

			// Send email
			$result = wp_mail( $to, $subject, $message, $headers );

			// Log to database
			$this->log_notification( $type, $to, $subject, $entity_id, $entity_type, $result );

			// Log to file
			if ( $result ) {
				$this->logger->info( "Email sent to {$to} - Type: {$type}", EMS_Logger::CONTEXT_EMAIL );
			} else {
				$this->logger->error( "Email failed to {$to} - Type: {$type}", EMS_Logger::CONTEXT_EMAIL );
			}

			return $result;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Log notification to database
	 *
	 * Records email notification in the notification log table.
	 *
	 * @since 1.1.0
	 * @param string $type Notification type.
	 * @param string $recipient Email recipient.
	 * @param string $subject Email subject.
	 * @param int    $entity_id Optional entity ID.
	 * @param string $entity_type Optional entity type.
	 * @param bool   $sent Whether email was sent successfully.
	 * @return int|false Insert ID on success, false on failure.
	 */
	private function log_notification( $type, $recipient, $subject, $entity_id = null, $entity_type = null, $sent = true ) {
		global $wpdb;
		
		try {
			$result = $wpdb->insert(
				$wpdb->prefix . 'ems_notification_log',
				array(
					'notification_type' => $type,
					'recipient_email'   => $recipient,
					'subject'           => $subject,
					'related_id'        => $entity_id,
					'related_type'      => $entity_type,
					'sent_date'         => current_time( 'mysql' ),
					'status'            => $sent ? 'sent' : 'failed',
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
			);

			return $result ? $wpdb->insert_id : false;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in log_notification: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	// ==========================================
	// CRON JOB METHODS
	// ==========================================

	/**
	 * Send event reminders
	 *
	 * Cron job to send reminders for upcoming events.
	 * Sends reminders 24-48 hours before event start.
	 *
	 * @since 1.0.0
	 */
	public function send_event_reminders() {
		$this->logger->debug( 'Event reminders cron executed', EMS_Logger::CONTEXT_EMAIL );
		
		global $wpdb;
		
		try {
			// Get events starting in next 24-48 hours that haven't had reminders sent
			$tomorrow_start = date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );
			$tomorrow_end   = date( 'Y-m-d H:i:s', strtotime( '+48 hours' ) );
			
			$events = get_posts( array(
				'post_type'      => 'ems_event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'event_start_date',
						'value'   => array( $tomorrow_start, $tomorrow_end ),
						'compare' => 'BETWEEN',
						'type'    => 'DATETIME',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_ems_reminder_sent',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_ems_reminder_sent',
							'value'   => '0',
							'compare' => '=',
						),
					),
				),
			) );
			
			$sent_count = 0;
			foreach ( $events as $event ) {
				// Get active registrations for this event
				$registrations = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE event_id = %d AND status = 'active'",
						$event->ID
					)
				);
				
				foreach ( $registrations as $registration ) {
					$subject = sprintf(
						__( 'Reminder: %s is Tomorrow!', 'event-management-system' ),
						$event->post_title
					);
					
					$message = $this->get_event_reminder_template( array(
						'first_name'  => $registration->first_name,
						'event_title' => $event->post_title,
						'event_date'  => get_post_meta( $event->ID, 'event_start_date', true ),
						'location'    => get_post_meta( $event->ID, 'event_location', true ),
					) );
					
					if ( $this->send_email( $registration->email, $subject, $message, 'event_reminder', $event->ID, 'event' ) ) {
						$sent_count++;
					}
				}
				
				// Mark reminder as sent
				update_post_meta( $event->ID, '_ems_reminder_sent', current_time( 'mysql' ) );
			}
			
			$this->logger->info( "Event reminders sent: {$sent_count}", EMS_Logger::CONTEXT_EMAIL );
			
		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_event_reminders: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
		}
	}

	/**
	 * Send session reminders
	 *
	 * Cron job to send reminders for upcoming sessions.
	 * Sends reminders 24 hours before session start.
	 *
	 * @since 1.0.0
	 */
	public function send_session_reminders() {
		$this->logger->debug( 'Session reminders cron executed', EMS_Logger::CONTEXT_EMAIL );
		
		global $wpdb;
		
		try {
			// Get sessions starting in next 24 hours that haven't had reminders sent
			$now          = current_time( 'mysql' );
			$tomorrow     = date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );
			
			$sessions = get_posts( array(
				'post_type'      => 'ems_session',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'start_datetime',
						'value'   => array( $now, $tomorrow ),
						'compare' => 'BETWEEN',
						'type'    => 'DATETIME',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_ems_session_reminder_sent',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_ems_session_reminder_sent',
							'value'   => '0',
							'compare' => '=',
						),
					),
				),
			) );
			
			$sent_count = 0;
			foreach ( $sessions as $session ) {
				$result = $this->send_session_reminder( $session->ID );
				if ( $result['success'] ) {
					$sent_count += $result['sent'];
					// Mark reminder as sent
					update_post_meta( $session->ID, '_ems_session_reminder_sent', current_time( 'mysql' ) );
				}
			}
			
			$this->logger->info( "Session reminders sent: {$sent_count}", EMS_Logger::CONTEXT_EMAIL );
			
		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_session_reminders: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
		}
	}

	// ==========================================
	// REGISTRATION EMAIL METHODS (PHASE 1)
	// ==========================================

	/**
	 * Send registration pending email
	 *
	 * Sent when a participant completes registration but payment is pending.
	 *
	 * @since 1.0.0
	 * @param int $registration_id Registration ID.
	 * @return bool Success status.
	 */
	public function send_registration_pending_email( $registration_id ) {
		try {
			global $wpdb;
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d",
					$registration_id
				)
			);

			if ( ! $registration ) {
				$this->logger->warning(
					"Registration not found for ID: {$registration_id}",
					EMS_Logger::CONTEXT_EMAIL
				);
				return false;
			}

			$event   = get_post( $registration->event_id );
			$subject = sprintf(
				__( 'Registration Received - %s', 'event-management-system' ),
				$event->post_title
			);
			
			$message = sprintf(
				'<p>Dear %s,</p><p>Thank you for registering for <strong>%s</strong>.</p><p><strong>Registration ID:</strong> %d</p><p><strong>Amount Due:</strong> $%s</p>',
				esc_html( $registration->first_name ),
				esc_html( $event->post_title ),
				$registration_id,
				number_format( $registration->amount_paid, 2 )
			);

			return $this->send_email(
				$registration->email,
				$subject,
				$message,
				'registration_pending',
				$registration->event_id,
				'event'
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_registration_pending_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send registration confirmed email
	 *
	 * Sent when payment is confirmed and registration is complete.
	 *
	 * @since 1.0.0
	 * @param int $registration_id Registration ID.
	 * @return bool Success status.
	 */
	public function send_registration_confirmed_email( $registration_id ) {
		try {
			global $wpdb;
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d",
					$registration_id
				)
			);

			if ( ! $registration ) {
				return false;
			}

			$event   = get_post( $registration->event_id );
			$subject = sprintf(
				__( 'Registration Confirmed - %s', 'event-management-system' ),
				$event->post_title
			);
			
			$message = sprintf(
				'<p>Dear %s,</p><p>Your registration for <strong>%s</strong> has been confirmed!</p><p><strong>Registration ID:</strong> %d</p>',
				esc_html( $registration->first_name ),
				esc_html( $event->post_title ),
				$registration_id
			);

			return $this->send_email(
				$registration->email,
				$subject,
				$message,
				'registration_confirmed',
				$registration->event_id,
				'event'
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_registration_confirmed_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send registration cancelled email
	 *
	 * Sent when a registration is cancelled (by user, admin, or system).
	 *
	 * @since 1.3.0
	 * @param int    $registration_id Registration ID.
	 * @param string $cancelled_by    Who cancelled: 'user', 'admin', or 'system'.
	 * @param string $reason          Optional cancellation reason.
	 * @return bool Success status.
	 */
	public function send_registration_cancelled_email( $registration_id, $cancelled_by = 'user', $reason = '' ) {
		try {
			global $wpdb;
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d",
					$registration_id
				)
			);

			if ( ! $registration ) {
				return false;
			}

			$event = get_post( $registration->event_id );
			if ( ! $event ) {
				return false;
			}

			// Build cancellation reason text
			$cancelled_by_text = '';
			switch ( $cancelled_by ) {
				case 'admin':
					$cancelled_by_text = __( 'This cancellation was initiated by the event administrator.', 'event-management-system' );
					break;
				case 'system':
					$cancelled_by_text = __( 'This cancellation was processed automatically by the system.', 'event-management-system' );
					break;
				default:
					$cancelled_by_text = __( 'You have successfully cancelled your registration.', 'event-management-system' );
			}

			$reason_text = '';
			if ( ! empty( $reason ) ) {
				$reason_text = sprintf(
					'<p><strong>%s</strong> %s</p>',
					__( 'Reason:', 'event-management-system' ),
					esc_html( $reason )
				);
			}

			// Refund information if payment was made
			$refund_info = '';
			if ( $registration->amount_paid > 0 && 'completed' === $registration->payment_status ) {
				$refund_info = sprintf(
					'<div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin: 15px 0;">
						<strong>%s</strong><br>
						%s
					</div>',
					__( 'Refund Information', 'event-management-system' ),
					__( 'If you paid for this registration, please contact us regarding your refund. Refunds are typically processed within 5-10 business days.', 'event-management-system' )
				);
			}

			$subject = sprintf(
				__( 'Registration Cancelled - %s', 'event-management-system' ),
				$event->post_title
			);

			$message = sprintf(
				'<p>%s %s,</p>
				<p>%s <strong>%s</strong> %s.</p>
				%s
				%s
				<div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 15px 0;">
					<strong>%s</strong><br>
					%s: %s<br>
					%s: %s<br>
					%s: %s
				</div>
				<p>%s</p>',
				__( 'Dear', 'event-management-system' ),
				esc_html( $registration->first_name ),
				__( 'Your registration for', 'event-management-system' ),
				esc_html( $event->post_title ),
				__( 'has been cancelled', 'event-management-system' ),
				$cancelled_by_text,
				$reason_text,
				__( 'Cancelled Registration Details', 'event-management-system' ),
				__( 'Registration ID', 'event-management-system' ),
				$registration_id,
				__( 'Ticket Type', 'event-management-system' ),
				esc_html( ucfirst( $registration->ticket_type ) ),
				__( 'Cancellation Date', 'event-management-system' ),
				current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				$refund_info
			);

			// Add re-registration info if event is still accepting registrations
			$reg_handler = new EMS_Registration();
			$event_check = $reg_handler->check_event_capacity( $registration->event_id );
			if ( $event_check['available'] ) {
				$message .= sprintf(
					'<p>%s <a href="%s">%s</a></p>',
					__( 'Changed your mind?', 'event-management-system' ),
					esc_url( get_permalink( $registration->event_id ) ),
					__( 'Register again', 'event-management-system' )
				);
			}

			$result = $this->send_email(
				$registration->email,
				$subject,
				$message,
				'registration_cancelled',
				$registration->event_id,
				'event'
			);

			// Also notify admin if cancelled by user
			if ( 'user' === $cancelled_by ) {
				$this->send_registration_cancelled_admin_notification( $registration_id, $reason );
			}

			return $result;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_registration_cancelled_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send admin notification when user cancels registration
	 *
	 * @since 1.3.0
	 * @param int    $registration_id Registration ID.
	 * @param string $reason          Optional cancellation reason.
	 * @return bool Success status.
	 */
	private function send_registration_cancelled_admin_notification( $registration_id, $reason = '' ) {
		try {
			global $wpdb;
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d",
					$registration_id
				)
			);

			if ( ! $registration ) {
				return false;
			}

			$event = get_post( $registration->event_id );
			if ( ! $event ) {
				return false;
			}

			$admin_email = get_option( 'admin_email' );
			
			$subject = sprintf(
				__( '[%s] Registration Cancelled: %s', 'event-management-system' ),
				get_bloginfo( 'name' ),
				$event->post_title
			);

			$reason_text = ! empty( $reason ) 
				? sprintf( '<p><strong>%s</strong> %s</p>', __( 'Reason given:', 'event-management-system' ), esc_html( $reason ) )
				: '';

			$message = sprintf(
				'<p>%s</p>
				<div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 15px 0;">
					<strong>%s</strong><br>
					%s: %s %s<br>
					%s: %s<br>
					%s: %s<br>
					%s: %s
				</div>
				%s
				<p><a href="%s">%s</a></p>',
				__( 'A participant has cancelled their registration:', 'event-management-system' ),
				esc_html( $event->post_title ),
				__( 'Name', 'event-management-system' ),
				esc_html( $registration->first_name ),
				esc_html( $registration->last_name ),
				__( 'Email', 'event-management-system' ),
				esc_html( $registration->email ),
				__( 'Ticket Type', 'event-management-system' ),
				esc_html( ucfirst( $registration->ticket_type ) ),
				__( 'Original Registration Date', 'event-management-system' ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $registration->registration_date ) ) ),
				$reason_text,
				esc_url( admin_url( 'admin.php?page=ems-registrations&event_id=' . $registration->event_id ) ),
				__( 'View all registrations for this event', 'event-management-system' )
			);

			return $this->send_email(
				$admin_email,
				$subject,
				$message,
				'registration_cancelled_admin',
				$registration->event_id,
				'event'
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_registration_cancelled_admin_notification: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send waitlist confirmation email
	 *
	 * Sent when a participant is added to the event waitlist.
	 *
	 * @since 1.0.0
	 * @param int $waitlist_id Waitlist entry ID.
	 * @return bool Success status.
	 */
	public function send_waitlist_confirmation_email( $waitlist_id ) {
		try {
			global $wpdb;
			$entry = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_waitlist WHERE id = %d",
					$waitlist_id
				)
			);

			if ( ! $entry ) {
				return false;
			}

			$event   = get_post( $entry->event_id );
			$subject = sprintf(
				__( 'Waitlist Confirmation - %s', 'event-management-system' ),
				$event->post_title
			);
			
			$message = sprintf(
				'<p>Dear %s,</p><p>You have been added to the waitlist for <strong>%s</strong>.</p><p><strong>Current Position:</strong> %d</p>',
				esc_html( $entry->first_name ),
				esc_html( $event->post_title ),
				$entry->position
			);

			return $this->send_email(
				$entry->email,
				$subject,
				$message,
				'waitlist_confirmation',
				$entry->event_id,
				'event'
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_waitlist_confirmation_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send waitlist spot available email
	 *
	 * Sent when a spot opens up for a waitlisted participant.
	 *
	 * @since 1.0.0
	 * @param int $waitlist_id Waitlist entry ID.
	 * @return bool Success status.
	 */
	public function send_waitlist_spot_available_email( $waitlist_id ) {
		try {
			global $wpdb;
			$entry = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_waitlist WHERE id = %d",
					$waitlist_id
				)
			);

			if ( ! $entry ) {
				return false;
			}

			$event            = get_post( $entry->event_id );
			$registration_url = get_permalink( $entry->event_id );
			$subject          = sprintf(
				__( 'Spot Available - %s', 'event-management-system' ),
				$event->post_title
			);
			
			$message = sprintf(
				'<p>Dear %s,</p><p>Good news! A spot has become available for <strong>%s</strong>.</p><p><a href="%s">Register Now</a></p>',
				esc_html( $entry->first_name ),
				esc_html( $event->post_title ),
				esc_url( $registration_url )
			);

			return $this->send_email(
				$entry->email,
				$subject,
				$message,
				'waitlist_spot_available',
				$entry->event_id,
				'event'
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_waitlist_spot_available_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send invoice email
	 *
	 * Sent with invoice details for manual payment.
	 *
	 * @since 1.0.0
	 * @param int    $registration_id Registration ID.
	 * @param string $invoice_number Invoice number.
	 * @param string $invoice_html Invoice HTML content.
	 * @return bool Success status.
	 */
	public function send_invoice_email( $registration_id, $invoice_number, $invoice_html ) {
		try {
			global $wpdb;
			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d",
					$registration_id
				)
			);

			if ( ! $registration ) {
				return false;
			}

			$event   = get_post( $registration->event_id );
			$subject = sprintf(
				__( 'Invoice %s - %s', 'event-management-system' ),
				$invoice_number,
				$event->post_title
			);

			return $this->send_email(
				$registration->email,
				$subject,
				$invoice_html,
				'invoice',
				$registration->event_id,
				'event'
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_invoice_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	// ==========================================
	// ABSTRACT EMAIL METHODS (PHASE 2)
	// ==========================================

	/**
	 * Send abstract submitted confirmation email
	 *
	 * Sent to the primary author when their abstract is submitted.
	 *
	 * @since 1.1.0
	 * @param int $abstract_id Abstract post ID.
	 * @return bool True if email sent successfully.
	 */
	public function send_abstract_submitted_email( $abstract_id ) {
		try {
			$abstract = get_post( $abstract_id );
			if ( ! $abstract ) {
				return false;
			}

			// Get primary author
			$author_id = get_post_meta( $abstract_id, 'primary_author_id', true );
			$author    = get_userdata( $author_id );
			
			if ( ! $author ) {
				return false;
			}

			// Get event details
			$event_id    = get_post_meta( $abstract_id, 'abstract_event_id', true );
			$event       = get_post( $event_id );
			$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );

			// Prepare email data
			$to      = $author->user_email;
			$subject = sprintf(
				__( 'Abstract Submitted: %s', 'event-management-system' ),
				$abstract->post_title
			);

			$message = $this->get_abstract_submitted_template( array(
				'author_name'     => $author->display_name,
				'abstract_title'  => $abstract->post_title,
				'event_title'     => $event_title,
				'abstract_type'   => ucfirst( get_post_meta( $abstract_id, 'abstract_type', true ) ),
				'submission_date' => get_post_meta( $abstract_id, 'submission_date', true ),
			) );

			// Send email
			return $this->send_email( $to, $subject, $message, 'abstract_submitted', $abstract_id, 'abstract' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_abstract_submitted_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send review assigned email
	 *
	 * Notifies a reviewer that they've been assigned an abstract to review.
	 *
	 * @since 1.1.0
	 * @param int $abstract_id Abstract post ID.
	 * @param int $reviewer_id Reviewer user ID.
	 * @return bool True if email sent successfully.
	 */
	public function send_review_assigned_email( $abstract_id, $reviewer_id ) {
		try {
			$abstract = get_post( $abstract_id );
			$reviewer = get_userdata( $reviewer_id );
			
			if ( ! $abstract || ! $reviewer ) {
				return false;
			}

			// Get event details
			$event_id    = get_post_meta( $abstract_id, 'abstract_event_id', true );
			$event       = get_post( $event_id );
			$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );

			// Prepare email data
			$to      = $reviewer->user_email;
			$subject = sprintf(
				__( 'Review Assignment: %s', 'event-management-system' ),
				$abstract->post_title
			);

			// Generate review link
			$review_url = add_query_arg(
				array(
					'page'        => 'ems-reviewer-dashboard',
					'abstract_id' => $abstract_id,
				),
				admin_url( 'admin.php' )
			);

			$message = $this->get_review_assigned_template( array(
				'reviewer_name'  => $reviewer->display_name,
				'abstract_title' => $abstract->post_title,
				'event_title'    => $event_title,
				'abstract_type'  => ucfirst( get_post_meta( $abstract_id, 'abstract_type', true ) ),
				'review_url'     => $review_url,
			) );

			// Send email
			return $this->send_email( $to, $subject, $message, 'review_assigned', $abstract_id, 'abstract' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_review_assigned_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send abstract approved email
	 *
	 * Notifies author that their abstract has been approved.
	 *
	 * @since 1.1.0
	 * @param int $abstract_id Abstract post ID.
	 * @return bool True if email sent successfully.
	 */
	public function send_abstract_approved_email( $abstract_id ) {
		try {
			$abstract = get_post( $abstract_id );
			if ( ! $abstract ) {
				return false;
			}

			// Get primary author
			$author_id = get_post_meta( $abstract_id, 'primary_author_id', true );
			$author    = get_userdata( $author_id );
			
			if ( ! $author ) {
				return false;
			}

			// Get event details
			$event_id    = get_post_meta( $abstract_id, 'abstract_event_id', true );
			$event       = get_post( $event_id );
			$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );

			// Prepare email data
			$to      = $author->user_email;
			$subject = sprintf(
				__( 'Abstract Approved: %s', 'event-management-system' ),
				$abstract->post_title
			);

			// Get review statistics
			$review_manager = new EMS_Abstract_Review();
			$stats          = $review_manager->get_review_statistics( $abstract_id );

			$message = $this->get_abstract_approved_template( array(
				'author_name'    => $author->display_name,
				'abstract_title' => $abstract->post_title,
				'event_title'    => $event_title,
				'average_score'  => isset( $stats['average_score'] ) ? $stats['average_score'] : 0,
				'approval_date'  => current_time( 'mysql' ),
			) );

			// Send email
			return $this->send_email( $to, $subject, $message, 'abstract_approved', $abstract_id, 'abstract' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_abstract_approved_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send abstract rejected email
	 *
	 * Notifies author that their abstract was not accepted.
	 *
	 * @since 1.1.0
	 * @param int $abstract_id Abstract post ID.
	 * @return bool True if email sent successfully.
	 */
	public function send_abstract_rejected_email( $abstract_id ) {
		try {
			$abstract = get_post( $abstract_id );
			if ( ! $abstract ) {
				return false;
			}

			// Get primary author
			$author_id = get_post_meta( $abstract_id, 'primary_author_id', true );
			$author    = get_userdata( $author_id );
			
			if ( ! $author ) {
				return false;
			}

			// Get event details
			$event_id    = get_post_meta( $abstract_id, 'abstract_event_id', true );
			$event       = get_post( $event_id );
			$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );

			// Prepare email data
			$to      = $author->user_email;
			$subject = sprintf(
				__( 'Abstract Review Decision: %s', 'event-management-system' ),
				$abstract->post_title
			);

			// Get consolidated feedback
			$review_manager = new EMS_Abstract_Review();
			$reviews        = $review_manager->get_abstract_reviews( $abstract_id );
			$feedback       = array();
			
			foreach ( $reviews as $review ) {
				if ( ! empty( $review->comments ) ) {
					$feedback[] = $review->comments;
				}
			}

			$message = $this->get_abstract_rejected_template( array(
				'author_name'    => $author->display_name,
				'abstract_title' => $abstract->post_title,
				'event_title'    => $event_title,
				'feedback'       => implode( "\n\n", $feedback ),
			) );

			// Send email
			return $this->send_email( $to, $subject, $message, 'abstract_rejected', $abstract_id, 'abstract' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_abstract_rejected_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send revision requested email
	 *
	 * Notifies author that revisions are requested for their abstract.
	 *
	 * @since 1.1.0
	 * @param int $abstract_id Abstract post ID.
	 * @return bool True if email sent successfully.
	 */
	public function send_revision_requested_email( $abstract_id ) {
		try {
			$abstract = get_post( $abstract_id );
			if ( ! $abstract ) {
				return false;
			}

			// Get primary author
			$author_id = get_post_meta( $abstract_id, 'primary_author_id', true );
			$author    = get_userdata( $author_id );
			
			if ( ! $author ) {
				return false;
			}

			// Get event details
			$event_id    = get_post_meta( $abstract_id, 'abstract_event_id', true );
			$event       = get_post( $event_id );
			$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );

			// Prepare email data
			$to      = $author->user_email;
			$subject = sprintf(
				__( 'Revision Requested: %s', 'event-management-system' ),
				$abstract->post_title
			);

			// Get revision comments
			$revision_comments = get_post_meta( $abstract_id, 'revision_comments', true );

			// Get consolidated feedback
			$review_manager = new EMS_Abstract_Review();
			$reviews        = $review_manager->get_abstract_reviews( $abstract_id );
			$feedback       = array();
			
			foreach ( $reviews as $review ) {
				if ( ! empty( $review->comments ) ) {
					$feedback[] = $review->comments;
				}
			}

			// Generate edit URL
			$edit_url = add_query_arg(
				array(
					'action'      => 'edit_abstract',
					'abstract_id' => $abstract_id,
				),
				home_url()
			);

			$message = $this->get_revision_requested_template( array(
				'author_name'        => $author->display_name,
				'abstract_title'     => $abstract->post_title,
				'event_title'        => $event_title,
				'revision_comments'  => $revision_comments,
				'feedback'           => implode( "\n\n", $feedback ),
				'edit_url'           => $edit_url,
			) );

			// Send email
			return $this->send_email( $to, $subject, $message, 'revision_requested', $abstract_id, 'abstract' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_revision_requested_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	// ==========================================
	// EMAIL TEMPLATE METHODS
	// ==========================================

	/**
	 * Get abstract submitted email template
	 *
	 * Returns HTML template for abstract submission confirmation.
	 *
	 * @since 1.1.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_abstract_submitted_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;">Abstract Submitted Successfully</h2>
			
			<p>Dear <?php echo esc_html( $data['author_name'] ); ?>,</p>
			
			<p>Thank you for submitting your abstract to <strong><?php echo esc_html( $data['event_title'] ); ?></strong>.</p>
			
			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Title:</strong> <?php echo esc_html( $data['abstract_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>Type:</strong> <?php echo esc_html( $data['abstract_type'] ); ?></p>
				<p style="margin: 5px 0;"><strong>Submitted:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['submission_date'] ) ) ); ?></p>
			</div>
			
			<p>Your abstract is now under review. You will be notified via email when the review process is complete.</p>
			
			<p><strong>Next Steps:</strong></p>
			<ul>
				<li>Your abstract will be assigned to reviewers</li>
				<li>The review process typically takes 2-4 weeks</li>
				<li>You can track the status in your presenter dashboard</li>
			</ul>
			
			<p>If you have any questions, please don't hesitate to contact us.</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get review assigned email template
	 *
	 * Returns HTML template for reviewer assignment notification.
	 *
	 * @since 1.1.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_review_assigned_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;">Abstract Review Assignment</h2>
			
			<p>Dear <?php echo esc_html( $data['reviewer_name'] ); ?>,</p>
			
			<p>You have been assigned to review an abstract for <strong><?php echo esc_html( $data['event_title'] ); ?></strong>.</p>
			
			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Title:</strong> <?php echo esc_html( $data['abstract_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>Type:</strong> <?php echo esc_html( $data['abstract_type'] ); ?></p>
			</div>
			
			<p><strong>Review Requirements:</strong></p>
			<ul>
				<li>Score the abstract on a scale of 1-10</li>
				<li>Provide detailed comments</li>
				<li>Recommend: Approve, Reject, or Request Revision</li>
			</ul>
			
			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['review_url'] ); ?>" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					Start Review
				</a>
			</div>
			
			<p>Please complete your review within the next two weeks.</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get abstract approved email template
	 *
	 * Returns HTML template for abstract approval notification.
	 *
	 * @since 1.1.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_abstract_approved_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #28a745;">🎉 Abstract Approved!</h2>
			
			<p>Dear <?php echo esc_html( $data['author_name'] ); ?>,</p>
			
			<p>Congratulations! Your abstract has been approved for <strong><?php echo esc_html( $data['event_title'] ); ?></strong>.</p>
			
			<div style="background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Title:</strong> <?php echo esc_html( $data['abstract_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>Average Score:</strong> <?php echo esc_html( number_format( $data['average_score'], 1 ) ); ?>/10</p>
				<p style="margin: 5px 0;"><strong>Approved:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['approval_date'] ) ) ); ?></p>
			</div>
			
			<p><strong>Next Steps:</strong></p>
			<ul>
				<li>Your abstract will be scheduled for presentation</li>
				<li>You will receive scheduling details soon</li>
				<li>Please prepare your presentation materials</li>
				<li>Update your presenter bio if needed</li>
			</ul>
			
			<p>We look forward to your presentation at the event!</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get abstract rejected email template
	 *
	 * Returns HTML template for abstract rejection notification.
	 *
	 * @since 1.1.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_abstract_rejected_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #dc3545;">Abstract Review Decision</h2>
			
			<p>Dear <?php echo esc_html( $data['author_name'] ); ?>,</p>
			
			<p>Thank you for submitting your abstract to <strong><?php echo esc_html( $data['event_title'] ); ?></strong>.</p>
			
			<p>After careful review, we regret to inform you that your abstract was not selected for presentation at this time.</p>
			
			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Title:</strong> <?php echo esc_html( $data['abstract_title'] ); ?></p>
			</div>
			
			<?php if ( ! empty( $data['feedback'] ) ) : ?>
			<p><strong>Reviewer Feedback:</strong></p>
			<div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; white-space: pre-line;">
				<?php echo esc_html( $data['feedback'] ); ?>
			</div>
			<?php endif; ?>
			
			<p>We encourage you to consider submitting to future events. The feedback provided may help strengthen future submissions.</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get revision requested email template
	 *
	 * Returns HTML template for revision request notification.
	 *
	 * @since 1.1.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_revision_requested_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #ffc107;">Revision Requested</h2>
			
			<p>Dear <?php echo esc_html( $data['author_name'] ); ?>,</p>
			
			<p>Your abstract for <strong><?php echo esc_html( $data['event_title'] ); ?></strong> has been reviewed, and the reviewers have requested some revisions.</p>
			
			<div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Title:</strong> <?php echo esc_html( $data['abstract_title'] ); ?></p>
			</div>
			
			<p><strong>Overall Comments:</strong></p>
			<div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; white-space: pre-line;">
				<?php echo esc_html( $data['revision_comments'] ); ?>
			</div>
			
			<?php if ( ! empty( $data['feedback'] ) ) : ?>
			<p><strong>Detailed Reviewer Feedback:</strong></p>
			<div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; white-space: pre-line;">
				<?php echo esc_html( $data['feedback'] ); ?>
			</div>
			<?php endif; ?>
			
			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['edit_url'] ); ?>" style="background-color: #ffc107; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					Edit Abstract
				</a>
			</div>
			
			<p>Please review the feedback carefully and update your abstract accordingly. Once revised, your abstract will be reviewed again.</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	// ==========================================
	// SCHEDULE EMAIL METHODS (PHASE 3)
	// ==========================================

	/**
	 * Send schedule published email
	 *
	 * Notifies registered participants that the event schedule is now available.
	 *
	 * @since 1.2.0
	 * @param int $event_id Event ID.
	 * @return array Results array with sent count.
	 */
	public function send_schedule_published_email( $event_id ) {
		global $wpdb;

		try {
			$event = get_post( $event_id );
			if ( ! $event ) {
				return array(
					'success' => false,
					'message' => __( 'Event not found', 'event-management-system' ),
				);
			}

			// Get all active registrations for this event
			$registrations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE event_id = %d AND status = 'active'",
					$event_id
				)
			);

			if ( empty( $registrations ) ) {
				return array(
					'success' => true,
					'sent'    => 0,
					'message' => __( 'No registrations found for this event', 'event-management-system' ),
				);
			}

			$sent = 0;
			foreach ( $registrations as $registration ) {
				$to      = $registration->email;
				$subject = sprintf(
					__( 'Event Schedule Published: %s', 'event-management-system' ),
					$event->post_title
				);

				$schedule_url = get_permalink( $event_id ) . '#schedule';

				$message = $this->get_schedule_published_template( array(
					'first_name'   => $registration->first_name,
					'event_title'  => $event->post_title,
					'schedule_url' => $schedule_url,
				) );

				if ( $this->send_email( $to, $subject, $message, 'schedule_published', $event_id, 'event' ) ) {
					$sent++;
				}
			}

			$this->logger->info(
				"Schedule published emails sent: {$sent} of " . count( $registrations ),
				EMS_Logger::CONTEXT_EMAIL
			);

			return array(
				'success' => true,
				'sent'    => $sent,
				'total'   => count( $registrations ),
				'message' => sprintf(
					__( 'Schedule notification sent to %d participants', 'event-management-system' ),
					$sent
				),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_schedule_published_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return array(
				'success' => false,
				'message' => __( 'Failed to send schedule notifications', 'event-management-system' ),
			);
		}
	}

	/**
	 * Send session registration confirmation
	 *
	 * Confirms a participant's registration for a specific session.
	 *
	 * @since 1.2.0
	 * @param int $session_id      Session ID.
	 * @param int $registration_id Event registration ID.
	 * @return bool True if sent successfully.
	 */
	public function send_session_registration_email( $session_id, $registration_id ) {
		global $wpdb;

		try {
			$session = get_post( $session_id );
			if ( ! $session ) {
				return false;
			}

			$registration = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_registrations WHERE id = %d",
					$registration_id
				)
			);

			if ( ! $registration ) {
				return false;
			}

			$event_id = get_post_meta( $session_id, 'event_id', true );
			$event    = get_post( $event_id );

			$to      = $registration->email;
			$subject = sprintf(
				__( 'Session Registration: %s', 'event-management-system' ),
				$session->post_title
			);

			$message = $this->get_session_registration_template( array(
				'first_name'     => $registration->first_name,
				'session_title'  => $session->post_title,
				'event_title'    => $event ? $event->post_title : '',
				'start_datetime' => get_post_meta( $session_id, 'start_datetime', true ),
				'end_datetime'   => get_post_meta( $session_id, 'end_datetime', true ),
				'location'       => get_post_meta( $session_id, 'location', true ),
			) );

			return $this->send_email( $to, $subject, $message, 'session_registration', $session_id, 'session' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_session_registration_email: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send session reminder
	 *
	 * Sends reminder email for upcoming session (typically 24 hours before).
	 *
	 * @since 1.2.0
	 * @param int $session_id Session ID.
	 * @return array Results array.
	 */
	public function send_session_reminder( $session_id ) {
		global $wpdb;

		try {
			$session = get_post( $session_id );
			if ( ! $session ) {
				return array( 'success' => false, 'sent' => 0 );
			}

			// Get all registrants for this session
			$registrations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT r.* 
					FROM {$wpdb->prefix}ems_registrations r
					INNER JOIN {$wpdb->prefix}ems_session_registrations sr ON r.id = sr.registration_id
					WHERE sr.session_id = %d AND r.status = 'active'",
					$session_id
				)
			);

			if ( empty( $registrations ) ) {
				return array( 'success' => true, 'sent' => 0 );
			}

			$event_id = get_post_meta( $session_id, 'event_id', true );
			$event    = get_post( $event_id );

			$sent = 0;
			foreach ( $registrations as $registration ) {
				$to      = $registration->email;
				$subject = sprintf(
					__( 'Reminder: %s Tomorrow', 'event-management-system' ),
					$session->post_title
				);

				$message = $this->get_session_reminder_template( array(
					'first_name'     => $registration->first_name,
					'session_title'  => $session->post_title,
					'event_title'    => $event ? $event->post_title : '',
					'start_datetime' => get_post_meta( $session_id, 'start_datetime', true ),
					'end_datetime'   => get_post_meta( $session_id, 'end_datetime', true ),
					'location'       => get_post_meta( $session_id, 'location', true ),
				) );

				if ( $this->send_email( $to, $subject, $message, 'session_reminder', $session_id, 'session' ) ) {
					$sent++;
				}
			}

			return array(
				'success' => true,
				'sent'    => $sent,
				'total'   => count( $registrations ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_session_reminder: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return array( 'success' => false, 'sent' => 0 );
		}
	}

	// ==========================================
	// SCHEDULE EMAIL TEMPLATES
	// ==========================================

	/**
	 * Get schedule published email template
	 *
	 * @since 1.2.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_schedule_published_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;">📅 Event Schedule Now Available!</h2>
			
			<p>Dear <?php echo esc_html( $data['first_name'] ); ?>,</p>
			
			<p>Great news! The schedule for <strong><?php echo esc_html( $data['event_title'] ); ?></strong> has been published.</p>
			
			<div style="background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0;">
				<p style="margin: 5px 0;">You can now:</p>
				<ul style="margin: 10px 0;">
					<li>View the complete event schedule</li>
					<li>Register for individual sessions</li>
					<li>Build your personal schedule</li>
					<li>Download to your calendar</li>
				</ul>
			</div>
			
			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['schedule_url'] ); ?>" style="background-color: #2196f3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					View Event Schedule
				</a>
			</div>
			
			<p><strong>Important:</strong> Some sessions have limited capacity and registration is on a first-come, first-served basis.</p>
			
			<p>We look forward to seeing you at the event!</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get session registration confirmation template
	 *
	 * @since 1.2.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_session_registration_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #28a745;">✓ Session Registration Confirmed</h2>
			
			<p>Dear <?php echo esc_html( $data['first_name'] ); ?>,</p>
			
			<p>You have successfully registered for the following session:</p>
			
			<div style="background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Session:</strong> <?php echo esc_html( $data['session_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>Event:</strong> <?php echo esc_html( $data['event_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>When:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $data['start_datetime'] ) ) ); ?></p>
				<p style="margin: 5px 0;"><strong>Duration:</strong> <?php echo esc_html( date( 'g:i A', strtotime( $data['start_datetime'] ) ) . ' - ' . date( 'g:i A', strtotime( $data['end_datetime'] ) ) ); ?></p>
				<p style="margin: 5px 0;"><strong>Location:</strong> <?php echo esc_html( $data['location'] ); ?></p>
			</div>
			
			<p>A reminder will be sent 24 hours before the session begins.</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get session reminder template
	 *
	 * @since 1.2.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_session_reminder_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #ff9800;">⏰ Session Reminder</h2>
			
			<p>Dear <?php echo esc_html( $data['first_name'] ); ?>,</p>
			
			<p>This is a friendly reminder about your upcoming session tomorrow:</p>
			
			<div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Session:</strong> <?php echo esc_html( $data['session_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>When:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $data['start_datetime'] ) ) ); ?></p>
				<p style="margin: 5px 0;"><strong>Time:</strong> <?php echo esc_html( date( 'g:i A', strtotime( $data['start_datetime'] ) ) . ' - ' . date( 'g:i A', strtotime( $data['end_datetime'] ) ) ); ?></p>
				<p style="margin: 5px 0;"><strong>Location:</strong> <?php echo esc_html( $data['location'] ); ?></p>
			</div>
			
			<p><strong>Tips:</strong></p>
			<ul>
				<li>Arrive 5-10 minutes early</li>
				<li>Bring any necessary materials</li>
				<li>Check for any last-minute room changes</li>
			</ul>
			
			<p>We look forward to seeing you there!</p>
			
			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get event reminder email template
	 *
	 * @since 1.2.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_event_reminder_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #ff9800;">⏰ Event Reminder</h2>

			<p>Dear <?php echo esc_html( $data['first_name'] ); ?>,</p>

			<p>This is a friendly reminder that <strong><?php echo esc_html( $data['event_title'] ); ?></strong> is coming up!</p>

			<div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ff9800; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong>Event:</strong> <?php echo esc_html( $data['event_title'] ); ?></p>
				<p style="margin: 5px 0;"><strong>Date:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $data['event_date'] ) ) ); ?></p>
				<p style="margin: 5px 0;"><strong>Location:</strong> <?php echo esc_html( $data['location'] ); ?></p>
			</div>

			<p><strong>Don't forget:</strong></p>
			<ul>
				<li>Plan your journey to arrive on time</li>
				<li>Bring your confirmation email or registration details</li>
				<li>Check the event schedule for session times</li>
			</ul>

			<p>We look forward to seeing you at the event!</p>

			<p>Best regards,<br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> Team</p>
		</div>
		<?php
		return ob_get_clean();
	}

	// ==========================================
	// SPONSORSHIP EMAIL METHODS (PHASE 9)
	// ==========================================

	/**
	 * Send sponsor onboarding confirmation email
	 *
	 * Sent when a new sponsor account is created.
	 *
	 * @since 1.5.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @return bool True if sent successfully.
	 */
	public function send_onboarding_confirmation( $sponsor_id ) {
		try {
			$sponsor = get_post( $sponsor_id );
			if ( ! $sponsor ) {
				return false;
			}

			// Get sponsor meta data
			$contact_email = get_post_meta( $sponsor_id, '_ems_sponsor_contact_email', true );
			$contact_name  = get_post_meta( $sponsor_id, '_ems_sponsor_contact_name', true );

			if ( empty( $contact_email ) ) {
				$this->logger->warning(
					"No contact email found for sponsor ID: {$sponsor_id}",
					EMS_Logger::CONTEXT_EMAIL
				);
				return false;
			}

			// Get sponsor user account if exists
			$user_id = get_post_meta( $sponsor_id, '_ems_sponsor_user_id', true );
			$user    = $user_id ? get_userdata( $user_id ) : null;

			$to      = $contact_email;
			$subject = sprintf(
				__( 'Welcome to %s - Sponsor Account Created', 'event-management-system' ),
				get_bloginfo( 'name' )
			);

			$message = $this->get_sponsor_onboarding_template( array(
				'sponsor_name' => ! empty( $contact_name ) ? $contact_name : $sponsor->post_title,
				'login_url'    => $user ? wp_login_url() : '',
				'portal_url'   => home_url( '/sponsor-portal/' ),
				'site_name'    => get_bloginfo( 'name' ),
				'has_user'     => (bool) $user,
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_onboarding_confirmation', $sponsor_id, 'sponsor' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_onboarding_confirmation: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send new sponsor admin notification
	 *
	 * Notifies admin when a new sponsor registers.
	 *
	 * @since 1.5.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @return bool True if sent successfully.
	 */
	public function send_onboarding_admin_notification( $sponsor_id ) {
		try {
			$sponsor = get_post( $sponsor_id );
			if ( ! $sponsor ) {
				return false;
			}

			$to      = get_option( 'admin_email' );
			$subject = sprintf(
				__( '[%s] New Sponsor Registration: %s', 'event-management-system' ),
				get_bloginfo( 'name' ),
				$sponsor->post_title
			);

			$message = $this->get_sponsor_onboarding_admin_template( array(
				'sponsor_name'   => $sponsor->post_title,
				'organisation'   => get_post_meta( $sponsor_id, '_ems_sponsor_legal_name', true ),
				'abn'            => get_post_meta( $sponsor_id, '_ems_sponsor_abn', true ),
				'contact_email'  => get_post_meta( $sponsor_id, '_ems_sponsor_contact_email', true ),
				'admin_edit_url' => admin_url( 'post.php?post=' . $sponsor_id . '&action=edit' ),
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_onboarding_admin', $sponsor_id, 'sponsor' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_onboarding_admin_notification: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send EOI received confirmation
	 *
	 * Confirms that the EOI was received by the sponsor.
	 *
	 * @since 1.5.0
	 * @param int $eoi_id EOI ID from database.
	 * @return bool True if sent successfully.
	 */
	public function send_eoi_confirmation( $eoi_id ) {
		global $wpdb;

		try {
			$eoi = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
					$eoi_id
				)
			);

			if ( ! $eoi ) {
				return false;
			}

			$sponsor = get_post( $eoi->sponsor_id );
			$event   = get_post( $eoi->event_id );

			if ( ! $sponsor || ! $event ) {
				return false;
			}

			$contact_email = get_post_meta( $eoi->sponsor_id, '_ems_sponsor_contact_email', true );
			$contact_name  = get_post_meta( $eoi->sponsor_id, '_ems_sponsor_contact_name', true );

			if ( empty( $contact_email ) ) {
				return false;
			}

			$to      = $contact_email;
			$subject = sprintf(
				__( 'EOI Received for %s', 'event-management-system' ),
				$event->post_title
			);

			$message = $this->get_eoi_confirmation_template( array(
				'sponsor_name'    => ! empty( $contact_name ) ? $contact_name : $sponsor->post_title,
				'event_name'      => $event->post_title,
				'preferred_level' => $eoi->preferred_level,
				'submitted_date'  => $eoi->submitted_at,
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_eoi_confirmation', $eoi->event_id, 'event' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_eoi_confirmation: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send EOI admin notification
	 *
	 * Notifies admin when a sponsor submits an EOI.
	 *
	 * @since 1.5.0
	 * @param int $eoi_id EOI ID from database.
	 * @return bool True if sent successfully.
	 */
	public function send_eoi_admin_notification( $eoi_id ) {
		global $wpdb;

		try {
			$eoi = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
					$eoi_id
				)
			);

			if ( ! $eoi ) {
				return false;
			}

			$sponsor = get_post( $eoi->sponsor_id );
			$event   = get_post( $eoi->event_id );

			if ( ! $sponsor || ! $event ) {
				return false;
			}

			$to      = get_option( 'admin_email' );
			$subject = sprintf(
				__( '[%s] New Sponsorship EOI: %s for %s', 'event-management-system' ),
				get_bloginfo( 'name' ),
				$sponsor->post_title,
				$event->post_title
			);

			$admin_eoi_url = admin_url( 'admin.php?page=ems-sponsor-eoi&event_id=' . $eoi->event_id );

			$message = $this->get_eoi_admin_notification_template( array(
				'sponsor_name'    => $sponsor->post_title,
				'event_name'      => $event->post_title,
				'preferred_level' => $eoi->preferred_level,
				'estimated_value' => $eoi->estimated_value,
				'admin_eoi_url'   => $admin_eoi_url,
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_eoi_admin', $eoi->event_id, 'event' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_eoi_admin_notification: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send EOI approved notification
	 *
	 * Notifies sponsor that their EOI has been approved.
	 *
	 * @since 1.5.0
	 * @param int $eoi_id EOI ID from database.
	 * @return bool True if sent successfully.
	 */
	public function send_eoi_approved( $eoi_id ) {
		global $wpdb;

		try {
			$eoi = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
					$eoi_id
				)
			);

			if ( ! $eoi ) {
				return false;
			}

			$sponsor = get_post( $eoi->sponsor_id );
			$event   = get_post( $eoi->event_id );

			if ( ! $sponsor || ! $event ) {
				return false;
			}

			// Get assigned level from sponsor_events meta
			$sponsor_events = get_post_meta( $eoi->sponsor_id, 'sponsor_events', true );
			if ( ! is_array( $sponsor_events ) ) {
				$sponsor_events = array();
			}

			$assigned_level = isset( $sponsor_events[ $eoi->event_id ]['level'] )
				? $sponsor_events[ $eoi->event_id ]['level']
				: $eoi->preferred_level;

			// Get level details
			$levels_manager = new EMS_Sponsorship_Levels();
			$levels = $levels_manager->get_event_levels( $eoi->event_id );
			$level_details = null;
			foreach ( $levels as $level ) {
				if ( $level->level_slug === $assigned_level ) {
					$level_details = $level;
					break;
				}
			}
			$recognition = $level_details ? $level_details->recognition_text : '';

			$contact_email = get_post_meta( $eoi->sponsor_id, '_ems_sponsor_contact_email', true );
			$contact_name  = get_post_meta( $eoi->sponsor_id, '_ems_sponsor_contact_name', true );

			if ( empty( $contact_email ) ) {
				return false;
			}

			$to      = $contact_email;
			$subject = sprintf(
				__( 'Sponsorship Approved: %s', 'event-management-system' ),
				$event->post_title
			);

			$message = $this->get_eoi_approved_template( array(
				'sponsor_name'     => ! empty( $contact_name ) ? $contact_name : $sponsor->post_title,
				'event_name'       => $event->post_title,
				'assigned_level'   => ucfirst( str_replace( '_', ' ', $assigned_level ) ),
				'recognition_text' => $recognition,
				'next_steps_url'   => home_url( '/sponsor-portal/' ),
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_eoi_approved', $eoi->event_id, 'event' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_eoi_approved: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send EOI rejected notification
	 *
	 * Notifies sponsor that their EOI has been declined.
	 *
	 * @since 1.5.0
	 * @param int    $eoi_id EOI ID from database.
	 * @param string $reason Optional rejection reason.
	 * @return bool True if sent successfully.
	 */
	public function send_eoi_rejected( $eoi_id, $reason = '' ) {
		global $wpdb;

		try {
			$eoi = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
					$eoi_id
				)
			);

			if ( ! $eoi ) {
				return false;
			}

			$sponsor = get_post( $eoi->sponsor_id );
			$event   = get_post( $eoi->event_id );

			if ( ! $sponsor || ! $event ) {
				return false;
			}

			$contact_email = get_post_meta( $eoi->sponsor_id, '_ems_sponsor_contact_email', true );
			$contact_name  = get_post_meta( $eoi->sponsor_id, '_ems_sponsor_contact_name', true );

			if ( empty( $contact_email ) ) {
				return false;
			}

			// Use review_notes from EOI if no reason provided
			if ( empty( $reason ) && ! empty( $eoi->review_notes ) ) {
				$reason = $eoi->review_notes;
			}

			$to      = $contact_email;
			$subject = sprintf(
				__( 'Sponsorship Update: %s', 'event-management-system' ),
				$event->post_title
			);

			$message = $this->get_eoi_rejected_template( array(
				'sponsor_name'     => ! empty( $contact_name ) ? $contact_name : $sponsor->post_title,
				'event_name'       => $event->post_title,
				'rejection_reason' => $reason,
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_eoi_rejected', $eoi->event_id, 'event' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_eoi_rejected: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	/**
	 * Send sponsorship level changed notification
	 *
	 * Notifies sponsor when their sponsorship level is updated.
	 *
	 * @since 1.5.0
	 * @param int    $sponsor_id Sponsor post ID.
	 * @param int    $event_id   Event post ID.
	 * @param string $old_level  Old level slug.
	 * @param string $new_level  New level slug.
	 * @return bool True if sent successfully.
	 */
	public function send_level_changed( $sponsor_id, $event_id, $old_level, $new_level ) {
		try {
			$sponsor = get_post( $sponsor_id );
			$event   = get_post( $event_id );

			if ( ! $sponsor || ! $event ) {
				return false;
			}

			$contact_email = get_post_meta( $sponsor_id, '_ems_sponsor_contact_email', true );
			$contact_name  = get_post_meta( $sponsor_id, '_ems_sponsor_contact_name', true );

			if ( empty( $contact_email ) ) {
				return false;
			}

			$to      = $contact_email;
			$subject = sprintf(
				__( 'Sponsorship Level Update: %s', 'event-management-system' ),
				$event->post_title
			);

			$message = $this->get_level_changed_template( array(
				'sponsor_name' => ! empty( $contact_name ) ? $contact_name : $sponsor->post_title,
				'event_name'   => $event->post_title,
				'old_level'    => ucfirst( str_replace( '_', ' ', $old_level ) ),
				'new_level'    => ucfirst( str_replace( '_', ' ', $new_level ) ),
			) );

			return $this->send_email( $to, $subject, $message, 'sponsor_level_changed', $event_id, 'event' );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in send_level_changed: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_EMAIL
			);
			return false;
		}
	}

	// ==========================================
	// SPONSORSHIP EMAIL TEMPLATES
	// ==========================================

	/**
	 * Get sponsor onboarding confirmation template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_sponsor_onboarding_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;">Welcome to <?php echo esc_html( $data['site_name'] ); ?>!</h2>

			<p>Dear <?php echo esc_html( $data['sponsor_name'] ); ?>,</p>

			<p>Thank you for becoming a sponsor with <strong><?php echo esc_html( $data['site_name'] ); ?></strong>. Your sponsor account has been successfully created.</p>

			<div style="background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Next Steps:', 'event-management-system' ); ?></strong></p>
				<ul style="margin: 10px 0;">
					<?php if ( $data['has_user'] ) : ?>
					<li><?php echo esc_html__( 'Log in to your account to access the sponsor portal', 'event-management-system' ); ?></li>
					<?php endif; ?>
					<li><?php echo esc_html__( 'Browse available events and express interest', 'event-management-system' ); ?></li>
					<li><?php echo esc_html__( 'Submit your sponsorship EOI (Expression of Interest)', 'event-management-system' ); ?></li>
					<li><?php echo esc_html__( 'Track your sponsorships and upload materials', 'event-management-system' ); ?></li>
				</ul>
			</div>

			<?php if ( $data['has_user'] ) : ?>
			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['login_url'] ); ?>" style="background-color: #2196f3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					<?php echo esc_html__( 'Log In to Your Account', 'event-management-system' ); ?>
				</a>
			</div>
			<?php endif; ?>

			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['portal_url'] ); ?>" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					<?php echo esc_html__( 'Visit Sponsor Portal', 'event-management-system' ); ?>
				</a>
			</div>

			<p><?php echo esc_html__( 'If you have any questions about the sponsorship process, please contact us.', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'Best regards,', 'event-management-system' ); ?><br>
			<?php echo esc_html( $data['site_name'] ); ?> <?php echo esc_html__( 'Team', 'event-management-system' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get sponsor onboarding admin notification template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_sponsor_onboarding_admin_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;"><?php echo esc_html__( 'New Sponsor Registration', 'event-management-system' ); ?></h2>

			<p><?php echo esc_html__( 'A new sponsor has registered on your site.', 'event-management-system' ); ?></p>

			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Sponsor Name:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['sponsor_name'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Organisation:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['organisation'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'ABN:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['abn'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Contact Email:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['contact_email'] ); ?></p>
			</div>

			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['admin_edit_url'] ); ?>" style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					<?php echo esc_html__( 'Review Sponsor Details', 'event-management-system' ); ?>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get EOI confirmation template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_eoi_confirmation_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #28a745;"><?php echo esc_html__( 'EOI Received', 'event-management-system' ); ?></h2>

			<p>Dear <?php echo esc_html( $data['sponsor_name'] ); ?>,</p>

			<p><?php echo esc_html__( 'Thank you for submitting your Expression of Interest for', 'event-management-system' ); ?> <strong><?php echo esc_html( $data['event_name'] ); ?></strong>.</p>

			<div style="background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Event:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['event_name'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Preferred Level:', 'event-management-system' ); ?></strong> <?php echo esc_html( ucfirst( str_replace( '_', ' ', $data['preferred_level'] ) ) ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Submitted:', 'event-management-system' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['submitted_date'] ) ) ); ?></p>
			</div>

			<p><strong><?php echo esc_html__( 'What Happens Next:', 'event-management-system' ); ?></strong></p>
			<ul>
				<li><?php echo esc_html__( 'Our team will review your EOI within 5-7 business days', 'event-management-system' ); ?></li>
				<li><?php echo esc_html__( 'We may contact you if additional information is needed', 'event-management-system' ); ?></li>
				<li><?php echo esc_html__( 'You will receive an email with our decision', 'event-management-system' ); ?></li>
			</ul>

			<p><?php echo esc_html__( 'Thank you for your interest in supporting our event.', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'Best regards,', 'event-management-system' ); ?><br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> <?php echo esc_html__( 'Team', 'event-management-system' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get EOI admin notification template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_eoi_admin_notification_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2c3e50;"><?php echo esc_html__( 'New Sponsorship EOI', 'event-management-system' ); ?></h2>

			<p><?php echo esc_html__( 'A sponsor has submitted an Expression of Interest for one of your events.', 'event-management-system' ); ?></p>

			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Sponsor:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['sponsor_name'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Event:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['event_name'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Preferred Level:', 'event-management-system' ); ?></strong> <?php echo esc_html( ucfirst( str_replace( '_', ' ', $data['preferred_level'] ) ) ); ?></p>
				<?php if ( ! empty( $data['estimated_value'] ) ) : ?>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Estimated Value:', 'event-management-system' ); ?></strong> $<?php echo esc_html( number_format( $data['estimated_value'], 2 ) ); ?></p>
				<?php endif; ?>
			</div>

			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['admin_eoi_url'] ); ?>" style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					<?php echo esc_html__( 'Review EOI', 'event-management-system' ); ?>
				</a>
			</div>

			<p><?php echo esc_html__( 'Please review and respond to this EOI at your earliest convenience.', 'event-management-system' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get EOI approved template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_eoi_approved_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #28a745;">🎉 <?php echo esc_html__( 'Sponsorship Approved!', 'event-management-system' ); ?></h2>

			<p>Dear <?php echo esc_html( $data['sponsor_name'] ); ?>,</p>

			<p><?php echo esc_html__( 'Great news! Your sponsorship application for', 'event-management-system' ); ?> <strong><?php echo esc_html( $data['event_name'] ); ?></strong> <?php echo esc_html__( 'has been approved.', 'event-management-system' ); ?></p>

			<div style="background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Event:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['event_name'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Sponsorship Level:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['assigned_level'] ); ?></p>
			</div>

			<?php if ( ! empty( $data['recognition_text'] ) ) : ?>
			<div style="background-color: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Recognition Benefits:', 'event-management-system' ); ?></strong></p>
				<p style="margin: 10px 0; white-space: pre-line;"><?php echo esc_html( $data['recognition_text'] ); ?></p>
			</div>
			<?php endif; ?>

			<p><strong><?php echo esc_html__( 'Next Steps:', 'event-management-system' ); ?></strong></p>
			<ul>
				<li><?php echo esc_html__( 'Visit the sponsor portal to upload your logo and materials', 'event-management-system' ); ?></li>
				<li><?php echo esc_html__( 'Review and confirm the sponsorship agreement', 'event-management-system' ); ?></li>
				<li><?php echo esc_html__( 'Track event updates and communications', 'event-management-system' ); ?></li>
			</ul>

			<div style="margin: 30px 0;">
				<a href="<?php echo esc_url( $data['next_steps_url'] ); ?>" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
					<?php echo esc_html__( 'Access Sponsor Portal', 'event-management-system' ); ?>
				</a>
			</div>

			<p><?php echo esc_html__( 'We look forward to working with you on this event!', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'Best regards,', 'event-management-system' ); ?><br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> <?php echo esc_html__( 'Team', 'event-management-system' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get EOI rejected template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_eoi_rejected_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #6c757d;"><?php echo esc_html__( 'Sponsorship Update', 'event-management-system' ); ?></h2>

			<p>Dear <?php echo esc_html( $data['sponsor_name'] ); ?>,</p>

			<p><?php echo esc_html__( 'Thank you for your interest in sponsoring', 'event-management-system' ); ?> <strong><?php echo esc_html( $data['event_name'] ); ?></strong>.</p>

			<p><?php echo esc_html__( 'After careful consideration, we are unable to accept your sponsorship proposal at this time.', 'event-management-system' ); ?></p>

			<?php if ( ! empty( $data['rejection_reason'] ) ) : ?>
			<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Additional Information:', 'event-management-system' ); ?></strong></p>
				<p style="margin: 10px 0; white-space: pre-line;"><?php echo esc_html( $data['rejection_reason'] ); ?></p>
			</div>
			<?php endif; ?>

			<p><?php echo esc_html__( 'We appreciate your interest in supporting our events and encourage you to consider future opportunities with us. We will keep your details on file for upcoming events that may be a better fit.', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'If you have any questions or would like to discuss alternative sponsorship opportunities, please feel free to contact us.', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'Best regards,', 'event-management-system' ); ?><br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> <?php echo esc_html__( 'Team', 'event-management-system' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get sponsorship level changed template
	 *
	 * @since 1.5.0
	 * @param array $data Template data.
	 * @return string HTML email content.
	 */
	private function get_level_changed_template( $data ) {
		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
			<h2 style="color: #2196f3;"><?php echo esc_html__( 'Sponsorship Level Update', 'event-management-system' ); ?></h2>

			<p>Dear <?php echo esc_html( $data['sponsor_name'] ); ?>,</p>

			<p><?php echo esc_html__( 'Your sponsorship level for', 'event-management-system' ); ?> <strong><?php echo esc_html( $data['event_name'] ); ?></strong> <?php echo esc_html__( 'has been updated.', 'event-management-system' ); ?></p>

			<div style="background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0;">
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'Previous Level:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['old_level'] ); ?></p>
				<p style="margin: 5px 0;"><strong><?php echo esc_html__( 'New Level:', 'event-management-system' ); ?></strong> <?php echo esc_html( $data['new_level'] ); ?></p>
			</div>

			<p><?php echo esc_html__( 'This change may affect your recognition benefits and sponsorship entitlements. Please visit the sponsor portal to review your updated benefits package.', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'If you have any questions about this change, please contact us.', 'event-management-system' ); ?></p>

			<p><?php echo esc_html__( 'Best regards,', 'event-management-system' ); ?><br>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?> <?php echo esc_html__( 'Team', 'event-management-system' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}
}
