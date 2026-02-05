<?php
/**
 * Security utility class
 *
 * Provides security functionality including nonce verification,
 * capability checks, rate limiting, and input sanitization.
 *
 * @package    EventManagementSystem
 * @subpackage Utilities
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Security class.
 *
 * Handles all security-related operations for the plugin.
 *
 * @since 1.0.0
 */
class EMS_Security {

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Rate limit storage (transients)
	 *
	 * @var string
	 */
	const RATE_LIMIT_PREFIX = 'ems_rate_limit_';

	/**
	 * Rate limits configuration
	 *
	 * @var array
	 */
	private $rate_limits = array(
		'abstract_submission' => array(
			'limit'  => 5,
			'period' => HOUR_IN_SECONDS,
			'scope'  => 'user',
		),
		'registration'        => array(
			'limit'  => 10,
			'period' => HOUR_IN_SECONDS,
			'scope'  => 'ip',
		),
		'file_upload'         => array(
			'limit'  => 20,
			'period' => HOUR_IN_SECONDS,
			'scope'  => 'user',
		),
		'login_attempt'       => array(
			'limit'  => 5,
			'period' => 15 * MINUTE_IN_SECONDS,
			'scope'  => 'ip',
		),
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Verify nonce
	 *
	 * @since 1.0.0
	 * @param string $nonce Nonce value
	 * @param string $action Nonce action
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	public function verify_nonce( $nonce, $action ) {
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			$this->logger->warning(
				'Nonce verification failed',
				EMS_Logger::CONTEXT_SECURITY,
				array(
					'action'  => $action,
					'user_id' => get_current_user_id(),
					'ip'      => $this->get_client_ip(),
				)
			);
			return new WP_Error( 'invalid_nonce', __( 'Security verification failed. Please refresh and try again.', 'event-management-system' ) );
		}
		return true;
	}

	/**
	 * Create nonce
	 *
	 * @since 1.0.0
	 * @param string $action Nonce action
	 * @return string Nonce value
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Check user capability
	 *
	 * @since 1.0.0
	 * @param string $capability Capability to check
	 * @param int    $user_id Optional user ID (defaults to current user)
	 * @return bool|WP_Error True if has capability, WP_Error if not
	 */
	public function check_capability( $capability, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user || ! $user->has_cap( $capability ) ) {
			$this->logger->warning(
				'Capability check failed',
				EMS_Logger::CONTEXT_SECURITY,
				array(
					'capability' => $capability,
					'user_id'    => $user_id,
					'ip'         => $this->get_client_ip(),
				)
			);
			return new WP_Error( 'insufficient_permissions', __( 'You do not have permission to perform this action.', 'event-management-system' ) );
		}

		return true;
	}

	/**
	 * Check if user is logged in
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error True if logged in, WP_Error if not
	 */
	public function check_logged_in() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to perform this action.', 'event-management-system' ) );
		}
		return true;
	}

	/**
	 * Get client IP address
	 *
	 * @since 1.0.0
	 * @return string IP address
	 */
	public function get_client_ip() {
		$ip = '';

		// Check for proxy headers (in order of preference)
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// If comma-separated (X-Forwarded-For), get first IP
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}

				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					break;
				}
			}
		}

		return $ip;
	}

	/**
	 * Check rate limit
	 *
	 * @since 1.0.0
	 * @param string $action Rate limit action
	 * @param int    $user_id Optional user ID (for user-scoped limits)
	 * @return bool|WP_Error True if within limit, WP_Error if exceeded
	 */
	public function check_rate_limit( $action, $user_id = 0 ) {
		// Check if rate limit is configured
		if ( ! isset( $this->rate_limits[ $action ] ) ) {
			return true;
		}

		$config = $this->rate_limits[ $action ];

		// Determine scope identifier
		if ( 'user' === $config['scope'] ) {
			if ( 0 === $user_id ) {
				$user_id = get_current_user_id();
			}
			$identifier = "user_{$user_id}";
		} else {
			$identifier = 'ip_' . $this->get_client_ip();
		}

		// Generate transient key
		$transient_key = self::RATE_LIMIT_PREFIX . $action . '_' . md5( $identifier );

		// Get current count
		$current_count = get_transient( $transient_key );

		if ( false === $current_count ) {
			// First attempt - initialize counter
			set_transient( $transient_key, 1, $config['period'] );
			return true;
		}

		// Check if limit exceeded
		if ( $current_count >= $config['limit'] ) {
			$this->logger->warning(
				'Rate limit exceeded',
				EMS_Logger::CONTEXT_SECURITY,
				array(
					'action'     => $action,
					'identifier' => $identifier,
					'count'      => $current_count,
					'limit'      => $config['limit'],
				)
			);

			return new WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d: number of minutes to wait */
					__( 'Too many attempts. Please try again in %d minutes.', 'event-management-system' ),
					ceil( $config['period'] / 60 )
				)
			);
		}

		// Increment counter
		set_transient( $transient_key, $current_count + 1, $config['period'] );

		return true;
	}

	/**
	 * Reset rate limit for a user/IP
	 *
	 * @since 1.0.0
	 * @param string $action Rate limit action
	 * @param int    $user_id Optional user ID
	 * @return bool Whether reset was successful
	 */
	public function reset_rate_limit( $action, $user_id = 0 ) {
		if ( ! isset( $this->rate_limits[ $action ] ) ) {
			return false;
		}

		$config = $this->rate_limits[ $action ];

		// Determine scope identifier
		if ( 'user' === $config['scope'] ) {
			if ( 0 === $user_id ) {
				$user_id = get_current_user_id();
			}
			$identifier = "user_{$user_id}";
		} else {
			$identifier = 'ip_' . $this->get_client_ip();
		}

		// Generate transient key
		$transient_key = self::RATE_LIMIT_PREFIX . $action . '_' . md5( $identifier );

		return delete_transient( $transient_key );
	}

	/**
	 * Sanitize filename
	 *
	 * @since 1.0.0
	 * @param string $filename Original filename
	 * @return string Sanitized filename
	 */
	public function sanitize_filename( $filename ) {
		// Remove any path information
		$filename = basename( $filename );

		// Use WordPress sanitization
		$filename = sanitize_file_name( $filename );

		// Additional security: remove any remaining special characters
		$filename = preg_replace( '/[^a-zA-Z0-9._-]/', '', $filename );

		// Ensure we have something left
		if ( empty( $filename ) ) {
			$filename = 'file_' . time();
		}

		return $filename;
	}

	/**
	 * Generate unique filename
	 *
	 * @since 1.0.0
	 * @param string $original_filename Original filename
	 * @param string $directory Target directory
	 * @return string Unique filename
	 */
	public function generate_unique_filename( $original_filename, $directory ) {
		$filename  = $this->sanitize_filename( $original_filename );
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		$basename  = pathinfo( $filename, PATHINFO_FILENAME );

		// Add timestamp and random string
		$unique_basename = $basename . '_' . time() . '_' . wp_generate_password( 8, false );
		$unique_filename = $unique_basename . '.' . $extension;

		// Ensure uniqueness (just in case)
		$counter = 1;
		while ( file_exists( $directory . '/' . $unique_filename ) ) {
			$unique_filename = $unique_basename . '_' . $counter . '.' . $extension;
			$counter++;
		}

		return $unique_filename;
	}

	/**
	 * Check if user can access file
	 *
	 * @since 1.0.0
	 * @param string $filepath File path
	 * @param int    $user_id User ID (0 for current user)
	 * @return bool|WP_Error True if allowed, WP_Error if not
	 */
	public function check_file_access( $filepath, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Parse the file path to determine access rules
		$relative_path = str_replace( EMS_UPLOAD_DIR, '', $filepath );
		$path_parts    = explode( '/', trim( $relative_path, '/' ) );

		// Admins have access to everything
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Check access based on path structure
		if ( isset( $path_parts[0] ) ) {
			switch ( $path_parts[0] ) {
				case 'events':
					// Events/{event-id}/public - everyone can access
					if ( isset( $path_parts[2] ) && 'public' === $path_parts[2] ) {
						return true;
					}
					// Other event folders - convenors and admins only
					return $this->check_capability( 'read_ems_events', $user_id );

				case 'presenters':
					// Presenters/{user-id} - owner and admins only
					if ( isset( $path_parts[1] ) ) {
						$owner_id = intval( $path_parts[1] );
						if ( $owner_id === $user_id ) {
							return true;
						}
					}
					return $this->check_capability( 'edit_ems_abstracts', $user_id );

				case 'sponsors':
					// Sponsors can access their own files
					// This requires checking if user is associated with the sponsor
					// Implementation depends on sponsor user relationship
					return $this->check_capability( 'access_ems_sponsor_portal', $user_id );

				default:
					// Unknown path - deny by default
					$this->logger->warning(
						'Attempted access to unknown file path',
						EMS_Logger::CONTEXT_SECURITY,
						array(
							'filepath' => $filepath,
							'user_id'  => $user_id,
						)
					);
					return new WP_Error( 'access_denied', __( 'You do not have permission to access this file.', 'event-management-system' ) );
			}
		}

		return new WP_Error( 'access_denied', __( 'You do not have permission to access this file.', 'event-management-system' ) );
	}

	/**
	 * Sanitize SQL ORDER BY clause
	 *
	 * @since 1.0.0
	 * @param string $orderby ORDER BY value
	 * @param array  $allowed_columns Allowed column names
	 * @return string Sanitized ORDER BY clause
	 */
	public function sanitize_orderby( $orderby, $allowed_columns = array() ) {
		// Default allowed columns if none provided
		if ( empty( $allowed_columns ) ) {
			$allowed_columns = array( 'id', 'date', 'title', 'name' );
		}

		// Remove any whitespace
		$orderby = trim( $orderby );

		// Check if it's in the allowed list
		if ( ! in_array( $orderby, $allowed_columns, true ) ) {
			return 'id'; // Default fallback
		}

		return $orderby;
	}

	/**
	 * Sanitize SQL ORDER direction
	 *
	 * @since 1.0.0
	 * @param string $order ORDER direction
	 * @return string Sanitized ORDER direction (ASC or DESC)
	 */
	public function sanitize_order( $order ) {
		$order = strtoupper( trim( $order ) );
		return ( 'DESC' === $order ) ? 'DESC' : 'ASC';
	}

	/**
	 * Verify AJAX referer
	 *
	 * @since 1.0.0
	 * @param string $action Nonce action
	 * @param string $query_arg Query arg name (default: '_wpnonce')
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	public function verify_ajax_referer( $action, $query_arg = '_wpnonce' ) {
		$nonce = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			$this->logger->warning(
				'AJAX nonce verification failed',
				EMS_Logger::CONTEXT_SECURITY,
				array(
					'action'  => $action,
					'user_id' => get_current_user_id(),
					'ip'      => $this->get_client_ip(),
				)
			);
			return new WP_Error( 'invalid_ajax_nonce', __( 'Security verification failed. Please refresh and try again.', 'event-management-system' ) );
		}

		return true;
	}

	/**
	 * Log security event
	 *
	 * @since 1.0.0
	 * @param string $event Event type
	 * @param string $description Event description
	 * @param array  $data Additional data
	 * @return void
	 */
	public function log_security_event( $event, $description, $data = array() ) {
		$log_data = array_merge(
			array(
				'event'   => $event,
				'user_id' => get_current_user_id(),
				'ip'      => $this->get_client_ip(),
			),
			$data
		);

		$this->logger->notice( $description, EMS_Logger::CONTEXT_SECURITY, $log_data );
	}

	/**
	 * Check for suspicious activity
	 *
	 * @since 1.0.0
	 * @param string $context Activity context
	 * @param array  $data Activity data
	 * @return bool Whether activity is suspicious
	 */
	public function is_suspicious_activity( $context, $data = array() ) {
		$suspicious = false;

		// Check for SQL injection patterns
		$sql_patterns = array( 'SELECT', 'UNION', 'DROP', 'INSERT', 'UPDATE', 'DELETE', '--', '/*', '*/', 'xp_', 'sp_' );
		foreach ( $data as $value ) {
			if ( is_string( $value ) ) {
				foreach ( $sql_patterns as $pattern ) {
					if ( stripos( $value, $pattern ) !== false ) {
						$suspicious = true;
						break 2;
					}
				}
			}
		}

		// Check for XSS patterns
		$xss_patterns = array( '<script', 'javascript:', 'onerror=', 'onload=' );
		foreach ( $data as $value ) {
			if ( is_string( $value ) ) {
				foreach ( $xss_patterns as $pattern ) {
					if ( stripos( $value, $pattern ) !== false ) {
						$suspicious = true;
						break 2;
					}
				}
			}
		}

		if ( $suspicious ) {
			$this->logger->alert(
				'Suspicious activity detected',
				EMS_Logger::CONTEXT_SECURITY,
				array(
					'context' => $context,
					'data'    => $data,
					'user_id' => get_current_user_id(),
					'ip'      => $this->get_client_ip(),
				)
			);
		}

		return $suspicious;
	}

	/**
	 * Generate secure random token
	 *
	 * @since 1.0.0
	 * @param int $length Token length
	 * @return string Random token
	 */
	public function generate_token( $length = 32 ) {
		return wp_generate_password( $length, false );
	}

	/**
	 * Hash sensitive data
	 *
	 * @since 1.0.0
	 * @param string $data Data to hash
	 * @return string Hashed data
	 */
	public function hash_data( $data ) {
		return wp_hash( $data );
	}
}
