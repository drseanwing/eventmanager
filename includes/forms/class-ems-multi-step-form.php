<?php
/**
 * Multi-step form engine
 *
 * Reusable framework for building multi-page forms with progress tracking,
 * server-side validation, state persistence, and a review step.
 * Used by the Sponsor Onboarding (Phase 5) and Expression of Interest (Phase 6) forms.
 *
 * @package    EventManagementSystem
 * @subpackage Forms
 * @since      1.5.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Multi-step form engine class.
 *
 * Manages step registration, rendering, validation, and state persistence
 * for multi-page public-facing forms.
 *
 * @since 1.5.0
 */
class EMS_Multi_Step_Form {

	/**
	 * Unique form identifier.
	 *
	 * @since  1.5.0
	 * @access protected
	 * @var    string
	 */
	protected $form_id;

	/**
	 * Registered steps.
	 *
	 * Each step is an array with keys: slug, title, fields, validation_rules, completed.
	 *
	 * @since  1.5.0
	 * @access protected
	 * @var    array
	 */
	protected $steps = array();

	/**
	 * Current step index (zero-based).
	 *
	 * @since  1.5.0
	 * @access protected
	 * @var    int
	 */
	protected $current_step = 0;

	/**
	 * Collected form data keyed by step slug.
	 *
	 * @since  1.5.0
	 * @access protected
	 * @var    array
	 */
	protected $form_data = array();

	/**
	 * Logger instance.
	 *
	 * @since  1.5.0
	 * @access protected
	 * @var    EMS_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since 1.5.0
	 * @param string $form_id Unique form identifier.
	 */
	public function __construct( $form_id ) {
		$this->form_id = sanitize_key( $form_id );
		$this->logger  = EMS_Logger::instance();
	}

	/**
	 * Register a step in the form.
	 *
	 * @since 1.5.0
	 * @param string $slug             Step slug (unique identifier).
	 * @param string $title            Step display title.
	 * @param array  $fields           Array of field definitions.
	 * @param array  $validation_rules Validation rules keyed by field name.
	 * @return void
	 */
	public function register_step( $slug, $title, $fields, $validation_rules = array() ) {
		$this->steps[] = array(
			'slug'             => sanitize_key( $slug ),
			'title'            => $title,
			'fields'           => $fields,
			'validation_rules' => $validation_rules,
			'completed'        => false,
		);
	}

	/**
	 * Render the complete form for the current step.
	 *
	 * Outputs the progress indicator, form tag with nonce and hidden fields,
	 * current step fields, and navigation buttons.
	 *
	 * @since 1.5.0
	 * @return string HTML output.
	 */
	public function render() {
		if ( empty( $this->steps ) ) {
			return '<div class="ems-notice ems-notice-error">' .
				esc_html__( 'No form steps have been configured.', 'event-management-system' ) .
				'</div>';
		}

		$html  = '<div class="ems-multi-step-form" data-form-id="' . esc_attr( $this->form_id ) . '">';
		$html .= $this->render_progress();
		$html .= '<form method="post" class="ems-form ems-step-form" id="ems-form-' . esc_attr( $this->form_id ) . '" enctype="multipart/form-data" novalidate>';
		$html .= wp_nonce_field( 'ems_form_' . $this->form_id, 'ems_form_nonce', true, false );
		$html .= '<input type="hidden" name="ems_form_id" value="' . esc_attr( $this->form_id ) . '" />';
		$html .= '<input type="hidden" name="ems_current_step" value="' . esc_attr( $this->current_step ) . '" />';

		// Render current step content
		$html .= $this->render_step( $this->current_step );

		// Navigation buttons
		$html .= $this->render_navigation();

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a specific step's fields.
	 *
	 * @since 1.5.0
	 * @param int $step_index Zero-based step index.
	 * @return string HTML output.
	 */
	public function render_step( $step_index ) {
		if ( ! isset( $this->steps[ $step_index ] ) ) {
			return '';
		}

		$step      = $this->steps[ $step_index ];
		$step_data = isset( $this->form_data[ $step['slug'] ] ) ? $this->form_data[ $step['slug'] ] : array();

		$html  = '<div class="ems-form-step" data-step="' . esc_attr( $step_index ) . '" data-step-slug="' . esc_attr( $step['slug'] ) . '">';
		$html .= '<h3 class="ems-form-step__title">' . esc_html( $step['title'] ) . '</h3>';

		// Encode validation rules as data attribute for client-side validation
		if ( ! empty( $step['validation_rules'] ) ) {
			$html .= '<div class="ems-step-validation-rules" data-rules="' . esc_attr( wp_json_encode( $step['validation_rules'] ) ) . '"></div>';
		}

		foreach ( $step['fields'] as $field ) {
			$value = isset( $step_data[ $field['name'] ] ) ? $step_data[ $field['name'] ] : '';
			$html .= EMS_Form_Renderer::render_field( $field, $value );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the progress indicator.
	 *
	 * Shows step numbers with titles and active/complete/pending states.
	 *
	 * @since 1.5.0
	 * @return string HTML output.
	 */
	private function render_progress() {
		$progress = $this->get_progress();

		$html = '<div class="ems-form-progress" role="navigation" aria-label="' . esc_attr__( 'Form progress', 'event-management-system' ) . '">';
		$html .= '<ol class="ems-form-progress__list">';

		foreach ( $this->steps as $index => $step ) {
			$state      = $progress[ $index ]['state'];
			$step_class = 'ems-form-progress__step ems-form-progress__step--' . $state;
			$aria       = '';

			if ( 'active' === $state ) {
				$aria = ' aria-current="step"';
			}

			$clickable = ( 'complete' === $state );
			$tag_open  = $clickable
				? '<button type="button" class="ems-progress-step-btn" data-step="' . esc_attr( $index ) . '">'
				: '<span>';
			$tag_close = $clickable ? '</button>' : '</span>';

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

		$html .= '</ol>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render navigation buttons (Previous / Next / Submit).
	 *
	 * @since 1.5.0
	 * @return string HTML output.
	 */
	private function render_navigation() {
		$is_first = ( 0 === $this->current_step );
		$is_last  = ( $this->current_step >= count( $this->steps ) - 1 );

		$html = '<div class="ems-form-navigation">';

		if ( ! $is_first ) {
			$html .= '<button type="button" class="ems-btn ems-btn-secondary ems-form-prev" data-direction="prev">';
			$html .= esc_html__( 'Previous', 'event-management-system' );
			$html .= '</button>';
		} else {
			// Spacer to keep Next/Submit right-aligned
			$html .= '<span></span>';
		}

		if ( $is_last ) {
			$html .= '<button type="submit" class="ems-btn ems-btn-primary ems-form-submit">';
			$html .= esc_html__( 'Submit', 'event-management-system' );
			$html .= '</button>';
		} else {
			$html .= '<button type="button" class="ems-btn ems-btn-primary ems-form-next" data-direction="next">';
			$html .= esc_html__( 'Next', 'event-management-system' );
			$html .= '</button>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Validate a step's data against its validation rules.
	 *
	 * @since 1.5.0
	 * @param string $step_slug Step slug to validate.
	 * @param array  $data      Submitted data for the step.
	 * @return true|WP_Error True on success, WP_Error with field-specific errors on failure.
	 */
	public function validate_step( $step_slug, $data ) {
		$step = $this->get_step_by_slug( $step_slug );

		if ( ! $step ) {
			return new WP_Error( 'invalid_step', __( 'Invalid form step.', 'event-management-system' ) );
		}

		$errors = new WP_Error();
		$rules  = $step['validation_rules'];

		if ( empty( $rules ) ) {
			return true;
		}

		foreach ( $rules as $field_name => $field_rules ) {
			if ( ! is_array( $field_rules ) ) {
				$field_rules = array( $field_rules );
			}

			$value = isset( $data[ $field_name ] ) ? $data[ $field_name ] : '';
			$label = $this->get_field_label( $step, $field_name );

			// Check conditional visibility - skip validation if the field is conditionally hidden
			if ( $this->is_field_conditionally_hidden( $step, $field_name, $data ) ) {
				continue;
			}

			foreach ( $field_rules as $rule ) {
				$error = $this->apply_validation_rule( $rule, $field_name, $value, $label, $data );
				if ( $error ) {
					$errors->add( $field_name, $error );
					break; // One error per field is enough
				}
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Apply a single validation rule to a field value.
	 *
	 * @since 1.5.0
	 * @param string $rule       Rule string (e.g. 'required', 'email', 'min:3', 'pattern:/regex/').
	 * @param string $field_name Field name.
	 * @param mixed  $value      Field value.
	 * @param string $label      Field label for error messages.
	 * @param array  $data       Full step data for conditional checks.
	 * @return string|null Error message or null if valid.
	 */
	private function apply_validation_rule( $rule, $field_name, $value, $label, $data ) {
		// Parse rule with parameters
		$rule_parts = explode( ':', $rule, 2 );
		$rule_name  = $rule_parts[0];
		$rule_param = isset( $rule_parts[1] ) ? $rule_parts[1] : '';

		switch ( $rule_name ) {
			case 'required':
				if ( is_array( $value ) ) {
					if ( empty( $value ) ) {
						return sprintf(
							/* translators: %s: field label */
							__( '%s is required.', 'event-management-system' ),
							$label
						);
					}
				} elseif ( '' === trim( (string) $value ) ) {
					return sprintf(
						/* translators: %s: field label */
						__( '%s is required.', 'event-management-system' ),
						$label
					);
				}
				break;

			case 'email':
				if ( '' !== $value && ! is_email( $value ) ) {
					return sprintf(
						/* translators: %s: field label */
						__( '%s must be a valid email address.', 'event-management-system' ),
						$label
					);
				}
				break;

			case 'url':
				if ( '' !== $value && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
					return sprintf(
						/* translators: %s: field label */
						__( '%s must be a valid URL.', 'event-management-system' ),
						$label
					);
				}
				break;

			case 'phone':
				if ( '' !== $value && ! preg_match( '/^[\+]?[\d\s\-\(\)]{7,20}$/', $value ) ) {
					return sprintf(
						/* translators: %s: field label */
						__( '%s must be a valid phone number.', 'event-management-system' ),
						$label
					);
				}
				break;

			case 'min':
				$min_length = absint( $rule_param );
				if ( '' !== $value && mb_strlen( $value ) < $min_length ) {
					return sprintf(
						/* translators: 1: field label, 2: minimum length */
						__( '%1$s must be at least %2$d characters.', 'event-management-system' ),
						$label,
						$min_length
					);
				}
				break;

			case 'max':
				$max_length = absint( $rule_param );
				if ( '' !== $value && mb_strlen( $value ) > $max_length ) {
					return sprintf(
						/* translators: 1: field label, 2: maximum length */
						__( '%1$s must not exceed %2$d characters.', 'event-management-system' ),
						$label,
						$max_length
					);
				}
				break;

			case 'pattern':
				if ( '' !== $value ) {
					// Limit input length to prevent ReDoS attacks
					$check_value = substr( $value, 0, 1000 );
					// Suppress errors in case of invalid regex pattern
					$match = @preg_match( $rule_param, $check_value );
					if ( false === $match ) {
						// Invalid pattern, skip validation
						break;
					}
					if ( 0 === $match ) {
						return sprintf(
							/* translators: %s: field label */
							__( '%s format is invalid.', 'event-management-system' ),
							$label
						);
					}
				}
				break;

			case 'in':
				$allowed = array_map( 'trim', explode( ',', $rule_param ) );
				if ( '' !== $value && ! in_array( (string) $value, $allowed, true ) ) {
					return sprintf(
						/* translators: %s: field label */
						__( '%s contains an invalid selection.', 'event-management-system' ),
						$label
					);
				}
				break;
		}

		return null;
	}

	/**
	 * Get the current step index.
	 *
	 * @since 1.5.0
	 * @return int Zero-based step index.
	 */
	public function get_current_step() {
		return $this->current_step;
	}

	/**
	 * Set the current step index.
	 *
	 * @since 1.5.0
	 * @param int $index Zero-based step index.
	 * @return void
	 */
	public function set_current_step( $index ) {
		$index = absint( $index );
		if ( $index < count( $this->steps ) ) {
			$this->current_step = $index;
		}
	}

	/**
	 * Get all collected form data.
	 *
	 * @since 1.5.0
	 * @return array Form data keyed by step slug.
	 */
	public function get_form_data() {
		return $this->form_data;
	}

	/**
	 * Set form data for a specific step.
	 *
	 * @since 1.5.0
	 * @param string $step_slug Step slug.
	 * @param array  $data      Data to set for the step.
	 * @return void
	 */
	public function set_form_data( $step_slug, $data ) {
		$this->form_data[ $step_slug ] = $data;

		// Mark step as completed
		foreach ( $this->steps as &$step ) {
			if ( $step['slug'] === $step_slug ) {
				$step['completed'] = true;
				break;
			}
		}
	}

	/**
	 * Get data for a specific step.
	 *
	 * @since 1.5.0
	 * @param string $step_slug Step slug.
	 * @return array Step data or empty array.
	 */
	public function get_step_data( $step_slug ) {
		return isset( $this->form_data[ $step_slug ] ) ? $this->form_data[ $step_slug ] : array();
	}

	/**
	 * Check if all steps have been completed.
	 *
	 * @since 1.5.0
	 * @return bool True if all steps are marked complete.
	 */
	public function is_complete() {
		foreach ( $this->steps as $step ) {
			if ( ! $step['completed'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get progress information for all steps.
	 *
	 * @since 1.5.0
	 * @return array Array of step states: 'pending', 'complete', or 'active'.
	 */
	public function get_progress() {
		$progress = array();

		foreach ( $this->steps as $index => $step ) {
			if ( $index === $this->current_step ) {
				$state = 'active';
			} elseif ( $step['completed'] ) {
				$state = 'complete';
			} else {
				$state = 'pending';
			}

			$progress[ $index ] = array(
				'slug'  => $step['slug'],
				'title' => $step['title'],
				'state' => $state,
			);
		}

		return $progress;
	}

	/**
	 * Get the total number of steps.
	 *
	 * @since 1.5.0
	 * @return int Step count.
	 */
	public function get_step_count() {
		return count( $this->steps );
	}

	/**
	 * Get all step definitions.
	 *
	 * @since 1.5.0
	 * @return array Steps array.
	 */
	public function get_steps() {
		return $this->steps;
	}

	/**
	 * Get the form ID.
	 *
	 * @since 1.5.0
	 * @return string Form ID.
	 */
	public function get_form_id() {
		return $this->form_id;
	}

	// -------------------------------------------------------------------------
	// State Persistence (Task 4.2)
	// -------------------------------------------------------------------------

	/**
	 * Save form state to a WordPress transient.
	 *
	 * For logged-in users the key includes the user ID.
	 * For guests, a session-based key is derived from a nonce.
	 *
	 * @since 1.5.0
	 * @return bool True on success.
	 */
	public function save_state() {
		$key = $this->get_state_key();

		$state = array(
			'form_id'      => $this->form_id,
			'current_step' => $this->current_step,
			'form_data'    => $this->form_data,
			'steps_status' => array(),
		);

		foreach ( $this->steps as $step ) {
			$state['steps_status'][ $step['slug'] ] = $step['completed'];
		}

		$result = set_transient( $key, $state, DAY_IN_SECONDS );

		$this->logger->debug(
			sprintf( 'Form state saved for %s (key: %s)', $this->form_id, $key ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return $result;
	}

	/**
	 * Load form state from a WordPress transient.
	 *
	 * Restores current_step, form_data, and step completion status.
	 *
	 * @since 1.5.0
	 * @return bool True if state was loaded, false if no saved state exists.
	 */
	public function load_state() {
		$key   = $this->get_state_key();
		$state = get_transient( $key );

		if ( false === $state || ! is_array( $state ) ) {
			return false;
		}

		if ( isset( $state['form_id'] ) && $state['form_id'] !== $this->form_id ) {
			return false;
		}

		if ( isset( $state['current_step'] ) ) {
			$this->current_step = absint( $state['current_step'] );
		}

		if ( isset( $state['form_data'] ) && is_array( $state['form_data'] ) ) {
			$this->form_data = $state['form_data'];
		}

		// Restore step completion status
		if ( isset( $state['steps_status'] ) && is_array( $state['steps_status'] ) ) {
			foreach ( $this->steps as &$step ) {
				if ( isset( $state['steps_status'][ $step['slug'] ] ) ) {
					$step['completed'] = (bool) $state['steps_status'][ $step['slug'] ];
				}
			}
		}

		$this->logger->debug(
			sprintf( 'Form state loaded for %s (key: %s)', $this->form_id, $key ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return true;
	}

	/**
	 * Clear saved form state.
	 *
	 * @since 1.5.0
	 * @return bool True on success.
	 */
	public function clear_state() {
		$key    = $this->get_state_key();
		$result = delete_transient( $key );

		$this->logger->debug(
			sprintf( 'Form state cleared for %s (key: %s)', $this->form_id, $key ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return $result;
	}

	/**
	 * Generate the transient key for form state persistence.
	 *
	 * Uses user ID for logged-in users, or a nonce-based session key for guests.
	 *
	 * @since 1.5.0
	 * @return string Transient key.
	 */
	private function get_state_key() {
		if ( is_user_logged_in() ) {
			$identifier = get_current_user_id();
		} else {
			// Use a session-based identifier for guests
			if ( isset( $_COOKIE['ems_form_session'] ) ) {
				$identifier = sanitize_key( $_COOKIE['ems_form_session'] );
			} else {
				$identifier = wp_generate_password( 32, false );
				if ( ! headers_sent() ) {
					setcookie( 'ems_form_session', $identifier, array(
						'expires'  => time() + DAY_IN_SECONDS,
						'path'     => COOKIEPATH,
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					) );
				}
			}
		}

		return 'ems_form_' . $this->form_id . '_' . $identifier;
	}

	// -------------------------------------------------------------------------
	// Review Step (Task 4.7)
	// -------------------------------------------------------------------------

	/**
	 * Render a review summary of all collected form data.
	 *
	 * Groups data by step title, shows field labels with entered values,
	 * provides edit links to navigate back, and includes a confirmation checkbox.
	 *
	 * @since 1.5.0
	 * @return string HTML output.
	 */
	public function render_review() {
		$html = '<div class="ems-form-review">';
		$html .= '<h3 class="ems-form-review__title">' . esc_html__( 'Review Your Information', 'event-management-system' ) . '</h3>';
		$html .= '<p class="ems-form-review__description">' . esc_html__( 'Please review the information below before submitting.', 'event-management-system' ) . '</p>';

		foreach ( $this->steps as $index => $step ) {
			$step_data = isset( $this->form_data[ $step['slug'] ] ) ? $this->form_data[ $step['slug'] ] : array();

			if ( empty( $step_data ) ) {
				continue;
			}

			$html .= '<div class="ems-form-review__section">';
			$html .= '<div class="ems-form-review__section-header">';
			$html .= '<h4>' . esc_html( $step['title'] ) . '</h4>';
			$html .= '<button type="button" class="ems-btn ems-btn-small ems-btn-secondary ems-review-edit" data-step="' . esc_attr( $index ) . '">';
			$html .= esc_html__( 'Edit', 'event-management-system' );
			$html .= '</button>';
			$html .= '</div>';

			$html .= '<dl class="ems-form-review__data">';

			foreach ( $step['fields'] as $field ) {
				$field_name  = $field['name'];
				$field_value = isset( $step_data[ $field_name ] ) ? $step_data[ $field_name ] : '';

				// Skip empty values and hidden fields
				if ( '' === $field_value || 'hidden' === $field['type'] || 'conditional_group' === $field['type'] ) {
					continue;
				}

				$display_value = $this->format_review_value( $field, $field_value );
				$field_label   = ! empty( $field['label'] ) ? $field['label'] : $field_name;

				$html .= '<div class="ems-form-review__item">';
				$html .= '<dt>' . esc_html( $field_label ) . '</dt>';
				$html .= '<dd>' . $display_value . '</dd>';
				$html .= '</div>';
			}

			$html .= '</dl>';
			$html .= '</div>';
		}

		// Confirmation checkbox
		$html .= '<div class="ems-form-review__confirm">';
		$html .= '<label class="ems-checkbox-label">';
		$html .= '<input type="checkbox" name="ems_confirm_submission" id="ems-confirm-submission" value="1" required />';
		$html .= ' ' . esc_html__( 'I confirm that the information above is correct and I wish to submit this form.', 'event-management-system' );
		$html .= ' <span class="ems-required">*</span>';
		$html .= '</label>';
		$html .= '<span class="ems-field-error" id="ems-error-confirm" role="alert"></span>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Format a field value for display in the review step.
	 *
	 * Converts select/radio option keys to labels, checkboxes to Yes/No, etc.
	 *
	 * @since 1.5.0
	 * @param array $field Field definition.
	 * @param mixed $value Field value.
	 * @return string Formatted display value (already escaped).
	 */
	private function format_review_value( $field, $value ) {
		switch ( $field['type'] ) {
			case 'select':
			case 'radio':
				$options = isset( $field['options'] ) ? $field['options'] : array();
				return isset( $options[ $value ] ) ? esc_html( $options[ $value ] ) : esc_html( $value );

			case 'multiselect':
				$options  = isset( $field['options'] ) ? $field['options'] : array();
				$selected = is_array( $value ) ? $value : explode( ',', $value );
				$labels   = array();
				foreach ( $selected as $val ) {
					$labels[] = isset( $options[ $val ] ) ? $options[ $val ] : $val;
				}
				return esc_html( implode( ', ', $labels ) );

			case 'checkbox':
				return ( '1' === (string) $value || 'yes' === $value || true === $value )
					? esc_html__( 'Yes', 'event-management-system' )
					: esc_html__( 'No', 'event-management-system' );

			case 'url':
				return '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';

			case 'email':
				return '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';

			case 'file':
				return esc_html( $value );

			case 'textarea':
				return nl2br( esc_html( $value ) );

			default:
				return esc_html( $value );
		}
	}

	// -------------------------------------------------------------------------
	// Helper Methods
	// -------------------------------------------------------------------------

	/**
	 * Get a step definition by slug.
	 *
	 * @since 1.5.0
	 * @param string $slug Step slug.
	 * @return array|null Step definition or null if not found.
	 */
	private function get_step_by_slug( $slug ) {
		foreach ( $this->steps as $step ) {
			if ( $step['slug'] === $slug ) {
				return $step;
			}
		}

		return null;
	}

	/**
	 * Get a step index by slug.
	 *
	 * @since 1.5.0
	 * @param string $slug Step slug.
	 * @return int|null Step index or null if not found.
	 */
	public function get_step_index_by_slug( $slug ) {
		foreach ( $this->steps as $index => $step ) {
			if ( $step['slug'] === $slug ) {
				return $index;
			}
		}

		return null;
	}

	/**
	 * Get the human-readable label for a field within a step.
	 *
	 * @since 1.5.0
	 * @param array  $step       Step definition.
	 * @param string $field_name Field name.
	 * @return string Field label.
	 */
	private function get_field_label( $step, $field_name ) {
		foreach ( $step['fields'] as $field ) {
			if ( $field['name'] === $field_name ) {
				return ! empty( $field['label'] ) ? $field['label'] : $field_name;
			}
		}

		return $field_name;
	}

	/**
	 * Check if a field is conditionally hidden based on current step data.
	 *
	 * @since 1.5.0
	 * @param array  $step       Step definition.
	 * @param string $field_name Field name to check.
	 * @param array  $data       Submitted data.
	 * @return bool True if the field should be hidden.
	 */
	private function is_field_conditionally_hidden( $step, $field_name, $data ) {
		foreach ( $step['fields'] as $field ) {
			if ( $field['name'] !== $field_name ) {
				continue;
			}

			if ( empty( $field['conditional'] ) ) {
				return false;
			}

			$condition     = $field['conditional'];
			$trigger_field = $condition['field'];
			$trigger_value = $condition['value'];
			$operator      = isset( $condition['operator'] ) ? $condition['operator'] : 'equals';
			$actual_value  = isset( $data[ $trigger_field ] ) ? $data[ $trigger_field ] : '';

			switch ( $operator ) {
				case 'equals':
					return ( (string) $actual_value !== (string) $trigger_value );
				case 'not_equals':
					return ( (string) $actual_value === (string) $trigger_value );
				case 'contains':
					return ( false === strpos( (string) $actual_value, (string) $trigger_value ) );
				default:
					return ( (string) $actual_value !== (string) $trigger_value );
			}
		}

		return false;
	}
}
