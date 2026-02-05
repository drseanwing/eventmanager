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
			this.initSponsorship();
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

		// ==========================================
		// SPONSORSHIP MANAGEMENT
		// ==========================================

		/**
		 * Initialize sponsorship meta box functionality
		 */
		initSponsorship: function() {
			// Only run on event edit screens
			if (!$('#ems-sponsorship-settings').length) {
				return;
			}

			this.initSponsorshipToggles();
			this.initSponsorshipLevels();
			this.initSponsorSearch();
			this.initSponsorLinking();
		},

		/**
		 * Toggle visibility of sponsorship sections
		 */
		initSponsorshipToggles: function() {
			// Toggle levels section when sponsorship enabled/disabled
			$('#ems_sponsorship_enabled').on('change', function() {
				if ($(this).is(':checked')) {
					$('#ems-sponsorship-levels-toggle').slideDown(200);
				} else {
					$('#ems-sponsorship-levels-toggle').slideUp(200);
				}
			});

			// Toggle levels UI when levels enabled/disabled
			$('#ems_sponsorship_levels_enabled').on('change', function() {
				if ($(this).is(':checked')) {
					$('#ems-sponsorship-levels-ui').slideDown(200);
				} else {
					$('#ems-sponsorship-levels-ui').slideUp(200);
				}
			});
		},

		/**
		 * Initialize sponsorship level management (add, save, delete, populate defaults)
		 */
		initSponsorshipLevels: function() {
			var self = this;

			// Populate defaults button
			$(document).on('click', '#ems-populate-defaults-btn', function(e) {
				e.preventDefault();
				var $button = $(this);
				var eventId = $button.data('event-id');

				$button.prop('disabled', true).text('Creating...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_populate_default_levels',
						nonce: ems_admin.nonce,
						event_id: eventId
					},
					success: function(response) {
						if (response.success) {
							self.showSponsorshipNotice(response.data.message, 'success');
							self.renderLevelRows(response.data.levels);
							$('#ems-populate-defaults-wrapper').hide();
							$('#ems-levels-table').show();
							// Also update the level dropdown for linking
							self.updateLevelDropdown(response.data.levels);
						} else {
							self.showSponsorshipNotice(response.data.message, 'error');
						}
					},
					error: function() {
						self.showSponsorshipNotice('An error occurred.', 'error');
					},
					complete: function() {
						$button.prop('disabled', false).text('Populate Defaults (Bronze / Silver / Gold)');
					}
				});
			});

			// Add new level button
			$(document).on('click', '#ems-add-level-btn', function(e) {
				e.preventDefault();
				var $button = $(this);
				var eventId = $button.data('event-id');

				var levelName = $.trim($('#ems-new-level-name').val());
				if (!levelName) {
					alert('Level name is required.');
					return;
				}

				$button.prop('disabled', true).text('Adding...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_save_sponsorship_levels',
						nonce: ems_admin.nonce,
						event_id: eventId,
						level_name: levelName,
						colour: $('#ems-new-level-colour').val(),
						value_aud: $('#ems-new-level-value').val(),
						slots_total: $('#ems-new-level-slots').val(),
						sort_order: $('#ems-new-level-sort').val(),
						recognition_text: $('#ems-new-level-recognition').val()
					},
					success: function(response) {
						if (response.success) {
							self.showSponsorshipNotice(response.data.message, 'success');
							self.appendLevelRow(response.data.level);
							$('#ems-levels-table').show();
							$('#ems-populate-defaults-wrapper').hide();
							// Clear the form
							$('#ems-new-level-name').val('');
							$('#ems-new-level-colour').val('#CD7F32');
							$('#ems-new-level-value').val('');
							$('#ems-new-level-slots').val('');
							$('#ems-new-level-sort').val('0');
							$('#ems-new-level-recognition').val('');
							// Update dropdown
							self.addLevelToDropdown(response.data.level);
						} else {
							self.showSponsorshipNotice(response.data.message, 'error');
						}
					},
					error: function() {
						self.showSponsorshipNotice('An error occurred.', 'error');
					},
					complete: function() {
						$button.prop('disabled', false).text('Add Level');
					}
				});
			});

			// Save existing level button
			$(document).on('click', '.ems-save-level-btn', function(e) {
				e.preventDefault();
				var $button = $(this);
				var levelId = $button.data('level-id');
				var $row = $button.closest('.ems-level-row');

				$button.prop('disabled', true).text('Saving...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_save_sponsorship_levels',
						nonce: ems_admin.nonce,
						level_id: levelId,
						level_name: $row.find('.ems-level-name').val(),
						colour: $row.find('.ems-level-colour').val(),
						value_aud: $row.find('.ems-level-value').val(),
						slots_total: $row.find('.ems-level-slots').val(),
						sort_order: $row.find('.ems-level-sort-order').val(),
						recognition_text: $row.find('.ems-level-recognition').val()
					},
					success: function(response) {
						if (response.success) {
							self.showSponsorshipNotice(response.data.message, 'success');
							// Update dropdown option text
							$('#ems-sponsor-level-select option[value="' + levelId + '"]').text(response.data.level.level_name);
						} else {
							self.showSponsorshipNotice(response.data.message, 'error');
						}
					},
					error: function() {
						self.showSponsorshipNotice('An error occurred.', 'error');
					},
					complete: function() {
						$button.prop('disabled', false).text('Save');
					}
				});
			});

			// Delete level button
			$(document).on('click', '.ems-delete-level-btn', function(e) {
				e.preventDefault();
				var $button = $(this);

				if ($button.prop('disabled')) {
					return;
				}

				if (!confirm('Are you sure you want to delete this level?')) {
					return;
				}

				var levelId = $button.data('level-id');
				var $row = $button.closest('.ems-level-row');

				$button.prop('disabled', true).text('Deleting...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_delete_sponsorship_level',
						nonce: ems_admin.nonce,
						level_id: levelId
					},
					success: function(response) {
						if (response.success) {
							$row.fadeOut(200, function() {
								$(this).remove();
								// Show populate defaults if no rows remain
								if ($('#ems-levels-tbody tr').length === 0) {
									$('#ems-levels-table').hide();
									$('#ems-populate-defaults-wrapper').show();
								}
							});
							self.showSponsorshipNotice(response.data.message, 'success');
							// Remove from dropdown
							$('#ems-sponsor-level-select option[value="' + levelId + '"]').remove();
						} else {
							self.showSponsorshipNotice(response.data.message, 'error');
							$button.prop('disabled', false).text('Delete');
						}
					},
					error: function() {
						self.showSponsorshipNotice('An error occurred.', 'error');
						$button.prop('disabled', false).text('Delete');
					}
				});
			});
		},

		/**
		 * Render level rows into the table (used after populate defaults)
		 */
		renderLevelRows: function(levels) {
			var $tbody = $('#ems-levels-tbody');
			$tbody.empty();

			for (var i = 0; i < levels.length; i++) {
				this.appendLevelRow(levels[i]);
			}
		},

		/**
		 * Append a single level row to the table
		 */
		appendLevelRow: function(level) {
			var deleteDisabled = parseInt(level.slots_filled, 10) > 0 ? ' disabled title="Cannot delete: sponsors linked"' : '';

			var row = '<tr class="ems-level-row" data-level-id="' + level.id + '">' +
				'<td><input type="number" class="ems-level-sort-order small-text" value="' + level.sort_order + '" min="0" style="width: 50px;" /></td>' +
				'<td><input type="text" class="ems-level-name" value="' + this.escAttr(level.level_name) + '" style="width: 100%;" /></td>' +
				'<td><input type="color" class="ems-level-colour" value="' + level.colour + '" /></td>' +
				'<td><input type="number" class="ems-level-value small-text" value="' + (level.value_aud || '') + '" min="0" step="0.01" style="width: 90px;" /></td>' +
				'<td><input type="number" class="ems-level-slots small-text" value="' + (level.slots_total || '') + '" min="0" style="width: 60px;" /></td>' +
				'<td><span class="ems-level-filled">' + level.slots_filled + '</span></td>' +
				'<td><textarea class="ems-level-recognition" rows="2" style="width: 100%;">' + this.escHtml(level.recognition_text || '') + '</textarea></td>' +
				'<td>' +
				'<button type="button" class="button button-small ems-save-level-btn" data-level-id="' + level.id + '">Save</button> ' +
				'<button type="button" class="button button-small button-link-delete ems-delete-level-btn" data-level-id="' + level.id + '"' + deleteDisabled + '>Delete</button>' +
				'</td>' +
				'</tr>';

			$('#ems-levels-tbody').append(row);
		},

		/**
		 * Update the level dropdown after populating defaults
		 */
		updateLevelDropdown: function(levels) {
			var $select = $('#ems-sponsor-level-select');
			// Keep the "No Level" option
			$select.find('option:not(:first)').remove();

			for (var i = 0; i < levels.length; i++) {
				$select.append('<option value="' + levels[i].id + '">' + this.escHtml(levels[i].level_name) + '</option>');
			}
		},

		/**
		 * Add a single level to the dropdown
		 */
		addLevelToDropdown: function(level) {
			$('#ems-sponsor-level-select').append(
				'<option value="' + level.id + '">' + this.escHtml(level.level_name) + '</option>'
			);
		},

		/**
		 * Initialize sponsor autocomplete search
		 */
		initSponsorSearch: function() {
			var self = this;
			var searchTimer = null;

			$('#ems-sponsor-search').on('input', function() {
				var $input = $(this);
				var query = $.trim($input.val());
				var $results = $('#ems-sponsor-search-results');

				// Clear selection
				$('#ems-sponsor-search-id').val('');

				if (searchTimer) {
					clearTimeout(searchTimer);
				}

				if (query.length < 2) {
					$results.hide().empty();
					return;
				}

				searchTimer = setTimeout(function() {
					$.ajax({
						url: ems_admin.ajax_url,
						type: 'POST',
						data: {
							action: 'ems_search_sponsors',
							nonce: ems_admin.nonce,
							search: query
						},
						success: function(response) {
							$results.empty();
							if (response.success && response.data.sponsors.length > 0) {
								for (var i = 0; i < response.data.sponsors.length; i++) {
									var sp = response.data.sponsors[i];
									$results.append(
										'<div class="ems-autocomplete-item" data-id="' + sp.id + '">' +
										self.escHtml(sp.title) +
										'</div>'
									);
								}
								$results.show();
							} else {
								$results.append('<div class="ems-autocomplete-empty">No sponsors found</div>');
								$results.show();
							}
						}
					});
				}, 300);
			});

			// Click on autocomplete result
			$(document).on('click', '.ems-autocomplete-item', function() {
				var $item = $(this);
				$('#ems-sponsor-search').val($item.text());
				$('#ems-sponsor-search-id').val($item.data('id'));
				$('#ems-sponsor-search-results').hide().empty();
			});

			// Hide results when clicking outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('#ems-sponsor-search, #ems-sponsor-search-results').length) {
					$('#ems-sponsor-search-results').hide();
				}
			});
		},

		/**
		 * Initialize sponsor link/unlink functionality
		 */
		initSponsorLinking: function() {
			var self = this;

			// Link sponsor button
			$(document).on('click', '#ems-link-sponsor-btn', function(e) {
				e.preventDefault();
				var $button = $(this);
				var eventId = $('#ems-event-id').val();
				var sponsorId = $('#ems-sponsor-search-id').val();
				var levelId = $('#ems-sponsor-level-select').val();

				if (!sponsorId) {
					alert('Please search for and select a sponsor first.');
					return;
				}

				$button.prop('disabled', true).text('Linking...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_link_sponsor_to_event',
						nonce: ems_admin.nonce,
						event_id: eventId,
						sponsor_id: sponsorId,
						level_id: levelId || 0
					},
					success: function(response) {
						if (response.success) {
							self.showSponsorshipNotice(response.data.message, 'success');
							// Remove "no sponsors" row
							$('#ems-linked-sponsors-table .ems-no-sponsors-row').remove();
							// Append new row
							self.appendSponsorRow(response.data);
							// Clear search
							$('#ems-sponsor-search').val('');
							$('#ems-sponsor-search-id').val('');
							$('#ems-sponsor-level-select').val('');
							// Update filled count in levels table if applicable
							if (response.data.level_id) {
								self.updateFilledCount(response.data.level_id, 1);
							}
						} else {
							self.showSponsorshipNotice(response.data.message, 'error');
						}
					},
					error: function() {
						self.showSponsorshipNotice('An error occurred.', 'error');
					},
					complete: function() {
						$button.prop('disabled', false).text('Link Sponsor');
					}
				});
			});

			// Unlink sponsor button
			$(document).on('click', '.ems-unlink-sponsor-btn', function(e) {
				e.preventDefault();
				if (!confirm('Are you sure you want to unlink this sponsor?')) {
					return;
				}

				var $button = $(this);
				var $row = $button.closest('tr');
				var eventId = $button.data('event-id');
				var sponsorId = $button.data('sponsor-id');

				$button.prop('disabled', true).text('Unlinking...');

				$.ajax({
					url: ems_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_unlink_sponsor_from_event',
						nonce: ems_admin.nonce,
						event_id: eventId,
						sponsor_id: sponsorId
					},
					success: function(response) {
						if (response.success) {
							var levelId = response.data.level_id;
							$row.fadeOut(200, function() {
								$(this).remove();
								// Show empty message if no sponsors remain
								if ($('#ems-linked-sponsors-table tbody tr').length === 0) {
									$('#ems-linked-sponsors-table tbody').append(
										'<tr class="ems-no-sponsors-row"><td colspan="4"><em>No sponsors linked to this event yet.</em></td></tr>'
									);
								}
							});
							self.showSponsorshipNotice(response.data.message, 'success');
							// Update filled count
							if (levelId) {
								self.updateFilledCount(levelId, -1);
							}
						} else {
							self.showSponsorshipNotice(response.data.message, 'error');
							$button.prop('disabled', false).text('Unlink');
						}
					},
					error: function() {
						self.showSponsorshipNotice('An error occurred.', 'error');
						$button.prop('disabled', false).text('Unlink');
					}
				});
			});
		},

		/**
		 * Append a linked sponsor row to the table
		 */
		appendSponsorRow: function(data) {
			var levelCell = '';
			if (data.level_name) {
				var contrastColor = this.getContrastColor(data.level_colour);
				levelCell = '<span class="ems-level-pill" style="background-color: ' + data.level_colour + '; color: ' + contrastColor + ';">' +
					this.escHtml(data.level_name) + '</span>';
			} else {
				levelCell = '<em>None</em>';
			}

			var eventId = $('#ems-event-id').val();
			var row = '<tr data-sponsor-id="' + data.sponsor_id + '" data-level-id="' + (data.level_id || '') + '">' +
				'<td><a href="' + data.sponsor_url + '">' + this.escHtml(data.sponsor_name) + '</a></td>' +
				'<td>' + levelCell + '</td>' +
				'<td>' + this.escHtml(data.linked_date) + '</td>' +
				'<td><button type="button" class="button button-small ems-unlink-sponsor-btn" data-sponsor-id="' + data.sponsor_id + '" data-event-id="' + eventId + '">Unlink</button></td>' +
				'</tr>';

			$('#ems-linked-sponsors-table tbody').append(row);
		},

		/**
		 * Update the filled count display in the levels table
		 */
		updateFilledCount: function(levelId, delta) {
			var $row = $('#ems-levels-tbody .ems-level-row[data-level-id="' + levelId + '"]');
			if ($row.length) {
				var $filled = $row.find('.ems-level-filled');
				var current = parseInt($filled.text(), 10) || 0;
				var updated = Math.max(0, current + delta);
				$filled.text(updated);

				// Enable/disable delete button
				var $deleteBtn = $row.find('.ems-delete-level-btn');
				if (updated > 0) {
					$deleteBtn.prop('disabled', true).attr('title', 'Cannot delete: sponsors linked');
				} else {
					$deleteBtn.prop('disabled', false).removeAttr('title');
				}
			}
		},

		/**
		 * Show a notice within the sponsorship meta box
		 */
		showSponsorshipNotice: function(message, type) {
			type = type || 'info';
			var cssClass = type === 'error' ? 'notice-error' : 'notice-success';

			var $notice = $('<div class="notice ' + cssClass + ' inline ems-sponsorship-notice" style="margin: 10px 0;"><p>' + message + '</p></div>');

			// Remove any previous notices
			$('.ems-sponsorship-notice').remove();

			$('#ems-sponsorship-settings').prepend($notice);

			setTimeout(function() {
				$notice.fadeOut(300, function() {
					$(this).remove();
				});
			}, 4000);
		},

		/**
		 * Get contrasting text colour for a background hex colour
		 */
		getContrastColor: function(hex) {
			hex = hex.replace('#', '');
			if (hex.length === 3) {
				hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
			}
			var r = parseInt(hex.substr(0, 2), 16);
			var g = parseInt(hex.substr(2, 2), 16);
			var b = parseInt(hex.substr(4, 2), 16);
			var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
			return (yiq >= 128) ? '#000000' : '#ffffff';
		},

		/**
		 * Escape HTML entities
		 */
		escHtml: function(str) {
			if (!str) return '';
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		},

		/**
		 * Escape for use in HTML attributes
		 */
		escAttr: function(str) {
			return this.escHtml(str);
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
