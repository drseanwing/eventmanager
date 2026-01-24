<?php
/**
 * Validator utility class
 *
 * Provides comprehensive validation functionality for all user inputs,
 * form data, and external data sources.
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
 * Validator class.
 *
 * Handles validation of various data types and formats with proper
 * error reporting and logging.
 *
 * @since 1.0.0
 */
class EMS_Validator {

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Validation errors
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Validate email address with DNS check
	 *
	 * @since 1.0.0
	 * @param string $email Email address to validate
	 * @param bool   $check_dns Whether to perform DNS check
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	public function validate_email( $email, $check_dns = true ) {
		// Basic validation
		if ( empty( $email ) ) {
			return new WP_Error( 'empty_email', __( 'Email address is required.', 'event-management-system' ) );
		}

		// WordPress email validation
		if ( ! is_email( $email ) ) {
			$this->logger->warning(
				'Invalid email format attempted',
				EMS_Logger::CONTEXT_SECURITY,
				array( 'email' => $email )
			);
			return new WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'event-management-system' ) );
		}

		// DNS check (optional but recommended)
		if ( $check_dns ) {
			list( $user, $domain ) = explode( '@', $email );
			if ( ! checkdnsrr( $domain, 'MX' ) && ! checkdnsrr( $domain, 'A' ) ) {
				$this->logger->warning(
					'Email with invalid DNS attempted',
					EMS_Logger::CONTEXT_SECURITY,
					array( 'email' => $email, 'domain' => $domain )
				);
				return new WP_Error( 'invalid_email_domain', __( 'Email domain does not exist or cannot receive email.', 'event-management-system' ) );
			}
		}

		return true;
	}

	/**
	 * Validate required field
	 *
	 * @since 1.0.0
	 * @param mixed  $value Field value
	 * @param string $field_name Field name for error message
	 * @return bool|WP_Error
	 */
	public function validate_required( $value, $field_name = 'This field' ) {
		if ( empty( $value ) && '0' !== $value && 0 !== $value ) {
			return new WP_Error(
				'required_field',
				sprintf(
					/* translators: %s: field name */
					__( '%s is required.', 'event-management-system' ),
					$field_name
				)
			);
		}
		return true;
	}

	/**
	 * Validate text length
	 *
	 * @since 1.0.0
	 * @param string $value Text value
	 * @param int    $min Minimum length
	 * @param int    $max Maximum length
	 * @param string $field_name Field name for error message
	 * @return bool|WP_Error
	 */
	public function validate_length( $value, $min = 0, $max = 0, $field_name = 'This field' ) {
		$length = strlen( $value );

		if ( $min > 0 && $length < $min ) {
			return new WP_Error(
				'text_too_short',
				sprintf(
					/* translators: 1: field name, 2: minimum length */
					__( '%1$s must be at least %2$d characters long.', 'event-management-system' ),
					$field_name,
					$min
				)
			);
		}

		if ( $max > 0 && $length > $max ) {
			return new WP_Error(
				'text_too_long',
				sprintf(
					/* translators: 1: field name, 2: maximum length */
					__( '%1$s must not exceed %2$d characters.', 'event-management-system' ),
					$field_name,
					$max
				)
			);
		}

		return true;
	}

	/**
	 * Validate URL
	 *
	 * @since 1.0.0
	 * @param string $url URL to validate
	 * @return bool|WP_Error
	 */
	public function validate_url( $url ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'empty_url', __( 'URL is required.', 'event-management-system' ) );
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$this->logger->warning(
				'Invalid URL format attempted',
				EMS_Logger::CONTEXT_SECURITY,
				array( 'url' => $url )
			);
			return new WP_Error( 'invalid_url', __( 'Please provide a valid URL.', 'event-management-system' ) );
		}

		return true;
	}

	/**
	 * Validate phone number
	 *
	 * @since 1.0.0
	 * @param string $phone Phone number to validate
	 * @return bool|WP_Error
	 */
	public function validate_phone( $phone ) {
		if ( empty( $phone ) ) {
			return new WP_Error( 'empty_phone', __( 'Phone number is required.', 'event-management-system' ) );
		}

		// Remove common formatting characters
		$cleaned = preg_replace( '/[^0-9+]/', '', $phone );

		// Validate length (international format: 10-15 digits)
		if ( strlen( $cleaned ) < 10 || strlen( $cleaned ) > 15 ) {
			return new WP_Error( 'invalid_phone', __( 'Please provide a valid phone number.', 'event-management-system' ) );
		}

		return true;
	}

	/**
	 * Validate date format
	 *
	 * @since 1.0.0
	 * @param string $date Date string
	 * @param string $format Expected format (default: Y-m-d)
	 * @return bool|WP_Error
	 */
	public function validate_date( $date, $format = 'Y-m-d' ) {
		if ( empty( $date ) ) {
			return new WP_Error( 'empty_date', __( 'Date is required.', 'event-management-system' ) );
		}

		$d = DateTime::createFromFormat( $format, $date );
		if ( ! $d || $d->format( $format ) !== $date ) {
			return new WP_Error(
				'invalid_date',
				sprintf(
					/* translators: %s: expected date format */
					__( 'Date must be in %s format.', 'event-management-system' ),
					$format
				)
			);
		}

		return true;
	}

	/**
	 * Validate datetime format
	 *
	 * @since 1.0.0
	 * @param string $datetime Datetime string
	 * @param string $format Expected format (default: Y-m-d H:i:s)
	 * @return bool|WP_Error
	 */
	public function validate_datetime( $datetime, $format = 'Y-m-d H:i:s' ) {
		if ( empty( $datetime ) ) {
			return new WP_Error( 'empty_datetime', __( 'Date and time are required.', 'event-management-system' ) );
		}

		$d = DateTime::createFromFormat( $format, $datetime );
		if ( ! $d || $d->format( $format ) !== $datetime ) {
			return new WP_Error(
				'invalid_datetime',
				sprintf(
					/* translators: %s: expected datetime format */
					__( 'Date and time must be in %s format.', 'event-management-system' ),
					$format
				)
			);
		}

		return true;
	}

	/**
	 * Validate date range
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @return bool|WP_Error
	 */
	public function validate_date_range( $start_date, $end_date ) {
		$start = strtotime( $start_date );
		$end   = strtotime( $end_date );

		if ( false === $start || false === $end ) {
			return new WP_Error( 'invalid_date_range', __( 'Invalid date format in date range.', 'event-management-system' ) );
		}

		if ( $end < $start ) {
			return new WP_Error( 'invalid_date_range', __( 'End date must be after start date.', 'event-management-system' ) );
		}

		return true;
	}

	/**
	 * Validate integer
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate
	 * @param int   $min Minimum value (optional)
	 * @param int   $max Maximum value (optional)
	 * @param string $field_name Field name for error message
	 * @return bool|WP_Error
	 */
	public function validate_integer( $value, $min = null, $max = null, $field_name = 'This field' ) {
		if ( ! is_numeric( $value ) || (int) $value != $value ) {
			return new WP_Error(
				'not_integer',
				sprintf(
					/* translators: %s: field name */
					__( '%s must be a whole number.', 'event-management-system' ),
					$field_name
				)
			);
		}

		$int_value = (int) $value;

		if ( null !== $min && $int_value < $min ) {
			return new WP_Error(
				'value_too_small',
				sprintf(
					/* translators: 1: field name, 2: minimum value */
					__( '%1$s must be at least %2$d.', 'event-management-system' ),
					$field_name,
					$min
				)
			);
		}

		if ( null !== $max && $int_value > $max ) {
			return new WP_Error(
				'value_too_large',
				sprintf(
					/* translators: 1: field name, 2: maximum value */
					__( '%1$s must not exceed %2$d.', 'event-management-system' ),
					$field_name,
					$max
				)
			);
		}

		return true;
	}

	/**
	 * Validate decimal/float
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to validate
	 * @param int   $min Minimum value (optional)
	 * @param int   $max Maximum value (optional)
	 * @param string $field_name Field name for error message
	 * @return bool|WP_Error
	 */
	public function validate_decimal( $value, $min = null, $max = null, $field_name = 'This field' ) {
		if ( ! is_numeric( $value ) ) {
			return new WP_Error(
				'not_numeric',
				sprintf(
					/* translators: %s: field name */
					__( '%s must be a number.', 'event-management-system' ),
					$field_name
				)
			);
		}

		$float_value = (float) $value;

		if ( null !== $min && $float_value < $min ) {
			return new WP_Error(
				'value_too_small',
				sprintf(
					/* translators: 1: field name, 2: minimum value */
					__( '%1$s must be at least %2$s.', 'event-management-system' ),
					$field_name,
					$min
				)
			);
		}

		if ( null !== $max && $float_value > $max ) {
			return new WP_Error(
				'value_too_large',
				sprintf(
					/* translators: 1: field name, 2: maximum value */
					__( '%1$s must not exceed %2$s.', 'event-management-system' ),
					$field_name,
					$max
				)
			);
		}

		return true;
	}

	/**
	 * Validate file upload
	 *
	 * @since 1.0.0
	 * @param array $file $_FILES array element
	 * @param array $allowed_types Allowed MIME types
	 * @param int   $max_size Maximum file size in bytes
	 * @return bool|WP_Error
	 */
	public function validate_file_upload( $file, $allowed_types = array(), $max_size = EMS_MAX_UPLOAD_SIZE ) {
		// Check if file was uploaded
		if ( empty( $file ) || ! isset( $file['tmp_name'] ) ) {
			return new WP_Error( 'no_file', __( 'No file was uploaded.', 'event-management-system' ) );
		}

		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			$this->logger->error(
				'File upload error',
				EMS_Logger::CONTEXT_FILES,
				array(
					'filename' => $file['name'],
					'error'    => $file['error'],
				)
			);
			return new WP_Error( 'upload_error', $this->get_upload_error_message( $file['error'] ) );
		}

		// Check file size
		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %s: maximum file size in MB */
					__( 'File size must not exceed %s MB.', 'event-management-system' ),
					number_format( $max_size / 1048576, 1 )
				)
			);
		}

		// Validate MIME type
		if ( ! empty( $allowed_types ) ) {
			$finfo     = finfo_open( FILEINFO_MIME_TYPE );
			$mime_type = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );

			if ( ! in_array( $mime_type, $allowed_types ) ) {
				$this->logger->warning(
					'Disallowed file type upload attempted',
					EMS_Logger::CONTEXT_SECURITY,
					array(
						'filename'  => $file['name'],
						'mime_type' => $mime_type,
						'allowed'   => $allowed_types,
					)
				);
				return new WP_Error( 'invalid_file_type', __( 'This file type is not allowed.', 'event-management-system' ) );
			}
		}

		// Validate file extension
		$filename  = sanitize_file_name( $file['name'] );
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		$allowed_extensions = array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip' );

		if ( ! in_array( $extension, $allowed_extensions ) ) {
			$this->logger->warning(
				'Disallowed file extension upload attempted',
				EMS_Logger::CONTEXT_SECURITY,
				array(
					'filename'  => $filename,
					'extension' => $extension,
				)
			);
			return new WP_Error(
				'invalid_extension',
				sprintf(
					/* translators: %s: comma-separated list of allowed extensions */
					__( 'Allowed file types: %s', 'event-management-system' ),
					implode( ', ', $allowed_extensions )
				)
			);
		}

		return true;
	}

	/**
	 * Get upload error message
	 *
	 * @since 1.0.0
	 * @param int $error_code PHP upload error code
	 * @return string Error message
	 */
	private function get_upload_error_message( $error_code ) {
		$messages = array(
			UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the maximum allowed size.', 'event-management-system' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the maximum allowed size.', 'event-management-system' ),
			UPLOAD_ERR_PARTIAL    => __( 'The file was only partially uploaded. Please try again.', 'event-management-system' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'event-management-system' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing temporary folder. Please contact support.', 'event-management-system' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk. Please contact support.', 'event-management-system' ),
			UPLOAD_ERR_EXTENSION  => __( 'File upload stopped by extension. Please contact support.', 'event-management-system' ),
		);

		return isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : __( 'Unknown upload error occurred.', 'event-management-system' );
	}

	/**
	 * Validate in array
	 *
	 * @since 1.0.0
	 * @param mixed  $value Value to check
	 * @param array  $allowed_values Allowed values
	 * @param string $field_name Field name for error message
	 * @return bool|WP_Error
	 */
	public function validate_in_array( $value, $allowed_values, $field_name = 'This field' ) {
		if ( ! in_array( $value, $allowed_values, true ) ) {
			return new WP_Error(
				'invalid_value',
				sprintf(
					/* translators: %s: field name */
					__( '%s contains an invalid value.', 'event-management-system' ),
					$field_name
				)
			);
		}
		return true;
	}

	/**
	 * Validate promo code format
	 *
	 * @since 1.0.0
	 * @param string $code Promo code
	 * @return bool|WP_Error
	 */
	public function validate_promo_code( $code ) {
		if ( empty( $code ) ) {
			return true; // Promo code is optional
		}

		// Alphanumeric and dashes only, 3-20 characters
		if ( ! preg_match( '/^[a-zA-Z0-9-]{3,20}$/', $code ) ) {
			return new WP_Error( 'invalid_promo_code', __( 'Invalid promo code format.', 'event-management-system' ) );
		}

		return true;
	}

	/**
	 * Batch validate multiple fields
	 *
	 * @since 1.0.0
	 * @param array $validations Array of validation rules
	 * @return bool|array True if all valid, array of errors if any invalid
	 */
	public function batch_validate( $validations ) {
		$errors = array();

		foreach ( $validations as $field => $rules ) {
			foreach ( $rules as $rule ) {
				$result = call_user_func_array( array( $this, $rule['method'] ), $rule['args'] );
				if ( is_wp_error( $result ) ) {
					$errors[ $field ] = $result->get_error_message();
					break; // Stop checking this field after first error
				}
			}
		}

		return empty( $errors ) ? true : $errors;
	}

	/**
	 * Sanitize and validate registration data
	 *
	 * @since 1.0.0
	 * @param array $data Registration form data
	 * @return array|WP_Error Sanitized data or WP_Error with all validation errors
	 */
	public function validate_registration_data( $data ) {
		$errors         = array();
		$sanitized_data = array();

		// Email validation
		$email_check = $this->validate_email( $data['email'] ?? '' );
		if ( is_wp_error( $email_check ) ) {
			$errors['email'] = $email_check->get_error_message();
		} else {
			$sanitized_data['email'] = sanitize_email( $data['email'] );
		}

		// First name validation
		$fname_check = $this->validate_required( $data['first_name'] ?? '', 'First name' );
		if ( is_wp_error( $fname_check ) ) {
			$errors['first_name'] = $fname_check->get_error_message();
		} else {
			$length_check = $this->validate_length( $data['first_name'], 2, 100, 'First name' );
			if ( is_wp_error( $length_check ) ) {
				$errors['first_name'] = $length_check->get_error_message();
			} else {
				$sanitized_data['first_name'] = sanitize_text_field( $data['first_name'] );
			}
		}

		// Last name validation
		$lname_check = $this->validate_required( $data['last_name'] ?? '', 'Last name' );
		if ( is_wp_error( $lname_check ) ) {
			$errors['last_name'] = $lname_check->get_error_message();
		} else {
			$length_check = $this->validate_length( $data['last_name'], 2, 100, 'Last name' );
			if ( is_wp_error( $length_check ) ) {
				$errors['last_name'] = $length_check->get_error_message();
			} else {
				$sanitized_data['last_name'] = sanitize_text_field( $data['last_name'] );
			}
		}

		// Discipline validation
		$allowed_disciplines = array( 'doctor', 'nurse', 'allied_health' );
		$discipline_check    = $this->validate_in_array( $data['discipline'] ?? '', $allowed_disciplines, 'Discipline' );
		if ( is_wp_error( $discipline_check ) ) {
			$errors['discipline'] = $discipline_check->get_error_message();
		} else {
			$sanitized_data['discipline'] = sanitize_text_field( $data['discipline'] );
		}

		// Specialty validation
		$specialty_check = $this->validate_required( $data['specialty'] ?? '', 'Specialty' );
		if ( is_wp_error( $specialty_check ) ) {
			$errors['specialty'] = $specialty_check->get_error_message();
		} else {
			$sanitized_data['specialty'] = sanitize_text_field( $data['specialty'] );
		}

		// Seniority validation
		$allowed_seniority = array( 'student', 'graduate', 'experienced', 'senior' );
		$seniority_check   = $this->validate_in_array( $data['seniority'] ?? '', $allowed_seniority, 'Seniority' );
		if ( is_wp_error( $seniority_check ) ) {
			$errors['seniority'] = $seniority_check->get_error_message();
		} else {
			$sanitized_data['seniority'] = sanitize_text_field( $data['seniority'] );
		}

		// Promo code validation (optional)
		if ( ! empty( $data['promo_code'] ) ) {
			$promo_check = $this->validate_promo_code( $data['promo_code'] );
			if ( is_wp_error( $promo_check ) ) {
				$errors['promo_code'] = $promo_check->get_error_message();
			} else {
				$sanitized_data['promo_code'] = strtoupper( sanitize_text_field( $data['promo_code'] ) );
			}
		}

		// Special requirements (optional)
		if ( ! empty( $data['special_requirements'] ) ) {
			$sanitized_data['special_requirements'] = sanitize_textarea_field( $data['special_requirements'] );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', 'Validation failed', $errors );
		}

		return $sanitized_data;
	}
}
