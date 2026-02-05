/**
 * EMS Multi-Step Form Engine - Client-Side
 *
 * Handles step navigation, client-side validation, conditional field display,
 * progress indicator interaction, auto-save, and AJAX form submission.
 *
 * @package EventManagementSystem
 * @since   1.5.0
 */

(function($) {
	'use strict';

	/**
	 * Multi-step form controller.
	 */
	var EMS_Forms = {

		/**
		 * Validation rule handlers.
		 */
		validators: {},

		/**
		 * Auto-save timer reference.
		 */
		saveTimer: null,

		/**
		 * Initialise all multi-step forms on the page.
		 */
		init: function() {
			var self = this;

			self.registerValidators();

			$('.ems-multi-step-form').each(function() {
				self.initForm($(this));
			});
		},

		// -----------------------------------------------------------------
		// Form initialisation
		// -----------------------------------------------------------------

		/**
		 * Initialise a single multi-step form.
		 *
		 * @param {jQuery} $wrapper The .ems-multi-step-form wrapper.
		 */
		initForm: function($wrapper) {
			var self   = this;
			var $form  = $wrapper.find('.ems-step-form');
			var formId = $wrapper.data('form-id');

			// Bind navigation buttons
			$wrapper.on('click', '.ems-form-next', function(e) {
				e.preventDefault();
				self.navigateNext($wrapper, $form);
			});

			$wrapper.on('click', '.ems-form-prev', function(e) {
				e.preventDefault();
				self.navigatePrev($wrapper, $form);
			});

			// Bind progress step clicks (completed steps)
			$wrapper.on('click', '.ems-progress-step-btn', function(e) {
				e.preventDefault();
				var targetStep = parseInt($(this).data('step'), 10);
				self.navigateToStep($wrapper, $form, targetStep);
			});

			// Bind review edit links
			$wrapper.on('click', '.ems-review-edit', function(e) {
				e.preventDefault();
				var targetStep = parseInt($(this).data('step'), 10);
				self.navigateToStep($wrapper, $form, targetStep);
			});

			// Bind form submission
			$form.on('submit', function(e) {
				e.preventDefault();
				self.submitForm($wrapper, $form);
			});

			// Clear field errors on input
			$form.on('input change', '.ems-form-control, input[type="checkbox"], input[type="radio"]', function() {
				self.clearFieldError($(this));
			});

			// Conditional field display
			self.initConditionalFields($form);
			$form.on('change', '.ems-form-control, input[type="radio"], input[type="checkbox"]', function() {
				self.evaluateConditionals($form);
			});

			// Auto-save on field blur (debounced)
			$form.on('blur', '.ems-form-control', function() {
				self.debounceSave($wrapper, $form);
			});
		},

		// -----------------------------------------------------------------
		// Navigation
		// -----------------------------------------------------------------

		/**
		 * Navigate to the next step after validating the current step.
		 */
		navigateNext: function($wrapper, $form) {
			var self        = this;
			var currentStep = parseInt($form.find('input[name="ems_current_step"]').val(), 10);

			// Validate current step client-side
			if (!self.validateCurrentStep($form)) {
				return;
			}

			// Validate server-side via AJAX
			self.ajaxValidateStep($wrapper, $form, currentStep, function(success) {
				if (success) {
					self.saveProgress($wrapper, $form, function() {
						self.goToStep($wrapper, $form, currentStep + 1);
					});
				}
			});
		},

		/**
		 * Navigate to the previous step (no validation needed).
		 */
		navigatePrev: function($wrapper, $form) {
			var currentStep = parseInt($form.find('input[name="ems_current_step"]').val(), 10);
			if (currentStep > 0) {
				this.goToStep($wrapper, $form, currentStep - 1);
			}
		},

		/**
		 * Navigate to a specific step (from progress bar or review edit).
		 */
		navigateToStep: function($wrapper, $form, targetStep) {
			this.goToStep($wrapper, $form, targetStep);
		},

		/**
		 * Transition to a step by reloading the form via AJAX or changing the URL.
		 *
		 * For simplicity and compatibility we submit a hidden redirect.
		 * The form state is already saved server-side.
		 */
		goToStep: function($wrapper, $form, stepIndex) {
			var formId = $wrapper.data('form-id');

			$form.find('input[name="ems_current_step"]').val(stepIndex);

			// Trigger a custom event so consuming code can hook in
			$wrapper.trigger('ems:step_change', { step: stepIndex, form_id: formId });

			// Reload via AJAX to get fresh step HTML
			var self = this;
			var data = {
				action:       'ems_form_save_progress',
				ems_form_id:  formId,
				current_step: stepIndex,
				form_data:    $form.serialize(),
				nonce:        $form.find('input[name="ems_form_nonce"]').val()
			};

			$.post(ems_forms.ajax_url, data, function(response) {
				if (response.success && response.data.redirect_url) {
					window.location.href = response.data.redirect_url;
				} else {
					// Fallback: reload the current page with step parameter
					var url = new URL(window.location.href);
					url.searchParams.set('ems_step', stepIndex);
					window.location.href = url.toString();
				}
			}).fail(function() {
				// Fallback reload
				var url = new URL(window.location.href);
				url.searchParams.set('ems_step', stepIndex);
				window.location.href = url.toString();
			});
		},

		// -----------------------------------------------------------------
		// Client-side validation
		// -----------------------------------------------------------------

		/**
		 * Register all built-in validators.
		 */
		registerValidators: function() {
			var self = this;

			self.validators.required = function(value, param, $field) {
				if ($field.is(':checkbox')) {
					return $field.is(':checked');
				}
				if (Array.isArray(value)) {
					return value.length > 0;
				}
				return $.trim(String(value)) !== '';
			};

			self.validators.email = function(value) {
				if (value === '') return true; // optional unless also 'required'
				return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
			};

			self.validators.phone = function(value) {
				if (value === '') return true;
				return /^[\+]?[\d\s\-\(\)]{7,20}$/.test(value);
			};

			self.validators.url = function(value) {
				if (value === '') return true;
				try {
					new URL(value);
					return true;
				} catch (e) {
					return false;
				}
			};

			self.validators.min = function(value, param) {
				if (value === '') return true;
				return String(value).length >= parseInt(param, 10);
			};

			self.validators.max = function(value, param) {
				if (value === '') return true;
				return String(value).length <= parseInt(param, 10);
			};

			self.validators.pattern = function(value, param) {
				if (value === '') return true;
				try {
					// Remove surrounding slashes if present
					var regexStr = param.replace(/^\/|\/$/g, '');
					var regex = new RegExp(regexStr);
					return regex.test(value);
				} catch (e) {
					return true;
				}
			};

			self.validators['in'] = function(value, param) {
				if (value === '') return true;
				var allowed = param.split(',').map(function(s) { return $.trim(s); });
				return allowed.indexOf(String(value)) !== -1;
			};
		},

		/**
		 * Validate the current step's fields using client-side rules.
		 *
		 * @param {jQuery} $form The form element.
		 * @return {boolean} True if all fields pass validation.
		 */
		validateCurrentStep: function($form) {
			var self      = this;
			var $step     = $form.find('.ems-form-step');
			var $rulesDiv = $step.find('.ems-step-validation-rules');
			var isValid   = true;
			var rules     = {};

			if ($rulesDiv.length && $rulesDiv.data('rules')) {
				rules = $rulesDiv.data('rules');
				if (typeof rules === 'string') {
					try {
						rules = JSON.parse(rules);
					} catch (e) {
						rules = {};
					}
				}
			}

			// Validate each field that has rules
			$.each(rules, function(fieldName, fieldRules) {
				var $field = $form.find('[name="' + fieldName + '"], [name="' + fieldName + '[]"]').first();

				// Skip conditionally hidden fields
				if ($field.length) {
					var $wrapper = $field.closest('.ems-form-field');
					if ($wrapper.length && $wrapper.css('display') === 'none') {
						return; // continue to next field
					}
					var $condGroup = $field.closest('.ems-conditional-group');
					if ($condGroup.length && $condGroup.css('display') === 'none') {
						return;
					}
				}

				if (!Array.isArray(fieldRules)) {
					fieldRules = [fieldRules];
				}

				var value = self.getFieldValue($form, fieldName);

				for (var i = 0; i < fieldRules.length; i++) {
					var ruleParts = fieldRules[i].split(':');
					var ruleName  = ruleParts[0];
					var ruleParam = ruleParts.slice(1).join(':');

					if (self.validators[ruleName]) {
						if (!self.validators[ruleName](value, ruleParam, $field)) {
							var errorMsg = self.getErrorMessage(ruleName, ruleParam, fieldName);
							self.showFieldError($form, fieldName, errorMsg);
							isValid = false;
							break; // one error per field
						}
					}
				}
			});

			if (!isValid) {
				self.scrollToFirstError($form);
			}

			return isValid;
		},

		/**
		 * Get the value of a field by name.
		 */
		getFieldValue: function($form, fieldName) {
			var $field = $form.find('[name="' + fieldName + '"]');

			if (!$field.length) {
				// Check for array-style names (multiselect checkboxes)
				var $checkboxes = $form.find('[name="' + fieldName + '[]"]:checked');
				if ($checkboxes.length) {
					var values = [];
					$checkboxes.each(function() { values.push($(this).val()); });
					return values;
				}
				return '';
			}

			if ($field.is(':radio')) {
				var $checked = $form.find('[name="' + fieldName + '"]:checked');
				return $checked.length ? $checked.val() : '';
			}

			if ($field.is(':checkbox') && !$field.attr('name').endsWith('[]')) {
				return $field.is(':checked') ? $field.val() : '';
			}

			return $field.val() || '';
		},

		/**
		 * Generate a human-readable error message for a failed rule.
		 */
		getErrorMessage: function(ruleName, ruleParam, fieldName) {
			var label = fieldName.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
				return l.toUpperCase();
			});

			var messages = {
				required: label + ' is required.',
				email:    label + ' must be a valid email address.',
				phone:    label + ' must be a valid phone number.',
				url:      label + ' must be a valid URL.',
				min:      label + ' must be at least ' + ruleParam + ' characters.',
				max:      label + ' must not exceed ' + ruleParam + ' characters.',
				pattern:  label + ' format is invalid.',
				'in':     label + ' contains an invalid selection.'
			};

			// Allow localised overrides via ems_forms.messages
			if (typeof ems_forms !== 'undefined' && ems_forms.messages && ems_forms.messages[ruleName]) {
				return ems_forms.messages[ruleName].replace('%s', label).replace('%d', ruleParam);
			}

			return messages[ruleName] || label + ' is invalid.';
		},

		// -----------------------------------------------------------------
		// Error display
		// -----------------------------------------------------------------

		/**
		 * Show an inline error message for a field.
		 */
		showFieldError: function($form, fieldName, message) {
			var $error = $form.find('#ems-error-' + fieldName);

			if (!$error.length) {
				// Fallback: find the field and append after it
				var $field = $form.find('[name="' + fieldName + '"]').first();
				if ($field.length) {
					$error = $('<span class="ems-field-error" id="ems-error-' + fieldName + '" role="alert"></span>');
					$field.closest('.ems-form-field').append($error);
				}
			}

			if ($error.length) {
				$error.text(message).show();
			}

			// Add error class to the input
			var $input = $form.find('[name="' + fieldName + '"], [name="' + fieldName + '[]"]');
			$input.addClass('ems-input-error');
		},

		/**
		 * Clear the error state for a field.
		 */
		clearFieldError: function($input) {
			$input.removeClass('ems-input-error');
			var name = $input.attr('name');
			if (name) {
				name = name.replace('[]', '');
				$('#ems-error-' + name).text('').hide();
			}
		},

		/**
		 * Scroll to the first visible error.
		 */
		scrollToFirstError: function($form) {
			var $firstError = $form.find('.ems-field-error:visible').first();
			if ($firstError.length) {
				$('html, body').animate({
					scrollTop: $firstError.offset().top - 120
				}, 300);
			}
		},

		// -----------------------------------------------------------------
		// Conditional fields
		// -----------------------------------------------------------------

		/**
		 * Initialise conditional field visibility.
		 */
		initConditionalFields: function($form) {
			this.evaluateConditionals($form);
		},

		/**
		 * Evaluate all conditional display rules and show/hide fields.
		 */
		evaluateConditionals: function($form) {
			var self = this;

			$form.find('[data-condition-field]').each(function() {
				var $el         = $(this);
				var condField   = $el.data('condition-field');
				var condValue   = String($el.data('condition-value'));
				var condOp      = $el.data('condition-operator') || 'equals';
				var actualValue = self.getFieldValue($form, condField);

				var show = false;

				switch (condOp) {
					case 'equals':
						show = (String(actualValue) === condValue);
						break;
					case 'not_equals':
						show = (String(actualValue) !== condValue);
						break;
					case 'contains':
						show = (String(actualValue).indexOf(condValue) !== -1);
						break;
					default:
						show = (String(actualValue) === condValue);
				}

				if (show) {
					$el.slideDown(200);
				} else {
					$el.slideUp(200);
				}
			});
		},

		// -----------------------------------------------------------------
		// AJAX helpers
		// -----------------------------------------------------------------

		/**
		 * Validate the current step server-side via AJAX.
		 *
		 * @param {jQuery}   $wrapper The form wrapper.
		 * @param {jQuery}   $form    The form element.
		 * @param {number}   stepIndex Current step index.
		 * @param {Function} callback  Called with (success:boolean).
		 */
		ajaxValidateStep: function($wrapper, $form, stepIndex, callback) {
			var self   = this;
			var formId = $wrapper.data('form-id');

			var data = {
				action:       'ems_form_validate_step',
				ems_form_id:  formId,
				current_step: stepIndex,
				form_data:    $form.serialize(),
				nonce:        $form.find('input[name="ems_form_nonce"]').val()
			};

			// Show loading state on Next button
			var $nextBtn = $wrapper.find('.ems-form-next');
			$nextBtn.prop('disabled', true).addClass('loading');

			$.post(ems_forms.ajax_url, data, function(response) {
				$nextBtn.prop('disabled', false).removeClass('loading');

				if (response.success) {
					callback(true);
				} else {
					// Display server-side errors
					if (response.data && response.data.errors) {
						$.each(response.data.errors, function(fieldName, messages) {
							var msg = Array.isArray(messages) ? messages[0] : messages;
							self.showFieldError($form, fieldName, msg);
						});
						self.scrollToFirstError($form);
					}
					callback(false);
				}
			}).fail(function() {
				$nextBtn.prop('disabled', false).removeClass('loading');
				// On network error, allow proceeding (server will revalidate on submit)
				callback(true);
			});
		},

		/**
		 * Save form progress via AJAX.
		 *
		 * @param {jQuery}   $wrapper The form wrapper.
		 * @param {jQuery}   $form    The form element.
		 * @param {Function} callback Optional callback on success.
		 */
		saveProgress: function($wrapper, $form, callback) {
			var formId = $wrapper.data('form-id');

			var data = {
				action:       'ems_form_save_progress',
				ems_form_id:  formId,
				current_step: $form.find('input[name="ems_current_step"]').val(),
				form_data:    $form.serialize(),
				nonce:        $form.find('input[name="ems_form_nonce"]').val()
			};

			$.post(ems_forms.ajax_url, data, function(response) {
				if (callback && typeof callback === 'function') {
					callback(response);
				}
			}).fail(function() {
				if (callback && typeof callback === 'function') {
					callback(null);
				}
			});
		},

		/**
		 * Debounced auto-save.
		 */
		debounceSave: function($wrapper, $form) {
			var self = this;

			if (self.saveTimer) {
				clearTimeout(self.saveTimer);
			}

			self.saveTimer = setTimeout(function() {
				self.saveProgress($wrapper, $form);
			}, 3000);
		},

		/**
		 * Submit the complete form.
		 */
		submitForm: function($wrapper, $form) {
			var self   = this;
			var formId = $wrapper.data('form-id');

			// Validate confirmation checkbox if present
			var $confirm = $form.find('#ems-confirm-submission');
			if ($confirm.length && !$confirm.is(':checked')) {
				self.showFieldError($form, 'confirm', ems_forms.i18n.confirm_required || 'Please confirm before submitting.');
				self.scrollToFirstError($form);
				return;
			}

			// Validate current step client-side
			if (!self.validateCurrentStep($form)) {
				return;
			}

			var $submitBtn = $wrapper.find('.ems-form-submit');
			$submitBtn.prop('disabled', true).addClass('loading');

			var data = {
				action:       'ems_form_submit',
				ems_form_id:  formId,
				form_data:    $form.serialize(),
				nonce:        $form.find('input[name="ems_form_nonce"]').val()
			};

			$.post(ems_forms.ajax_url, data, function(response) {
				$submitBtn.prop('disabled', false).removeClass('loading');

				if (response.success) {
					// Show success message
					var message = response.data.message || ems_forms.i18n.submit_success || 'Form submitted successfully!';
					$wrapper.html(
						'<div class="ems-notice ems-notice-success"><p>' + $('<span>').text(message).html() + '</p></div>'
					);

					// Redirect if provided
					if (response.data.redirect_url) {
						setTimeout(function() {
							window.location.href = response.data.redirect_url;
						}, 2000);
					}

					// Scroll to success message
					$('html, body').animate({
						scrollTop: $wrapper.offset().top - 100
					}, 300);
				} else {
					// Display errors
					if (response.data && response.data.errors) {
						$.each(response.data.errors, function(fieldName, messages) {
							var msg = Array.isArray(messages) ? messages[0] : messages;
							self.showFieldError($form, fieldName, msg);
						});
						self.scrollToFirstError($form);
					} else {
						var errMsg = (response.data && response.data.message) || ems_forms.i18n.submit_error || 'An error occurred. Please try again.';
						$form.prepend(
							'<div class="ems-notice ems-notice-error ems-form-message"><p>' + $('<span>').text(errMsg).html() + '</p></div>'
						);
						$('html, body').animate({
							scrollTop: $form.offset().top - 100
						}, 300);
					}
				}
			}).fail(function() {
				$submitBtn.prop('disabled', false).removeClass('loading');
				$form.prepend(
					'<div class="ems-notice ems-notice-error ems-form-message"><p>' +
					$('<span>').text(ems_forms.i18n.network_error || 'A network error occurred. Please try again.').html() +
					'</p></div>'
				);
			});
		}
	};

	// Initialise on DOM ready
	$(document).ready(function() {
		EMS_Forms.init();
	});

	// Expose for external use
	window.EMS_Forms = EMS_Forms;

})(jQuery);
