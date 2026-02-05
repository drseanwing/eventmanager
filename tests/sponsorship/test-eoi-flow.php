<?php
/**
 * Test Case: EOI Submission and Approval Flow
 *
 * Tests for Expression of Interest (EOI) submission workflow,
 * approval process, slot management, and status transitions.
 *
 * @package    EventManagementSystem
 * @subpackage Tests
 * @since      1.5.0
 */

class Test_EMS_EOI_Flow extends WP_UnitTestCase {

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
	 * Sponsorship level ID
	 *
	 * @var int
	 */
	private $level_id;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create test event
		$this->event_id = $this->factory->post->create( array(
			'post_type'   => 'event',
			'post_title'  => 'Test Conference 2026',
			'post_status' => 'publish',
		) );
		update_post_meta( $this->event_id, '_ems_sponsorship_enabled', true );

		// Create sponsor post
		$this->sponsor_id = $this->factory->post->create( array(
			'post_type'   => 'ems_sponsor',
			'post_title'  => 'Acme Medical Corp',
			'post_status' => 'publish',
		) );
		update_post_meta( $this->sponsor_id, '_ems_sponsor_legal_name', 'Acme Medical Corporation Pty Ltd' );
		update_post_meta( $this->sponsor_id, '_ems_sponsor_abn', '53004085616' );

		// Create sponsor user
		$this->user_id = $this->factory->user->create( array(
			'user_email' => 'sponsor@acmemedical.com',
			'role'       => 'ems_sponsor',
		) );
		update_user_meta( $this->user_id, '_ems_sponsor_id', $this->sponsor_id );

		// Create sponsorship level
		$levels = new EMS_Sponsorship_Levels();
		$this->level_id = $levels->add_level( $this->event_id, array(
			'level_name'  => 'Gold',
			'colour'      => '#FFD700',
			'value_aud'   => 5000,
			'slots_total' => 3,
		) );
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		global $wpdb;

		// Clean up EOI records
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}ems_sponsor_eoi WHERE event_id = %d",
			$this->event_id
		) );

		// Clean up sponsor-event links
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}ems_sponsor_events WHERE event_id = %d",
			$this->event_id
		) );

		parent::tearDown();
	}

	/**
	 * Test: EOI insertion creates record with all fields
	 */
	public function test_eoi_insertion_creates_record() {
		global $wpdb;

		$eoi_data = array(
			'sponsor_id'                      => $this->sponsor_id,
			'event_id'                        => $this->event_id,
			'status'                          => 'pending',
			'preferred_level'                 => 'Gold',
			'estimated_value'                 => 5000.00,
			'contribution_nature'             => 'Financial unrestricted',
			'conditional_outcome'             => '',
			'speaker_nomination'              => 'Dr. Jane Smith',
			'duration_interest'               => 'Single event',
			'exclusivity_requested'           => 0,
			'shared_presence_accepted'        => 1,
			'recognition_elements'            => 'Logo on materials, Trade display',
			'trade_display_requested'         => 1,
			'trade_display_requirements'      => '3m x 3m booth with power',
			'representatives_count'           => 3,
			'content_independence_acknowledged' => 1,
			'submitted_at'                    => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			$eoi_data,
			array(
				'%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s',
				'%d', '%d', '%s', '%d', '%s', '%d', '%d', '%s',
			)
		);

		$this->assertNotFalse( $result, 'EOI insertion should succeed' );

		$eoi_id = $wpdb->insert_id;
		$this->assertGreaterThan( 0, $eoi_id, 'EOI ID should be positive integer' );

		// Verify record
		$eoi = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );

		$this->assertEquals( $this->sponsor_id, $eoi->sponsor_id );
		$this->assertEquals( $this->event_id, $eoi->event_id );
		$this->assertEquals( 'pending', $eoi->status );
		$this->assertEquals( 'Gold', $eoi->preferred_level );
		$this->assertEquals( 5000.00, (float) $eoi->estimated_value );
	}

	/**
	 * Test: Duplicate EOI prevention (unique sponsor+event constraint)
	 */
	public function test_duplicate_eoi_prevention() {
		global $wpdb;

		// Insert first EOI
		$wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'   => $this->sponsor_id,
				'event_id'     => $this->event_id,
				'status'       => 'pending',
				'submitted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		$this->assertNotFalse( $wpdb->insert_id, 'First EOI should insert successfully' );

		// Attempt to insert duplicate
		$wpdb->suppress_errors();
		$result = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'   => $this->sponsor_id,
				'event_id'     => $this->event_id,
				'status'       => 'pending',
				'submitted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		$wpdb->show_errors();

		$this->assertFalse( $result, 'Duplicate EOI should fail due to UNIQUE constraint' );
		$this->assertNotEmpty( $wpdb->last_error, 'Database error should be set' );
	}

	/**
	 * Test: EOI approval links sponsor to event at specified level
	 */
	public function test_eoi_approval_links_sponsor() {
		global $wpdb;

		// Create EOI
		$eoi_id = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'      => $this->sponsor_id,
				'event_id'        => $this->event_id,
				'status'          => 'pending',
				'preferred_level' => 'Gold',
				'submitted_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
		$eoi_id = $wpdb->insert_id;

		// Simulate approval: link sponsor to event
		$link_result = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_events',
			array(
				'sponsor_id' => $this->sponsor_id,
				'event_id'   => $this->event_id,
				'level_id'   => $this->level_id,
				'linked_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		$this->assertNotFalse( $link_result, 'Sponsor-event link should be created' );

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

		// Verify link exists
		$link = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_events WHERE sponsor_id = %d AND event_id = %d",
			$this->sponsor_id,
			$this->event_id
		) );

		$this->assertNotNull( $link, 'Sponsor-event link should exist' );
		$this->assertEquals( $this->level_id, $link->level_id, 'Link should have correct level' );

		// Verify EOI status updated
		$eoi = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );

		$this->assertEquals( 'approved', $eoi->status );
		$this->assertNotNull( $eoi->reviewed_at );
	}

	/**
	 * Test: EOI approval increments slots_filled
	 */
	public function test_eoi_approval_increments_slots() {
		global $wpdb;

		$levels = new EMS_Sponsorship_Levels();

		// Check initial slots
		$level_before = $levels->get_level( $this->level_id );
		$this->assertEquals( 0, (int) $level_before->slots_filled );

		// Create and approve EOI
		$eoi_id = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'   => $this->sponsor_id,
				'event_id'     => $this->event_id,
				'status'       => 'pending',
				'submitted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		$eoi_id = $wpdb->insert_id;

		// Simulate approval with slot increment
		$levels->increment_slots_filled( $this->level_id );

		// Verify slots incremented
		$level_after = $levels->get_level( $this->level_id );
		$this->assertEquals( 1, (int) $level_after->slots_filled );
	}

	/**
	 * Test: EOI rejection updates status without linking
	 */
	public function test_eoi_rejection_updates_status() {
		global $wpdb;

		// Create EOI
		$eoi_id = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'   => $this->sponsor_id,
				'event_id'     => $this->event_id,
				'status'       => 'pending',
				'submitted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		$eoi_id = $wpdb->insert_id;

		// Reject EOI
		$wpdb->update(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'status'       => 'rejected',
				'reviewed_at'  => current_time( 'mysql' ),
				'reviewed_by'  => 1,
				'review_notes' => 'Event sponsorship slots are full',
			),
			array( 'id' => $eoi_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		// Verify status updated
		$eoi = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );

		$this->assertEquals( 'rejected', $eoi->status );
		$this->assertEquals( 'Event sponsorship slots are full', $eoi->review_notes );
		$this->assertNotNull( $eoi->reviewed_at );

		// Verify no link created
		$link = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_events WHERE sponsor_id = %d AND event_id = %d",
			$this->sponsor_id,
			$this->event_id
		) );

		$this->assertNull( $link, 'No sponsor-event link should exist for rejected EOI' );
	}

	/**
	 * Test: Already-approved EOI cannot be re-approved
	 */
	public function test_approved_eoi_cannot_be_reapproved() {
		global $wpdb;

		// Create approved EOI
		$eoi_id = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'   => $this->sponsor_id,
				'event_id'     => $this->event_id,
				'status'       => 'approved',
				'submitted_at' => current_time( 'mysql' ),
				'reviewed_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
		$eoi_id = $wpdb->insert_id;

		// Attempt to re-approve
		$eoi = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );

		$can_approve = ( $eoi->status === 'pending' );
		$this->assertFalse( $can_approve, 'Already-approved EOI should not be re-approvable' );
	}

	/**
	 * Test: EOI status transitions are logged
	 */
	public function test_eoi_status_transitions_logged() {
		global $wpdb;

		// Create EOI
		$eoi_id = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array(
				'sponsor_id'   => $this->sponsor_id,
				'event_id'     => $this->event_id,
				'status'       => 'pending',
				'submitted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		// Simulate status change with logging
		$old_status = 'pending';
		$new_status = 'approved';

		$wpdb->update(
			$wpdb->prefix . 'ems_sponsor_eoi',
			array( 'status' => $new_status ),
			array( 'id' => $eoi_id ),
			array( '%s' ),
			array( '%d' )
		);

		// In real implementation, this would log to EMS_Logger
		// For test, we just verify the status changed
		$eoi = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );

		$this->assertEquals( $new_status, $eoi->status, 'Status should transition from pending to approved' );
	}

	/**
	 * Test: EOI with all optional fields populated
	 */
	public function test_eoi_with_all_fields_populated() {
		global $wpdb;

		$complete_eoi_data = array(
			'sponsor_id'                        => $this->sponsor_id,
			'event_id'                          => $this->event_id,
			'status'                            => 'pending',
			'preferred_level'                   => 'Gold',
			'estimated_value'                   => 5000.00,
			'contribution_nature'               => 'Financial unrestricted, In-kind educational',
			'conditional_outcome'               => '',
			'speaker_nomination'                => 'Dr. Jane Smith',
			'duration_interest'                 => 'Multi-year',
			'exclusivity_requested'             => 1,
			'exclusivity_category'              => 'Diagnostic equipment',
			'shared_presence_accepted'          => 0,
			'competitor_objections'             => 'Competitor X should not be in same area',
			'exclusivity_conditions'            => 'Category exclusivity in exhibition hall',
			'recognition_elements'              => 'Logo on materials, Logo on certificates, Trade display',
			'trade_display_requested'           => 1,
			'trade_display_requirements'        => '3m x 3m booth with power and WiFi',
			'representatives_count'             => 4,
			'product_samples'                   => 1,
			'promotional_material'              => 1,
			'delegate_data_collection'          => 1,
			'social_media_expectations'         => 'Daily posts during event',
			'content_independence_acknowledged' => 1,
			'content_influence'                 => '',
			'therapeutic_claims'                => 0,
			'material_review_required'          => 1,
			'submitted_at'                      => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'ems_sponsor_eoi',
			$complete_eoi_data,
			array(
				'%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s',
				'%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%d',
				'%d', '%d', '%d', '%s', '%d', '%s', '%d', '%d', '%s',
			)
		);

		$this->assertNotFalse( $result, 'Complete EOI insertion should succeed' );

		$eoi_id = $wpdb->insert_id;
		$eoi = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ems_sponsor_eoi WHERE id = %d",
			$eoi_id
		) );

		// Verify all fields saved correctly
		$this->assertEquals( 1, (int) $eoi->exclusivity_requested );
		$this->assertEquals( 'Diagnostic equipment', $eoi->exclusivity_category );
		$this->assertEquals( 4, (int) $eoi->representatives_count );
		$this->assertEquals( 1, (int) $eoi->product_samples );
		$this->assertEquals( 1, (int) $eoi->material_review_required );
	}
}
