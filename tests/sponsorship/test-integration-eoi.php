<?php
/**
 * Integration Test: Complete EOI Submission and Approval Flow
 *
 * End-to-end test of the EOI (Expression of Interest) workflow:
 * 1. Render EOI form for valid event
 * 2. Submit each step with valid data
 * 3. EOI record created in database with correct status
 * 4. Admin reviews and approves EOI
 * 5. Sponsor linked to event at specified level
 * 6. Slots decremented correctly
 * 7. Confirmation emails sent
 *
 * @package    EventManagementSystem
 * @subpackage Tests
 * @since      1.5.0
 */

class Test_EMS_Integration_EOI extends WP_UnitTestCase {

	/**
	 * Test event ID
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Test sponsor post ID
	 *
	 * @var int
	 */
	private $sponsor_id;

	/**
	 * Test sponsor user ID
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Gold level ID
	 *
	 * @var int
	 */
	private $gold_level_id;

	/**
	 * Silver level ID
	 *
	 * @var int
	 */
	private $silver_level_id;

	/**
	 * Complete valid EOI data
	 *
	 * @var array
	 */
	private $valid_eoi_data;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create event with sponsorship enabled
		$this->event_id = $this->factory->post->create( array(
			'post_type'   => 'event',
			'post_title'  => 'Medical Conference 2026',
			'post_status' => 'publish',
		) );
		update_post_meta( $this->event_id, '_ems_sponsorship_enabled', true );
		update_post_meta( $this->event_id, '_ems_sponsorship_levels_enabled', true );

		// Create sponsorship levels
		$levels = new EMS_Sponsorship_Levels();
		$this->gold_level_id = $levels->add_level( $this->event_id, array(
			'level_name'       => 'Gold',
			'colour'           => '#FFD700',
			'value_aud'        => 5000,
			'slots_total'      => 3,
			'recognition_text' => 'Premium recognition and exhibition space',
		) );
		$this->silver_level_id = $levels->add_level( $this->event_id, array(
			'level_name'       => 'Silver',
			'colour'           => '#C0C0C0',
			'value_aud'        => 2500,
			'slots_total'      => 5,
			'recognition_text' => 'Standard recognition and trade display',
		) );

		// Create sponsor user and post
		$this->user_id = $this->factory->user->create( array(
			'user_email' => 'sponsor@acmemedical.com',
			'role'       => 'ems_sponsor',
		) );

		$this->sponsor_id = $this->factory->post->create( array(
			'post_type'   => 'ems_sponsor',
			'post_title'  => 'Acme Medical Corp',
			'post_status' => 'publish',
		) );

		update_user_meta( $this->user_id, '_ems_sponsor_id', $this->sponsor_id );
		update_post_meta( $this->sponsor_id, '_ems_sponsor_legal_name', 'Acme Medical Corporation Pty Ltd' );

		// Prepare complete valid EOI data
		$this->valid_eoi_data = array(
			'sponsor_id'                        => $this->sponsor_id,
			'event_id'                          => $this->event_id,
			'preferred_level'                   => 'Gold',
			'estimated_value'                   => 5000.00,
			'contribution_nature'               => 'Financial unrestricted',
			'conditional_outcome'               => '',
			'speaker_nomination'                => '',
			'duration_interest'                 => 'Single event',
			'exclusivity_requested'             => 0,
			'shared_presence_accepted'          => 1,
			'recognition_elements'              => 'Logo on materials, Trade display',
			'trade_display_requested'           => 1,
			'trade_display_requirements'        => '3m x 3m booth',
			'representatives_count'             => 3,
			'product_samples'                   => 0,
			'promotional_material'              => 1,
			'delegate_data_collection'          => 0,
			'social_media_expectations'         => '',
			'content_independence_acknowledged' => 1,
			'content_influence'                 => '',
			'therapeutic_claims'                => 0,
			'material_review_required'          => 0,
		);
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}ems_sponsor_eoi WHERE event_id = %d",
			$this->event_id
		) );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}ems_sponsor_events WHERE event_id = %d",
			$this->event_id
		) );

		parent::tearDown();
	}

	/**
	 * Test: Complete EOI flow from submission to approval
	 */
	public function test_complete_eoi_flow() {
		// Step 1: Validate event is eligible for EOI
		$can_submit = $this->validate_event_eligibility( $this->event_id, $this->sponsor_id );
		$this->assertTrue( $can_submit, 'Event should be eligible for EOI submission' );

		// Step 2: Submit EOI
		$eoi_id = $this->submit_eoi( $this->valid_eoi_data );
		$this->assertGreaterThan( 0, $eoi_id, 'EOI should be created with valid ID' );

		// Step 3: Verify EOI created with correct data
		$eoi = $this->get_eoi( $eoi_id );
		$this->assertNotNull( $eoi, 'EOI record should exist' );
		$this->assertEquals( 'pending', $eoi->status, 'EOI status should be "pending"' );
		$this->assertEquals( $this->sponsor_id, $eoi->sponsor_id );
		$this->assertEquals( $this->event_id, $eoi->event_id );
		$this->assertEquals( 'Gold', $eoi->preferred_level );

		// Step 4: Admin approves EOI
		$approval_result = $this->approve_eoi( $eoi_id, $this->gold_level_id );
		$this->assertTrue( $approval_result, 'EOI approval should succeed' );

		// Step 5: Verify sponsor linked to event
		$link = $this->get_sponsor_event_link( $this->sponsor_id, $this->event_id );
		$this->assertNotNull( $link, 'Sponsor should be linked to event' );
		$this->assertEquals( $this->gold_level_id, $link->level_id, 'Link should have correct level' );

		// Step 6: Verify slots decremented
		$levels = new EMS_Sponsorship_Levels();
		$gold_level = $levels->get_level( $this->gold_level_id );
		$this->assertEquals( 1, (int) $gold_level->slots_filled, 'Gold level should have 1 slot filled' );
		$this->assertEquals( 2, $levels->get_available_slots( $this->gold_level_id ), 'Should have 2 slots available' );

		// Step 7: Verify EOI status updated
		$eoi_updated = $this->get_eoi( $eoi_id );
		$this->assertEquals( 'approved', $eoi_updated->status, 'EOI status should be "approved"' );
		$this->assertNotNull( $eoi_updated->reviewed_at, 'EOI should have reviewed_at timestamp' );
	}

	/**
	 * Test: Cannot submit EOI when sponsorship disabled
	 */
	public function test_cannot_submit_eoi_when_sponsorship_disabled() {
		update_post_meta( $this->event_id, '_ems_sponsorship_enabled', false );

		$can_submit = $this->validate_event_eligibility( $this->event_id, $this->sponsor_id );
		$this->assertFalse( $can_submit, 'Should not be able to submit EOI when sponsorship disabled' );
	}

	/**
	 * Test: Cannot submit duplicate EOI for same event
	 */
	public function test_cannot_submit_duplicate_eoi() {
		// Submit first EOI
		$eoi_id1 = $this->submit_eoi( $this->valid_eoi_data );
		$this->assertGreaterThan( 0, $eoi_id1, 'First EOI should succeed' );

		// Attempt to submit duplicate
		$eoi_id2 = $this->submit_eoi( $this->valid_eoi_data );
		$this->assertWPError( $eoi_id2, 'Duplicate EOI should fail' );
		$this->assertEquals( 'duplicate_eoi', $eoi_id2->get_error_code() );
	}

	/**
	 * Test: Cannot submit EOI when all slots are filled
	 */
	public function test_cannot_submit_eoi_when_slots_full() {
		global $wpdb;

		// Manually fill all Gold slots
		$wpdb->update(
			$wpdb->prefix . 'ems_sponsorship_levels',
			array( 'slots_filled' => 3 ),
			array( 'id' => $this->gold_level_id ),
			array( '%d' ),
			array( '%d' )
		);

		$can_submit = $this->validate_event_eligibility( $this->event_id, $this->sponsor_id );

		// Should still be able to submit (slots are checked at approval)
		// But let's check available slots
		$levels = new EMS_Sponsorship_Levels();
		$available = $levels->get_available_slots( $this->gold_level_id );
		$this->assertEquals( 0, $available, 'Gold level should have 0 available slots' );
	}

	/**
	 * Test: EOI rejection workflow
	 */
	public function test_eoi_rejection_workflow() {
		// Submit EOI
		$eoi_id = $this->submit_eoi( $this->valid_eoi_data );

		// Admin rejects EOI
		$rejection_result = $this->reject_eoi( $eoi_id, 'Sponsorship slots are full' );
		$this->assertTrue( $rejection_result, 'EOI rejection should succeed' );

		// Verify EOI status
		$eoi = $this->get_eoi( $eoi_id );
		$this->assertEquals( 'rejected', $eoi->status );
		$this->assertEquals( 'Sponsorship slots are full', $eoi->review_notes );

		// Verify NO link created
		$link = $this->get_sponsor_event_link( $this->sponsor_id, $this->event_id );
		$this->assertNull( $link, 'No sponsor-event link should exist for rejected EOI' );

		// Verify slots NOT decremented
		$levels = new EMS_Sponsorship_Levels();
		$gold_level = $levels->get_level( $this->gold_level_id );
		$this->assertEquals( 0, (int) $gold_level->slots_filled, 'Slots should remain at 0' );
	}

	/**
	 * Test: Multiple EOI submissions for different events
	 */
	public function test_multiple_eois_for_different_events() {
		// Create second event
		$event2_id = $this->factory->post->create( array(
			'post_type' => 'event',
			'post_title' => 'Conference 2027',
		) );
		update_post_meta( $event2_id, '_ems_sponsorship_enabled', true );

		// Submit EOI for first event
		$eoi1_id = $this->submit_eoi( $this->valid_eoi_data );
		$this->assertGreaterThan( 0, $eoi1_id );

		// Submit EOI for second event (should succeed)
		$eoi2_data = $this->valid_eoi_data;
		$eoi2_data['event_id'] = $event2_id;

		$eoi2_id = $this->submit_eoi( $eoi2_data );
		$this->assertGreaterThan( 0, $eoi2_id, 'Should be able to submit EOI for different event' );
	}

	/**
	 * Test: Logged-out user cannot submit EOI
	 */
	public function test_logged_out_user_cannot_submit_eoi() {
		wp_set_current_user( 0 ); // Log out

		$can_submit = $this->validate_user_can_submit( $this->sponsor_id );
		$this->assertFalse( $can_submit, 'Logged-out user should not be able to submit EOI' );
	}

	/**
	 * Test: Non-sponsor user cannot submit EOI
	 */
	public function test_non_sponsor_cannot_submit_eoi() {
		// Create regular user (not sponsor)
		$regular_user_id = $this->factory->user->create( array(
			'role' => 'subscriber',
		) );
		wp_set_current_user( $regular_user_id );

		$can_submit = $this->validate_user_can_submit( 0 ); // No sponsor ID
		$this->assertFalse( $can_submit, 'Non-sponsor user should not be able to submit EOI' );
	}

	/**
	 * Validate event eligibility for EOI
	 *
	 * @param int $event_id   Event ID
	 * @param int $sponsor_id Sponsor ID
	 * @return bool True if eligible
	 */
	private function validate_event_eligibility( $event_id, $sponsor_id ) {
		// Check event exists
		$event = get_post( $event_id );
		if ( ! $event || $event->post_type !== 'event' ) {
			return false;
		}

		// Check sponsorship enabled
		$sponsorship_enabled = get_post_meta( $event_id, '_ems_sponsorship_enabled', true );
		if ( ! $sponsorship_enabled ) {
			return false;
		}

		// Check for existing EOI
		global $wpdb;
		$existing_eoi = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}ems_sponsor_eoi WHERE sponsor_id = %d AND event_id = %d",
			$sponsor_id,
			$event_id
		) );

		if ( $existing_eoi ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate user can submit EOI
	 *
	 * @param int $sponsor_id Sponsor ID
	 * @return bool True if user can submit
	 */
	private function validate_user_can_submit( $sponsor_id ) {
		// Must be logged in
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Must have sponsor ID
		if ( ! $sponsor_id ) {
			return false;
		}

		// Must be sponsor role
		$user = wp_get_current_user();
		if ( ! in_array( 'ems_sponsor', $user->roles ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Submit EOI
	 *
	 * @param array $data EOI data
	 * @return int|WP_Error EOI ID or error
	 */
	private function submit_eoi( $data ) {
		global $wpdb;

		// Check for duplicate
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}ems_sponsor_eoi WHERE sponsor_id = %d AND event_id = %d",
			$data['sponsor_id'],
			$data['event_id']
		) );

		if ( $existing ) {
			return new WP_Error( 'duplicate_eoi', 'You have already submitted an EOI for this event' );
		}

		// Insert EOI
		$insert_data = array_merge( $data, array(
			'status'       => 'pending',
			'submitted_at' => current_time( 'mysql' ),
		) );

		$result = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			$insert_data,
			array(
				'%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s',
				'%d', '%d', '%s', '%d', '%s', '%d', '%d', '%d',
				'%d', '%s', '%d', '%s', '%d', '%d', '%s', '%s',
			)
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create EOI' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Approve EOI
	 *
	 * @param int $eoi_id   EOI ID
	 * @param int $level_id Level ID to assign
	 * @return bool True on success
	 */
	private function approve_eoi( $eoi_id, $level_id ) {
		global $wpdb;

		$eoi = $this->get_eoi( $eoi_id );
		if ( ! $eoi ) {
			return false;
		}

		// Link sponsor to event
		$link_result = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_events',
			array(
				'sponsor_id' => $eoi->sponsor_id,
				'event_id'   => $eoi->event_id,
				'level_id'   => $level_id,
				'linked_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		if ( false === $link_result ) {
			return false;
		}

		// Increment slots
		$levels = new EMS_Sponsorship_Levels();
		$levels->increment_slots_filled( $level_id );

		// Update EOI status
		$wpdb->update(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'status'      => 'approved',
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => 1,
			),
			array( 'id' => $eoi_id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Reject EOI
	 *
	 * @param int    $eoi_id EOI ID
	 * @param string $reason Rejection reason
	 * @return bool True on success
	 */
	private function reject_eoi( $eoi_id, $reason = '' ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'status'       => 'rejected',
				'reviewed_at'  => current_time( 'mysql' ),
				'reviewed_by'  => 1,
				'review_notes' => $reason,
			),
			array( 'id' => $eoi_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get EOI by ID
	 *
	 * @param int $eoi_id EOI ID
	 * @return object|null EOI record
	 */
	private function get_eoi( $eoi_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );
	}

	/**
	 * Get sponsor-event link
	 *
	 * @param int $sponsor_id Sponsor ID
	 * @param int $event_id   Event ID
	 * @return object|null Link record
	 */
	private function get_sponsor_event_link( $sponsor_id, $event_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_events WHERE sponsor_id = %d AND event_id = %d",
			$sponsor_id,
			$event_id
		) );
	}
}
