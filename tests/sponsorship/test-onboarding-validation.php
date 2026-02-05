<?php
/**
 * Test Case: Sponsor Onboarding Form Validation
 *
 * Tests for validation logic in the sponsor onboarding process,
 * including ABN/ACN validation, email validation, required fields,
 * and duplicate detection.
 *
 * @package    EventManagementSystem
 * @subpackage Tests
 * @since      1.5.0
 */

class Test_EMS_Onboarding_Validation extends WP_UnitTestCase {

	/**
	 * Validator instance
	 *
	 * @var EMS_Validator
	 */
	private $validator;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();
		$this->validator = new EMS_Validator();
	}

	/**
	 * Test: Valid ABN passes validation
	 *
	 * ABN validation uses weighted check digit algorithm.
	 * Valid test ABN: 53 004 085 616
	 */
	public function test_valid_abn_passes_validation() {
		$valid_abn = '53004085616';

		$result = $this->validator->validate_abn( $valid_abn );
		$this->assertTrue( $result, 'Valid ABN should pass validation' );

		// Test with spaces
		$result_with_spaces = $this->validator->validate_abn( '53 004 085 616' );
		$this->assertTrue( $result_with_spaces, 'Valid ABN with spaces should pass after cleaning' );
	}

	/**
	 * Test: Invalid ABN fails validation
	 */
	public function test_invalid_abn_fails_validation() {
		$invalid_abn = '53004085617'; // Changed last digit

		$result = $this->validator->validate_abn( $invalid_abn );
		$this->assertWPError( $result, 'Invalid ABN should return WP_Error' );
		$this->assertEquals( 'invalid_abn', $result->get_error_code() );
	}

	/**
	 * Test: ABN with wrong length fails validation
	 */
	public function test_abn_wrong_length_fails() {
		$too_short = '123456789'; // Only 9 digits
		$too_long = '123456789012'; // 12 digits

		$result_short = $this->validator->validate_abn( $too_short );
		$this->assertWPError( $result_short, 'Too-short ABN should fail' );

		$result_long = $this->validator->validate_abn( $too_long );
		$this->assertWPError( $result_long, 'Too-long ABN should fail' );
	}

	/**
	 * Test: Valid ACN passes validation
	 *
	 * ACN validation uses modulo 10 check digit.
	 * Valid test ACN: 004 085 616
	 */
	public function test_valid_acn_passes_validation() {
		$valid_acn = '004085616';

		$result = $this->validator->validate_acn( $valid_acn );
		$this->assertTrue( $result, 'Valid ACN should pass validation' );

		// Test with spaces
		$result_with_spaces = $this->validator->validate_acn( '004 085 616' );
		$this->assertTrue( $result_with_spaces, 'Valid ACN with spaces should pass' );
	}

	/**
	 * Test: Invalid ACN fails validation
	 */
	public function test_invalid_acn_fails_validation() {
		$invalid_acn = '004085617'; // Changed last digit

		$result = $this->validator->validate_acn( $invalid_acn );
		$this->assertWPError( $result, 'Invalid ACN should return WP_Error' );
		$this->assertEquals( 'invalid_acn', $result->get_error_code() );
	}

	/**
	 * Test: Required field validation enforces presence
	 */
	public function test_required_field_validation() {
		// Empty string should fail
		$result_empty = $this->validator->validate_required( '', 'Legal name' );
		$this->assertWPError( $result_empty, 'Empty string should fail required validation' );

		// Null should fail
		$result_null = $this->validator->validate_required( null, 'Legal name' );
		$this->assertWPError( $result_null, 'Null should fail required validation' );

		// Non-empty string should pass
		$result_valid = $this->validator->validate_required( 'Acme Corporation', 'Legal name' );
		$this->assertTrue( $result_valid, 'Non-empty string should pass' );

		// Zero should pass (edge case)
		$result_zero = $this->validator->validate_required( '0', 'Field' );
		$this->assertTrue( $result_zero, 'String "0" should pass required validation' );
	}

	/**
	 * Test: Email format validation
	 */
	public function test_email_format_validation() {
		// Valid emails
		$valid_emails = array(
			'sponsor@example.com',
			'contact+sponsor@company.com.au',
			'test.user@sub.domain.org',
		);

		foreach ( $valid_emails as $email ) {
			$result = $this->validator->validate_email( $email, false ); // Skip DNS check
			$this->assertTrue( $result, "Email '{$email}' should be valid" );
		}

		// Invalid emails
		$invalid_emails = array(
			'not-an-email',
			'@example.com',
			'sponsor@',
			'sponsor@.com',
			'',
		);

		foreach ( $invalid_emails as $email ) {
			$result = $this->validator->validate_email( $email, false );
			$this->assertWPError( $result, "Email '{$email}' should be invalid" );
		}
	}

	/**
	 * Test: Duplicate ABN detection prevents duplicate registrations
	 */
	public function test_duplicate_abn_detection() {
		// Create existing sponsor with ABN
		$existing_sponsor_id = $this->factory->post->create( array(
			'post_type'  => 'ems_sponsor',
			'post_title' => 'Existing Sponsor Ltd',
		) );
		update_post_meta( $existing_sponsor_id, '_ems_sponsor_abn', '53004085616' );

		// Attempt to create another sponsor with same ABN
		$duplicate_check = $this->check_duplicate_abn( '53004085616' );

		$this->assertTrue( $duplicate_check, 'Should detect duplicate ABN' );
	}

	/**
	 * Test: Duplicate email detection prevents duplicate accounts
	 */
	public function test_duplicate_email_detection() {
		// Create existing user with email
		$existing_user_id = $this->factory->user->create( array(
			'user_email' => 'sponsor@example.com',
			'role'       => 'ems_sponsor',
		) );

		// Check for duplicate email
		$duplicate_check = email_exists( 'sponsor@example.com' );

		$this->assertEquals( $existing_user_id, $duplicate_check, 'Should detect duplicate email' );

		// Different email should not be detected as duplicate
		$no_duplicate = email_exists( 'different@example.com' );
		$this->assertFalse( $no_duplicate, 'Different email should not be detected as duplicate' );
	}

	/**
	 * Test: Rate limiting prevents spam submissions
	 */
	public function test_rate_limiting() {
		$ip_address = '192.168.1.100';
		$transient_key = 'ems_onboarding_rate_limit_' . md5( $ip_address );

		// Simulate 3 submissions
		set_transient( $transient_key, array(
			time() - 600,  // 10 minutes ago
			time() - 300,  // 5 minutes ago
			time() - 60,   // 1 minute ago
		), HOUR_IN_SECONDS );

		// Check if rate limit exceeded (3 submissions per hour)
		$submissions = get_transient( $transient_key );
		$recent_submissions = array_filter( $submissions, function( $timestamp ) {
			return $timestamp > ( time() - HOUR_IN_SECONDS );
		} );

		$this->assertCount( 3, $recent_submissions, 'Should track 3 recent submissions' );

		$is_rate_limited = count( $recent_submissions ) >= 3;
		$this->assertTrue( $is_rate_limited, 'Should be rate limited after 3 submissions' );
	}

	/**
	 * Test: Complete form data validation (integration test)
	 */
	public function test_complete_form_validation() {
		$valid_data = array(
			'legal_name'     => 'Acme Medical Corporation Pty Ltd',
			'trading_name'   => 'Acme Medical',
			'abn'            => '53004085616',
			'acn'            => '004085616',
			'website_url'    => 'https://www.acmemedical.com.au',
			'contact_name'   => 'Jane Smith',
			'contact_email'  => 'jane.smith@acmemedical.com.au',
			'contact_phone'  => '+61 2 9876 5432',
			'signatory_name' => 'John Doe',
			'signatory_email' => 'john.doe@acmemedical.com.au',
		);

		// Validate all fields
		$errors = array();

		// Required fields
		$required_fields = array( 'legal_name', 'abn', 'contact_name', 'contact_email', 'signatory_name' );
		foreach ( $required_fields as $field ) {
			$result = $this->validator->validate_required( $valid_data[ $field ], ucfirst( str_replace( '_', ' ', $field ) ) );
			if ( is_wp_error( $result ) ) {
				$errors[ $field ] = $result->get_error_message();
			}
		}

		// Email fields
		$email_fields = array( 'contact_email', 'signatory_email' );
		foreach ( $email_fields as $field ) {
			$result = $this->validator->validate_email( $valid_data[ $field ], false );
			if ( is_wp_error( $result ) ) {
				$errors[ $field ] = $result->get_error_message();
			}
		}

		// URL fields
		$result = $this->validator->validate_url( $valid_data['website_url'] );
		if ( is_wp_error( $result ) ) {
			$errors['website_url'] = $result->get_error_message();
		}

		// Phone fields
		$result = $this->validator->validate_phone( $valid_data['contact_phone'] );
		if ( is_wp_error( $result ) ) {
			$errors['contact_phone'] = $result->get_error_message();
		}

		$this->assertEmpty( $errors, 'Valid complete form data should pass all validations. Errors: ' . print_r( $errors, true ) );
	}

	/**
	 * Helper: Check for duplicate ABN
	 *
	 * @param string $abn ABN to check
	 * @return bool True if duplicate found
	 */
	private function check_duplicate_abn( $abn ) {
		global $wpdb;

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ems_sponsor_abn' AND meta_value = %s LIMIT 1",
			$abn
		) );

		return ! empty( $existing );
	}
}
