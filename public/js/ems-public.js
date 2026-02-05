/**
 * Event Management System - Public Scripts
 *
 * Handles registration forms, abstract submissions, and general public functionality.
 *
 * @package EventManagementSystem
 * @since   1.0.0
 * @version 1.1.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize public functionality
		EMS_Public.init();
	});

	var EMS_Public = {
		/**
		 * Initialize public scripts
		 */
		init: function() {
			this.handleRegistrationForm();
			this.handleAbstractSubmission();
			this.initFormValidation();
			this.initFileUploads();
		},

		/**
		 * Handle event registration form submission
		 */
		handleRegistrationForm: function() {
			var self = this;

			$('#ems-registration-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $submitBtn = $form.find('[type="submit"]');
				var originalBtnText = $submitBtn.text();
				
				// Validate form
				if (!self.validateForm($form)) {
					return false;
				}

				// Disable button and show loading
				$submitBtn.prop('disabled', true).text('Processing...');

				// Collect form data
				var formData = new FormData($form[0]);
				formData.append('action', 'ems_submit_registration');
				formData.append('nonce', ems_public.nonce);

				// Submit via AJAX
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							// Show success message
							self.showMessage($form, response.data.message || 'Registration successful!', 'success');
							
							// Reset form
							$form[0].reset();
							
							// Redirect if URL provided
							if (response.data.redirect_url) {
								setTimeout(function() {
									window.location.href = response.data.redirect_url;
								}, 2000);
							}
						} else {
							// Show error message
							self.showMessage($form, response.data.message || 'Registration failed. Please try again.', 'error');
						}
					},
					error: function(xhr, status, error) {
						self.showMessage($form, 'An error occurred. Please try again.', 'error');
						console.error('Registration error:', error);
					},
					complete: function() {
						$submitBtn.prop('disabled', false).text(originalBtnText);
					}
				});
			});
		},

		/**
		 * Handle abstract submission form
		 */
		handleAbstractSubmission: function() {
			var self = this;

			// Abstract submission form
			$('#ems-abstract-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $submitBtn = $form.find('[type="submit"]');
				var originalBtnText = $submitBtn.text();
				
				// Validate form
				if (!self.validateForm($form)) {
					return false;
				}

				// Disable button and show loading
				$submitBtn.prop('disabled', true).text('Submitting...');

				// Collect form data
				var formData = new FormData($form[0]);
				formData.append('action', 'ems_submit_abstract');
				formData.append('nonce', ems_public.nonce);

				// Submit via AJAX
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							// Show success message
							self.showMessage($form, response.data.message || 'Abstract submitted successfully!', 'success');
							
							// Redirect to dashboard if URL provided
							if (response.data.redirect_url) {
								setTimeout(function() {
									window.location.href = response.data.redirect_url;
								}, 2000);
							}
						} else {
							// Show error message
							self.showMessage($form, response.data.message || 'Submission failed. Please try again.', 'error');
						}
					},
					error: function(xhr, status, error) {
						self.showMessage($form, 'An error occurred. Please try again.', 'error');
						console.error('Abstract submission error:', error);
					},
					complete: function() {
						$submitBtn.prop('disabled', false).text(originalBtnText);
					}
				});
			});

			// Co-author management
			$(document).on('click', '.ems-add-coauthor', function(e) {
				e.preventDefault();
				var $container = $(this).closest('.ems-coauthors-container');
				var index = $container.find('.ems-coauthor-row').length;
				
				var newRow = '<div class="ems-coauthor-row" data-index="' + index + '">' +
					'<input type="text" name="coauthors[' + index + '][name]" placeholder="Name" class="ems-form-control">' +
					'<input type="email" name="coauthors[' + index + '][email]" placeholder="Email" class="ems-form-control">' +
					'<input type="text" name="coauthors[' + index + '][affiliation]" placeholder="Affiliation" class="ems-form-control">' +
					'<button type="button" class="ems-remove-coauthor ems-btn ems-btn-small ems-btn-danger">Remove</button>' +
					'</div>';
				
				$container.find('.ems-coauthors-list').append(newRow);
			});

			$(document).on('click', '.ems-remove-coauthor', function(e) {
				e.preventDefault();
				$(this).closest('.ems-coauthor-row').fadeOut(200, function() {
					$(this).remove();
				});
			});
		},

		/**
		 * Initialize form validation
		 */
		initFormValidation: function() {
			// Real-time email validation
			$(document).on('blur', 'input[type="email"]', function() {
				var $input = $(this);
				var email = $input.val();
				
				if (email && !EMS_Public.isValidEmail(email)) {
					$input.addClass('ems-input-error');
					EMS_Public.showFieldError($input, 'Please enter a valid email address');
				} else {
					$input.removeClass('ems-input-error');
					EMS_Public.hideFieldError($input);
				}
			});

			// Required field validation on blur
			$(document).on('blur', '[required]', function() {
				var $input = $(this);
				
				if (!$input.val().trim()) {
					$input.addClass('ems-input-error');
					EMS_Public.showFieldError($input, 'This field is required');
				} else {
					$input.removeClass('ems-input-error');
					EMS_Public.hideFieldError($input);
				}
			});
		},

		/**
		 * Initialize file upload handling
		 */
		initFileUploads: function() {
			// File input change handler
			$(document).on('change', '.ems-file-input', function() {
				var $input = $(this);
				var files = this.files;
				var $preview = $input.siblings('.ems-file-preview');
				
				if (!$preview.length) {
					$preview = $('<div class="ems-file-preview"></div>');
					$input.after($preview);
				}
				
				$preview.empty();
				
				for (var i = 0; i < files.length; i++) {
					var file = files[i];
					var fileSize = (file.size / 1024 / 1024).toFixed(2);
					
					$preview.append(
						'<div class="ems-file-item">' +
						'<span class="ems-file-name">' + file.name + '</span>' +
						'<span class="ems-file-size">(' + fileSize + ' MB)</span>' +
						'</div>'
					);
				}
			});

			// Drag and drop support
			$('.ems-dropzone').on('dragover', function(e) {
				e.preventDefault();
				$(this).addClass('ems-dropzone-active');
			}).on('dragleave', function(e) {
				e.preventDefault();
				$(this).removeClass('ems-dropzone-active');
			}).on('drop', function(e) {
				e.preventDefault();
				$(this).removeClass('ems-dropzone-active');
				
				var files = e.originalEvent.dataTransfer.files;
				var $fileInput = $(this).find('input[type="file"]');
				
				if ($fileInput.length && files.length) {
					$fileInput[0].files = files;
					$fileInput.trigger('change');
				}
			});
		},

		/**
		 * Validate form
		 *
		 * @param {jQuery} $form Form element
		 * @return {boolean} True if valid
		 */
		validateForm: function($form) {
			var isValid = true;
			var self = this;
			
			// Check required fields
			$form.find('[required]').each(function() {
				var $input = $(this);
				
				if (!$input.val().trim()) {
					$input.addClass('ems-input-error');
					self.showFieldError($input, 'This field is required');
					isValid = false;
				} else {
					$input.removeClass('ems-input-error');
					self.hideFieldError($input);
				}
			});
			
			// Check email fields
			$form.find('input[type="email"]').each(function() {
				var $input = $(this);
				var email = $input.val();
				
				if (email && !self.isValidEmail(email)) {
					$input.addClass('ems-input-error');
					self.showFieldError($input, 'Please enter a valid email address');
					isValid = false;
				}
			});
			
			// Scroll to first error
			if (!isValid) {
				var $firstError = $form.find('.ems-input-error').first();
				if ($firstError.length) {
					$('html, body').animate({
						scrollTop: $firstError.offset().top - 100
					}, 300);
				}
			}
			
			return isValid;
		},

		/**
		 * Show field error
		 *
		 * @param {jQuery} $input Input element
		 * @param {string} message Error message
		 */
		showFieldError: function($input, message) {
			var $error = $input.siblings('.ems-field-error');
			
			if (!$error.length) {
				$error = $('<span class="ems-field-error"></span>');
				$input.after($error);
			}
			
			$error.text(message).show();
		},

		/**
		 * Hide field error
		 *
		 * @param {jQuery} $input Input element
		 */
		hideFieldError: function($input) {
			$input.siblings('.ems-field-error').hide();
		},

		/**
		 * Show form message
		 *
		 * @param {jQuery} $form Form element
		 * @param {string} message Message text
		 * @param {string} type Message type (success, error, warning)
		 */
		showMessage: function($form, message, type) {
			type = type || 'notice';
			
			// Remove existing messages
			$form.find('.ems-form-message').remove();
			
			var $message = $('<div class="ems-form-message ems-' + type + '">' + message + '</div>');
			$form.prepend($message);
			
			// Scroll to message
			$('html, body').animate({
				scrollTop: $message.offset().top - 100
			}, 300);
			
			// Auto-dismiss success messages
			if (type === 'success') {
				setTimeout(function() {
					$message.fadeOut(300, function() {
						$(this).remove();
					});
				}, 5000);
			}
		},

		/**
		 * Validate email address
		 *
		 * @param {string} email Email address
		 * @return {boolean} True if valid
		 */
		isValidEmail: function(email) {
			var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return regex.test(email);
		}
	};

	// Expose for external use
	window.EMS_Public = EMS_Public;

})(jQuery);
