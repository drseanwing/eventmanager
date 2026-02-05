<?php
/**
 * Test Case: Sponsorship Levels CRUD Operations
 *
 * Tests for EMS_Sponsorship_Levels class covering all CRUD operations,
 * slot management, and data integrity.
 *
 * @package    EventManagementSystem
 * @subpackage Tests
 * @since      1.5.0
 */

class Test_EMS_Sponsorship_Levels extends WP_UnitTestCase {

	/**
	 * Sponsorship levels instance
	 *
	 * @var EMS_Sponsorship_Levels
	 */
	private $levels;

	/**
	 * Test event ID
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		$this->levels = new EMS_Sponsorship_Levels();

		// Create a test event
		$this->event_id = $this->factory->post->create( array(
			'post_type'   => 'event',
			'post_title'  => 'Test Conference 2026',
			'post_status' => 'publish',
		) );

		// Enable sponsorship for the event
		update_post_meta( $this->event_id, '_ems_sponsorship_enabled', true );
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown() {
		global $wpdb;

		// Clean up test data
		$wpdb->query( "DELETE FROM {$wpdb->prefix}ems_sponsorship_levels WHERE event_id = {$this->event_id}" );

		parent::tearDown();
	}

	/**
	 * Test: add_level() creates a record with all fields
	 */
	public function test_add_level_creates_record() {
		$level_data = array(
			'level_name'       => 'Gold',
			'colour'           => '#FFD700',
			'value_aud'        => 5000.00,
			'slots_total'      => 5,
			'recognition_text' => 'Logo on website, prime booth location',
			'sort_order'       => 1,
		);

		$level_id = $this->levels->add_level( $this->event_id, $level_data );

		$this->assertNotFalse( $level_id, 'Level ID should be returned' );
		$this->assertGreaterThan( 0, $level_id, 'Level ID should be positive integer' );

		// Verify the level was created with correct data
		$level = $this->levels->get_level( $level_id );

		$this->assertNotNull( $level, 'Level should be retrievable' );
		$this->assertEquals( 'Gold', $level->level_name );
		$this->assertEquals( 'gold', $level->level_slug );
		$this->assertEquals( '#FFD700', $level->colour );
		$this->assertEquals( 5000.00, (float) $level->value_aud );
		$this->assertEquals( 5, (int) $level->slots_total );
		$this->assertEquals( 0, (int) $level->slots_filled );
		$this->assertEquals( 1, (int) $level->sort_order );
		$this->assertEquals( 1, (int) $level->enabled );
	}

	/**
	 * Test: add_level() enforces slug uniqueness within event
	 */
	public function test_add_level_enforces_unique_slug() {
		// Create first Gold level
		$level1_id = $this->levels->add_level( $this->event_id, array(
			'level_name' => 'Gold',
			'colour'     => '#FFD700',
		) );

		// Create second Gold level (should get auto-incremented slug)
		$level2_id = $this->levels->add_level( $this->event_id, array(
			'level_name' => 'Gold',
			'colour'     => '#FFD700',
		) );

		$level1 = $this->levels->get_level( $level1_id );
		$level2 = $this->levels->get_level( $level2_id );

		$this->assertEquals( 'gold', $level1->level_slug );
		$this->assertEquals( 'gold-1', $level2->level_slug );
	}

	/**
	 * Test: get_event_levels() returns all levels for event ordered by sort_order
	 */
	public function test_get_event_levels_returns_ordered_levels() {
		// Create levels in random order
		$this->levels->add_level( $this->event_id, array(
			'level_name' => 'Bronze',
			'sort_order' => 3,
		) );
		$this->levels->add_level( $this->event_id, array(
			'level_name' => 'Gold',
			'sort_order' => 1,
		) );
		$this->levels->add_level( $this->event_id, array(
			'level_name' => 'Silver',
			'sort_order' => 2,
		) );

		$levels = $this->levels->get_event_levels( $this->event_id );

		$this->assertCount( 3, $levels, 'Should return 3 levels' );
		$this->assertEquals( 'Gold', $levels[0]->level_name, 'First should be Gold (sort_order 1)' );
		$this->assertEquals( 'Silver', $levels[1]->level_name, 'Second should be Silver (sort_order 2)' );
		$this->assertEquals( 'Bronze', $levels[2]->level_name, 'Third should be Bronze (sort_order 3)' );
	}

	/**
	 * Test: update_level() modifies existing level correctly
	 */
	public function test_update_level_modifies_correctly() {
		$level_id = $this->levels->add_level( $this->event_id, array(
			'level_name' => 'Gold',
			'value_aud'  => 5000,
			'slots_total' => 5,
		) );

		// Update the level
		$result = $this->levels->update_level( $level_id, array(
			'value_aud'   => 7500,
			'slots_total' => 3,
		) );

		$this->assertTrue( $result, 'Update should return true' );

		$updated = $this->levels->get_level( $level_id );

		$this->assertEquals( 7500.00, (float) $updated->value_aud, 'Value should be updated' );
		$this->assertEquals( 3, (int) $updated->slots_total, 'Slots should be updated' );
		$this->assertEquals( 'Gold', $updated->level_name, 'Name should remain unchanged' );
	}

	/**
	 * Test: delete_level() removes record when slots_filled is 0
	 */
	public function test_delete_level_removes_record() {
		$level_id = $this->levels->add_level( $this->event_id, array(
			'level_name' => 'Gold',
		) );

		$result = $this->levels->delete_level( $level_id );

		$this->assertTrue( $result, 'Delete should return true for unfilled level' );

		$deleted = $this->levels->get_level( $level_id );
		$this->assertNull( $deleted, 'Level should no longer exist' );
	}

	/**
	 * Test: delete_level() prevents deletion when slots are filled
	 */
	public function test_delete_level_prevents_deletion_with_filled_slots() {
		global $wpdb;

		$level_id = $this->levels->add_level( $this->event_id, array(
			'level_name' => 'Gold',
		) );

		// Manually set slots_filled > 0
		$wpdb->update(
			$wpdb->prefix . 'ems_sponsorship_levels',
			array( 'slots_filled' => 2 ),
			array( 'id' => $level_id ),
			array( '%d' ),
			array( '%d' )
		);

		$result = $this->levels->delete_level( $level_id );

		$this->assertWPError( $result, 'Delete should return WP_Error when slots filled' );
		$this->assertEquals( 'has_sponsors', $result->get_error_code() );

		// Verify level still exists
		$level = $this->levels->get_level( $level_id );
		$this->assertNotNull( $level, 'Level should still exist after failed delete' );
	}

	/**
	 * Test: populate_defaults() creates Bronze/Silver/Gold levels
	 */
	public function test_populate_defaults_creates_standard_levels() {
		$created_ids = $this->levels->populate_defaults( $this->event_id );

		$this->assertCount( 3, $created_ids, 'Should create 3 default levels' );

		$levels = $this->levels->get_event_levels( $this->event_id );

		$this->assertEquals( 'Bronze', $levels[0]->level_name );
		$this->assertEquals( '#CD7F32', $levels[0]->colour );
		$this->assertEquals( 1000.00, (float) $levels[0]->value_aud );
		$this->assertEquals( 10, (int) $levels[0]->slots_total );

		$this->assertEquals( 'Silver', $levels[1]->level_name );
		$this->assertEquals( '#C0C0C0', $levels[1]->colour );
		$this->assertEquals( 2500.00, (float) $levels[1]->value_aud );

		$this->assertEquals( 'Gold', $levels[2]->level_name );
		$this->assertEquals( '#FFD700', $levels[2]->colour );
		$this->assertEquals( 5000.00, (float) $levels[2]->value_aud );
	}

	/**
	 * Test: increment_slots_filled() is atomic and respects slot limits
	 */
	public function test_increment_slots_filled_respects_limits() {
		$level_id = $this->levels->add_level( $this->event_id, array(
			'level_name'  => 'Gold',
			'slots_total' => 2,
		) );

		// First increment should succeed
		$result1 = $this->levels->increment_slots_filled( $level_id );
		$this->assertTrue( $result1, 'First increment should succeed' );

		$level = $this->levels->get_level( $level_id );
		$this->assertEquals( 1, (int) $level->slots_filled );

		// Second increment should succeed
		$result2 = $this->levels->increment_slots_filled( $level_id );
		$this->assertTrue( $result2, 'Second increment should succeed' );

		$level = $this->levels->get_level( $level_id );
		$this->assertEquals( 2, (int) $level->slots_filled );

		// Third increment should fail (slots full)
		$result3 = $this->levels->increment_slots_filled( $level_id );
		$this->assertFalse( $result3, 'Third increment should fail when slots full' );

		$level = $this->levels->get_level( $level_id );
		$this->assertEquals( 2, (int) $level->slots_filled, 'Slots should remain at 2' );
	}

	/**
	 * Test: get_available_slots() returns correct count
	 */
	public function test_get_available_slots_returns_correct_count() {
		$level_id = $this->levels->add_level( $this->event_id, array(
			'level_name'  => 'Gold',
			'slots_total' => 5,
		) );

		// Initially should have 5 available
		$available = $this->levels->get_available_slots( $level_id );
		$this->assertEquals( 5, $available );

		// After incrementing, should have 4 available
		$this->levels->increment_slots_filled( $level_id );
		$available = $this->levels->get_available_slots( $level_id );
		$this->assertEquals( 4, $available );

		// Increment twice more
		$this->levels->increment_slots_filled( $level_id );
		$this->levels->increment_slots_filled( $level_id );
		$available = $this->levels->get_available_slots( $level_id );
		$this->assertEquals( 2, $available );
	}

	/**
	 * Test: get_available_slots() returns -1 for unlimited (NULL slots_total)
	 */
	public function test_get_available_slots_returns_unlimited() {
		global $wpdb;

		$level_id = $this->levels->add_level( $this->event_id, array(
			'level_name'  => 'Gold',
			'slots_total' => null, // Unlimited
		) );

		// Force NULL value (wpdb->insert may convert to 0)
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}ems_sponsorship_levels SET slots_total = NULL WHERE id = %d",
			$level_id
		) );

		$available = $this->levels->get_available_slots( $level_id );
		$this->assertEquals( -1, $available, 'Should return -1 for unlimited slots' );
	}
}
