<?php
/**
 * Form field renderer
 *
 * Renders individual form fields for the multi-step form engine.
 * Supports text, textarea, email, phone, url, select, multiselect,
 * checkbox, radio, file, date, number, hidden, and conditional_group types.
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
 * Form field renderer class.
 *
 * Provides static methods to render form fields with consistent markup,
 * labels, help text, error containers, and conditional display attributes.
 *
 * @since 1.5.0
 */
class EMS_Form_Renderer {

	/**
	 * Render a form field.
	 *
	 * Dispatches to the appropriate render method based on field type.
	 * Each field outputs a wrapper div with label, input, help text,
	 * and error container.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition array.
	 * @param string $value Current field value.
	 * @return string Rendered HTML.
	 */
	public static function render_field( $field, $value = '' ) {
		$field = wp_parse_args( $field, array(
			'name'        => '',
			'type'        => 'text',
			'label'       => '',
			'required'    => false,
			'options'     => array(),
			'help_text'   => '',
			'conditional' => null,
			'default'     => '',
			'placeholder' => '',
			'min'         => '',
			'max'         => '',
			'accept'      => '',
			'rows'        => 5,
			'fields'      => array(),
		) );

		if ( '' === $value && '' !== $field['default'] ) {
			$value = $field['default'];
		}

		$type   = $field['type'];
		$method = 'render_' . $type;

		if ( ! method_exists( __CLASS__, $method ) ) {
			$method = 'render_text';
		}

		$conditional_attrs = self::get_conditional_attrs( $field );

		$wrapper_classes = array(
			'ems-form-field',
			'ems-form-field--' . esc_attr( $type ),
		);

		if ( $field['required'] ) {
			$wrapper_classes[] = 'ems-form-field--required';
		}

		$html  = '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"' . $conditional_attrs . '>';
		$html .= call_user_func( array( __CLASS__, $method ), $field, $value );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a text input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_text( $field, $value ) {
		$aria_describedby = self::get_aria_describedby( $field );
		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="text" name="%s" id="ems-field-%s" value="%s" class="ems-form-control" placeholder="%s"%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ),
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a textarea field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_textarea( $field, $value ) {
		$aria_describedby = self::get_aria_describedby( $field );
		$html  = self::render_label( $field );
		$html .= sprintf(
			'<textarea name="%s" id="ems-field-%s" class="ems-form-control" rows="%d" placeholder="%s"%s%s>%s</textarea>',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			absint( $field['rows'] ),
			esc_attr( $field['placeholder'] ),
			$aria_describedby,
			$field['required'] ? ' required' : '',
			esc_textarea( $value )
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render an email input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_email( $field, $value ) {
		$aria_describedby = self::get_aria_describedby( $field );
		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="email" name="%s" id="ems-field-%s" value="%s" class="ems-form-control" placeholder="%s"%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ),
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a phone input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_phone( $field, $value ) {
		$aria_describedby = self::get_aria_describedby( $field );
		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="tel" name="%s" id="ems-field-%s" value="%s" class="ems-form-control" placeholder="%s"%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ),
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a URL input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_url( $field, $value ) {
		$aria_describedby = self::get_aria_describedby( $field );
		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="url" name="%s" id="ems-field-%s" value="%s" class="ems-form-control" placeholder="%s"%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ? $field['placeholder'] : 'https://' ),
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a select dropdown field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_select( $field, $value ) {
		$aria_describedby = self::get_aria_describedby( $field );
		$html  = self::render_label( $field );
		$html .= sprintf(
			'<select name="%s" id="ems-field-%s" class="ems-form-control"%s%s>',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= '<option value="">' . esc_html__( 'Select...', 'event-management-system' ) . '</option>';

		foreach ( $field['options'] as $opt_value => $opt_label ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $opt_value ),
				selected( $value, $opt_value, false ),
				esc_html( $opt_label )
			);
		}

		$html .= '</select>';
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a multiselect field (multiple checkboxes).
	 *
	 * @since 1.5.0
	 * @param array        $field Field definition.
	 * @param string|array $value Current value(s).
	 * @return string HTML output.
	 */
	private static function render_multiselect( $field, $value ) {
		$selected = is_array( $value ) ? $value : ( $value ? explode( ',', $value ) : array() );
		$required_indicator = $field['required'] ? ' <span class="ems-required">*</span>' : '';

		$html  = '<fieldset>';
		$html .= '<legend>' . esc_html( $field['label'] ) . $required_indicator . '</legend>';
		$html .= '<div class="ems-multiselect-group">';

		foreach ( $field['options'] as $opt_value => $opt_label ) {
			$checked = in_array( (string) $opt_value, $selected, true ) ? ' checked' : '';
			$html   .= sprintf(
				'<label class="ems-checkbox-label"><input type="checkbox" name="%s[]" value="%s"%s /> %s</label>',
				esc_attr( $field['name'] ),
				esc_attr( $opt_value ),
				$checked,
				esc_html( $opt_label )
			);
		}

		$html .= '</div>';
		$html .= '</fieldset>';
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a single checkbox field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_checkbox( $field, $value ) {
		$checked = ( '1' === (string) $value || 'yes' === $value || true === $value ) ? ' checked' : '';
		$aria_describedby = self::get_aria_describedby( $field );

		$html  = '<label class="ems-checkbox-label">';
		$html .= sprintf(
			'<input type="checkbox" name="%s" id="ems-field-%s" value="1"%s%s%s /> ',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			$checked,
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= esc_html( $field['label'] );
		if ( $field['required'] ) {
			$html .= ' <span class="ems-required">*</span>';
		}
		$html .= '</label>';
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a radio button group.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_radio( $field, $value ) {
		$required_indicator = $field['required'] ? ' <span class="ems-required">*</span>' : '';

		$html  = '<fieldset>';
		$html .= '<legend>' . esc_html( $field['label'] ) . $required_indicator . '</legend>';
		$html .= '<div class="ems-radio-group">';

		foreach ( $field['options'] as $opt_value => $opt_label ) {
			$checked = checked( $value, $opt_value, false );
			$html   .= sprintf(
				'<label class="ems-radio-label"><input type="radio" name="%s" value="%s"%s%s /> %s</label>',
				esc_attr( $field['name'] ),
				esc_attr( $opt_value ),
				$checked,
				$field['required'] ? ' required' : '',
				esc_html( $opt_label )
			);
		}

		$html .= '</div>';
		$html .= '</fieldset>';
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a file upload field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value (filename).
	 * @return string HTML output.
	 */
	private static function render_file( $field, $value ) {
		$accept = ! empty( $field['accept'] ) ? ' accept="' . esc_attr( $field['accept'] ) . '"' : '';
		$aria_describedby = self::get_aria_describedby( $field );

		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="file" name="%s" id="ems-field-%s" class="ems-form-control"%s%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			$accept,
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);

		if ( $value ) {
			$html .= '<p class="ems-file-current">';
			$html .= esc_html__( 'Current file: ', 'event-management-system' ) . esc_html( $value );
			$html .= '</p>';
		}

		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a date input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_date( $field, $value ) {
		$min_attr = ! empty( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
		$max_attr = ! empty( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
		$aria_describedby = self::get_aria_describedby( $field );

		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="date" name="%s" id="ems-field-%s" value="%s" class="ems-form-control"%s%s%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value ),
			$min_attr,
			$max_attr,
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a number input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_number( $field, $value ) {
		$min_attr = ( '' !== $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
		$max_attr = ( '' !== $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
		$aria_describedby = self::get_aria_describedby( $field );

		$html  = self::render_label( $field );
		$html .= sprintf(
			'<input type="number" name="%s" id="ems-field-%s" value="%s" class="ems-form-control" placeholder="%s"%s%s%s%s />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ),
			$min_attr,
			$max_attr,
			$aria_describedby,
			$field['required'] ? ' required' : ''
		);
		$html .= self::render_help_text( $field );
		$html .= self::render_error_container( $field );

		return $html;
	}

	/**
	 * Render a hidden input field.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition.
	 * @param string $value Current value.
	 * @return string HTML output.
	 */
	private static function render_hidden( $field, $value ) {
		return sprintf(
			'<input type="hidden" name="%s" id="ems-field-%s" value="%s" />',
			esc_attr( $field['name'] ),
			esc_attr( $field['name'] ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a conditional group wrapper.
	 *
	 * Wraps child fields in a div with data-condition attributes
	 * that the client-side JS uses to show/hide the group.
	 *
	 * @since 1.5.0
	 * @param array  $field Field definition with 'conditional' and 'fields' keys.
	 * @param string $value Unused for group wrappers.
	 * @return string HTML output.
	 */
	private static function render_conditional_group( $field, $value ) {
		$condition = $field['conditional'];
		$attrs     = '';

		if ( $condition ) {
			$attrs .= sprintf(
				' data-condition-field="%s" data-condition-value="%s"',
				esc_attr( $condition['field'] ),
				esc_attr( $condition['value'] )
			);
			if ( ! empty( $condition['operator'] ) ) {
				$attrs .= sprintf( ' data-condition-operator="%s"', esc_attr( $condition['operator'] ) );
			}
		}

		$html = '<div class="ems-conditional-group"' . $attrs . ' style="display:none;">';

		if ( ! empty( $field['fields'] ) ) {
			foreach ( $field['fields'] as $child_field ) {
				$child_value = '';
				$html       .= self::render_field( $child_field, $child_value );
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a field label.
	 *
	 * @since 1.5.0
	 * @param array $field Field definition.
	 * @return string HTML label element.
	 */
	private static function render_label( $field ) {
		if ( empty( $field['label'] ) ) {
			return '';
		}

		$required_indicator = $field['required'] ? ' <span class="ems-required">*</span>' : '';

		return sprintf(
			'<label for="ems-field-%s">%s%s</label>',
			esc_attr( $field['name'] ),
			esc_html( $field['label'] ),
			$required_indicator
		);
	}

	/**
	 * Render help text below a field.
	 *
	 * @since 1.5.0
	 * @param array $field Field definition.
	 * @return string HTML help text element.
	 */
	private static function render_help_text( $field ) {
		if ( empty( $field['help_text'] ) ) {
			return '';
		}

		return sprintf(
			'<span id="ems-help-%s" class="ems-form-help">%s</span>',
			esc_attr( $field['name'] ),
			esc_html( $field['help_text'] )
		);
	}

	/**
	 * Render an error container for a field.
	 *
	 * @since 1.5.0
	 * @param array $field Field definition.
	 * @return string HTML error container element.
	 */
	private static function render_error_container( $field ) {
		return sprintf(
			'<span class="ems-field-error" id="ems-error-%s" role="alert"></span>',
			esc_attr( $field['name'] )
		);
	}

	/**
	 * Get conditional display data attributes for a field wrapper.
	 *
	 * @since 1.5.0
	 * @param array $field Field definition.
	 * @return string Data attributes string or empty string.
	 */
	private static function get_conditional_attrs( $field ) {
		if ( empty( $field['conditional'] ) || 'conditional_group' === $field['type'] ) {
			return '';
		}

		$condition = $field['conditional'];
		$attrs     = sprintf(
			' data-condition-field="%s" data-condition-value="%s"',
			esc_attr( $condition['field'] ),
			esc_attr( $condition['value'] )
		);

		if ( ! empty( $condition['operator'] ) ) {
			$attrs .= sprintf( ' data-condition-operator="%s"', esc_attr( $condition['operator'] ) );
		}

		return $attrs;
	}

	/**
	 * Get aria-describedby attribute for a field.
	 *
	 * Links the input to its error container and help text for screen readers.
	 *
	 * @since 1.5.0
	 * @param array $field Field definition.
	 * @return string aria-describedby attribute or empty string.
	 */
	private static function get_aria_describedby( $field ) {
		$ids = array();

		// Always link to error container
		$ids[] = 'ems-error-' . esc_attr( $field['name'] );

		// Link to help text if present
		if ( ! empty( $field['help_text'] ) ) {
			$ids[] = 'ems-help-' . esc_attr( $field['name'] );
		}

		if ( empty( $ids ) ) {
			return '';
		}

		return ' aria-describedby="' . implode( ' ', $ids ) . '"';
	}
}
