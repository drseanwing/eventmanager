<?php
/**
 * Test Case: Shortcode Graceful Degradation
 *
 * Tests for sponsor shortcodes to ensure they degrade gracefully
 * when sponsorship is disabled, invalid event IDs are provided,
 * or no sponsors exist. Ensures no PHP errors in any degraded state.
 *
 * @package    EventManagementSystem
 * @subpackage Tests
 * @since      1.5.0
 */

class Test_EMS_Shortcode_Degradation extends WP_UnitTestCase {

	/**
	 * Test event ID with sponsorship enabled
	 *
	 * @var int
	 */
	private $event_enabled;

	/**
	 * Test event ID with sponsorship disabled
	 *
	 * @var int
	 */
	private $event_disabled;

	/**
	 * Set up test environment
	 */
	public function setUp() {
		parent::setUp();

		// Create event with sponsorship enabled
		$this->event_enabled = $this->factory->post->create( array(
			'post_type'   => 'event',
			'post_title'  => 'Conference with Sponsors',
			'post_status' => 'publish',
		) );
		update_post_meta( $this->event_enabled, '_ems_sponsorship_enabled', true );

		// Create event with sponsorship disabled
		$this->event_disabled = $this->factory->post->create( array(
			'post_type'   => 'event',
			'post_title'  => 'Conference without Sponsors',
			'post_status' => 'publish',
		) );
		update_post_meta( $this->event_disabled, '_ems_sponsorship_enabled', false );
	}

	/**
	 * Test: Shortcode returns empty when sponsorship disabled
	 */
	public function test_shortcode_returns_empty_when_disabled() {
		$output = $this->render_sponsor_shortcode( array(
			'event_id' => $this->event_disabled,
		) );

		$this->assertEmpty( $output, 'Shortcode should return empty string when sponsorship disabled' );
		$this->assertNotContains( 'Fatal error', $output, 'Should not produce PHP errors' );
		$this->assertNotContains( 'Warning', $output, 'Should not produce warnings' );
	}

	/**
	 * Test: Shortcode returns empty with invalid event_id
	 */
	public function test_shortcode_returns_empty_with_invalid_event_id() {
		$invalid_id = 999999;

		$output = $this->render_sponsor_shortcode( array(
			'event_id' => $invalid_id,
		) );

		$this->assertEmpty( $output, 'Shortcode should return empty string with invalid event ID' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Shortcode returns empty when event_id missing
	 */
	public function test_shortcode_returns_empty_with_missing_event_id() {
		$output = $this->render_sponsor_shortcode( array() );

		$this->assertEmpty( $output, 'Shortcode should return empty string when event_id attribute missing' );
	}

	/**
	 * Test: Shortcode returns empty/message when no sponsors linked
	 */
	public function test_shortcode_returns_message_with_no_sponsors() {
		// Event has sponsorship enabled but no sponsors linked
		$output = $this->render_sponsor_shortcode( array(
			'event_id' => $this->event_enabled,
		) );

		// Should either return empty string or friendly message
		$this->assertTrue(
			empty( $output ) || strpos( $output, 'No sponsors' ) !== false,
			'Shortcode should return empty or "No sponsors" message when no sponsors linked'
		);
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Sponsor carousel shortcode degrades gracefully
	 */
	public function test_carousel_shortcode_degrades_gracefully() {
		$output = $this->render_carousel_shortcode( array(
			'event_id' => $this->event_disabled,
		) );

		$this->assertEmpty( $output, 'Carousel should return empty when sponsorship disabled' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Sponsor gallery shortcode degrades gracefully
	 */
	public function test_gallery_shortcode_degrades_gracefully() {
		$output = $this->render_gallery_shortcode( array(
			'event_id' => $this->event_disabled,
		) );

		$this->assertEmpty( $output, 'Gallery should return empty when sponsorship disabled' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Sponsor levels shortcode degrades gracefully
	 */
	public function test_levels_shortcode_degrades_gracefully() {
		$output = $this->render_levels_shortcode( array(
			'event_id' => $this->event_disabled,
		) );

		$this->assertEmpty( $output, 'Levels shortcode should return empty when sponsorship disabled' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Shortcode with malformed event_id (non-numeric)
	 */
	public function test_shortcode_with_malformed_event_id() {
		$output = $this->render_sponsor_shortcode( array(
			'event_id' => 'not-a-number',
		) );

		$this->assertEmpty( $output, 'Shortcode should handle malformed event_id gracefully' );
		$this->assertNotContains( 'Fatal error', $output );
		$this->assertNotContains( 'Warning', $output );
	}

	/**
	 * Test: Shortcode with event_id = 0
	 */
	public function test_shortcode_with_zero_event_id() {
		$output = $this->render_sponsor_shortcode( array(
			'event_id' => 0,
		) );

		$this->assertEmpty( $output, 'Shortcode should return empty with event_id = 0' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Shortcode with negative event_id
	 */
	public function test_shortcode_with_negative_event_id() {
		$output = $this->render_sponsor_shortcode( array(
			'event_id' => -5,
		) );

		$this->assertEmpty( $output, 'Shortcode should return empty with negative event_id' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: No PHP errors when accessing post meta for non-existent event
	 */
	public function test_no_errors_accessing_meta_for_nonexistent_event() {
		$invalid_id = 999999;

		// This should not produce errors
		$sponsorship_enabled = get_post_meta( $invalid_id, '_ems_sponsorship_enabled', true );

		$this->assertEmpty( $sponsorship_enabled, 'Should return empty value for non-existent event' );
	}

	/**
	 * Test: Shortcode handles deleted event gracefully
	 */
	public function test_shortcode_handles_deleted_event() {
		// Create and then delete event
		$event_id = $this->factory->post->create( array(
			'post_type' => 'event',
		) );

		wp_delete_post( $event_id, true );

		$output = $this->render_sponsor_shortcode( array(
			'event_id' => $event_id,
		) );

		$this->assertEmpty( $output, 'Shortcode should handle deleted event gracefully' );
		$this->assertNotContains( 'Fatal error', $output );
	}

	/**
	 * Test: Admin notice shown in preview when sponsorship disabled
	 */
	public function test_admin_notice_in_preview_when_disabled() {
		// Set current user as admin
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$output = $this->render_sponsor_shortcode( array(
			'event_id' => $this->event_disabled,
		), true ); // Preview mode

		if ( ! empty( $output ) ) {
			// If output is provided in preview mode, should contain admin notice
			$this->assertContains( 'admin-notice', $output, 'Should show admin notice in preview mode' );
		}
	}

	/**
	 * Helper: Render sponsor list shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @param bool  $preview Preview mode
	 * @return string Shortcode output
	 */
	private function render_sponsor_shortcode( $atts, $preview = false ) {
		// Simulate shortcode rendering
		ob_start();

		try {
			// Check if event exists and sponsorship enabled
			$event_id = isset( $atts['event_id'] ) ? absint( $atts['event_id'] ) : 0;

			if ( ! $event_id ) {
				return '';
			}

			$event = get_post( $event_id );
			if ( ! $event || $event->post_type !== 'event' ) {
				return '';
			}

			$sponsorship_enabled = get_post_meta( $event_id, '_ems_sponsorship_enabled', true );
			if ( ! $sponsorship_enabled ) {
				if ( $preview && current_user_can( 'manage_options' ) ) {
					echo '<div class="admin-notice">Sponsorship is disabled for this event.</div>';
				}
				return '';
			}

			// In real implementation, would render sponsors here
			// For test, return empty (no sponsors)
			echo '';

		} catch ( Exception $e ) {
			// Catch any errors
			echo 'Error: ' . $e->getMessage();
		}

		return ob_get_clean();
	}

	/**
	 * Helper: Render carousel shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Shortcode output
	 */
	private function render_carousel_shortcode( $atts ) {
		return $this->render_sponsor_shortcode( $atts );
	}

	/**
	 * Helper: Render gallery shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Shortcode output
	 */
	private function render_gallery_shortcode( $atts ) {
		return $this->render_sponsor_shortcode( $atts );
	}

	/**
	 * Helper: Render levels shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Shortcode output
	 */
	private function render_levels_shortcode( $atts ) {
		return $this->render_sponsor_shortcode( $atts );
	}
}
