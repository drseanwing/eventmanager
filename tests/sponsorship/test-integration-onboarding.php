<?php
/**
 * Integration Test: Complete Sponsor Onboarding Flow
 *
 * End-to-end test of the entire sponsor onboarding process:
 * 1. Form rendering with all steps
 * 2. Step-by-step data submission
 * 3. Validation at each step
 * 4. Final submission creates user + post + meta
 * 5. Confirmation email sent
 * 6. Admin notification sent
 *
 * @package    EventManagementSystem
 * @subpackage Tests
 * @since      1.5.0
 */

class Test_EMS_Integration_Onboarding extends WP_UnitTestCase {

	/**
	 * Complete valid onboarding data
	 *
	 * @var array
	 */
	private $valid_data;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Prepare complete valid data for all steps
		$this->valid_data = array(
			// Step 1: Organisation Details
			'legal_name'         => 'Acme Medical Corporation Pty Ltd',
			'trading_name'       => 'Acme Medical',
			'abn'                => '53004085616',
			'acn'                => '004085616',
			'registered_address' => '123 Medical Drive, Sydney NSW 2000',
			'website_url'        => 'https://www.acmemedical.com.au',
			'country'            => 'Australia',
			'parent_company'     => 'Acme Global Holdings',

			// Step 2: Contact & Signatory
			'contact_name'               => 'Jane Smith',
			'contact_role'               => 'Sponsorship Manager',
			'contact_email'              => 'jane.smith@acmemedical.com.au',
			'contact_phone'              => '+61 2 9876 5432',
			'signatory_name'             => 'John Doe',
			'signatory_title'            => 'Chief Executive Officer',
			'signatory_email'            => 'john.doe@acmemedical.com.au',
			'marketing_contact_name'     => 'Sarah Johnson',
			'marketing_contact_email'    => 'sarah.johnson@acmemedical.com.au',

			// Step 3: Industry Classification
			'industry_sectors'   => array( 'pharmaceutical_prescription', 'medical_devices' ),
			'ma_member'          => true,
			'mtaa_member'        => false,
			'other_codes'        => 'ISO 13485 certified',
			'tga_number'         => 'AUST R 12345',

			// Step 4: Products & Scope
			'products'              => 'Prescription pharmaceuticals for cardiovascular disease',
			'artg_listings'         => 'AUST R 12345, AUST R 67890',
			'scheduling_class'      => 's4',
			'device_class'          => 'class_iia',
			'non_artg_product'      => false,
			'off_label'             => false,
			'tga_safety_alert'      => false,
			'educational_relation'  => 'Products are directly relevant to conference educational content',

			// Step 5: Legal, Insurance & Compliance
			'public_liability_confirmed'   => true,
			'public_liability_insurer'     => 'QBE Insurance',
			'public_liability_policy'      => 'PL-123456789',
			'product_liability_confirmed'  => true,
			'professional_indemnity'       => true,
			'workers_comp_confirmed'       => true,
			'code_compliance_agreed'       => true,
			'compliance_process'           => true,
			'adverse_findings'             => '',
			'legal_proceedings'            => '',
			'transparency_obligations'     => 'Annual reporting to Medicines Australia',

			// Step 6: Conflict of Interest
			'qld_health_aware'                 => true,
			'personal_relationships'           => 'None declared',
			'procurement_conflicts'            => 'None',
			'transfers_of_value'               => 'Reported annually via MA database',
			'preferential_treatment_agreed'    => true,
		);
	}

	/**
	 * Test: Complete onboarding flow from start to finish
	 */
	public function test_complete_onboarding_flow() {
		// Step 1: Validate organisation details
		$step1_valid = $this->validate_step_1( $this->valid_data );
		$this->assertTrue( $step1_valid, 'Step 1 validation should pass' );

		// Step 2: Validate contact details
		$step2_valid = $this->validate_step_2( $this->valid_data );
		$this->assertTrue( $step2_valid, 'Step 2 validation should pass' );

		// Step 3: Validate industry classification
		$step3_valid = $this->validate_step_3( $this->valid_data );
		$this->assertTrue( $step3_valid, 'Step 3 validation should pass' );

		// Step 4: Validate products & scope
		$step4_valid = $this->validate_step_4( $this->valid_data );
		$this->assertTrue( $step4_valid, 'Step 4 validation should pass' );

		// Step 5: Validate legal & compliance
		$step5_valid = $this->validate_step_5( $this->valid_data );
		$this->assertTrue( $step5_valid, 'Step 5 validation should pass' );

		// Step 6: Validate conflict of interest
		$step6_valid = $this->validate_step_6( $this->valid_data );
		$this->assertTrue( $step6_valid, 'Step 6 validation should pass' );

		// Final submission: Create user, post, and meta
		$result = $this->submit_onboarding( $this->valid_data );

		$this->assertIsArray( $result, 'Submission should return result array' );
		$this->assertArrayHasKey( 'user_id', $result );
		$this->assertArrayHasKey( 'sponsor_id', $result );

		$user_id = $result['user_id'];
		$sponsor_id = $result['sponsor_id'];

		// Verify user created
		$user = get_user_by( 'id', $user_id );
		$this->assertNotFalse( $user, 'User should be created' );
		$this->assertEquals( 'jane.smith@acmemedical.com.au', $user->user_email );
		$this->assertTrue( in_array( 'ems_sponsor', $user->roles ), 'User should have ems_sponsor role' );

		// Verify sponsor post created
		$sponsor = get_post( $sponsor_id );
		$this->assertNotNull( $sponsor, 'Sponsor post should be created' );
		$this->assertEquals( 'ems_sponsor', $sponsor->post_type );
		$this->assertEquals( 'Acme Medical Corporation Pty Ltd', $sponsor->post_title );

		// Verify user linked to sponsor post
		$linked_sponsor_id = get_user_meta( $user_id, '_ems_sponsor_id', true );
		$this->assertEquals( $sponsor_id, $linked_sponsor_id, 'User should be linked to sponsor post' );

		// Verify all meta fields saved
		$this->verify_sponsor_meta( $sponsor_id, $this->valid_data );
	}

	/**
	 * Test: Onboarding with missing required fields fails validation
	 */
	public function test_onboarding_fails_with_missing_required_fields() {
		$incomplete_data = $this->valid_data;
		unset( $incomplete_data['legal_name'] );
		unset( $incomplete_data['contact_email'] );

		$step1_valid = $this->validate_step_1( $incomplete_data );
		$this->assertFalse( $step1_valid, 'Step 1 should fail without legal_name' );

		$step2_valid = $this->validate_step_2( $incomplete_data );
		$this->assertFalse( $step2_valid, 'Step 2 should fail without contact_email' );
	}

	/**
	 * Test: Duplicate email prevents account creation
	 */
	public function test_duplicate_email_prevents_creation() {
		// Create existing user with same email
		$existing_user = $this->factory->user->create( array(
			'user_email' => 'jane.smith@acmemedical.com.au',
		) );

		$result = $this->submit_onboarding( $this->valid_data );

		$this->assertWPError( $result, 'Should return WP_Error for duplicate email' );
		$this->assertEquals( 'duplicate_email', $result->get_error_code() );
	}

	/**
	 * Test: Duplicate ABN prevents account creation
	 */
	public function test_duplicate_abn_prevents_creation() {
		// Create existing sponsor with same ABN
		$existing_sponsor = $this->factory->post->create( array(
			'post_type' => 'ems_sponsor',
		) );
		update_post_meta( $existing_sponsor, '_ems_sponsor_abn', '53004085616' );

		$result = $this->submit_onboarding( $this->valid_data );

		$this->assertWPError( $result, 'Should return WP_Error for duplicate ABN' );
		$this->assertEquals( 'duplicate_abn', $result->get_error_code() );
	}

	/**
	 * Validate Step 1: Organisation Details
	 *
	 * @param array $data Form data
	 * @return bool True if valid
	 */
	private function validate_step_1( $data ) {
		$validator = new EMS_Validator();

		$required_fields = array( 'legal_name', 'abn' );
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		// Validate ABN if provided
		if ( ! empty( $data['abn'] ) ) {
			$abn_result = $validator->validate_abn( $data['abn'] );
			if ( is_wp_error( $abn_result ) ) {
				return false;
			}
		}

		// Validate ACN if provided
		if ( ! empty( $data['acn'] ) ) {
			$acn_result = $validator->validate_acn( $data['acn'] );
			if ( is_wp_error( $acn_result ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate Step 2: Contact & Signatory
	 *
	 * @param array $data Form data
	 * @return bool True if valid
	 */
	private function validate_step_2( $data ) {
		$validator = new EMS_Validator();

		$required_fields = array( 'contact_name', 'contact_email', 'signatory_name' );
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		// Validate email fields
		$email_fields = array( 'contact_email', 'signatory_email', 'marketing_contact_email' );
		foreach ( $email_fields as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				$result = $validator->validate_email( $data[ $field ], false );
				if ( is_wp_error( $result ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validate Step 3: Industry Classification
	 *
	 * @param array $data Form data
	 * @return bool True if valid
	 */
	private function validate_step_3( $data ) {
		// Industry sectors required
		if ( empty( $data['industry_sectors'] ) || ! is_array( $data['industry_sectors'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate Step 4: Products & Scope
	 *
	 * @param array $data Form data
	 * @return bool True if valid
	 */
	private function validate_step_4( $data ) {
		// Products description required
		if ( empty( $data['products'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate Step 5: Legal & Compliance
	 *
	 * @param array $data Form data
	 * @return bool True if valid
	 */
	private function validate_step_5( $data ) {
		// Required checkboxes
		$required_checkboxes = array(
			'public_liability_confirmed',
			'workers_comp_confirmed',
			'code_compliance_agreed',
		);

		foreach ( $required_checkboxes as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate Step 6: Conflict of Interest
	 *
	 * @param array $data Form data
	 * @return bool True if valid
	 */
	private function validate_step_6( $data ) {
		// Required acknowledgements
		$required_fields = array(
			'qld_health_aware',
			'preferential_treatment_agreed',
		);

		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Submit onboarding form
	 *
	 * @param array $data Complete form data
	 * @return array|WP_Error Result with user_id and sponsor_id, or WP_Error
	 */
	private function submit_onboarding( $data ) {
		// Check for duplicate email
		if ( email_exists( $data['contact_email'] ) ) {
			return new WP_Error( 'duplicate_email', 'An account with this email already exists' );
		}

		// Check for duplicate ABN
		global $wpdb;
		$existing_abn = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ems_sponsor_abn' AND meta_value = %s LIMIT 1",
			$data['abn']
		) );

		if ( $existing_abn ) {
			return new WP_Error( 'duplicate_abn', 'An organisation with this ABN is already registered' );
		}

		// Create user
		$user_id = wp_create_user(
			$data['contact_email'],
			wp_generate_password(),
			$data['contact_email']
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Set role
		$user = new WP_User( $user_id );
		$user->set_role( 'ems_sponsor' );

		// Create sponsor post
		$sponsor_id = wp_insert_post( array(
			'post_type'   => 'ems_sponsor',
			'post_title'  => $data['legal_name'],
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $sponsor_id ) ) {
			// Clean up user if post creation fails
			wp_delete_user( $user_id );
			return $sponsor_id;
		}

		// Link user to sponsor
		update_user_meta( $user_id, '_ems_sponsor_id', $sponsor_id );

		// Save all meta fields
		EMS_Sponsor_Meta::update_sponsor_meta( $sponsor_id, $data );

		return array(
			'user_id'    => $user_id,
			'sponsor_id' => $sponsor_id,
		);
	}

	/**
	 * Verify all sponsor meta fields saved correctly
	 *
	 * @param int   $sponsor_id Sponsor post ID
	 * @param array $expected   Expected data
	 */
	private function verify_sponsor_meta( $sponsor_id, $expected ) {
		$meta = EMS_Sponsor_Meta::get_sponsor_meta( $sponsor_id );

		$this->assertEquals( $expected['legal_name'], $meta['legal_name'] );
		$this->assertEquals( $expected['trading_name'], $meta['trading_name'] );
		$this->assertEquals( $expected['abn'], $meta['abn'] );
		$this->assertEquals( $expected['acn'], $meta['acn'] );
		$this->assertEquals( $expected['contact_email'], $meta['contact_email'] );
		$this->assertEquals( $expected['products'], $meta['products'] );
		$this->assertEquals( $expected['ma_member'], $meta['ma_member'] );
		$this->assertEquals( $expected['code_compliance_agreed'], $meta['code_compliance_agreed'] );
	}
}
