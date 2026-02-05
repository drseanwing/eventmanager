<?php
/**
 * Payment Gateway Abstract Class and Invoice Generator
 *
 * Provides payment gateway abstraction for extensibility and includes
 * a default invoice generator for manual payment processing.
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
 * Abstract Payment Gateway Class
 *
 * All payment gateway implementations should extend this class.
 *
 * @since 1.0.0
 */
abstract class EMS_Payment_Gateway {

	/**
	 * Gateway ID
	 *
	 * @var string
	 */
	protected $gateway_id;

	/**
	 * Gateway name
	 *
	 * @var string
	 */
	protected $gateway_name;

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Process payment
	 *
	 * @since 1.0.0
	 * @param int   $registration_id Registration ID.
	 * @param float $amount Amount to charge.
	 * @return array Result with success, transaction_id, message
	 */
	abstract public function process_payment( $registration_id, $amount );

	/**
	 * Verify payment status
	 *
	 * @since 1.0.0
	 * @param string $transaction_id Transaction ID.
	 * @return array Result with success, status, message
	 */
	abstract public function verify_payment( $transaction_id );

	/**
	 * Process refund
	 *
	 * @since 1.0.0
	 * @param string $transaction_id Original transaction ID.
	 * @param float  $amount Amount to refund.
	 * @return array Result with success, refund_id, message
	 */
	abstract public function refund_payment( $transaction_id, $amount );

	/**
	 * Get payment URL for user to complete payment
	 *
	 * @since 1.0.0
	 * @param int $registration_id Registration ID.
	 * @return string|false Payment URL or false
	 */
	abstract public function get_payment_url( $registration_id );

	/**
	 * Get gateway ID
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id() {
		return $this->gateway_id;
	}

	/**
	 * Get gateway name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name() {
		return $this->gateway_name;
	}
}

/**
 * Invoice Generator Payment Gateway
 *
 * Default gateway that generates invoices and sends via email for manual payment.
 *
 * @since 1.0.0
 */
class EMS_Invoice_Gateway extends EMS_Payment_Gateway {

	/**
	 * Registration instance
	 *
	 * @var EMS_Registration
	 */
	private $registration;

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
		parent::__construct();
		
		$this->gateway_id    = 'invoice';
		$this->gateway_name  = __( 'Invoice / Manual Payment', 'event-management-system' );
		$this->registration  = new EMS_Registration();
		$this->email_manager = new EMS_Email_Manager();
	}

	/**
	 * Process payment (generate invoice and send)
	 *
	 * @since 1.0.0
	 * @param int   $registration_id Registration ID.
	 * @param float $amount Amount to charge.
	 * @return array Result with success, transaction_id, message
	 */
	public function process_payment( $registration_id, $amount ) {
		try {
			$registration = $this->registration->get_registration( $registration_id );

			if ( ! $registration ) {
				return array(
					'success' => false,
					'message' => __( 'Registration not found', 'event-management-system' ),
				);
			}

			// Generate invoice number
			$invoice_number = $this->generate_invoice_number( $registration_id );

			// Generate invoice HTML
			$invoice_html = $this->generate_invoice_html( $registration, $invoice_number, $amount );

			// In a full implementation, you might generate a PDF here
			// For now, we'll send the HTML invoice via email

			$email_sent = $this->email_manager->send_invoice_email( $registration_id, $invoice_number, $invoice_html );

			if ( $email_sent ) {
				$this->logger->info(
					"Invoice {$invoice_number} generated and sent for registration {$registration_id}",
					EMS_Logger::CONTEXT_PAYMENT
				);

				return array(
					'success'        => true,
					'transaction_id' => $invoice_number,
					'invoice_number' => $invoice_number,
					'message'        => __( 'Invoice sent to your email', 'event-management-system' ),
				);
			} else {
				$this->logger->error(
					"Failed to send invoice for registration {$registration_id}",
					EMS_Logger::CONTEXT_PAYMENT
				);

				return array(
					'success' => false,
					'message' => __( 'Failed to send invoice', 'event-management-system' ),
				);
			}

		} catch ( Exception $e ) {
			$this->logger->error(
				"Exception processing invoice payment: " . $e->getMessage(),
				EMS_Logger::CONTEXT_PAYMENT
			);

			return array(
				'success' => false,
				'message' => __( 'An error occurred', 'event-management-system' ),
			);
		}
	}

	/**
	 * Verify payment status (manual verification)
	 *
	 * @since 1.0.0
	 * @param string $transaction_id Transaction/Invoice ID.
	 * @return array Result with success, status, message
	 */
	public function verify_payment( $transaction_id ) {
		// For invoice gateway, payment verification is manual by admin
		// Check database for payment status

		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';

		$payment_status = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT payment_status FROM {$table_name} WHERE payment_reference = %s",
				$transaction_id
			)
		);

		if ( $payment_status ) {
			return array(
				'success' => true,
				'status'  => $payment_status,
				'message' => sprintf( __( 'Payment status: %s', 'event-management-system' ), $payment_status ),
			);
		}

		return array(
			'success' => false,
			'message' => __( 'Payment not found', 'event-management-system' ),
		);
	}

	/**
	 * Process refund (manual process)
	 *
	 * @since 1.0.0
	 * @param string $transaction_id Original transaction ID.
	 * @param float  $amount Amount to refund.
	 * @return array Result with success, refund_id, message
	 */
	public function refund_payment( $transaction_id, $amount ) {
		// For invoice gateway, refunds are processed manually
		// Log the refund request

		$this->logger->info(
			"Refund requested for invoice {$transaction_id}, amount: {$amount}",
			EMS_Logger::CONTEXT_PAYMENT
		);

		return array(
			'success'   => true,
			'refund_id' => 'MANUAL-REFUND-' . time(),
			'message'   => __( 'Refund will be processed manually. Admin has been notified.', 'event-management-system' ),
		);
	}

	/**
	 * Get payment URL (returns null for invoice gateway)
	 *
	 * @since 1.0.0
	 * @param int $registration_id Registration ID.
	 * @return string|false
	 */
	public function get_payment_url( $registration_id ) {
		// Invoice gateway doesn't have a payment URL
		// Payment is made via bank transfer or other manual method
		return false;
	}

	/**
	 * Generate unique invoice number
	 *
	 * @since 1.0.0
	 * @param int $registration_id Registration ID.
	 * @return string Invoice number
	 */
	private function generate_invoice_number( $registration_id ) {
		$prefix = get_option( 'ems_invoice_prefix', 'INV' );
		$year   = date( 'Y' );
		$number = str_pad( $registration_id, 6, '0', STR_PAD_LEFT );

		return "{$prefix}-{$year}-{$number}";
	}

	/**
	 * Generate invoice HTML
	 *
	 * @since 1.0.0
	 * @param object $registration Registration object.
	 * @param string $invoice_number Invoice number.
	 * @param float  $amount Amount.
	 * @return string Invoice HTML
	 */
	private function generate_invoice_html( $registration, $invoice_number, $amount ) {
		$event = get_post( $registration->event_id );
		$event_date = get_post_meta( $registration->event_id, 'event_start_date', true );
		$org_name = get_option( 'ems_organization_name', get_bloginfo( 'name' ) );
		$org_address = get_option( 'ems_organization_address', '' );
		$payment_instructions = get_option( 'ems_payment_instructions', __( 'Please contact us for payment instructions.', 'event-management-system' ) );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title><?php echo esc_html( __( 'Invoice', 'event-management-system' ) ); ?></title>
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
				.invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
				.invoice-header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
				.invoice-header h1 { margin: 0; font-size: 28px; }
				.invoice-number { color: #666; font-size: 14px; }
				.company-details { margin-bottom: 30px; }
				.billing-details { margin-bottom: 30px; }
				.invoice-details { margin-bottom: 30px; background: #f9f9f9; padding: 15px; }
				table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
				th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
				th { background: #f5f5f5; font-weight: bold; }
				.total-row { font-weight: bold; font-size: 16px; background: #f0f0f0; }
				.payment-instructions { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-top: 20px; }
				.footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
			</style>
		</head>
		<body>
			<div class="invoice-container">
				<div class="invoice-header">
					<h1><?php echo esc_html( __( 'INVOICE', 'event-management-system' ) ); ?></h1>
					<div class="invoice-number"><?php echo esc_html( $invoice_number ); ?></div>
					<div class="invoice-date"><?php echo esc_html( __( 'Date:', 'event-management-system' ) ); ?> <?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></div>
				</div>

				<div class="company-details">
					<strong><?php echo esc_html( $org_name ); ?></strong><br>
					<?php if ( ! empty( $org_address ) ) : ?>
						<?php echo nl2br( esc_html( $org_address ) ); ?>
					<?php endif; ?>
				</div>

				<div class="billing-details">
					<strong><?php echo esc_html( __( 'Bill To:', 'event-management-system' ) ); ?></strong><br>
					<?php echo esc_html( $registration->first_name . ' ' . $registration->last_name ); ?><br>
					<?php echo esc_html( $registration->email ); ?><br>
					<?php echo esc_html( ucfirst( $registration->discipline ) ); ?> - <?php echo esc_html( ucfirst( $registration->seniority ) ); ?>
				</div>

				<div class="invoice-details">
					<strong><?php echo esc_html( __( 'Event:', 'event-management-system' ) ); ?></strong> <?php echo esc_html( $event->post_title ); ?><br>
					<?php if ( $event_date ) : ?>
						<strong><?php echo esc_html( __( 'Date:', 'event-management-system' ) ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event_date ) ) ); ?><br>
					<?php endif; ?>
					<strong><?php echo esc_html( __( 'Registration ID:', 'event-management-system' ) ); ?></strong> <?php echo esc_html( $registration->id ); ?>
				</div>

				<table>
					<thead>
						<tr>
							<th><?php echo esc_html( __( 'Description', 'event-management-system' ) ); ?></th>
							<th><?php echo esc_html( __( 'Quantity', 'event-management-system' ) ); ?></th>
							<th><?php echo esc_html( __( 'Amount', 'event-management-system' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $registration->ticket_type ) ) ); ?> <?php echo esc_html( __( 'Ticket', 'event-management-system' ) ); ?></td>
							<td><?php echo esc_html( $registration->ticket_quantity ); ?></td>
							<td>$<?php echo esc_html( number_format( $amount, 2 ) ); ?></td>
						</tr>
						<?php if ( ! empty( $registration->promo_code ) ) : ?>
							<tr>
								<td colspan="2"><?php echo esc_html( __( 'Promo Code:', 'event-management-system' ) ); ?> <?php echo esc_html( $registration->promo_code ); ?></td>
								<td><?php echo esc_html( __( 'Applied', 'event-management-system' ) ); ?></td>
							</tr>
						<?php endif; ?>
						<tr class="total-row">
							<td colspan="2"><?php echo esc_html( __( 'TOTAL AMOUNT DUE', 'event-management-system' ) ); ?></td>
							<td>$<?php echo esc_html( number_format( $amount, 2 ) ); ?></td>
						</tr>
					</tbody>
				</table>

				<div class="payment-instructions">
					<strong><?php echo esc_html( __( 'Payment Instructions:', 'event-management-system' ) ); ?></strong><br>
					<?php echo nl2br( esc_html( $payment_instructions ) ); ?>
				</div>

				<div class="footer">
					<?php echo esc_html( __( 'Thank you for your registration!', 'event-management-system' ) ); ?><br>
					<?php echo esc_html( __( 'Please reference invoice number', 'event-management-system' ) ); ?> <?php echo esc_html( $invoice_number ); ?> <?php echo esc_html( __( 'with your payment', 'event-management-system' ) ); ?>
				</div>
			</div>
		</body>
		</html>
		<?php

		return ob_get_clean();
	}

	/**
	 * Manual payment confirmation (called by admin)
	 *
	 * @since 1.0.0
	 * @param int    $registration_id Registration ID.
	 * @param string $payment_reference Payment reference.
	 * @return array Result
	 */
	public function confirm_manual_payment( $registration_id, $payment_reference ) {
		$result = $this->registration->confirm_payment( $registration_id, $payment_reference );

		if ( $result['success'] ) {
			$this->logger->info(
				"Manual payment confirmed for registration {$registration_id}, reference: {$payment_reference}",
				EMS_Logger::CONTEXT_PAYMENT
			);
		}

		return $result;
	}
}

/**
 * Payment Gateway Manager
 *
 * Manages available payment gateways and routing.
 *
 * @since 1.0.0
 */
class EMS_Payment_Gateway_Manager {

	/**
	 * Available gateways
	 *
	 * @var array
	 */
	private $gateways = array();

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
		$this->register_default_gateways();
	}

	/**
	 * Register default gateways
	 *
	 * @since 1.0.0
	 */
	private function register_default_gateways() {
		$this->register_gateway( 'invoice', 'EMS_Invoice_Gateway' );
	}

	/**
	 * Register a payment gateway
	 *
	 * @since 1.0.0
	 * @param string $gateway_id Gateway ID.
	 * @param string $gateway_class Gateway class name.
	 * @return bool Success status
	 */
	public function register_gateway( $gateway_id, $gateway_class ) {
		if ( ! class_exists( $gateway_class ) ) {
			$this->logger->error(
				"Cannot register gateway '{$gateway_id}': Class '{$gateway_class}' not found",
				EMS_Logger::CONTEXT_GENERAL
			);
			return false;
		}

		$this->gateways[ $gateway_id ] = $gateway_class;

		$this->logger->debug(
			"Payment gateway registered: {$gateway_id}",
			EMS_Logger::CONTEXT_GENERAL
		);

		return true;
	}

	/**
	 * Get gateway instance
	 *
	 * @since 1.0.0
	 * @param string $gateway_id Gateway ID.
	 * @return EMS_Payment_Gateway|false Gateway instance or false
	 */
	public function get_gateway( $gateway_id ) {
		if ( ! isset( $this->gateways[ $gateway_id ] ) ) {
			return false;
		}

		$class = $this->gateways[ $gateway_id ];
		return new $class();
	}

	/**
	 * Get all available gateways
	 *
	 * @since 1.0.0
	 * @return array Array of gateway instances
	 */
	public function get_all_gateways() {
		$instances = array();

		foreach ( $this->gateways as $id => $class ) {
			$instances[ $id ] = new $class();
		}

		return $instances;
	}

	/**
	 * Get active gateway (from settings)
	 *
	 * @since 1.0.0
	 * @return EMS_Payment_Gateway Active gateway instance
	 */
	public function get_active_gateway() {
		$active_gateway_id = get_option( 'ems_active_payment_gateway', 'invoice' );
		$gateway = $this->get_gateway( $active_gateway_id );

		if ( ! $gateway ) {
			// Fallback to invoice gateway
			$gateway = $this->get_gateway( 'invoice' );
		}

		return $gateway;
	}
}
