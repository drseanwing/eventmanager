<?php
/**
 * Sponsor Expression of Interest (EOI) Shortcode
 *
 * Renders a multi-step form for sponsors to express interest in sponsoring
 * a specific event. Uses the EMS_Multi_Step_Form engine for step management,
 * validation, state persistence, and review display.
 *
 * Usage: [ems_sponsor_eoi event_id="123"]
 *
 * @package    EventManagementSystem
 * @subpackage Public/Shortcodes
 * @since      1.5.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sponsor EOI shortcode class.
 *
 * Implements the [ems_sponsor_eoi] shortcode with a 5-step multi-step form
 * for sponsors to express interest in sponsoring a specific event.
 *
 * Steps:
 *   1. Sponsorship Commitment & Preferences
 *   2. Exclusivity & Category Protection
 *   3. Desired Recognition & Visibility
 *   4. Educational Independence & Content Boundaries
 *   5. Review & Submit
 *
 * @since 1.5.0
 */
class EMS_Sponsor_EOI_Shortcode {

	/**
	 * Logger instance.
	 *
	 * @since  1.5.0
	 * @access private
	 * @var    EMS_Logger
	 */
	private $logger;

	/**
	 * Sponsorship levels API instance.
	 *
	 * @since  1.5.0
	 * @access private
	 * @var    EMS_Sponsorship_Levels
	 */
	private $levels_api;

	/**
	 * Sponsor portal instance.
	 *
	 * @since  1.5.0
	 * @access private
	 * @var    EMS_Sponsor_Portal
	 */
	private $sponsor_portal;

	/**
	 * Constructor.
	 *
	 * Initialises dependencies and registers the shortcode.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$this->logger         = EMS_Logger::instance();
		$this->levels_api     = new EMS_Sponsorship_Levels();
		$this->sponsor_portal = new EMS_Sponsor_Portal();

		add_shortcode( 'ems_sponsor_eoi', array( $this, 'render_shortcode' ) );

		// Handle POST submission.
		add_action( 'template_redirect', array( $this, 'handle_submission' ) );
	}

	// =========================================================================
	// Shortcode Entry Point
	// =========================================================================

	/**
	 * Render the [ems_sponsor_eoi] shortcode.
	 *
	 * Performs authentication gating, event validation, duplicate EOI checks,
	 * and renders the multi-step form when all preconditions are met.
	 *
	 * @since 1.5.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'event_id' => 0,
		), $atts, 'ems_sponsor_eoi' );

		$event_id = absint( $atts['event_id'] );

		// ---- Success message after redirect ----
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
		if ( isset( $_GET['ems_eoi_submitted'] ) && '1' === $_GET['ems_eoi_submitted'] ) {
			return '<div class="ems-notice ems-notice-success">'
				. '<strong>' . esc_html__( 'Thank You!', 'event-management-system' ) . '</strong><br />'
				. esc_html__( 'Your expression of interest has been submitted successfully. You will be notified when it has been reviewed.', 'event-management-system' )
				. '</div>';
		}

		// ---- Authentication gate (Task 6.11) ----
		$auth_gate = $this->check_authentication();
		if ( false !== $auth_gate ) {
			return $auth_gate;
		}

		// ---- Event validation (Task 6.2) ----
		$validation = $this->validate_event( $event_id );
		if ( false !== $validation ) {
			return $validation;
		}

		// ---- Duplicate EOI check ----
		$sponsor_id  = $this->get_current_sponsor_id();
		$existing_eoi = $this->get_existing_eoi( $sponsor_id, $event_id );
		if ( $existing_eoi ) {
			return $this->render_existing_eoi_status( $existing_eoi );
		}

		// ---- Build and render the form ----
		return $this->render_form( $event_id );
	}

	// =========================================================================
	// Authentication Gate (Task 6.11)
	// =========================================================================

	/**
	 * Check authentication and sponsor role.
	 *
	 * Returns HTML error/notice when the user cannot proceed,
	 * or false when authentication is satisfied.
	 *
	 * @since 1.5.0
	 * @return string|false HTML notice or false if authenticated.
	 */
	private function check_authentication() {
		if ( ! is_user_logged_in() ) {
			$login_url     = wp_login_url( get_permalink() );
			$onboarding_url = $this->get_onboarding_url();

			return '<div class="ems-notice ems-notice-warning">'
				. sprintf(
					/* translators: 1: login URL, 2: onboarding URL */
					__( 'Please <a href="%1$s">log in</a> to submit a sponsorship expression of interest. If you do not yet have a sponsor account, please <a href="%2$s">register here</a>.', 'event-management-system' ),
					esc_url( $login_url ),
					esc_url( $onboarding_url )
				)
				. '</div>';
		}

		if ( ! current_user_can( 'access_ems_sponsor_portal' ) ) {
			$onboarding_url = $this->get_onboarding_url();

			return '<div class="ems-notice ems-notice-warning">'
				. sprintf(
					/* translators: %s: onboarding URL */
					__( 'You need a sponsor account to submit an expression of interest. Please complete the <a href="%s">sponsor onboarding process</a> first.', 'event-management-system' ),
					esc_url( $onboarding_url )
				)
				. '</div>';
		}

		$sponsor_id = $this->get_current_sponsor_id();
		if ( ! $sponsor_id ) {
			$onboarding_url = $this->get_onboarding_url();

			return '<div class="ems-notice ems-notice-warning">'
				. sprintf(
					/* translators: %s: onboarding URL */
					__( 'No sponsor profile is associated with your account. Please complete the <a href="%s">sponsor onboarding process</a> first.', 'event-management-system' ),
					esc_url( $onboarding_url )
				)
				. '</div>';
		}

		return false;
	}

	// =========================================================================
	// Event Validation (Task 6.2)
	// =========================================================================

	/**
	 * Validate that the event exists, has sponsorship enabled, and has available slots.
	 *
	 * Returns HTML error when the event cannot accept EOIs,
	 * or false when all checks pass.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return string|false HTML notice or false if valid.
	 */
	private function validate_event( $event_id ) {
		if ( ! $event_id ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'No event specified. Please provide an event ID.', 'event-management-system' )
				. '</div>';
		}

		$event = get_post( $event_id );
		if ( ! $event || 'ems_event' !== $event->post_type || 'publish' !== $event->post_status ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'This event could not be found.', 'event-management-system' )
				. '</div>';
		}

		$sponsorship_enabled = (bool) get_post_meta( $event_id, '_ems_sponsorship_enabled', true );
		if ( ! $sponsorship_enabled ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'Sponsorship is not available for this event.', 'event-management-system' )
				. '</div>';
		}

		$levels = $this->levels_api->get_event_levels( $event_id );
		$enabled_levels = array_filter( $levels, function ( $level ) {
			return intval( $level->enabled );
		} );

		if ( empty( $enabled_levels ) ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'Sponsorship levels have not been configured for this event.', 'event-management-system' )
				. '</div>';
		}

		// Check whether any slots remain across all enabled levels.
		$any_available = false;
		foreach ( $enabled_levels as $level ) {
			if ( is_null( $level->slots_total ) ) {
				// Unlimited slots.
				$any_available = true;
				break;
			}
			if ( intval( $level->slots_total ) > intval( $level->slots_filled ) ) {
				$any_available = true;
				break;
			}
		}

		if ( ! $any_available ) {
			return '<div class="ems-notice ems-notice-error">'
				. esc_html__( 'All sponsorship positions for this event have been filled.', 'event-management-system' )
				. '</div>';
		}

		return false;
	}

	// =========================================================================
	// Existing EOI Check
	// =========================================================================

	/**
	 * Check for an existing EOI for this sponsor + event pair.
	 *
	 * @since 1.5.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @param int $event_id   Event post ID.
	 * @return object|null EOI row object or null if none exists.
	 */
	private function get_existing_eoi( $sponsor_id, $event_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ems_sponsor_eoi';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE sponsor_id = %d AND event_id = %d",
				$sponsor_id,
				$event_id
			)
		);
	}

	/**
	 * Render the status of an existing EOI submission.
	 *
	 * @since 1.5.0
	 * @param object $eoi EOI database row.
	 * @return string HTML output.
	 */
	private function render_existing_eoi_status( $eoi ) {
		$status_labels = array(
			'pending'        => __( 'Pending Review', 'event-management-system' ),
			'approved'       => __( 'Approved', 'event-management-system' ),
			'rejected'       => __( 'Declined', 'event-management-system' ),
			'info_requested' => __( 'More Information Requested', 'event-management-system' ),
		);

		$status_label = isset( $status_labels[ $eoi->status ] )
			? $status_labels[ $eoi->status ]
			: esc_html( $eoi->status );

		$class_map = array(
			'pending'        => 'ems-notice-info',
			'approved'       => 'ems-notice-success',
			'rejected'       => 'ems-notice-error',
			'info_requested' => 'ems-notice-warning',
		);

		$notice_class = isset( $class_map[ $eoi->status ] )
			? $class_map[ $eoi->status ]
			: 'ems-notice-info';

		$html  = '<div class="ems-notice ' . esc_attr( $notice_class ) . '">';
		$html .= '<strong>' . esc_html__( 'Expression of Interest Already Submitted', 'event-management-system' ) . '</strong><br />';
		$html .= sprintf(
			/* translators: 1: formatted date, 2: status label */
			esc_html__( 'You submitted an expression of interest on %1$s. Current status: %2$s.', 'event-management-system' ),
			esc_html( date_i18n( get_option( 'date_format' ), strtotime( $eoi->submitted_at ) ) ),
			'<strong>' . esc_html( $status_label ) . '</strong>'
		);

		if ( ! empty( $eoi->review_notes ) && 'info_requested' === $eoi->status ) {
			$html .= '<br /><br />';
			$html .= '<strong>' . esc_html__( 'Reviewer Notes:', 'event-management-system' ) . '</strong><br />';
			$html .= nl2br( esc_html( $eoi->review_notes ) );
		}

		$html .= '</div>';

		return $html;
	}

	// =========================================================================
	// Form Builder
	// =========================================================================

	/**
	 * Build and render the 5-step EOI form.
	 *
	 * @since 1.5.0
	 * @param int $event_id Event post ID.
	 * @return string HTML output.
	 */
	private function render_form( $event_id ) {
		$form = new EMS_Multi_Step_Form( 'sponsor_eoi_' . $event_id );

		// Register steps 1-4 with field definitions.
		$this->register_step_commitment( $form, $event_id );
		$this->register_step_exclusivity( $form );
		$this->register_step_recognition( $form );
		$this->register_step_independence( $form );

		// Step 5: Review (uses the form engine's built-in review renderer).
		$form->register_step(
			'review',
			__( 'Review & Submit', 'event-management-system' ),
			array(),
			array()
		);

		// Load any saved state.
		$form->load_state();

		// Handle step navigation via POST.
		$this->process_step_navigation( $form, $event_id );

		// If we are on the review step, render review instead of fields.
		$steps      = $form->get_steps();
		$current    = $form->get_current_step();
		$last_index = count( $steps ) - 1;

		if ( $current === $last_index ) {
			return $this->render_review_step( $form, $event_id );
		}

		// Add hidden event_id field to the form output.
		$html  = $form->render();
		$html  = str_replace(
			'<input type="hidden" name="ems_current_step"',
			'<input type="hidden" name="ems_eoi_event_id" value="' . esc_attr( $event_id ) . '" />'
			. '<input type="hidden" name="ems_current_step"',
			$html
		);

		return $html;
	}

	/**
	 * Render the review step with form wrapper for final submission.
	 *
	 * @since 1.5.0
	 * @param EMS_Multi_Step_Form $form     Form instance.
	 * @param int                 $event_id Event post ID.
	 * @return string HTML output.
	 */
	private function render_review_step( $form, $event_id ) {
		$steps      = $form->get_steps();
		$last_index = count( $steps ) - 1;

		$html  = '<div class="ems-multi-step-form" data-form-id="' . esc_attr( $form->get_form_id() ) . '">';

		// Progress bar.
		$progress = $form->get_progress();
		$html    .= '<div class="ems-form-progress" role="navigation" aria-label="' . esc_attr__( 'Form progress', 'event-management-system' ) . '">';
		$html    .= '<ol class="ems-form-progress__list">';
		foreach ( $steps as $index => $step ) {
			$state      = $progress[ $index ]['state'];
			$step_class = 'ems-form-progress__step ems-form-progress__step--' . $state;
			$aria       = ( 'active' === $state ) ? ' aria-current="step"' : '';
			$clickable  = ( 'complete' === $state );
			$tag_open   = $clickable
				? '<button type="button" class="ems-progress-step-btn" data-step="' . esc_attr( $index ) . '">'
				: '<span>';
			$tag_close  = $clickable ? '</button>' : '</span>';

			$html .= '<li class="' . esc_attr( $step_class ) . '"' . $aria . '>';
			$html .= $tag_open;
			$html .= '<span class="ems-form-progress__number">';
			if ( 'complete' === $state ) {
				$html .= '<span class="ems-form-progress__check" aria-hidden="true">&#10003;</span>';
			} else {
				$html .= esc_html( $index + 1 );
			}
			$html .= '</span>';
			$html .= '<span class="ems-form-progress__title" title="' . esc_attr( $step['title'] ) . '">' . esc_html( $step['title'] ) . '</span>';
			$html .= $tag_close;
			$html .= '</li>';
		}
		$html .= '</ol></div>';

		// Form element.
		$html .= '<form method="post" class="ems-form ems-step-form" id="ems-form-' . esc_attr( $form->get_form_id() ) . '">';
		$html .= wp_nonce_field( 'ems_form_' . $form->get_form_id(), 'ems_form_nonce', true, false );
		$html .= '<input type="hidden" name="ems_form_id" value="' . esc_attr( $form->get_form_id() ) . '" />';
		$html .= '<input type="hidden" name="ems_eoi_event_id" value="' . esc_attr( $event_id ) . '" />';
		$html .= '<input type="hidden" name="ems_current_step" value="' . esc_attr( $last_index ) . '" />';

		// Review content.
		$html .= '<div class="ems-form-step" data-step="' . esc_attr( $last_index ) . '" data-step-slug="review">';
		$html .= $form->render_review();
		$html .= '</div>';

		// Navigation (Previous + Submit).
		$html .= '<div class="ems-form-navigation">';
		$html .= '<button type="submit" name="ems_form_direction" value="prev" class="ems-btn ems-btn-secondary ems-form-prev">';
		$html .= esc_html__( 'Previous', 'event-management-system' );
		$html .= '</button>';
		$html .= '<button type="submit" name="ems_eoi_submit" value="1" class="ems-btn ems-btn-primary ems-form-submit">';
		$html .= esc_html__( 'Submit Expression of Interest', 'event-management-system' );
		$html .= '</button>';
		$html .= '</div>';

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	// =========================================================================
	// Step Navigation Processing
	// =========================================================================

	/**
	 * Process form step navigation via POST.
	 *
	 * Validates current step data, persists state, and advances/retreats
	 * the form step index as appropriate.
	 *
	 * @since 1.5.0
	 * @param EMS_Multi_Step_Form $form     Form instance.
	 * @param int                 $event_id Event post ID.
	 * @return void
	 */
	private function process_step_navigation( $form, $event_id ) {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// Check this is actually our form.
		if ( ! isset( $_POST['ems_form_id'] ) || $form->get_form_id() !== sanitize_key( $_POST['ems_form_id'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['ems_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ems_form_nonce'] ) ), 'ems_form_' . $form->get_form_id() ) ) {
			return;
		}

		$current_step = absint( $_POST['ems_current_step'] ?? 0 );
		$form->set_current_step( $current_step );

		$steps     = $form->get_steps();
		$step_slug = isset( $steps[ $current_step ] ) ? $steps[ $current_step ]['slug'] : '';

		// Determine direction from button clicks.
		$direction = '';
		if ( isset( $_POST['ems_form_direction'] ) ) {
			$direction = sanitize_key( $_POST['ems_form_direction'] );
		}

		// If going back, just update step and save.
		if ( 'prev' === $direction && $current_step > 0 ) {
			$form->set_current_step( $current_step - 1 );
			$form->save_state();
			return;
		}

		// Going forward: collect and validate current step data.
		if ( 'review' !== $step_slug ) {
			$step_data = $this->collect_step_data( $step_slug, $steps[ $current_step ] );
			$validation = $form->validate_step( $step_slug, $step_data );

			if ( is_wp_error( $validation ) ) {
				// Validation failed; stay on current step. Data is NOT saved.
				return;
			}

			// Save validated data.
			$form->set_form_data( $step_slug, $step_data );
		}

		// Advance to next step.
		$next_step = $current_step + 1;
		if ( $next_step < count( $steps ) ) {
			$form->set_current_step( $next_step );
		}

		$form->save_state();
	}

	/**
	 * Collect sanitised step data from POST.
	 *
	 * @since 1.5.0
	 * @param string $step_slug Step slug.
	 * @param array  $step      Step definition.
	 * @return array Sanitised field data.
	 */
	private function collect_step_data( $step_slug, $step ) {
		$data = array();

		foreach ( $step['fields'] as $field ) {
			$name = $field['name'];
			$type = $field['type'];

			if ( 'multiselect' === $type ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in caller
				$raw = isset( $_POST[ $name ] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST[ $name ] ) ) : array();
				$data[ $name ] = $raw;
			} elseif ( 'checkbox' === $type ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data[ $name ] = isset( $_POST[ $name ] ) ? '1' : '0';
			} elseif ( 'textarea' === $type ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data[ $name ] = isset( $_POST[ $name ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) ) : '';
			} elseif ( 'number' === $type ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data[ $name ] = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
			} else {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data[ $name ] = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
			}
		}

		return $data;
	}

	// =========================================================================
	// Step 1: Sponsorship Commitment & Preferences (Task 6.3)
	// =========================================================================

	/**
	 * Register Step 1 fields: Sponsorship Commitment & Preferences.
	 *
	 * @since 1.5.0
	 * @param EMS_Multi_Step_Form $form     Form instance.
	 * @param int                 $event_id Event post ID.
	 * @return void
	 */
	private function register_step_commitment( $form, $event_id ) {
		$levels  = $this->levels_api->get_event_levels( $event_id );
		$options = array();
		$available_levels = array();

		foreach ( $levels as $level ) {
			if ( ! intval( $level->enabled ) ) {
				continue;
			}

			$remaining = is_null( $level->slots_total )
				? __( 'unlimited', 'event-management-system' )
				: max( 0, intval( $level->slots_total ) - intval( $level->slots_filled ) );

			$value_display = ! is_null( $level->value_aud )
				? ' - $' . number_format( (float) $level->value_aud, 2 )
				: '';

			$sold_out = ! is_null( $level->slots_total )
				&& intval( $level->slots_filled ) >= intval( $level->slots_total );

			// Skip sold-out levels entirely.
			if ( $sold_out ) {
				continue;
			}

			$label = sprintf(
				'%s%s (%s %s)',
				esc_html( $level->level_name ),
				$value_display,
				is_numeric( $remaining ) ? $remaining : $remaining,
				is_numeric( $remaining )
					? _n( 'slot remaining', 'slots remaining', $remaining, 'event-management-system' )
					: __( 'slots', 'event-management-system' )
			);

			$options[ $level->level_slug ] = $label;
			$available_levels[] = $level->level_slug;
		}

		$fields = array(
			array(
				'name'     => 'preferred_level',
				'type'     => 'select',
				'label'    => __( 'Preferred Sponsorship Level', 'event-management-system' ),
				'required' => true,
				'options'  => $options,
			),
			array(
				'name'        => 'estimated_value',
				'type'        => 'number',
				'label'       => __( 'Estimated Sponsorship Value (AUD)', 'event-management-system' ),
				'required'    => false,
				'min'         => '0',
				'placeholder' => __( 'e.g. 5000', 'event-management-system' ),
			),
			array(
				'name'     => 'contribution_nature',
				'type'     => 'multiselect',
				'label'    => __( 'Nature of Contribution', 'event-management-system' ),
				'required' => true,
				'options'  => array(
					'financial_unrestricted'  => __( 'Financial unrestricted', 'event-management-system' ),
					'financial_earmarked'     => __( 'Financial earmarked', 'event-management-system' ),
					'in_kind_equipment'       => __( 'In-kind equipment', 'event-management-system' ),
					'in_kind_consumables'     => __( 'In-kind consumables', 'event-management-system' ),
					'in_kind_catering'        => __( 'In-kind catering', 'event-management-system' ),
					'in_kind_educational'     => __( 'In-kind educational', 'event-management-system' ),
					'in_kind_speaker_support' => __( 'In-kind speaker support', 'event-management-system' ),
					'in_kind_av'              => __( 'In-kind AV', 'event-management-system' ),
					'other'                   => __( 'Other', 'event-management-system' ),
				),
			),
			array(
				'name'      => 'conditional_outcome',
				'type'      => 'textarea',
				'label'     => __( 'Conditional Outcome', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'Describe any conditions tied to this sponsorship. Note: Under the Medicines Australia Code of Conduct, sponsorship must not be conditional on prescribing or purchasing decisions.', 'event-management-system' ),
				'rows'      => 4,
			),
			array(
				'name'      => 'speaker_nomination',
				'type'      => 'textarea',
				'label'     => __( 'Speaker Nomination', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'If you wish to nominate a speaker, please provide their name and qualifications. The organiser retains final authority over speaker selection.', 'event-management-system' ),
				'rows'      => 4,
			),
			array(
				'name'     => 'duration_interest',
				'type'     => 'select',
				'label'    => __( 'Duration of Interest', 'event-management-system' ),
				'required' => false,
				'options'  => array(
					'single_event' => __( 'Single event', 'event-management-system' ),
					'annual'       => __( 'Annual', 'event-management-system' ),
					'multi_year'   => __( 'Multi-year', 'event-management-system' ),
				),
			),
			array(
				'name'        => 'specific_events',
				'type'        => 'textarea',
				'label'       => __( 'Specific Events of Interest', 'event-management-system' ),
				'required'    => false,
				'help_text'   => __( 'If your interest extends beyond this event, list other events you would like to sponsor.', 'event-management-system' ),
				'rows'        => 3,
			),
		);

		$validation_rules = array(
			'preferred_level'    => array( 'required', array( 'in', $available_levels ) ),
			'contribution_nature' => array( 'required' ),
		);

		$form->register_step(
			'commitment',
			__( 'Commitment & Preferences', 'event-management-system' ),
			$fields,
			$validation_rules
		);
	}

	// =========================================================================
	// Step 2: Exclusivity & Category Protection (Task 6.4)
	// =========================================================================

	/**
	 * Register Step 2 fields: Exclusivity & Category Protection.
	 *
	 * @since 1.5.0
	 * @param EMS_Multi_Step_Form $form Form instance.
	 * @return void
	 */
	private function register_step_exclusivity( $form ) {
		$fields = array(
			array(
				'name'     => 'exclusivity_requested',
				'type'     => 'radio',
				'label'    => __( 'Are you requesting category exclusivity?', 'event-management-system' ),
				'required' => true,
				'options'  => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
			),
			array(
				'name'        => 'exclusivity_category',
				'type'        => 'text',
				'label'       => __( 'Exclusivity Category', 'event-management-system' ),
				'required'    => false,
				'placeholder' => __( 'e.g. Orthopaedic Implants', 'event-management-system' ),
				'conditional' => array(
					'field' => 'exclusivity_requested',
					'value' => 'yes',
				),
			),
			array(
				'name'        => 'shared_presence_accepted',
				'type'        => 'radio',
				'label'       => __( 'Would you accept a shared presence with other sponsors in your category?', 'event-management-system' ),
				'required'    => false,
				'options'     => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
				'conditional' => array(
					'field' => 'exclusivity_requested',
					'value' => 'yes',
				),
			),
			array(
				'name'        => 'competitor_objections',
				'type'        => 'textarea',
				'label'       => __( 'Competitor Objections', 'event-management-system' ),
				'required'    => false,
				'help_text'   => __( 'List any specific competitors whose presence you would object to.', 'event-management-system' ),
				'rows'        => 3,
				'conditional' => array(
					'field' => 'exclusivity_requested',
					'value' => 'yes',
				),
			),
			array(
				'name'        => 'exclusivity_conditions',
				'type'        => 'textarea',
				'label'       => __( 'Exclusivity Conditions', 'event-management-system' ),
				'required'    => false,
				'help_text'   => __( 'Describe any specific conditions for your exclusivity request.', 'event-management-system' ),
				'rows'        => 3,
				'conditional' => array(
					'field' => 'exclusivity_requested',
					'value' => 'yes',
				),
			),
		);

		$validation_rules = array(
			'exclusivity_requested' => array( 'required' ),
		);

		$form->register_step(
			'exclusivity',
			__( 'Exclusivity & Category Protection', 'event-management-system' ),
			$fields,
			$validation_rules
		);
	}

	// =========================================================================
	// Step 3: Desired Recognition & Visibility (Task 6.5)
	// =========================================================================

	/**
	 * Register Step 3 fields: Desired Recognition & Visibility.
	 *
	 * @since 1.5.0
	 * @param EMS_Multi_Step_Form $form Form instance.
	 * @return void
	 */
	private function register_step_recognition( $form ) {
		$fields = array(
			array(
				'name'     => 'recognition_elements',
				'type'     => 'multiselect',
				'label'    => __( 'Desired Recognition Elements', 'event-management-system' ),
				'required' => true,
				'options'  => array(
					'logo_materials'      => __( 'Logo on materials', 'event-management-system' ),
					'logo_certificates'   => __( 'Logo on certificates', 'event-management-system' ),
					'verbal_acknowledge'  => __( 'Verbal acknowledgement', 'event-management-system' ),
					'trade_display'       => __( 'Trade display', 'event-management-system' ),
					'product_demo'        => __( 'Product demo', 'event-management-system' ),
					'branded_items'       => __( 'Branded items', 'event-management-system' ),
					'signage'             => __( 'Signage', 'event-management-system' ),
					'digital_presence'    => __( 'Digital presence', 'event-management-system' ),
					'named_session'       => __( 'Named session', 'event-management-system' ),
					'sponsored_break'     => __( 'Sponsored break', 'event-management-system' ),
					'other'               => __( 'Other', 'event-management-system' ),
				),
			),
			array(
				'name'     => 'trade_display_requested',
				'type'     => 'radio',
				'label'    => __( 'Do you require a trade display?', 'event-management-system' ),
				'required' => true,
				'options'  => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
			),
			array(
				'name'        => 'trade_display_requirements',
				'type'        => 'textarea',
				'label'       => __( 'Trade Display Requirements', 'event-management-system' ),
				'required'    => false,
				'help_text'   => __( 'Describe your space, power, and setup requirements.', 'event-management-system' ),
				'rows'        => 4,
				'conditional' => array(
					'field' => 'trade_display_requested',
					'value' => 'yes',
				),
			),
			array(
				'name'        => 'representatives_count',
				'type'        => 'number',
				'label'       => __( 'Number of Representatives Attending', 'event-management-system' ),
				'required'    => false,
				'min'         => '0',
				'placeholder' => __( 'e.g. 3', 'event-management-system' ),
			),
			array(
				'name'      => 'product_samples',
				'type'      => 'radio',
				'label'     => __( 'Do you intend to provide product samples?', 'event-management-system' ),
				'required'  => true,
				'options'   => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
				'help_text' => __( 'Product samples must comply with TGA regulations. Schedule 3, 4, and 8 products require additional approval and may be restricted.', 'event-management-system' ),
			),
			array(
				'name'      => 'promotional_material',
				'type'      => 'radio',
				'label'     => __( 'Will you distribute promotional material?', 'event-management-system' ),
				'required'  => true,
				'options'   => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
				'help_text' => __( 'All promotional material must comply with AHPRA advertising guidelines and TGA regulations. Material may need to be submitted for review prior to the event.', 'event-management-system' ),
			),
			array(
				'name'      => 'delegate_data_collection',
				'type'      => 'radio',
				'label'     => __( 'Do you intend to collect delegate data?', 'event-management-system' ),
				'required'  => true,
				'options'   => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
				'help_text' => __( 'Any data collection must comply with the Privacy Act 1988 and Australian Privacy Principles. Delegates must provide informed consent.', 'event-management-system' ),
			),
			array(
				'name'      => 'social_media_expectations',
				'type'      => 'textarea',
				'label'     => __( 'Social Media Expectations', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'Describe any social media coverage or tagging you expect as part of your sponsorship.', 'event-management-system' ),
				'rows'      => 3,
			),
		);

		$validation_rules = array(
			'recognition_elements'      => array( 'required' ),
			'trade_display_requested'   => array( 'required' ),
			'product_samples'           => array( 'required' ),
			'promotional_material'      => array( 'required' ),
			'delegate_data_collection'  => array( 'required' ),
		);

		$form->register_step(
			'recognition',
			__( 'Recognition & Visibility', 'event-management-system' ),
			$fields,
			$validation_rules
		);
	}

	// =========================================================================
	// Step 4: Educational Independence & Content Boundaries (Task 6.6)
	// =========================================================================

	/**
	 * Register Step 4 fields: Educational Independence & Content Boundaries.
	 *
	 * @since 1.5.0
	 * @param EMS_Multi_Step_Form $form Form instance.
	 * @return void
	 */
	private function register_step_independence( $form ) {
		$fields = array(
			array(
				'name'     => 'content_independence_acknowledged',
				'type'     => 'checkbox',
				'label'    => __( 'The event organiser retains sole control over educational content, speaker selection, and program design.', 'event-management-system' ),
				'required' => true,
			),
			array(
				'name'      => 'content_influence',
				'type'      => 'textarea',
				'label'     => __( 'Content Influence', 'event-management-system' ),
				'required'  => false,
				'help_text' => __( 'If the sponsor seeks to influence educational content in any way, please describe. This will be reviewed for compliance with educational independence requirements.', 'event-management-system' ),
				'rows'      => 4,
			),
			array(
				'name'      => 'therapeutic_claims',
				'type'      => 'radio',
				'label'     => __( 'Will your sponsorship involve therapeutic claims?', 'event-management-system' ),
				'required'  => true,
				'options'   => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
				'help_text' => __( 'Therapeutic claims must comply with TGA regulations and the Medicines Australia Code of Conduct. All claims must be substantiated and balanced.', 'event-management-system' ),
			),
			array(
				'name'      => 'material_review_required',
				'type'      => 'radio',
				'label'     => __( 'Do you require the organiser to review your materials before the event?', 'event-management-system' ),
				'required'  => true,
				'options'   => array(
					'yes' => __( 'Yes', 'event-management-system' ),
					'no'  => __( 'No', 'event-management-system' ),
				),
				'help_text' => __( 'This relates to branding and compliance review of sponsor materials, not editorial control over educational content.', 'event-management-system' ),
			),
		);

		$validation_rules = array(
			'content_independence_acknowledged' => array( 'required' ),
			'therapeutic_claims'                => array( 'required' ),
			'material_review_required'          => array( 'required' ),
		);

		$form->register_step(
			'independence',
			__( 'Educational Independence', 'event-management-system' ),
			$fields,
			$validation_rules
		);
	}

	// =========================================================================
	// Submission Handler (Task 6.8)
	// =========================================================================

	/**
	 * Handle the final EOI form submission.
	 *
	 * Runs on template_redirect so that it fires before rendering.
	 * On success, clears form state and sets a query variable for the
	 * shortcode to detect and display a success message.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function handle_submission() {
		// Check if this is an EOI final submission.
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( ! isset( $_POST['ems_eoi_submit'] ) || '1' !== $_POST['ems_eoi_submit'] ) {
			return;
		}

		$event_id = isset( $_POST['ems_eoi_event_id'] ) ? absint( $_POST['ems_eoi_event_id'] ) : 0;
		$form_id  = isset( $_POST['ems_form_id'] ) ? sanitize_key( $_POST['ems_form_id'] ) : '';

		if ( ! $event_id || ! $form_id ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['ems_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ems_form_nonce'] ) ), 'ems_form_' . $form_id ) ) {
			$this->logger->warning(
				'EOI submission failed nonce verification',
				EMS_Logger::CONTEXT_SECURITY
			);
			return;
		}

		// Verify confirmation checkbox.
		if ( empty( $_POST['ems_confirm_submission'] ) ) {
			return;
		}

		// Verify user is logged in and is a sponsor.
		if ( ! is_user_logged_in() || ! current_user_can( 'access_ems_sponsor_portal' ) ) {
			$this->logger->warning(
				'EOI submission attempted by non-sponsor user',
				EMS_Logger::CONTEXT_SECURITY
			);
			return;
		}

		$sponsor_id = $this->get_current_sponsor_id();
		if ( ! $sponsor_id ) {
			$this->logger->warning(
				'EOI submission attempted by user without sponsor profile',
				EMS_Logger::CONTEXT_SECURITY
			);
			return;
		}

		// Verify event exists and has sponsorship enabled.
		$event = get_post( $event_id );
		if ( ! $event || 'ems_event' !== $event->post_type ) {
			return;
		}

		$sponsorship_enabled = (bool) get_post_meta( $event_id, '_ems_sponsorship_enabled', true );
		if ( ! $sponsorship_enabled ) {
			return;
		}

		// Check no existing EOI for this sponsor + event.
		$existing = $this->get_existing_eoi( $sponsor_id, $event_id );
		if ( $existing ) {
			return;
		}

		// Rate limiting: max 5 EOI submissions per hour per user
		$rate_limit = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit ) ) {
			$this->logger->warning(
				sprintf( 'EOI rate limit exceeded for user %d', get_current_user_id() ),
				EMS_Logger::CONTEXT_SECURITY
			);
			wp_die(
				esc_html__( 'You have submitted too many expressions of interest recently. Please try again later.', 'event-management-system' ),
				esc_html__( 'Rate Limit Exceeded', 'event-management-system' ),
				array(
					'response'  => 429,
					'back_link' => true,
				)
			);
		}

		// Load form state to retrieve all step data.
		$form = new EMS_Multi_Step_Form( $form_id );

		// Register steps so state loading can restore completion status.
		$this->register_step_commitment( $form, $event_id );
		$this->register_step_exclusivity( $form );
		$this->register_step_recognition( $form );
		$this->register_step_independence( $form );
		$form->register_step( 'review', __( 'Review & Submit', 'event-management-system' ), array(), array() );

		$form->load_state();
		$form_data = $form->get_form_data();

		// Flatten step data for database insertion.
		$commitment   = isset( $form_data['commitment'] ) ? $form_data['commitment'] : array();
		$exclusivity  = isset( $form_data['exclusivity'] ) ? $form_data['exclusivity'] : array();
		$recognition  = isset( $form_data['recognition'] ) ? $form_data['recognition'] : array();
		$independence = isset( $form_data['independence'] ) ? $form_data['independence'] : array();

		// Insert into database.
		$result = $this->insert_eoi(
			$sponsor_id,
			$event_id,
			$commitment,
			$exclusivity,
			$recognition,
			$independence
		);

		if ( $result ) {
			// Capture the insert ID before clearing state.
			$eoi_id = $result;

			// Record rate limit
			$this->record_eoi_submission();

			// Clear form state.
			$form->clear_state();

			// Send notification emails.
			$email_manager = new EMS_Email_Manager();
			$email_manager->send_eoi_confirmation( $eoi_id );
			$email_manager->send_eoi_admin_notification( $eoi_id );

			$this->logger->info(
				sprintf(
					'Sponsor EOI submitted: sponsor_id=%d, event_id=%d, eoi_id=%d',
					$sponsor_id,
					$event_id,
					$eoi_id
				),
				EMS_Logger::CONTEXT_GENERAL
			);

			// Redirect with success parameter to prevent resubmission.
			$redirect_url = add_query_arg(
				array(
					'ems_eoi_submitted' => '1',
					'ems_eoi_event'     => $event_id,
				),
				get_permalink()
			);

			wp_safe_redirect( $redirect_url );
			exit;
		} else {
			$this->logger->error(
				sprintf( 'Failed to insert EOI for sponsor %d, event %d', $sponsor_id, $event_id ),
				EMS_Logger::CONTEXT_GENERAL
			);
		}
	}

	/**
	 * Insert an EOI record into the database.
	 *
	 * @since 1.5.0
	 * @param int   $sponsor_id  Sponsor post ID.
	 * @param int   $event_id    Event post ID.
	 * @param array $commitment  Step 1 data.
	 * @param array $exclusivity Step 2 data.
	 * @param array $recognition Step 3 data.
	 * @param array $independence Step 4 data.
	 * @return int|false Inserted EOI ID or false on failure.
	 */
	private function insert_eoi( $sponsor_id, $event_id, $commitment, $exclusivity, $recognition, $independence ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ems_sponsor_eoi';

		// Serialize array values for TEXT columns.
		$contribution_nature  = isset( $commitment['contribution_nature'] ) && is_array( $commitment['contribution_nature'] )
			? implode( ', ', $commitment['contribution_nature'] )
			: ( $commitment['contribution_nature'] ?? '' );

		$recognition_elements = isset( $recognition['recognition_elements'] ) && is_array( $recognition['recognition_elements'] )
			? implode( ', ', $recognition['recognition_elements'] )
			: ( $recognition['recognition_elements'] ?? '' );

		$data = array(
			'sponsor_id'                       => $sponsor_id,
			'event_id'                         => $event_id,
			'status'                           => 'pending',
			'preferred_level'                  => sanitize_text_field( $commitment['preferred_level'] ?? '' ),
			'estimated_value'                  => ! empty( $commitment['estimated_value'] ) ? floatval( $commitment['estimated_value'] ) : null,
			'contribution_nature'              => sanitize_textarea_field( $contribution_nature ),
			'conditional_outcome'              => sanitize_textarea_field( $commitment['conditional_outcome'] ?? '' ),
			'speaker_nomination'               => sanitize_textarea_field( $commitment['speaker_nomination'] ?? '' ),
			'duration_interest'                => sanitize_text_field( $commitment['duration_interest'] ?? '' ),
			'exclusivity_requested'            => ( 'yes' === ( $exclusivity['exclusivity_requested'] ?? 'no' ) ) ? 1 : 0,
			'exclusivity_category'             => sanitize_text_field( $exclusivity['exclusivity_category'] ?? '' ),
			'shared_presence_accepted'         => ( 'yes' === ( $exclusivity['shared_presence_accepted'] ?? 'no' ) ) ? 1 : 0,
			'competitor_objections'            => sanitize_textarea_field( $exclusivity['competitor_objections'] ?? '' ),
			'exclusivity_conditions'           => sanitize_textarea_field( $exclusivity['exclusivity_conditions'] ?? '' ),
			'recognition_elements'             => sanitize_textarea_field( $recognition_elements ),
			'trade_display_requested'          => ( 'yes' === ( $recognition['trade_display_requested'] ?? 'no' ) ) ? 1 : 0,
			'trade_display_requirements'       => sanitize_textarea_field( $recognition['trade_display_requirements'] ?? '' ),
			'representatives_count'            => ! empty( $recognition['representatives_count'] ) ? absint( $recognition['representatives_count'] ) : null,
			'product_samples'                  => ( 'yes' === ( $recognition['product_samples'] ?? 'no' ) ) ? 1 : 0,
			'promotional_material'             => ( 'yes' === ( $recognition['promotional_material'] ?? 'no' ) ) ? 1 : 0,
			'delegate_data_collection'         => ( 'yes' === ( $recognition['delegate_data_collection'] ?? 'no' ) ) ? 1 : 0,
			'social_media_expectations'        => sanitize_textarea_field( $recognition['social_media_expectations'] ?? '' ),
			'content_independence_acknowledged' => ( '1' === ( $independence['content_independence_acknowledged'] ?? '0' ) ) ? 1 : 0,
			'content_influence'                => sanitize_textarea_field( $independence['content_influence'] ?? '' ),
			'therapeutic_claims'               => ( 'yes' === ( $independence['therapeutic_claims'] ?? 'no' ) ) ? 1 : 0,
			'material_review_required'         => ( 'yes' === ( $independence['material_review_required'] ?? 'no' ) ) ? 1 : 0,
			'submitted_at'                     => current_time( 'mysql' ),
		);

		$format = array(
			'%d', // sponsor_id
			'%d', // event_id
			'%s', // status
			'%s', // preferred_level
			'%f', // estimated_value
			'%s', // contribution_nature
			'%s', // conditional_outcome
			'%s', // speaker_nomination
			'%s', // duration_interest
			'%d', // exclusivity_requested
			'%s', // exclusivity_category
			'%d', // shared_presence_accepted
			'%s', // competitor_objections
			'%s', // exclusivity_conditions
			'%s', // recognition_elements
			'%d', // trade_display_requested
			'%s', // trade_display_requirements
			'%d', // representatives_count
			'%d', // product_samples
			'%d', // promotional_material
			'%d', // delegate_data_collection
			'%s', // social_media_expectations
			'%d', // content_independence_acknowledged
			'%s', // content_influence
			'%d', // therapeutic_claims
			'%d', // material_review_required
			'%s', // submitted_at
		);

		$result = $wpdb->insert( $table, $data, $format );

		if ( false === $result ) {
			$this->logger->error(
				sprintf( 'Database error inserting EOI: %s', $wpdb->last_error ),
				EMS_Logger::CONTEXT_GENERAL
			);
			return false;
		}

		$eoi_id = $wpdb->insert_id;

		// Fix NULL values that were cast to 0 by format specifiers.
		$null_updates = array();
		if ( is_null( $data['estimated_value'] ) ) {
			$null_updates[] = 'estimated_value = NULL';
		}
		if ( is_null( $data['representatives_count'] ) ) {
			$null_updates[] = 'representatives_count = NULL';
		}
		if ( ! empty( $null_updates ) ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET " . implode( ', ', $null_updates ) . " WHERE id = %d",
					$eoi_id
				)
			);
		}

		return $eoi_id;
	}

	// =========================================================================
	// Helper Methods
	// =========================================================================

	/**
	 * Get the sponsor post ID for the current logged-in user.
	 *
	 * @since 1.5.0
	 * @return int|false Sponsor post ID or false if not found.
	 */
	private function get_current_sponsor_id() {
		return $this->sponsor_portal->get_user_sponsor_id( get_current_user_id() );
	}

	/**
	 * Get the onboarding page URL by searching for the shortcode.
	 *
	 * @since 1.5.0
	 * @return string Onboarding page URL.
	 */
	private function get_onboarding_url() {
		global $wpdb;
		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_content LIKE %s LIMIT 1",
				'page',
				'publish',
				'%[ems_sponsor_onboarding%'
			)
		);
		if ( $page_id ) {
			return get_permalink( $page_id );
		}
		return home_url( '/sponsor-onboarding/' );
	}

	// =========================================================================
	// Rate Limiting (Task 10.4)
	// =========================================================================

	/**
	 * Check the rate limit for the current user.
	 *
	 * Allows a maximum of 5 EOI submissions per user per hour.
	 *
	 * @since 1.5.0
	 * @return true|WP_Error True if within limit, WP_Error if exceeded.
	 */
	private function check_rate_limit() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return true; // Not logged in users shouldn't reach here anyway
		}

		$key = 'ems_eoi_rl_' . $user_id;

		$attempts = get_transient( $key );

		if ( false !== $attempts && (int) $attempts >= 5 ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Too many submission attempts. Please try again later.', 'event-management-system' )
			);
		}

		return true;
	}

	/**
	 * Record an EOI submission attempt for rate limiting.
	 *
	 * Preserves the original TTL when incrementing the counter to prevent
	 * timer reset on each submission.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function record_eoi_submission() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$key = 'ems_eoi_rl_' . $user_id;

		$attempts = get_transient( $key );
		if ( false === $attempts ) {
			// First submission in window - set counter to 1 with full hour expiry
			set_transient( $key, 1, HOUR_IN_SECONDS );
		} else {
			// Subsequent submission - preserve remaining TTL to prevent timer reset
			global $wpdb;
			$timeout_key = '_transient_timeout_' . $key;
			$timeout = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
					$timeout_key
				)
			);

			if ( $timeout ) {
				$ttl = max( 0, (int) $timeout - time() );
				if ( $ttl > 0 ) {
					set_transient( $key, (int) $attempts + 1, $ttl );
				} else {
					// Transient expired, start new window
					set_transient( $key, 1, HOUR_IN_SECONDS );
				}
			} else {
				// Transient expired, start new window
				set_transient( $key, 1, HOUR_IN_SECONDS );
			}
		}
	}
}
