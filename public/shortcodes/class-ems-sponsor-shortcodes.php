<?php
/**
 * Sponsor Portal Shortcodes
 *
 * Provides shortcodes for sponsor portal dashboard and file management.
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/public/shortcodes
 * @since      1.4.0
 * @version    1.5.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EMS_Sponsor_Shortcodes
 *
 * Registers and renders shortcodes for:
 * - [ems_sponsor_portal] - Sponsor portal dashboard with tabs
 * - [ems_sponsor_files] - Sponsor file listing
 *
 * @since 1.4.0
 */
class EMS_Sponsor_Shortcodes {

	/**
	 * Logger instance
	 *
	 * @since 1.4.0
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Sponsor portal handler
	 *
	 * @since 1.4.0
	 * @var EMS_Sponsor_Portal
	 */
	private $sponsor_portal;

	/**
	 * Sponsorship levels API
	 *
	 * @since 1.5.0
	 * @var EMS_Sponsorship_Levels
	 */
	private $levels_api;

	/**
	 * Constructor
	 *
	 * Initialize dependencies and register shortcodes.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {
		$this->logger         = EMS_Logger::instance();
		$this->sponsor_portal = new EMS_Sponsor_Portal();
		$this->levels_api     = new EMS_Sponsorship_Levels();

		// Register shortcodes
		add_shortcode( 'ems_sponsor_portal', array( $this, 'sponsor_portal_shortcode' ) );
		add_shortcode( 'ems_sponsor_files', array( $this, 'sponsor_files_shortcode' ) );

		// Handle form submissions via AJAX
		add_action( 'wp_ajax_ems_upload_sponsor_file', array( $this, 'ajax_upload_sponsor_file' ) );
		add_action( 'wp_ajax_ems_delete_sponsor_file', array( $this, 'ajax_delete_sponsor_file' ) );
		add_action( 'wp_ajax_ems_save_sponsor_profile', array( $this, 'ajax_save_sponsor_profile' ) );
		add_action( 'wp_ajax_ems_get_eoi_details', array( $this, 'ajax_get_eoi_details' ) );
		add_action( 'wp_ajax_ems_save_sponsor_page', array( $this, 'ajax_save_sponsor_page' ) );
		add_action( 'wp_ajax_ems_save_sponsor_logo', array( $this, 'ajax_save_sponsor_logo' ) );
	}

	/**
	 * Sponsor portal dashboard shortcode
	 *
	 * Renders the main sponsor portal with tabbed navigation:
	 * - Dashboard (statistics + events with level details)
	 * - Organisation Profile (view/edit)
	 * - Sponsorship Applications (EOI tracking)
	 *
	 * Usage: [ems_sponsor_portal]
	 *
	 * @since 1.4.0
	 * @param array $atts Shortcode attributes.
	 * @return string Portal HTML.
	 */
	public function sponsor_portal_shortcode( $atts ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( add_query_arg( array() ) );
			return '<div class="ems-notice ems-notice-warning">' .
				   sprintf(
					   __( 'Please <a href="%s">log in</a> to access the sponsor portal.', 'event-management-system' ),
					   esc_url( $login_url )
				   ) .
				   '</div>';
		}

		$is_admin = current_user_can( 'manage_options' );

		// Check if user has sponsor capability (admins always allowed).
		if ( ! $is_admin && ! current_user_can( 'access_ems_sponsor_portal' ) ) {
			return '<div class="ems-notice ems-notice-error">' .
				   __( 'You do not have access to the sponsor portal.', 'event-management-system' ) .
				   '</div>';
		}

		// Admin sponsor picker: accept ?sponsor_id= query param.
		$sponsor_id = 0;
		if ( $is_admin ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
			$requested = isset( $_GET['sponsor_id'] ) ? absint( $_GET['sponsor_id'] ) : 0;
			if ( $requested ) {
				$requested_post = get_post( $requested );
				if ( $requested_post && 'ems_sponsor' === $requested_post->post_type ) {
					$sponsor_id = $requested;
				}
			}

			if ( ! $sponsor_id ) {
				// Show sponsor picker for admins.
				return $this->render_admin_sponsor_picker();
			}
		} else {
			$sponsor_id = $this->sponsor_portal->get_user_sponsor_id( get_current_user_id() );
		}

		if ( ! $sponsor_id ) {
			return '<div class="ems-notice ems-notice-error">' .
				   __( 'No sponsor profile associated with your account. Please contact the administrator.', 'event-management-system' ) .
				   '</div>';
		}

		// Get sponsor data
		$sponsor    = get_post( $sponsor_id );
		$events     = $this->sponsor_portal->get_sponsor_events( $sponsor_id );
		$statistics = $this->sponsor_portal->get_sponsor_statistics( $sponsor_id );
		$meta       = EMS_Sponsor_Meta::get_sponsor_meta( $sponsor_id );
		$eois       = $this->sponsor_portal->get_sponsor_eois( $sponsor_id );

		// Determine active tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only
		$active_tab = isset( $_GET['portal_tab'] ) ? sanitize_key( $_GET['portal_tab'] ) : 'dashboard';
		$valid_tabs = array( 'dashboard', 'profile', 'page', 'applications' );
		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'dashboard';
		}

		ob_start();
		?>

		<div class="ems-sponsor-portal">
			<!-- Portal Header -->
			<div class="ems-sponsor-header">
				<?php if ( $is_admin ) : ?>
					<p style="margin-bottom: 8px;">
						<a href="<?php echo esc_url( remove_query_arg( 'sponsor_id' ) ); ?>" style="text-decoration: none; font-size: 13px;">&larr; <?php esc_html_e( 'All Sponsors', 'event-management-system' ); ?></a>
						&nbsp;|&nbsp;
						<a href="<?php echo esc_url( get_edit_post_link( $sponsor_id ) ); ?>" style="text-decoration: none; font-size: 13px;"><?php esc_html_e( 'Edit in Admin', 'event-management-system' ); ?></a>
					</p>
				<?php endif; ?>
				<h1><?php echo esc_html( sprintf( __( 'Welcome, %s', 'event-management-system' ), $sponsor->post_title ) ); ?></h1>
				<p class="ems-sponsor-description"><?php esc_html_e( 'Manage your event sponsorships, organisation profile, and applications.', 'event-management-system' ); ?></p>
			</div>

			<!-- Portal Tab Navigation -->
			<div class="ems-portal-tabs" role="tablist">
				<button type="button" class="ems-portal-tab <?php echo 'dashboard' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'dashboard' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-dashboard" data-tab="dashboard">
					<?php esc_html_e( 'Dashboard', 'event-management-system' ); ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'profile' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'profile' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-profile" data-tab="profile">
					<?php esc_html_e( 'Organisation Profile', 'event-management-system' ); ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'page' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'page' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-page" data-tab="page">
					<?php esc_html_e( 'My Sponsor Page', 'event-management-system' ); ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'applications' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'applications' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-applications" data-tab="applications">
					<?php esc_html_e( 'Sponsorship Applications', 'event-management-system' ); ?>
					<?php if ( ! empty( $eois ) ) : ?>
						<span class="ems-portal-tab__count"><?php echo esc_html( count( $eois ) ); ?></span>
					<?php endif; ?>
				</button>
			</div>

			<!-- Tab: Dashboard -->
			<div class="ems-portal-tab-content <?php echo 'dashboard' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-dashboard" role="tabpanel">
				<?php $this->render_dashboard_tab( $sponsor_id, $sponsor, $events, $statistics ); ?>
			</div>

			<!-- Tab: Organisation Profile -->
			<div class="ems-portal-tab-content <?php echo 'profile' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-profile" role="tabpanel">
				<?php $this->render_profile_tab( $sponsor_id, $meta ); ?>
			</div>

			<!-- Tab: My Sponsor Page -->
			<div class="ems-portal-tab-content <?php echo 'page' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-page" role="tabpanel">
				<?php $this->render_page_tab( $sponsor_id, $sponsor ); ?>
			</div>

			<!-- Tab: Sponsorship Applications -->
			<div class="ems-portal-tab-content <?php echo 'applications' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-applications" role="tabpanel">
				<?php $this->render_applications_tab( $sponsor_id, $eois ); ?>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Tab navigation
			$('.ems-portal-tab').on('click', function() {
				var tabId = $(this).data('tab');

				// Update active tab button
				$('.ems-portal-tab').removeClass('ems-portal-tab--active').attr('aria-selected', 'false');
				$(this).addClass('ems-portal-tab--active').attr('aria-selected', 'true');

				// Update active tab content
				$('.ems-portal-tab-content').removeClass('ems-portal-tab-content--active');
				$('#ems-tab-' + tabId).addClass('ems-portal-tab-content--active');

				// Update URL without reload
				if (window.history && window.history.replaceState) {
					var url = new URL(window.location);
					url.searchParams.set('portal_tab', tabId);
					window.history.replaceState({}, '', url);
				}
			});

			// Toggle files section
			$('.ems-toggle-files').on('click', function() {
				var eventId = $(this).data('event-id');
				$('#ems-files-' + eventId).slideToggle();
			});

			// Handle file upload via AJAX
			$('.ems-sponsor-file-upload-form').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var eventId = $form.data('event-id');
				var sponsorId = $form.data('sponsor-id');
				var $status = $form.find('.ems-upload-status');
				var formData = new FormData();

				var fileInput = $form.find('input[type="file"]')[0];
				if (!fileInput.files[0]) {
					$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'Please select a file.', 'event-management-system' ) ); ?></div>');
					return;
				}

				formData.append('action', 'ems_upload_sponsor_file');
				formData.append('nonce', ems_public.nonce);
				formData.append('sponsor_id', sponsorId);
				formData.append('event_id', eventId);
				formData.append('sponsor_file', fileInput.files[0]);
				formData.append('file_description', $form.find('textarea[name="file_description"]').val());

				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Uploading file...', 'event-management-system' ) ); ?></div>');
				$form.find('button[type="submit"]').prop('disabled', true);

				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							$status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
							setTimeout(function() { location.reload(); }, 2000);
						} else {
							$status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
							$form.find('button[type="submit"]').prop('disabled', false);
						}
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred. Please try again.', 'event-management-system' ) ); ?></div>');
						$form.find('button[type="submit"]').prop('disabled', false);
					}
				});
			});

			// Handle file deletion
			$('.ems-delete-file').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this file?', 'event-management-system' ) ); ?>')) {
					return;
				}

				var fileId = $(this).data('file-id');
				var $row = $(this).closest('tr');

				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_delete_sponsor_file',
						nonce: ems_public.nonce,
						file_id: fileId
					},
					success: function(response) {
						if (response.success) {
							$row.fadeOut(function() { $row.remove(); });
						} else {
							alert(response.data);
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'event-management-system' ) ); ?>');
					}
				});
			});

			// Handle profile save via AJAX
			$('#ems-sponsor-profile-form').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var $status = $form.find('.ems-profile-save-status');
				var $btn = $form.find('button[type="submit"]');

				$btn.prop('disabled', true).addClass('loading');
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Saving profile...', 'event-management-system' ) ); ?></div>');

				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: $form.serialize(),
					success: function(response) {
						if (response.success) {
							$status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
						} else {
							$status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
						}
						$btn.prop('disabled', false).removeClass('loading');
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred. Please try again.', 'event-management-system' ) ); ?></div>');
						$btn.prop('disabled', false).removeClass('loading');
					}
				});
			});

			// Toggle profile edit mode
			$('.ems-profile-edit-toggle').on('click', function() {
				$('.ems-profile-view').toggleClass('ems-hidden');
				$('.ems-profile-edit').toggleClass('ems-hidden');
			});

			// EOI details expand/collapse
			$('.ems-eoi-view-details').on('click', function() {
				var eoiId = $(this).data('eoi-id');
				var $details = $('#ems-eoi-details-' + eoiId);

				if ($details.is(':visible')) {
					$details.slideUp();
					$(this).text('<?php echo esc_js( __( 'View Details', 'event-management-system' ) ); ?>');
					return;
				}

				// Load details via AJAX if not already loaded
				if ($details.find('.ems-eoi-detail-content').length === 0) {
					$details.html('<div class="ems-loading"><div class="ems-spinner"></div></div>');
					$details.slideDown();

					$.ajax({
						url: ems_public.ajax_url,
						type: 'POST',
						data: {
							action: 'ems_get_eoi_details',
							nonce: ems_public.nonce,
							eoi_id: eoiId
						},
						success: function(response) {
							if (response.success) {
								$details.html(response.data.html);
							} else {
								$details.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
							}
						},
						error: function() {
							$details.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'Failed to load details.', 'event-management-system' ) ); ?></div>');
						}
					});
				} else {
					$details.slideDown();
				}

				$(this).text('<?php echo esc_js( __( 'Hide Details', 'event-management-system' ) ); ?>');
			});
		});
		</script>

		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Tab Renderers
	// =========================================================================

	/**
	 * Render the Dashboard tab content
	 *
	 * Shows statistics and event cards with sponsorship level details.
	 *
	 * @since 1.5.0
	 * @param int    $sponsor_id Sponsor post ID.
	 * @param WP_Post $sponsor  Sponsor post object.
	 * @param array  $events    Array of event data.
	 * @param array  $statistics Sponsor statistics.
	 * @return void
	 */
	/**
	 * Render admin sponsor picker when no sponsor_id is selected.
	 *
	 * @since 1.6.0
	 * @return string HTML for the sponsor picker.
	 */
	private function render_admin_sponsor_picker() {
		$sponsors = get_posts( array(
			'post_type'      => 'ems_sponsor',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$current_url = remove_query_arg( 'sponsor_id' );

		ob_start();
		?>
		<div class="ems-sponsor-portal">
			<div class="ems-sponsor-header">
				<h1><?php esc_html_e( 'Sponsor Portal — Admin View', 'event-management-system' ); ?></h1>
				<p class="ems-sponsor-description"><?php esc_html_e( 'Select a sponsor to view their dashboard.', 'event-management-system' ); ?></p>
			</div>

			<?php if ( empty( $sponsors ) ) : ?>
				<div class="ems-notice ems-notice-warning">
					<?php esc_html_e( 'No sponsors have been created yet.', 'event-management-system' ); ?>
				</div>
			<?php else : ?>
				<div class="ems-admin-sponsor-picker" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 20px;">
					<?php foreach ( $sponsors as $sp ) :
						$linked_user_id = get_post_meta( $sp->ID, '_ems_sponsor_user_id', true );
						$linked_user    = $linked_user_id ? get_user_by( 'ID', $linked_user_id ) : null;
						$linked_events  = get_post_meta( $sp->ID, '_ems_linked_events', true );
						$event_count    = is_array( $linked_events ) ? count( $linked_events ) : 0;
						$view_url       = add_query_arg( 'sponsor_id', $sp->ID, $current_url );
						?>
						<a href="<?php echo esc_url( $view_url ); ?>" class="ems-sponsor-picker-card" style="
							display: block;
							padding: 16px;
							border: 1px solid #c3c4c7;
							border-radius: 6px;
							text-decoration: none;
							color: inherit;
							background: #fff;
							transition: border-color 0.2s, box-shadow 0.2s;
						" onmouseover="this.style.borderColor='#2271b1';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'" onmouseout="this.style.borderColor='#c3c4c7';this.style.boxShadow='none'">
							<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
								<?php if ( has_post_thumbnail( $sp->ID ) ) : ?>
									<div style="width: 48px; height: 48px; flex-shrink: 0;">
										<?php echo get_the_post_thumbnail( $sp->ID, 'thumbnail', array(
											'style' => 'width: 48px; height: 48px; object-fit: contain; border-radius: 4px;',
										) ); ?>
									</div>
								<?php endif; ?>
								<div>
									<strong style="font-size: 15px;"><?php echo esc_html( $sp->post_title ); ?></strong>
									<span style="display: block; font-size: 12px; color: #646970; text-transform: capitalize;">
										<?php echo esc_html( $sp->post_status ); ?>
									</span>
								</div>
							</div>
							<div style="font-size: 12px; color: #646970;">
								<?php
								$details = array();
								$details[] = sprintf(
									/* translators: %d: number of events */
									_n( '%d event', '%d events', $event_count, 'event-management-system' ),
									$event_count
								);
								if ( $linked_user ) {
									$details[] = sprintf(
										/* translators: %s: user email */
										__( 'User: %s', 'event-management-system' ),
										$linked_user->user_email
									);
								} else {
									$details[] = '<span style="color: #d63638;">' . esc_html__( 'No linked user', 'event-management-system' ) . '</span>';
								}
								echo implode( ' &middot; ', $details );
								?>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_dashboard_tab( $sponsor_id, $sponsor, $events, $statistics ) {
		?>
		<!-- Statistics Dashboard -->
		<div class="ems-sponsor-statistics">
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['event_count'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Linked Events', 'event-management-system' ); ?></div>
			</div>
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['file_count'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Files Uploaded', 'event-management-system' ); ?></div>
			</div>
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['total_downloads'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Total Downloads', 'event-management-system' ); ?></div>
			</div>
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( size_format( $statistics['total_size'] ) ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Storage Used', 'event-management-system' ); ?></div>
			</div>
		</div>

		<!-- Events List -->
		<div class="ems-sponsor-events-section">
			<h2><?php esc_html_e( 'Your Sponsored Events', 'event-management-system' ); ?></h2>

			<?php if ( empty( $events ) ) : ?>
				<p class="ems-no-results"><?php esc_html_e( 'You are not currently linked to any events.', 'event-management-system' ); ?></p>
			<?php else : ?>
				<div class="ems-sponsor-events-list">
					<?php foreach ( $events as $event ) : ?>
						<?php
						// Task 8.3: Get level details for this sponsor's level on this event
						$level_info = $this->get_event_level_info( $event['event_id'], $event['sponsor_level'] );
						?>
						<div class="ems-sponsor-event-card" data-event-id="<?php echo esc_attr( $event['event_id'] ); ?>">
							<div class="ems-event-header">
								<h3><?php echo esc_html( $event['event_title'] ); ?></h3>
								<?php if ( $level_info ) : ?>
									<span class="ems-sponsor-level-pill" style="background-color: <?php echo esc_attr( $level_info['colour'] ); ?>; color: <?php echo esc_attr( $this->get_contrast_colour( $level_info['colour'] ) ); ?>;">
										<?php echo esc_html( $level_info['name'] ); ?>
									</span>
								<?php elseif ( $event['sponsor_level'] ) : ?>
									<span class="ems-sponsor-level-badge"><?php echo esc_html( $event['sponsor_level'] ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( $event['event_date'] ) : ?>
								<p class="ems-event-date">
									<strong><?php esc_html_e( 'Date:', 'event-management-system' ); ?></strong>
									<?php echo esc_html( EMS_Date_Helper::format( $event['event_date'] ) ); ?>
								</p>
							<?php endif; ?>

							<?php // Task 8.3: Show level value and recognition text ?>
							<?php if ( $level_info ) : ?>
								<div class="ems-event-level-details">
									<?php if ( ! empty( $level_info['value_aud'] ) ) : ?>
										<p class="ems-event-level-value">
											<strong><?php esc_html_e( 'Sponsorship Value:', 'event-management-system' ); ?></strong>
											<?php echo esc_html( '$' . number_format( (float) $level_info['value_aud'], 2 ) . ' AUD' ); ?>
										</p>
									<?php endif; ?>
									<?php if ( ! empty( $level_info['recognition_text'] ) ) : ?>
										<div class="ems-event-level-recognition">
											<strong><?php esc_html_e( 'Recognition:', 'event-management-system' ); ?></strong>
											<p><?php echo esc_html( $level_info['recognition_text'] ); ?></p>
										</div>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<div class="ems-event-actions">
								<button type="button" class="ems-button ems-toggle-files" data-event-id="<?php echo esc_attr( $event['event_id'] ); ?>">
									<?php esc_html_e( 'Manage Files', 'event-management-system' ); ?>
								</button>
							</div>

							<!-- Files Section (Initially Hidden) -->
							<div class="ems-event-files" id="ems-files-<?php echo esc_attr( $event['event_id'] ); ?>" style="display: none;">
								<h4><?php esc_html_e( 'Files for this Event', 'event-management-system' ); ?></h4>

								<!-- Upload Form -->
								<div class="ems-file-upload-section">
									<h5><?php esc_html_e( 'Upload New File', 'event-management-system' ); ?></h5>
									<form class="ems-sponsor-file-upload-form" data-event-id="<?php echo esc_attr( $event['event_id'] ); ?>" data-sponsor-id="<?php echo esc_attr( $sponsor_id ); ?>">
										<div class="ems-form-group">
											<label for="sponsor-file-<?php echo esc_attr( $event['event_id'] ); ?>"><?php esc_html_e( 'Choose File:', 'event-management-system' ); ?></label>
											<input type="file" id="sponsor-file-<?php echo esc_attr( $event['event_id'] ); ?>" name="sponsor_file" required />
											<p class="ems-field-help"><?php esc_html_e( 'Allowed types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, PNG, GIF, ZIP, MP4, MOV (Max: 25MB)', 'event-management-system' ); ?></p>
										</div>
										<div class="ems-form-group">
											<label for="file-description-<?php echo esc_attr( $event['event_id'] ); ?>"><?php esc_html_e( 'Description:', 'event-management-system' ); ?></label>
											<textarea id="file-description-<?php echo esc_attr( $event['event_id'] ); ?>" name="file_description" rows="3"></textarea>
										</div>
										<div class="ems-form-group">
											<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Upload File', 'event-management-system' ); ?></button>
										</div>
										<div class="ems-upload-status"></div>
									</form>
								</div>

								<!-- Files List -->
								<div class="ems-files-list">
									<?php
									$files = $this->sponsor_portal->get_sponsor_files( $sponsor_id, $event['event_id'] );
									if ( empty( $files ) ) :
									?>
										<p class="ems-no-files"><?php esc_html_e( 'No files uploaded yet.', 'event-management-system' ); ?></p>
									<?php else : ?>
										<table class="ems-files-table">
											<thead>
												<tr>
													<th><?php esc_html_e( 'File Name', 'event-management-system' ); ?></th>
													<th><?php esc_html_e( 'Size', 'event-management-system' ); ?></th>
													<th><?php esc_html_e( 'Uploaded', 'event-management-system' ); ?></th>
													<th><?php esc_html_e( 'Downloads', 'event-management-system' ); ?></th>
													<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $files as $file ) : ?>
													<tr data-file-id="<?php echo esc_attr( $file->id ); ?>">
														<td>
															<strong><?php echo esc_html( $file->file_name ); ?></strong>
															<?php if ( $file->description ) : ?>
																<br /><small><?php echo esc_html( $file->description ); ?></small>
															<?php endif; ?>
														</td>
														<td><?php echo esc_html( size_format( $file->file_size ) ); ?></td>
														<td><?php echo esc_html( EMS_Date_Helper::format( $file->upload_date ) ); ?></td>
														<td><?php echo esc_html( $file->downloads ); ?></td>
														<td>
															<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'ems_download_sponsor_file' => $file->id ), home_url() ), 'ems_download_file_' . $file->id ) ); ?>" class="ems-button ems-button-small" target="_blank">
																<?php esc_html_e( 'Download', 'event-management-system' ); ?>
															</a>
															<button type="button" class="ems-button ems-button-small ems-button-danger ems-delete-file" data-file-id="<?php echo esc_attr( $file->id ); ?>">
																<?php esc_html_e( 'Delete', 'event-management-system' ); ?>
															</button>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the Organisation Profile tab
	 *
	 * Shows sponsor organisation details with inline edit capability.
	 *
	 * @since 1.5.0
	 * @param int   $sponsor_id Sponsor post ID.
	 * @param array $meta       Sponsor meta data.
	 * @return void
	 */
	private function render_profile_tab( $sponsor_id, $meta ) {
		$industry_sectors = is_array( $meta['industry_sectors'] ) ? $meta['industry_sectors'] : array();
		?>
		<div class="ems-sponsor-profile-section">
			<div class="ems-profile-header">
				<h2><?php esc_html_e( 'Organisation Profile', 'event-management-system' ); ?></h2>
				<button type="button" class="ems-button ems-button-small ems-profile-edit-toggle">
					<?php esc_html_e( 'Edit Profile', 'event-management-system' ); ?>
				</button>
			</div>

			<!-- View Mode -->
			<div class="ems-profile-view">
				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Organisation Details', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php $this->render_profile_field( __( 'Legal Name', 'event-management-system' ), $meta['legal_name'] ); ?>
						<?php $this->render_profile_field( __( 'Trading Name', 'event-management-system' ), $meta['trading_name'] ); ?>
						<?php $this->render_profile_field( __( 'ABN', 'event-management-system' ), $meta['abn'] ); ?>
						<?php $this->render_profile_field( __( 'ACN', 'event-management-system' ), $meta['acn'] ); ?>
						<?php $this->render_profile_field( __( 'Registered Address', 'event-management-system' ), $meta['registered_address'] ); ?>
						<?php $this->render_profile_field( __( 'Website', 'event-management-system' ), $meta['website_url'], 'url' ); ?>
						<?php $this->render_profile_field( __( 'Country', 'event-management-system' ), $meta['country'] ); ?>
						<?php $this->render_profile_field( __( 'Parent Company', 'event-management-system' ), $meta['parent_company'] ); ?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Primary Contact', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php $this->render_profile_field( __( 'Contact Name', 'event-management-system' ), $meta['contact_name'] ); ?>
						<?php $this->render_profile_field( __( 'Contact Role', 'event-management-system' ), $meta['contact_role'] ); ?>
						<?php $this->render_profile_field( __( 'Contact Email', 'event-management-system' ), $meta['contact_email'], 'email' ); ?>
						<?php $this->render_profile_field( __( 'Contact Phone', 'event-management-system' ), $meta['contact_phone'] ); ?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Signatory', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php $this->render_profile_field( __( 'Signatory Name', 'event-management-system' ), $meta['signatory_name'] ); ?>
						<?php $this->render_profile_field( __( 'Signatory Title', 'event-management-system' ), $meta['signatory_title'] ); ?>
						<?php $this->render_profile_field( __( 'Signatory Email', 'event-management-system' ), $meta['signatory_email'], 'email' ); ?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Marketing Contact', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php $this->render_profile_field( __( 'Marketing Contact Name', 'event-management-system' ), $meta['marketing_contact_name'] ); ?>
						<?php $this->render_profile_field( __( 'Marketing Contact Email', 'event-management-system' ), $meta['marketing_contact_email'], 'email' ); ?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Industry Classification', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php
						$sector_labels = array();
						foreach ( $industry_sectors as $sector ) {
							if ( isset( EMS_Sponsor_Meta::INDUSTRY_SECTORS[ $sector ] ) ) {
								$sector_labels[] = EMS_Sponsor_Meta::INDUSTRY_SECTORS[ $sector ];
							}
						}
						$this->render_profile_field( __( 'Industry Sectors', 'event-management-system' ), implode( ', ', $sector_labels ) );
						$this->render_profile_field( __( 'Medicines Australia Member', 'event-management-system' ), $meta['ma_member'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'MTAA Member', 'event-management-system' ), $meta['mtaa_member'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'Other Industry Codes', 'event-management-system' ), $meta['other_codes'] );
						$this->render_profile_field( __( 'TGA Number', 'event-management-system' ), $meta['tga_number'] );
						?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Products & Scope', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php
						$this->render_profile_field( __( 'Products/Services', 'event-management-system' ), $meta['products'] );
						$this->render_profile_field( __( 'ARTG Listings', 'event-management-system' ), $meta['artg_listings'] );
						$sched_label = isset( EMS_Sponsor_Meta::SCHEDULING_CLASSES[ $meta['scheduling_class'] ] ) ? EMS_Sponsor_Meta::SCHEDULING_CLASSES[ $meta['scheduling_class'] ] : $meta['scheduling_class'];
						$this->render_profile_field( __( 'Scheduling Class', 'event-management-system' ), $sched_label );
						$device_label = isset( EMS_Sponsor_Meta::DEVICE_CLASSES[ $meta['device_class'] ] ) ? EMS_Sponsor_Meta::DEVICE_CLASSES[ $meta['device_class'] ] : $meta['device_class'];
						$this->render_profile_field( __( 'Device Class', 'event-management-system' ), $device_label );
						$this->render_profile_field( __( 'Non-ARTG Product', 'event-management-system' ), $meta['non_artg_product'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'Off-label Use', 'event-management-system' ), $meta['off_label'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'TGA Safety Alert', 'event-management-system' ), $meta['tga_safety_alert'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'Educational Relationship', 'event-management-system' ), $meta['educational_relation'] );
						?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Legal, Insurance & Compliance', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php
						$this->render_profile_field( __( 'Public Liability Insurance', 'event-management-system' ), $meta['public_liability_confirmed'] ? __( 'Confirmed', 'event-management-system' ) : __( 'Not confirmed', 'event-management-system' ) );
						$this->render_profile_field( __( 'Insurer', 'event-management-system' ), $meta['public_liability_insurer'] );
						$this->render_profile_field( __( 'Policy Number', 'event-management-system' ), $meta['public_liability_policy'] );
						$this->render_profile_field( __( 'Product Liability', 'event-management-system' ), $meta['product_liability_confirmed'] ? __( 'Confirmed', 'event-management-system' ) : __( 'Not confirmed', 'event-management-system' ) );
						$this->render_profile_field( __( 'Professional Indemnity', 'event-management-system' ), $meta['professional_indemnity'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'Workers Compensation', 'event-management-system' ), $meta['workers_comp_confirmed'] ? __( 'Confirmed', 'event-management-system' ) : __( 'Not confirmed', 'event-management-system' ) );
						$this->render_profile_field( __( 'Code Compliance Agreed', 'event-management-system' ), $meta['code_compliance_agreed'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'Adverse Findings', 'event-management-system' ), $meta['adverse_findings'] );
						$this->render_profile_field( __( 'Legal Proceedings', 'event-management-system' ), $meta['legal_proceedings'] );
						$this->render_profile_field( __( 'Transparency Obligations', 'event-management-system' ), $meta['transparency_obligations'] );
						?>
					</div>
				</div>

				<div class="ems-profile-section">
					<h3><?php esc_html_e( 'Conflict of Interest', 'event-management-system' ); ?></h3>
					<div class="ems-profile-grid">
						<?php
						$this->render_profile_field( __( 'QLD Health Policy Aware', 'event-management-system' ), $meta['qld_health_aware'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						$this->render_profile_field( __( 'Personal Relationships', 'event-management-system' ), $meta['personal_relationships'] );
						$this->render_profile_field( __( 'Procurement Conflicts', 'event-management-system' ), $meta['procurement_conflicts'] );
						$this->render_profile_field( __( 'Transfers of Value', 'event-management-system' ), $meta['transfers_of_value'] );
						$this->render_profile_field( __( 'Preferential Treatment Agreed', 'event-management-system' ), $meta['preferential_treatment_agreed'] ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) );
						?>
					</div>
				</div>
			</div>

			<!-- Edit Mode (Initially Hidden) -->
			<div class="ems-profile-edit ems-hidden">
				<form id="ems-sponsor-profile-form" class="ems-form">
					<input type="hidden" name="action" value="ems_save_sponsor_profile" />
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ems_public_nonce' ) ); ?>" />
					<input type="hidden" name="sponsor_id" value="<?php echo esc_attr( $sponsor_id ); ?>" />

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Organisation Details', 'event-management-system' ); ?></h3>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'legal_name', __( 'Legal Name', 'event-management-system' ), $meta['legal_name'], 'text' ); ?>
							<?php $this->render_edit_field( 'trading_name', __( 'Trading Name', 'event-management-system' ), $meta['trading_name'], 'text' ); ?>
						</div>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'abn', __( 'ABN', 'event-management-system' ), $meta['abn'], 'text' ); ?>
							<?php $this->render_edit_field( 'acn', __( 'ACN', 'event-management-system' ), $meta['acn'], 'text' ); ?>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-registered_address"><?php esc_html_e( 'Registered Address', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-registered_address" name="registered_address" rows="3" class="ems-form-control"><?php echo esc_textarea( $meta['registered_address'] ); ?></textarea>
						</div>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'website_url', __( 'Website URL', 'event-management-system' ), $meta['website_url'], 'url' ); ?>
							<?php $this->render_edit_field( 'country', __( 'Country', 'event-management-system' ), $meta['country'], 'text' ); ?>
						</div>
						<?php $this->render_edit_field( 'parent_company', __( 'Parent Company', 'event-management-system' ), $meta['parent_company'], 'text' ); ?>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Primary Contact', 'event-management-system' ); ?></h3>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'contact_name', __( 'Contact Name', 'event-management-system' ), $meta['contact_name'], 'text' ); ?>
							<?php $this->render_edit_field( 'contact_role', __( 'Contact Role', 'event-management-system' ), $meta['contact_role'], 'text' ); ?>
						</div>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'contact_email', __( 'Contact Email', 'event-management-system' ), $meta['contact_email'], 'email' ); ?>
							<?php $this->render_edit_field( 'contact_phone', __( 'Contact Phone', 'event-management-system' ), $meta['contact_phone'], 'tel' ); ?>
						</div>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Signatory', 'event-management-system' ); ?></h3>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'signatory_name', __( 'Signatory Name', 'event-management-system' ), $meta['signatory_name'], 'text' ); ?>
							<?php $this->render_edit_field( 'signatory_title', __( 'Signatory Title', 'event-management-system' ), $meta['signatory_title'], 'text' ); ?>
						</div>
						<?php $this->render_edit_field( 'signatory_email', __( 'Signatory Email', 'event-management-system' ), $meta['signatory_email'], 'email' ); ?>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Marketing Contact', 'event-management-system' ); ?></h3>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'marketing_contact_name', __( 'Marketing Contact Name', 'event-management-system' ), $meta['marketing_contact_name'], 'text' ); ?>
							<?php $this->render_edit_field( 'marketing_contact_email', __( 'Marketing Contact Email', 'event-management-system' ), $meta['marketing_contact_email'], 'email' ); ?>
						</div>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Industry Classification', 'event-management-system' ); ?></h3>
						<div class="ems-form-group">
							<label><?php esc_html_e( 'Industry Sectors', 'event-management-system' ); ?></label>
							<div class="ems-checkbox-group">
								<?php foreach ( EMS_Sponsor_Meta::INDUSTRY_SECTORS as $key => $label ) : ?>
									<label class="ems-checkbox-label">
										<input type="checkbox" name="industry_sectors[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $industry_sectors, true ) ); ?> />
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ems-form-row">
							<div class="ems-form-group">
								<label class="ems-checkbox-label">
									<input type="checkbox" name="ma_member" value="1" <?php checked( $meta['ma_member'] ); ?> />
									<?php esc_html_e( 'Medicines Australia Member', 'event-management-system' ); ?>
								</label>
							</div>
							<div class="ems-form-group">
								<label class="ems-checkbox-label">
									<input type="checkbox" name="mtaa_member" value="1" <?php checked( $meta['mtaa_member'] ); ?> />
									<?php esc_html_e( 'MTAA Member', 'event-management-system' ); ?>
								</label>
							</div>
						</div>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'other_codes', __( 'Other Industry Codes', 'event-management-system' ), $meta['other_codes'], 'text' ); ?>
							<?php $this->render_edit_field( 'tga_number', __( 'TGA Number', 'event-management-system' ), $meta['tga_number'], 'text' ); ?>
						</div>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Products & Scope', 'event-management-system' ); ?></h3>
						<div class="ems-form-group">
							<label for="ems-profile-products"><?php esc_html_e( 'Products/Services', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-products" name="products" rows="3" class="ems-form-control"><?php echo esc_textarea( $meta['products'] ); ?></textarea>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-artg_listings"><?php esc_html_e( 'ARTG Listings', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-artg_listings" name="artg_listings" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['artg_listings'] ); ?></textarea>
						</div>
						<div class="ems-form-row">
							<div class="ems-form-group">
								<label for="ems-profile-scheduling_class"><?php esc_html_e( 'Scheduling Class', 'event-management-system' ); ?></label>
								<select id="ems-profile-scheduling_class" name="scheduling_class" class="ems-form-control">
									<option value=""><?php esc_html_e( '-- Select --', 'event-management-system' ); ?></option>
									<?php foreach ( EMS_Sponsor_Meta::SCHEDULING_CLASSES as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta['scheduling_class'], $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="ems-form-group">
								<label for="ems-profile-device_class"><?php esc_html_e( 'Device Class', 'event-management-system' ); ?></label>
								<select id="ems-profile-device_class" name="device_class" class="ems-form-control">
									<option value=""><?php esc_html_e( '-- Select --', 'event-management-system' ); ?></option>
									<?php foreach ( EMS_Sponsor_Meta::DEVICE_CLASSES as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta['device_class'], $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="ems-form-group">
							<label class="ems-checkbox-label">
								<input type="checkbox" name="non_artg_product" value="1" <?php checked( $meta['non_artg_product'] ); ?> />
								<?php esc_html_e( 'Non-ARTG product', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="off_label" value="1" <?php checked( $meta['off_label'] ); ?> />
								<?php esc_html_e( 'Off-label use', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="tga_safety_alert" value="1" <?php checked( $meta['tga_safety_alert'] ); ?> />
								<?php esc_html_e( 'TGA safety alert', 'event-management-system' ); ?>
							</label>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-educational_relation"><?php esc_html_e( 'Educational Relationship', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-educational_relation" name="educational_relation" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['educational_relation'] ); ?></textarea>
						</div>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Legal, Insurance & Compliance', 'event-management-system' ); ?></h3>
						<div class="ems-form-group">
							<label class="ems-checkbox-label">
								<input type="checkbox" name="public_liability_confirmed" value="1" <?php checked( $meta['public_liability_confirmed'] ); ?> />
								<?php esc_html_e( 'Public liability insurance confirmed', 'event-management-system' ); ?>
							</label>
						</div>
						<div class="ems-form-row">
							<?php $this->render_edit_field( 'public_liability_insurer', __( 'Insurer', 'event-management-system' ), $meta['public_liability_insurer'], 'text' ); ?>
							<?php $this->render_edit_field( 'public_liability_policy', __( 'Policy Number', 'event-management-system' ), $meta['public_liability_policy'], 'text' ); ?>
						</div>
						<div class="ems-form-group">
							<label class="ems-checkbox-label">
								<input type="checkbox" name="product_liability_confirmed" value="1" <?php checked( $meta['product_liability_confirmed'] ); ?> />
								<?php esc_html_e( 'Product liability confirmed', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="professional_indemnity" value="1" <?php checked( $meta['professional_indemnity'] ); ?> />
								<?php esc_html_e( 'Professional indemnity insurance', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="workers_comp_confirmed" value="1" <?php checked( $meta['workers_comp_confirmed'] ); ?> />
								<?php esc_html_e( 'Workers compensation confirmed', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="code_compliance_agreed" value="1" <?php checked( $meta['code_compliance_agreed'] ); ?> />
								<?php esc_html_e( 'Code compliance agreed', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="compliance_process" value="1" <?php checked( $meta['compliance_process'] ); ?> />
								<?php esc_html_e( 'Documented compliance process', 'event-management-system' ); ?>
							</label>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-adverse_findings"><?php esc_html_e( 'Adverse Findings', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-adverse_findings" name="adverse_findings" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['adverse_findings'] ); ?></textarea>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-legal_proceedings"><?php esc_html_e( 'Legal Proceedings', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-legal_proceedings" name="legal_proceedings" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['legal_proceedings'] ); ?></textarea>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-transparency_obligations"><?php esc_html_e( 'Transparency Obligations', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-transparency_obligations" name="transparency_obligations" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['transparency_obligations'] ); ?></textarea>
						</div>
					</div>

					<div class="ems-profile-section">
						<h3><?php esc_html_e( 'Conflict of Interest', 'event-management-system' ); ?></h3>
						<div class="ems-form-group">
							<label class="ems-checkbox-label">
								<input type="checkbox" name="qld_health_aware" value="1" <?php checked( $meta['qld_health_aware'] ); ?> />
								<?php esc_html_e( 'Aware of QLD Health gifting policy', 'event-management-system' ); ?>
							</label>
							<label class="ems-checkbox-label">
								<input type="checkbox" name="preferential_treatment_agreed" value="1" <?php checked( $meta['preferential_treatment_agreed'] ); ?> />
								<?php esc_html_e( 'Preferential treatment agreement', 'event-management-system' ); ?>
							</label>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-personal_relationships"><?php esc_html_e( 'Personal Relationships', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-personal_relationships" name="personal_relationships" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['personal_relationships'] ); ?></textarea>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-procurement_conflicts"><?php esc_html_e( 'Procurement Conflicts', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-procurement_conflicts" name="procurement_conflicts" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['procurement_conflicts'] ); ?></textarea>
						</div>
						<div class="ems-form-group">
							<label for="ems-profile-transfers_of_value"><?php esc_html_e( 'Transfers of Value', 'event-management-system' ); ?></label>
							<textarea id="ems-profile-transfers_of_value" name="transfers_of_value" rows="2" class="ems-form-control"><?php echo esc_textarea( $meta['transfers_of_value'] ); ?></textarea>
						</div>
					</div>

					<div class="ems-profile-actions">
						<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Save Changes', 'event-management-system' ); ?></button>
						<button type="button" class="ems-button ems-profile-edit-toggle"><?php esc_html_e( 'Cancel', 'event-management-system' ); ?></button>
					</div>
					<div class="ems-profile-save-status"></div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Sponsorship Applications (EOI) tab
	 *
	 * Shows a table of all EOI submissions with status badges and expandable details.
	 *
	 * @since 1.5.0
	 * @param int   $sponsor_id Sponsor post ID.
	 * @param array $eois       Array of EOI row objects.
	 * @return void
	 */
	private function render_applications_tab( $sponsor_id, $eois ) {
		$eoi_page_id  = get_option( 'ems_page_sponsor_eoi' );
		$eoi_base_url = $eoi_page_id ? get_permalink( $eoi_page_id ) : '';
		?>
		<div class="ems-sponsor-applications-section">
			<div class="ems-applications-header">
				<h2><?php esc_html_e( 'Sponsorship Applications', 'event-management-system' ); ?></h2>
				<?php if ( $eoi_base_url ) : ?>
					<?php
					// Get events with sponsorship enabled that this sponsor hasn't already applied to.
					$existing_event_ids = array_map( function( $eoi ) { return absint( $eoi->event_id ); }, $eois );
					$available_events = get_posts( array(
						'post_type'      => 'ems_event',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'meta_query'     => array(
							array(
								'key'   => '_ems_sponsorship_enabled',
								'value' => '1',
							),
						),
					) );
					$available_events = array_filter( $available_events, function( $event ) use ( $existing_event_ids ) {
						return ! in_array( $event->ID, $existing_event_ids, true );
					} );
					?>
					<?php if ( ! empty( $available_events ) ) : ?>
						<div class="ems-new-eoi-dropdown">
							<label for="ems-eoi-event-select"><?php esc_html_e( 'New Application:', 'event-management-system' ); ?></label>
							<select id="ems-eoi-event-select">
								<option value=""><?php esc_html_e( '-- Select an event --', 'event-management-system' ); ?></option>
								<?php foreach ( $available_events as $event ) : ?>
									<option value="<?php echo esc_attr( add_query_arg( 'event_id', $event->ID, $eoi_base_url ) ); ?>">
										<?php echo esc_html( $event->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="ems-button ems-button-primary ems-button-small" id="ems-eoi-go" disabled>
								<?php esc_html_e( 'Express Interest', 'event-management-system' ); ?>
							</button>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php if ( empty( $eois ) ) : ?>
				<p class="ems-no-results"><?php esc_html_e( 'You have not submitted any sponsorship applications yet.', 'event-management-system' ); ?></p>
			<?php else : ?>
				<div class="ems-eoi-table-wrapper">
					<table class="ems-table ems-eoi-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Submitted', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Status', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Preferred Level', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $eois as $eoi ) : ?>
								<?php
								$event       = get_post( $eoi->event_id );
								$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );
								$status_data = $this->get_eoi_status_data( $eoi->status );
								?>
								<tr>
									<td data-label="<?php esc_attr_e( 'Event', 'event-management-system' ); ?>">
										<strong><?php echo esc_html( $event_title ); ?></strong>
									</td>
									<td data-label="<?php esc_attr_e( 'Submitted', 'event-management-system' ); ?>">
										<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $eoi->submitted_at ) ) ); ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Status', 'event-management-system' ); ?>">
										<span class="ems-badge <?php echo esc_attr( $status_data['class'] ); ?>">
											<?php echo esc_html( $status_data['label'] ); ?>
										</span>
									</td>
									<td data-label="<?php esc_attr_e( 'Level', 'event-management-system' ); ?>">
										<?php echo esc_html( ucfirst( str_replace( array( '-', '_' ), ' ', $eoi->preferred_level ) ) ); ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Actions', 'event-management-system' ); ?>">
										<button type="button" class="ems-button ems-button-small ems-eoi-view-details" data-eoi-id="<?php echo esc_attr( $eoi->id ); ?>">
											<?php esc_html_e( 'View Details', 'event-management-system' ); ?>
										</button>
									</td>
								</tr>
								<tr class="ems-eoi-details-row">
									<td colspan="5">
										<div class="ems-eoi-details" id="ems-eoi-details-<?php echo esc_attr( $eoi->id ); ?>" style="display: none;"></div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Tab: My Sponsor Page
	// =========================================================================

	/**
	 * Render the My Sponsor Page tab
	 *
	 * Provides a WYSIWYG editor for the sponsor's public page content
	 * and logo/featured image management via the WordPress media uploader.
	 *
	 * @since 1.6.0
	 * @param int      $sponsor_id Sponsor post ID.
	 * @param WP_Post  $sponsor    Sponsor post object.
	 * @return void
	 */
	private function render_page_tab( $sponsor_id, $sponsor ) {
		// Enqueue WordPress media uploader scripts.
		wp_enqueue_media();

		$page_url      = get_permalink( $sponsor_id );
		$thumbnail_id  = get_post_thumbnail_id( $sponsor_id );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';
		?>
		<div class="ems-sponsor-page-section">
			<div class="ems-page-header">
				<h2><?php esc_html_e( 'My Sponsor Page', 'event-management-system' ); ?></h2>
				<?php if ( 'publish' === $sponsor->post_status ) : ?>
					<a href="<?php echo esc_url( $page_url ); ?>" class="ems-button ems-button-small" target="_blank">
						<?php esc_html_e( 'View Public Page', 'event-management-system' ); ?>
					</a>
				<?php else : ?>
					<span class="ems-badge ems-badge-warning"><?php esc_html_e( 'Pending Approval', 'event-management-system' ); ?></span>
				<?php endif; ?>
			</div>

			<p class="ems-page-description">
				<?php esc_html_e( 'Edit the content and logo displayed on your public sponsor page. Visitors will see your logo, description, website, and a list of events you sponsor.', 'event-management-system' ); ?>
			</p>

			<!-- Logo / Featured Image -->
			<div class="ems-page-logo-section">
				<h3><?php esc_html_e( 'Sponsor Logo', 'event-management-system' ); ?></h3>
				<div class="ems-logo-preview" id="ems-logo-preview">
					<?php if ( $thumbnail_url ) : ?>
						<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $sponsor->post_title ); ?>" />
					<?php else : ?>
						<span class="ems-logo-placeholder"><?php esc_html_e( 'No logo uploaded', 'event-management-system' ); ?></span>
					<?php endif; ?>
				</div>
				<div class="ems-logo-actions">
					<button type="button" class="ems-button ems-button-small" id="ems-upload-logo">
						<?php echo $thumbnail_url ? esc_html__( 'Change Logo', 'event-management-system' ) : esc_html__( 'Upload Logo', 'event-management-system' ); ?>
					</button>
					<?php if ( $thumbnail_url ) : ?>
						<button type="button" class="ems-button ems-button-small ems-button-danger" id="ems-remove-logo">
							<?php esc_html_e( 'Remove Logo', 'event-management-system' ); ?>
						</button>
					<?php endif; ?>
				</div>
				<input type="hidden" id="ems-logo-attachment-id" value="<?php echo esc_attr( $thumbnail_id ); ?>" />
				<div class="ems-logo-save-status"></div>
			</div>

			<!-- Page Content WYSIWYG -->
			<div class="ems-page-content-section">
				<h3><?php esc_html_e( 'Page Description', 'event-management-system' ); ?></h3>
				<form id="ems-sponsor-page-form" class="ems-form">
					<input type="hidden" name="action" value="ems_save_sponsor_page" />
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ems_public_nonce' ) ); ?>" />
					<input type="hidden" name="sponsor_id" value="<?php echo esc_attr( $sponsor_id ); ?>" />
					<?php
					wp_editor(
						$sponsor->post_content,
						'ems_sponsor_page_content',
						array(
							'textarea_name' => 'page_content',
							'textarea_rows' => 12,
							'media_buttons' => true,
							'teeny'         => false,
							'quicktags'     => true,
							'tinymce'       => array(
								'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,link,unlink,image,removeformat',
								'toolbar2' => '',
							),
						)
					);
					?>
					<div class="ems-page-content-actions">
						<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Save Page Content', 'event-management-system' ); ?></button>
					</div>
					<div class="ems-page-save-status"></div>
				</form>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Logo upload via WP media uploader
			$('#ems-upload-logo').on('click', function(e) {
				e.preventDefault();
				var frame = wp.media({
					title: '<?php echo esc_js( __( 'Select Sponsor Logo', 'event-management-system' ) ); ?>',
					button: { text: '<?php echo esc_js( __( 'Use as Logo', 'event-management-system' ) ); ?>' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#ems-logo-attachment-id').val(attachment.id);
					var imgUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
					$('#ems-logo-preview').html('<img src="' + imgUrl + '" alt="" />');
					$('#ems-remove-logo').show();
					$('#ems-upload-logo').text('<?php echo esc_js( __( 'Change Logo', 'event-management-system' ) ); ?>');

					// Save immediately via AJAX
					var $status = $('.ems-logo-save-status');
					$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Saving logo...', 'event-management-system' ) ); ?></div>');
					$.ajax({
						url: ems_public.ajax_url,
						type: 'POST',
						data: {
							action: 'ems_save_sponsor_logo',
							nonce: ems_public.nonce,
							sponsor_id: <?php echo esc_js( $sponsor_id ); ?>,
							attachment_id: attachment.id
						},
						success: function(response) {
							$status.html(response.success
								? '<div class="ems-notice ems-notice-success">' + response.data.message + '</div>'
								: '<div class="ems-notice ems-notice-error">' + response.data + '</div>'
							);
						},
						error: function() {
							$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'Failed to save logo.', 'event-management-system' ) ); ?></div>');
						}
					});
				});

				frame.open();
			});

			// Remove logo
			$('#ems-remove-logo').on('click', function() {
				$('#ems-logo-attachment-id').val('0');
				$('#ems-logo-preview').html('<span class="ems-logo-placeholder"><?php echo esc_js( __( 'No logo uploaded', 'event-management-system' ) ); ?></span>');
				$(this).hide();
				$('#ems-upload-logo').text('<?php echo esc_js( __( 'Upload Logo', 'event-management-system' ) ); ?>');

				var $status = $('.ems-logo-save-status');
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Removing logo...', 'event-management-system' ) ); ?></div>');
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_save_sponsor_logo',
						nonce: ems_public.nonce,
						sponsor_id: <?php echo esc_js( $sponsor_id ); ?>,
						attachment_id: 0
					},
					success: function(response) {
						$status.html(response.success
							? '<div class="ems-notice ems-notice-success">' + response.data.message + '</div>'
							: '<div class="ems-notice ems-notice-error">' + response.data + '</div>'
						);
					}
				});
			});

			// Save page content
			$('#ems-sponsor-page-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $btn = $form.find('button[type="submit"]');
				var $status = $form.find('.ems-page-save-status');

				// Sync TinyMCE content to textarea
				if (typeof tinymce !== 'undefined') {
					var editor = tinymce.get('ems_sponsor_page_content');
					if (editor) {
						editor.save();
					}
				}

				$btn.prop('disabled', true).addClass('loading');
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Saving page content...', 'event-management-system' ) ); ?></div>');

				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: $form.serialize(),
					success: function(response) {
						$status.html(response.success
							? '<div class="ems-notice ems-notice-success">' + response.data.message + '</div>'
							: '<div class="ems-notice ems-notice-error">' + response.data + '</div>'
						);
						$btn.prop('disabled', false).removeClass('loading');
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred. Please try again.', 'event-management-system' ) ); ?></div>');
						$btn.prop('disabled', false).removeClass('loading');
					}
				});
			});

			// EOI event selector
			$('#ems-eoi-event-select').on('change', function() {
				$('#ems-eoi-go').prop('disabled', !$(this).val());
			});
			$('#ems-eoi-go').on('click', function() {
				var url = $('#ems-eoi-event-select').val();
				if (url) { window.location.href = url; }
			});
		});
		</script>
		<?php
	}

	// =========================================================================
	// Helper Methods
	// =========================================================================

	/**
	 * Render a profile field in view mode
	 *
	 * @since 1.5.0
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @param string $type  Display type (text, url, email).
	 * @return void
	 */
	private function render_profile_field( $label, $value, $type = 'text' ) {
		?>
		<div class="ems-profile-field">
			<dt><?php echo esc_html( $label ); ?></dt>
			<dd>
				<?php if ( empty( $value ) ) : ?>
					<span class="ems-profile-empty"><?php esc_html_e( 'Not provided', 'event-management-system' ); ?></span>
				<?php elseif ( 'url' === $type ) : ?>
					<a href="<?php echo esc_url( $value ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $value ); ?></a>
				<?php elseif ( 'email' === $type ) : ?>
					<a href="mailto:<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></a>
				<?php else : ?>
					<?php echo nl2br( esc_html( $value ) ); ?>
				<?php endif; ?>
			</dd>
		</div>
		<?php
	}

	/**
	 * Render an edit form field
	 *
	 * @since 1.5.0
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param string $value Current value.
	 * @param string $type  Input type.
	 * @return void
	 */
	private function render_edit_field( $name, $label, $value, $type = 'text' ) {
		?>
		<div class="ems-form-group">
			<label for="ems-profile-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="<?php echo esc_attr( $type ); ?>"
				   id="ems-profile-<?php echo esc_attr( $name ); ?>"
				   name="<?php echo esc_attr( $name ); ?>"
				   value="<?php echo esc_attr( $value ); ?>"
				   class="ems-form-control" />
		</div>
		<?php
	}

	/**
	 * Get EOI status display data
	 *
	 * @since 1.5.0
	 * @param string $status Status string.
	 * @return array Array with 'label' and 'class' keys.
	 */
	private function get_eoi_status_data( $status ) {
		$map = array(
			'pending'        => array(
				'label' => __( 'Pending Review', 'event-management-system' ),
				'class' => 'ems-badge-eoi-pending',
			),
			'approved'       => array(
				'label' => __( 'Approved', 'event-management-system' ),
				'class' => 'ems-badge-eoi-approved',
			),
			'rejected'       => array(
				'label' => __( 'Declined', 'event-management-system' ),
				'class' => 'ems-badge-eoi-rejected',
			),
			'info_requested' => array(
				'label' => __( 'Info Requested', 'event-management-system' ),
				'class' => 'ems-badge-eoi-info',
			),
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : array(
			'label' => ucfirst( $status ),
			'class' => 'ems-badge-primary',
		);
	}

	/**
	 * Get level info for a sponsor's assignment on an event
	 *
	 * Queries the sponsorship_levels table to get level name, colour, value, and recognition.
	 *
	 * @since 1.5.0
	 * @param int    $event_id      Event post ID.
	 * @param string $sponsor_level Level name/slug from sponsor_events table.
	 * @return array|null Level info array or null if not found.
	 */
	private function get_event_level_info( $event_id, $sponsor_level ) {
		if ( empty( $sponsor_level ) ) {
			return null;
		}

		$levels = $this->levels_api->get_event_levels( $event_id );
		if ( empty( $levels ) ) {
			return null;
		}

		// Match by slug or name (case-insensitive)
		foreach ( $levels as $level ) {
			if ( $level->level_slug === $sponsor_level
				|| strtolower( $level->level_name ) === strtolower( $sponsor_level )
			) {
				return array(
					'name'             => $level->level_name,
					'slug'             => $level->level_slug,
					'colour'           => $level->colour,
					'value_aud'        => $level->value_aud,
					'recognition_text' => $level->recognition_text,
				);
			}
		}

		return null;
	}

	/**
	 * Get contrast colour for text on a given background colour
	 *
	 * @since 1.5.0
	 * @param string $hex_colour Hex colour code.
	 * @return string Either '#ffffff' or '#333333'.
	 */
	private function get_contrast_colour( $hex_colour ) {
		$hex = ltrim( $hex_colour, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		// Calculate relative luminance
		$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;

		return $luminance > 0.5 ? '#333333' : '#ffffff';
	}

	// =========================================================================
	// Shortcodes: Standalone Sponsor Files
	// =========================================================================

	/**
	 * Sponsor files list shortcode
	 *
	 * Renders a list of files for a specific sponsor/event.
	 *
	 * Usage: [ems_sponsor_files sponsor_id="123" event_id="456"]
	 *
	 * @since 1.4.0
	 * @param array $atts Shortcode attributes.
	 * @return string Files list HTML.
	 */
	public function sponsor_files_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'sponsor_id' => 0,
			'event_id'   => 0,
		), $atts, 'ems_sponsor_files' );

		$sponsor_id = absint( $atts['sponsor_id'] );
		$event_id   = absint( $atts['event_id'] );

		if ( ! $sponsor_id ) {
			return '<p>' . esc_html__( 'Invalid sponsor ID', 'event-management-system' ) . '</p>';
		}

		$files = $this->sponsor_portal->get_sponsor_files( $sponsor_id, $event_id );

		if ( empty( $files ) ) {
			return '<p>' . esc_html__( 'No files available.', 'event-management-system' ) . '</p>';
		}

		ob_start();
		?>
		<div class="ems-sponsor-files-widget">
			<h3><?php esc_html_e( 'Sponsor Files', 'event-management-system' ); ?></h3>
			<ul class="ems-files-list">
				<?php foreach ( $files as $file ) : ?>
					<li>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'ems_download_sponsor_file' => $file->id ), home_url() ), 'ems_download_file_' . $file->id ) ); ?>" target="_blank">
							<?php echo esc_html( $file->file_name ); ?>
						</a>
						<span class="ems-file-size">(<?php echo esc_html( size_format( $file->file_size ) ); ?>)</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX handler for sponsor file upload
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function ajax_upload_sponsor_file() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			// Sanitize POST data
			$sponsor_id  = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;
			$event_id    = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
			$description = isset( $_POST['file_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['file_description'] ) ) : '';

			if ( ! $sponsor_id || ! $event_id ) {
				wp_send_json_error( __( 'Invalid parameters', 'event-management-system' ) );
			}

			// SECURITY: Verify that the current user owns/is linked to this sponsor
			require_once EMS_PLUGIN_DIR . 'includes/collaboration/class-ems-sponsor-portal.php';
			$portal = new EMS_Sponsor_Portal();
			$user_sponsor_id = $portal->get_user_sponsor_id( get_current_user_id() );
			if ( absint( $user_sponsor_id ) !== $sponsor_id && ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'event-management-system' ) );
			}

			// Check if file was uploaded
			if ( ! isset( $_FILES['sponsor_file'] ) || empty( $_FILES['sponsor_file']['name'] ) ) {
				wp_send_json_error( __( 'No file uploaded', 'event-management-system' ) );
			}

			// Sanitize file data
			$file_data = array(
				'name'     => sanitize_file_name( $_FILES['sponsor_file']['name'] ),
				'type'     => sanitize_mime_type( $_FILES['sponsor_file']['type'] ),
				'tmp_name' => $_FILES['sponsor_file']['tmp_name'],
				'error'    => $_FILES['sponsor_file']['error'],
				'size'     => absint( $_FILES['sponsor_file']['size'] ),
			);

			// Upload file
			$result = $this->sponsor_portal->upload_sponsor_file(
				$file_data,
				$sponsor_id,
				$event_id,
				array(
					'description' => $description,
					'visibility'  => 'private',
				)
			);

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result['message'] );
			}

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in ajax_upload_sponsor_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			wp_send_json_error( __( 'An error occurred during file upload', 'event-management-system' ) );
		}
	}

	/**
	 * AJAX handler for sponsor file deletion
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function ajax_delete_sponsor_file() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			$file_id = isset( $_POST['file_id'] ) ? absint( $_POST['file_id'] ) : 0;

			if ( ! $file_id ) {
				wp_send_json_error( __( 'Invalid file ID', 'event-management-system' ) );
			}

			$result = $this->sponsor_portal->delete_sponsor_file( $file_id );

			if ( $result['success'] ) {
				wp_send_json_success( $result['message'] );
			} else {
				wp_send_json_error( $result['message'] );
			}

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in ajax_delete_sponsor_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			wp_send_json_error( __( 'An error occurred during file deletion', 'event-management-system' ) );
		}
	}

	/**
	 * AJAX handler for saving sponsor profile
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function ajax_save_sponsor_profile() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			$sponsor_id = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;

			if ( ! $sponsor_id ) {
				wp_send_json_error( __( 'Invalid sponsor ID', 'event-management-system' ) );
			}

			// Collect text profile fields from POST
			$text_fields = array(
				'legal_name', 'trading_name', 'abn', 'acn',
				'website_url', 'country', 'parent_company',
				'contact_name', 'contact_role', 'contact_email', 'contact_phone',
				'signatory_name', 'signatory_title', 'signatory_email',
				'marketing_contact_name', 'marketing_contact_email',
				'other_codes', 'tga_number',
				'public_liability_insurer', 'public_liability_policy',
				'scheduling_class', 'device_class',
			);

			$data = array();
			foreach ( $text_fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				}
			}

			// Textarea fields
			$textarea_fields = array(
				'registered_address', 'products', 'artg_listings', 'educational_relation',
				'adverse_findings', 'legal_proceedings', 'transparency_obligations',
				'personal_relationships', 'procurement_conflicts', 'transfers_of_value',
			);
			foreach ( $textarea_fields as $field ) {
				if ( isset( $_POST[ $field ] ) ) {
					$data[ $field ] = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
				}
			}

			// Checkbox / boolean fields (value is '1' if checked, absent if unchecked)
			$boolean_fields = array(
				'ma_member', 'mtaa_member',
				'non_artg_product', 'off_label', 'tga_safety_alert',
				'public_liability_confirmed', 'product_liability_confirmed',
				'professional_indemnity', 'workers_comp_confirmed',
				'code_compliance_agreed', 'compliance_process',
				'qld_health_aware', 'preferential_treatment_agreed',
			);
			foreach ( $boolean_fields as $field ) {
				$data[ $field ] = ! empty( $_POST[ $field ] ) ? '1' : '';
			}

			// Array fields (multi-select checkboxes)
			if ( isset( $_POST['industry_sectors'] ) && is_array( $_POST['industry_sectors'] ) ) {
				$data['industry_sectors'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['industry_sectors'] ) );
			} else {
				$data['industry_sectors'] = array();
			}

			// Email field sanitization
			$email_fields = array( 'contact_email', 'signatory_email', 'marketing_contact_email' );
			foreach ( $email_fields as $email_field ) {
				if ( isset( $data[ $email_field ] ) ) {
					$data[ $email_field ] = sanitize_email( $data[ $email_field ] );
				}
			}

			// URL field sanitization
			if ( isset( $data['website_url'] ) ) {
				$data['website_url'] = esc_url_raw( $data['website_url'] );
			}

			$result = $this->sponsor_portal->update_sponsor_profile( $sponsor_id, $data );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result['message'] );
			}

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in ajax_save_sponsor_profile: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			wp_send_json_error( __( 'An error occurred while saving your profile', 'event-management-system' ) );
		}
	}

	/**
	 * AJAX handler for loading EOI details
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function ajax_get_eoi_details() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			$eoi_id = isset( $_POST['eoi_id'] ) ? absint( $_POST['eoi_id'] ) : 0;

			if ( ! $eoi_id ) {
				wp_send_json_error( __( 'Invalid application ID', 'event-management-system' ) );
			}

			// Get current user's sponsor ID
			$sponsor_id = $this->sponsor_portal->get_user_sponsor_id( get_current_user_id() );
			if ( ! $sponsor_id ) {
				wp_send_json_error( __( 'No sponsor profile found', 'event-management-system' ) );
			}

			// Get EOI with ownership check
			$eoi = $this->sponsor_portal->get_sponsor_eoi( $eoi_id, $sponsor_id );
			if ( ! $eoi ) {
				wp_send_json_error( __( 'Application not found', 'event-management-system' ) );
			}

			// Build HTML for EOI details
			$html = $this->build_eoi_details_html( $eoi );

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in ajax_get_eoi_details: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			wp_send_json_error( __( 'An error occurred while loading application details', 'event-management-system' ) );
		}
	}

	/**
	 * AJAX handler for saving sponsor page content
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function ajax_save_sponsor_page() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			$sponsor_id = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;
			$content    = isset( $_POST['page_content'] ) ? wp_kses_post( wp_unslash( $_POST['page_content'] ) ) : '';

			if ( ! $sponsor_id ) {
				wp_send_json_error( __( 'Invalid sponsor ID', 'event-management-system' ) );
			}

			$result = $this->sponsor_portal->update_sponsor_page_content( $sponsor_id, $content );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result['message'] );
			}
		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in ajax_save_sponsor_page: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			wp_send_json_error( __( 'An error occurred while saving page content', 'event-management-system' ) );
		}
	}

	/**
	 * AJAX handler for saving sponsor logo
	 *
	 * @since 1.6.0
	 * @return void
	 */
	public function ajax_save_sponsor_logo() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		try {
			$sponsor_id    = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;
			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

			if ( ! $sponsor_id ) {
				wp_send_json_error( __( 'Invalid sponsor ID', 'event-management-system' ) );
			}

			$result = $this->sponsor_portal->update_sponsor_logo( $sponsor_id, $attachment_id );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result['message'] );
			}
		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in ajax_save_sponsor_logo: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			wp_send_json_error( __( 'An error occurred while saving the logo', 'event-management-system' ) );
		}
	}

	/**
	 * Build HTML for EOI detail display
	 *
	 * @since 1.5.0
	 * @param object $eoi EOI database row.
	 * @return string HTML content.
	 */
	private function build_eoi_details_html( $eoi ) {
		$event       = get_post( $eoi->event_id );
		$event_title = $event ? $event->post_title : __( 'Unknown Event', 'event-management-system' );

		ob_start();
		?>
		<div class="ems-eoi-detail-content">
			<?php if ( ! empty( $eoi->review_notes ) ) : ?>
				<div class="ems-notice <?php echo 'info_requested' === $eoi->status ? 'ems-notice-warning' : 'ems-notice-info'; ?>">
					<strong><?php esc_html_e( 'Reviewer Notes:', 'event-management-system' ); ?></strong><br />
					<?php echo nl2br( esc_html( $eoi->review_notes ) ); ?>
				</div>
			<?php endif; ?>

			<div class="ems-eoi-detail-grid">
				<div class="ems-eoi-detail-section">
					<h4><?php esc_html_e( 'Commitment & Preferences', 'event-management-system' ); ?></h4>
					<dl class="ems-eoi-detail-list">
						<div class="ems-eoi-detail-item">
							<dt><?php esc_html_e( 'Preferred Level', 'event-management-system' ); ?></dt>
							<dd><?php echo esc_html( ucfirst( str_replace( array( '-', '_' ), ' ', $eoi->preferred_level ) ) ); ?></dd>
						</div>
						<?php if ( ! empty( $eoi->estimated_value ) ) : ?>
							<div class="ems-eoi-detail-item">
								<dt><?php esc_html_e( 'Estimated Value', 'event-management-system' ); ?></dt>
								<dd><?php echo esc_html( '$' . number_format( (float) $eoi->estimated_value, 2 ) . ' AUD' ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $eoi->contribution_nature ) ) : ?>
							<div class="ems-eoi-detail-item">
								<dt><?php esc_html_e( 'Contribution Nature', 'event-management-system' ); ?></dt>
								<dd><?php echo esc_html( $eoi->contribution_nature ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $eoi->duration_interest ) ) : ?>
							<div class="ems-eoi-detail-item">
								<dt><?php esc_html_e( 'Duration of Interest', 'event-management-system' ); ?></dt>
								<dd><?php echo esc_html( ucfirst( str_replace( '_', ' ', $eoi->duration_interest ) ) ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</div>

				<div class="ems-eoi-detail-section">
					<h4><?php esc_html_e( 'Exclusivity', 'event-management-system' ); ?></h4>
					<dl class="ems-eoi-detail-list">
						<div class="ems-eoi-detail-item">
							<dt><?php esc_html_e( 'Exclusivity Requested', 'event-management-system' ); ?></dt>
							<dd><?php echo esc_html( $eoi->exclusivity_requested ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) ); ?></dd>
						</div>
						<?php if ( $eoi->exclusivity_requested && ! empty( $eoi->exclusivity_category ) ) : ?>
							<div class="ems-eoi-detail-item">
								<dt><?php esc_html_e( 'Category', 'event-management-system' ); ?></dt>
								<dd><?php echo esc_html( $eoi->exclusivity_category ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</div>

				<div class="ems-eoi-detail-section">
					<h4><?php esc_html_e( 'Recognition & Visibility', 'event-management-system' ); ?></h4>
					<dl class="ems-eoi-detail-list">
						<?php if ( ! empty( $eoi->recognition_elements ) ) : ?>
							<div class="ems-eoi-detail-item">
								<dt><?php esc_html_e( 'Recognition Elements', 'event-management-system' ); ?></dt>
								<dd><?php echo esc_html( $eoi->recognition_elements ); ?></dd>
							</div>
						<?php endif; ?>
						<div class="ems-eoi-detail-item">
							<dt><?php esc_html_e( 'Trade Display', 'event-management-system' ); ?></dt>
							<dd><?php echo esc_html( $eoi->trade_display_requested ? __( 'Yes', 'event-management-system' ) : __( 'No', 'event-management-system' ) ); ?></dd>
						</div>
					</dl>
				</div>
			</div>

			<?php if ( $eoi->reviewed_at ) : ?>
				<div class="ems-eoi-review-info">
					<small>
						<?php
						echo esc_html( sprintf(
							/* translators: %s: date */
							__( 'Reviewed on %s', 'event-management-system' ),
							date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $eoi->reviewed_at ) )
						) );
						?>
					</small>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
