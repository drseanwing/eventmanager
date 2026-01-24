/**
 * Event Management System - Admin Scripts
 *
 * Handles admin AJAX operations, schedule builder, and admin UI interactions.
 *
 * @package EventManagementSystem
 * @since   1.0.0
 * @version 1.1.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize admin functionality
		EMS_Admin.init();
	});

	var EMS_Admin = {
		/**
		 * Initialize admin scripts
		 */
		init: function() {
			this.initReviewerAssignment();
			this.initScheduleBuilder();
			this.initFileUploads();
			this.initBulkActions();
			this.initMetaBoxes();
		},

		/**
		 * Initialize reviewer assignment functionality
		 */
		initReviewerAssignment: function() {
			var self = this;

			// Assign reviewer button
			$(document).on('click', '.ems-assign-reviewer-btn', function(e) {
				e.preventDefault();
				
				var $button = $(this);
				var abstractId = $button.data('abstract-id');
				var reviewerId = $button.closest('.ems-reviewer-select').find('select').val();
				
				if (!reviewerId) {
					alert('Please select a reviewer');
					return;
				}

				$button.prop('disabled', true).text('Assigning...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_assign_reviewer',
						nonce: ems_admin.nonce,
						abstract_id: abstractId,
						reviewer_id: reviewerId
					},
					success: function(response) {
						if (response.success) {
							self.showNotice(response.data.message || 'Reviewer assigned successfully', 'success');
							// Reload the page to show updated assignments
							location.reload();
						} else {
							self.showNotice(response.data.message || 'Failed to assign reviewer', 'error');
							$button.prop('disabled', false).text('Assign');
						}
					},
					error: function() {
						self.showNotice('An error occurred', 'error');
						$button.prop('disabled', false).text('Assign');
					}
				});
			});
		},

		/**
		 * Initialize schedule builder functionality
		 */
		initScheduleBuilder: function() {
			var self = this;

			// Make sessions sortable if jQuery UI is available
			if (typeof $.fn.sortable !== 'undefined') {
				$('.ems-schedule-sessions').sortable({
					handle: '.ems-session-handle',
					placeholder: 'ems-session-placeholder',
					update: function(event, ui) {
						self.updateSessionOrder();
					}
				});
			}

			// Save schedule button
			$(document).on('click', '.ems-save-schedule', function(e) {
				e.preventDefault();
				
				var $button = $(this);
				var eventId = $button.data('event-id');
				var sessions = self.collectSessionData();
				
				$button.prop('disabled', true).text('Saving...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_save_schedule',
						nonce: ems_admin.nonce,
						event_id: eventId,
						sessions: sessions
					},
					success: function(response) {
						if (response.success) {
							self.showNotice(response.data.message || 'Schedule saved successfully', 'success');
						} else {
							self.showNotice(response.data.message || 'Failed to save schedule', 'error');
						}
					},
					error: function() {
						self.showNotice('An error occurred while saving', 'error');
					},
					complete: function() {
						$button.prop('disabled', false).text('Save Schedule');
					}
				});
			});

			// Add session button
			$(document).on('click', '.ems-add-session', function(e) {
				e.preventDefault();
				var eventId = $(this).data('event-id');
				self.openSessionModal(eventId);
			});

			// Edit session
			$(document).on('click', '.ems-edit-session', function(e) {
				e.preventDefault();
				var sessionId = $(this).data('session-id');
				self.openSessionModal(null, sessionId);
			});

			// Delete session
			$(document).on('click', '.ems-delete-session', function(e) {
				e.preventDefault();
				if (confirm('Are you sure you want to delete this session?')) {
					var $sessionRow = $(this).closest('.ems-session-row');
					$sessionRow.fadeOut(200, function() {
						$(this).remove();
						self.updateSessionOrder();
					});
				}
			});
		},

		/**
		 * Collect session data from schedule builder
		 */
		collectSessionData: function() {
			var sessions = [];
			
			$('.ems-session-row').each(function(index) {
				var $row = $(this);
				sessions.push({
					id: $row.data('session-id') || 0,
					title: $row.find('.ems-session-title').text(),
					start_time: $row.find('.ems-session-start').val(),
					end_time: $row.find('.ems-session-end').val(),
					location: $row.find('.ems-session-location').val(),
					order: index
				});
			});
			
			return sessions;
		},

		/**
		 * Update session order after drag/drop
		 */
		updateSessionOrder: function() {
			$('.ems-session-row').each(function(index) {
				$(this).find('.ems-session-order').val(index);
			});
		},

		/**
		 * Open session editing modal
		 */
		openSessionModal: function(eventId, sessionId) {
			// This would open a modal for creating/editing sessions
			// For now, redirect to the session edit page
			if (sessionId) {
				window.location.href = 'post.php?post=' + sessionId + '&action=edit';
			} else if (eventId) {
				window.location.href = 'post-new.php?post_type=ems_session&event_id=' + eventId;
			}
		},

		/**
		 * Initialize file upload functionality
		 */
		initFileUploads: function() {
			var self = this;

			// File upload via AJAX
			$(document).on('change', '.ems-admin-file-input', function() {
				var $input = $(this);
				var file = this.files[0];
				var entityType = $input.data('entity-type') || 'general';
				var entityId = $input.data('entity-id') || 0;
				
				if (!file) return;

				var formData = new FormData();
				formData.append('action', 'ems_upload_file');
				formData.append('nonce', ems_admin.nonce);
				formData.append('file', file);
				formData.append('entity_type', entityType);
				formData.append('entity_id', entityId);

				var $progress = $input.siblings('.ems-upload-progress');
				if (!$progress.length) {
					$progress = $('<div class="ems-upload-progress"><div class="ems-progress-bar"></div></div>');
					$input.after($progress);
				}

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					xhr: function() {
						var xhr = new window.XMLHttpRequest();
						xhr.upload.addEventListener('progress', function(evt) {
							if (evt.lengthComputable) {
								var percentComplete = (evt.loaded / evt.total) * 100;
								$progress.find('.ems-progress-bar').css('width', percentComplete + '%');
							}
						}, false);
						return xhr;
					},
					success: function(response) {
						if (response.success) {
							self.showNotice('File uploaded successfully', 'success');
							// Update file list if present
							if (response.data.file_url) {
								var $fileList = $input.closest('.ems-file-upload-wrapper').find('.ems-file-list');
								if ($fileList.length) {
									$fileList.append(
										'<div class="ems-file-item">' +
										'<a href="' + response.data.file_url + '" target="_blank">' + response.data.file_name + '</a>' +
										'<button type="button" class="ems-remove-file" data-file-id="' + response.data.file_id + '">Remove</button>' +
										'</div>'
									);
								}
							}
						} else {
							self.showNotice(response.data.message || 'Upload failed', 'error');
						}
					},
					error: function() {
						self.showNotice('Upload failed', 'error');
					},
					complete: function() {
						$progress.fadeOut(200);
						$input.val('');
					}
				});
			});

			// Remove file
			$(document).on('click', '.ems-remove-file', function(e) {
				e.preventDefault();
				var $button = $(this);
				var fileId = $button.data('file-id');
				
				if (confirm('Are you sure you want to remove this file?')) {
					$button.closest('.ems-file-item').fadeOut(200, function() {
						$(this).remove();
					});
				}
			});
		},

		/**
		 * Initialize bulk actions
		 */
		initBulkActions: function() {
			var self = this;

			// Select all checkbox
			$(document).on('change', '.ems-select-all', function() {
				var isChecked = $(this).prop('checked');
				$(this).closest('table').find('.ems-select-item').prop('checked', isChecked);
			});

			// Bulk action submit
			$(document).on('click', '.ems-bulk-action-submit', function(e) {
				e.preventDefault();
				
				var action = $(this).siblings('.ems-bulk-action-select').val();
				if (!action) {
					alert('Please select an action');
					return;
				}

				var selectedIds = [];
				$('.ems-select-item:checked').each(function() {
					selectedIds.push($(this).val());
				});

				if (selectedIds.length === 0) {
					alert('Please select at least one item');
					return;
				}

				if (confirm('Are you sure you want to perform this action on ' + selectedIds.length + ' items?')) {
					// Process bulk action
					self.processBulkAction(action, selectedIds);
				}
			});
		},

		/**
		 * Process bulk action
		 */
		processBulkAction: function(action, ids) {
			var self = this;
			
			$.ajax({
				url: ems_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'ems_bulk_action',
					nonce: ems_admin.nonce,
					bulk_action: action,
					ids: ids
				},
				success: function(response) {
					if (response.success) {
						self.showNotice(response.data.message || 'Bulk action completed', 'success');
						location.reload();
					} else {
						self.showNotice(response.data.message || 'Bulk action failed', 'error');
					}
				},
				error: function() {
					self.showNotice('An error occurred', 'error');
				}
			});
		},

		/**
		 * Initialize meta boxes
		 */
		initMetaBoxes: function() {
			// Event date/time picker
			if (typeof $.fn.datepicker !== 'undefined') {
				$('.ems-datepicker').datepicker({
					dateFormat: 'yy-mm-dd'
				});
			}

			// Time picker
			if (typeof $.fn.timepicker !== 'undefined') {
				$('.ems-timepicker').timepicker({
					timeFormat: 'HH:mm',
					interval: 15
				});
			}

			// Ticket type repeater
			$(document).on('click', '.ems-add-ticket-type', function(e) {
				e.preventDefault();
				var $container = $(this).closest('.ems-ticket-types');
				var index = $container.find('.ems-ticket-row').length;
				
				var newRow = 
					'<div class="ems-ticket-row" data-index="' + index + '">' +
					'<input type="text" name="ticket_types[' + index + '][name]" placeholder="Ticket Name">' +
					'<input type="number" name="ticket_types[' + index + '][price]" placeholder="Price" step="0.01">' +
					'<input type="number" name="ticket_types[' + index + '][quantity]" placeholder="Quantity">' +
					'<button type="button" class="button ems-remove-ticket-type">Remove</button>' +
					'</div>';
				
				$container.find('.ems-ticket-rows').append(newRow);
			});

			$(document).on('click', '.ems-remove-ticket-type', function(e) {
				e.preventDefault();
				$(this).closest('.ems-ticket-row').remove();
			});
		},

		/**
		 * Show admin notice
		 *
		 * @param {string} message Notice message
		 * @param {string} type Notice type (success, error, warning)
		 */
		showNotice: function(message, type) {
			type = type || 'info';
			var noticeClass = 'notice-' + (type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'));
			
			var $notice = $(
				'<div class="notice ' + noticeClass + ' is-dismissible">' +
				'<p>' + message + '</p>' +
				'<button type="button" class="notice-dismiss">' +
				'<span class="screen-reader-text">Dismiss this notice.</span>' +
				'</button>' +
				'</div>'
			);
			
			// Insert after the page title
			$('.wrap > h1').first().after($notice);
			
			// Scroll to notice
			$('html, body').animate({
				scrollTop: $notice.offset().top - 50
			}, 300);
			
			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
			
			// Manual dismiss
			$notice.find('.notice-dismiss').on('click', function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			});
		}
	};

	// Expose for external use
	window.EMS_Admin = EMS_Admin;

})(jQuery);
