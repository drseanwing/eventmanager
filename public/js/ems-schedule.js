/**
 * Schedule Display JavaScript
 *
 * Handles schedule filtering, search, and session registration.
 *
 * @package    EventManagementSystem
 * @subpackage Public/JS
 * @since      1.2.0
 */

(function($) {
	'use strict';

	/**
	 * Schedule Manager
	 */
	var EMSSchedule = {

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.initFilters();
			this.initSearch();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Session registration
			$(document).on('click', '.ems-register-session', this.registerSession);
			
			// Session cancellation
			$(document).on('click', '.ems-cancel-session', this.cancelSession);
			
			// Filter changes
			$(document).on('change', '.ems-filter', this.handleFilterChange);
			
			// Clear filters
			$(document).on('click', '.ems-clear-filters', this.clearFilters);
			
			// Search input
			$(document).on('input', '.ems-search-input', this.handleSearch);
		},

		/**
		 * Initialize filters
		 */
		initFilters: function() {
			// Store original session cards for filtering
			$('.ems-session-card').each(function() {
				var $card = $(this);
				$card.data('original-html', $card.html());
				$card.data('searchable-text', $card.text().toLowerCase());
			});
		},

		/**
		 * Initialize search
		 */
		initSearch: function() {
			// Debounce search input
			var searchTimeout;
			$(document).on('input', '.ems-search-input', function() {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(function() {
					EMSSchedule.performSearch();
				}, 300);
			});
		},

		/**
		 * Register for session
		 */
		registerSession: function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var sessionId = $button.data('session-id');
			
			if (!sessionId) {
				alert(emsSchedule.strings.registerError);
				return;
			}

			// Disable button
			$button.prop('disabled', true).text(emsSchedule.strings.loading);

			// Send AJAX request
			$.ajax({
				url: emsSchedule.ajaxurl,
				type: 'POST',
				data: {
					action: 'ems_register_session',
					nonce: emsSchedule.nonce,
					session_id: sessionId
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						EMSSchedule.showMessage(response.data.message || emsSchedule.strings.registerSuccess, 'success');
						
						// Update button state
						$button.replaceWith('<button class="ems-btn ems-btn-danger ems-cancel-session" data-session-id="' + sessionId + '">' + 
							'Cancel Registration</button>');
						
						// Update capacity display
						EMSSchedule.updateCapacity($button.closest('.ems-session-card'));
						
					} else {
						// Show error message
						EMSSchedule.showMessage(response.data.message || emsSchedule.strings.registerError, 'error');
						$button.prop('disabled', false).text('Register');
					}
				},
				error: function() {
					EMSSchedule.showMessage(emsSchedule.strings.registerError, 'error');
					$button.prop('disabled', false).text('Register');
				}
			});
		},

		/**
		 * Cancel session registration
		 */
		cancelSession: function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var sessionId = $button.data('session-id');
			
			if (!sessionId) {
				alert(emsSchedule.strings.cancelError);
				return;
			}

			// Confirm cancellation
			if (!confirm(emsSchedule.strings.confirmCancel)) {
				return;
			}

			// Disable button
			$button.prop('disabled', true).text(emsSchedule.strings.loading);

			// Send AJAX request
			$.ajax({
				url: emsSchedule.ajaxurl,
				type: 'POST',
				data: {
					action: 'ems_cancel_session',
					nonce: emsSchedule.nonce,
					session_id: sessionId
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						EMSSchedule.showMessage(response.data.message || emsSchedule.strings.cancelSuccess, 'success');
						
						// Check if we're on participant schedule page
						var $participantSession = $button.closest('.ems-participant-session');
						if ($participantSession.length) {
							// Remove the session from participant schedule
							$participantSession.fadeOut(300, function() {
								$(this).remove();
								
								// Check if there are any sessions left
								if ($('.ems-participant-session').length === 0) {
									$('.ems-participant-sessions').html(
										'<p class="ems-no-sessions">' + 
										'You have not registered for any sessions yet.' + 
										'</p>'
									);
								}
							});
						} else {
							// Update button state on schedule page
							$button.replaceWith('<button class="ems-btn ems-btn-primary ems-register-session" data-session-id="' + sessionId + '">' + 
								'Register</button>');
							
							// Update capacity display
							EMSSchedule.updateCapacity($button.closest('.ems-session-card'));
						}
						
					} else {
						// Show error message
						EMSSchedule.showMessage(response.data.message || emsSchedule.strings.cancelError, 'error');
						$button.prop('disabled', false).text('Cancel');
					}
				},
				error: function() {
					EMSSchedule.showMessage(emsSchedule.strings.cancelError, 'error');
					$button.prop('disabled', false).text('Cancel');
				}
			});
		},

		/**
		 * Handle filter change
		 */
		handleFilterChange: function() {
			EMSSchedule.applyFilters();
		},

		/**
		 * Apply filters
		 */
		applyFilters: function() {
			var filters = {
				track: $('.ems-filter[data-filter="track"]').val(),
				session_type: $('.ems-filter[data-filter="session_type"]').val(),
				location: $('.ems-filter[data-filter="location"]').val()
			};

			// Show/hide sessions based on filters
			$('.ems-session-card').each(function() {
				var $card = $(this);
				var show = true;

				// Check each filter
				$.each(filters, function(filterName, filterValue) {
					if (filterValue && $card.data(filterName) !== filterValue) {
						show = false;
						return false; // Break loop
					}
				});

				if (show) {
					$card.fadeIn(200);
				} else {
					$card.fadeOut(200);
				}
			});

			// Check if any groups are now empty
			$('.ems-schedule-group').each(function() {
				var $group = $(this);
				var visibleSessions = $group.find('.ems-session-card:visible').length;
				
				if (visibleSessions === 0) {
					$group.hide();
				} else {
					$group.show();
				}
			});
		},

		/**
		 * Clear filters
		 */
		clearFilters: function(e) {
			e.preventDefault();
			
			// Reset all filter dropdowns
			$('.ems-filter').val('');
			
			// Show all sessions
			$('.ems-session-card').fadeIn(200);
			$('.ems-schedule-group').show();
			
			// Clear search
			$('.ems-search-input').val('');
		},

		/**
		 * Handle search
		 */
		handleSearch: function() {
			var searchTerm = $(this).val().toLowerCase().trim();

			if (searchTerm.length === 0) {
				// Show all sessions
				$('.ems-session-card').fadeIn(200);
				$('.ems-schedule-group').show();
				return;
			}

			// Filter sessions by search term
			$('.ems-session-card').each(function() {
				var $card = $(this);
				var searchableText = $card.data('searchable-text');

				if (searchableText.indexOf(searchTerm) !== -1) {
					$card.fadeIn(200);
				} else {
					$card.fadeOut(200);
				}
			});

			// Hide empty groups
			$('.ems-schedule-group').each(function() {
				var $group = $(this);
				var visibleSessions = $group.find('.ems-session-card:visible').length;
				
				if (visibleSessions === 0) {
					$group.hide();
				} else {
					$group.show();
				}
			});
		},

		/**
		 * Update capacity display
		 */
		updateCapacity: function($card) {
			// Reload the session card to get updated capacity
			// This would need an AJAX endpoint to fetch updated session data
			// For now, we'll just add a visual indicator
			var $capacity = $card.find('.ems-session-capacity');
			if ($capacity.length) {
				$capacity.addClass('capacity-updated');
				setTimeout(function() {
					$capacity.removeClass('capacity-updated');
				}, 1000);
			}
		},

		/**
		 * Show message
		 */
		showMessage: function(message, type) {
			type = type || 'notice';
			
			var $message = $('<div class="ems-' + type + '">' + message + '</div>');
			
			// Insert at the top of the schedule wrapper
			var $wrapper = $('.ems-schedule-wrapper, .ems-participant-schedule, .ems-session-detail').first();
			if ($wrapper.length) {
				$message.prependTo($wrapper).hide().fadeIn(300);
				
				// Auto-dismiss after 5 seconds
				setTimeout(function() {
					$message.fadeOut(300, function() {
						$(this).remove();
					});
				}, 5000);
			} else {
				alert(message);
			}
		}
	};

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function() {
		if ($('.ems-schedule-wrapper, .ems-participant-schedule, .ems-session-detail').length) {
			EMSSchedule.init();
		}
	});

})(jQuery);
