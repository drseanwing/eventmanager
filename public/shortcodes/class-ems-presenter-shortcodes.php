<?php
/**
 * Presenter Portal Shortcodes
 *
 * Provides shortcodes for presenter portal dashboard:
 * - [ems_presenter_portal] - Tabbed presenter dashboard
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/public/shortcodes
 * @since      1.7.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EMS_Presenter_Shortcodes
 *
 * @since 1.7.0
 */
class EMS_Presenter_Shortcodes {

	/**
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * @var EMS_Presenter_Portal
	 */
	private $presenter_portal;

	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		$this->logger           = EMS_Logger::instance();
		$this->presenter_portal = new EMS_Presenter_Portal();

		add_shortcode( 'ems_presenter_portal', array( $this, 'presenter_portal_shortcode' ) );

		// AJAX handlers
		add_action( 'wp_ajax_ems_save_presenter_bio', array( $this, 'ajax_save_presenter_bio' ) );
		add_action( 'wp_ajax_ems_upload_presenter_file', array( $this, 'ajax_upload_presenter_file' ) );
		add_action( 'wp_ajax_ems_delete_presenter_file', array( $this, 'ajax_delete_presenter_file' ) );
		add_action( 'wp_ajax_ems_save_presenter_headshot', array( $this, 'ajax_save_presenter_headshot' ) );
		add_action( 'wp_ajax_ems_respond_presenter_headshot', array( $this, 'ajax_respond_presenter_headshot' ) );
		add_action( 'wp_ajax_ems_save_presenter_availability', array( $this, 'ajax_save_presenter_availability' ) );
	}

	// =========================================================================
	// MAIN SHORTCODE
	// =========================================================================

	/**
	 * Presenter portal dashboard shortcode
	 *
	 * @since 1.7.0
	 * @param array $atts Shortcode attributes.
	 * @return string Portal HTML.
	 */
	public function presenter_portal_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( add_query_arg( array() ) );
			return '<div class="ems-notice ems-notice-warning">' .
				   sprintf(
					   __( 'Please <a href="%s">log in</a> to access the presenter portal.', 'event-management-system' ),
					   esc_url( $login_url )
				   ) .
				   '</div>';
		}

		$is_admin = current_user_can( 'manage_options' ) || current_user_can( 'manage_ems_events' );

		// Admin presenter picker: accept ?presenter_id= query param.
		$presenter_user_id = 0;
		if ( $is_admin ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested = isset( $_GET['presenter_id'] ) ? absint( $_GET['presenter_id'] ) : 0;
			if ( $requested ) {
				$user = get_user_by( 'ID', $requested );
				if ( $user ) {
					$presenter_user_id = $requested;
				}
			}

			if ( ! $presenter_user_id ) {
				return $this->render_admin_presenter_picker();
			}
		} else {
			if ( ! current_user_can( 'access_ems_presenter_portal' ) ) {
				return '<div class="ems-notice ems-notice-error">' .
					   __( 'You do not have access to the presenter portal.', 'event-management-system' ) .
					   '</div>';
			}
			$presenter_user_id = get_current_user_id();
		}

		// Verify presenter has sessions
		$sessions   = $this->presenter_portal->get_presenter_sessions( $presenter_user_id );
		$statistics = $this->presenter_portal->get_presenter_statistics( $presenter_user_id );
		$bio        = $this->presenter_portal->get_presenter_bio( $presenter_user_id );
		$headshot   = $this->presenter_portal->get_presenter_headshot( $presenter_user_id );
		$events     = $this->presenter_portal->get_presenter_events( $presenter_user_id );
		$user       = get_user_by( 'ID', $presenter_user_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['portal_tab'] ) ? sanitize_key( $_GET['portal_tab'] ) : 'dashboard';
		$valid_tabs = array( 'dashboard', 'profile', 'sessions', 'schedule', 'headshot' );
		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'dashboard';
		}

		ob_start();
		?>

		<div class="ems-presenter-portal">
			<!-- Portal Header -->
			<div class="ems-presenter-header">
				<?php if ( $is_admin ) : ?>
					<p style="margin-bottom: 8px;">
						<a href="<?php echo esc_url( remove_query_arg( 'presenter_id' ) ); ?>" style="text-decoration: none; font-size: 13px;">&larr; <?php esc_html_e( 'All Presenters', 'event-management-system' ); ?></a>
					</p>
				<?php endif; ?>
				<h1><?php echo esc_html( sprintf( __( 'Welcome, %s', 'event-management-system' ), $user->display_name ) ); ?></h1>
				<p class="ems-presenter-description"><?php esc_html_e( 'Manage your presentations, profile, files, and schedule preferences.', 'event-management-system' ); ?></p>
			</div>

			<!-- Tab Navigation -->
			<div class="ems-portal-tabs" role="tablist">
				<button type="button" class="ems-portal-tab <?php echo 'dashboard' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'dashboard' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-dashboard" data-tab="dashboard">
					<?php esc_html_e( 'Dashboard', 'event-management-system' ); ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'profile' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'profile' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-profile" data-tab="profile">
					<?php esc_html_e( 'My Profile', 'event-management-system' ); ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'sessions' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'sessions' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-sessions" data-tab="sessions">
					<?php esc_html_e( 'My Sessions', 'event-management-system' ); ?>
					<?php if ( ! empty( $sessions ) ) : ?>
						<span class="ems-portal-tab__count"><?php echo esc_html( count( $sessions ) ); ?></span>
					<?php endif; ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'schedule' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'schedule' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-schedule" data-tab="schedule">
					<?php esc_html_e( 'Schedule & Availability', 'event-management-system' ); ?>
				</button>
				<button type="button" class="ems-portal-tab <?php echo 'headshot' === $active_tab ? 'ems-portal-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo 'headshot' === $active_tab ? 'true' : 'false'; ?>" aria-controls="ems-tab-headshot" data-tab="headshot">
					<?php esc_html_e( 'Headshot', 'event-management-system' ); ?>
					<?php if ( 'pending_approval' === $headshot['status'] ) : ?>
						<span class="ems-portal-tab__count">!</span>
					<?php endif; ?>
				</button>
			</div>

			<!-- Tab: Dashboard -->
			<div class="ems-portal-tab-content <?php echo 'dashboard' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-dashboard" role="tabpanel">
				<?php $this->render_dashboard_tab( $presenter_user_id, $sessions, $statistics, $events ); ?>
			</div>

			<!-- Tab: Profile -->
			<div class="ems-portal-tab-content <?php echo 'profile' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-profile" role="tabpanel">
				<?php $this->render_profile_tab( $presenter_user_id, $bio ); ?>
			</div>

			<!-- Tab: Sessions -->
			<div class="ems-portal-tab-content <?php echo 'sessions' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-sessions" role="tabpanel">
				<?php $this->render_sessions_tab( $presenter_user_id, $sessions ); ?>
			</div>

			<!-- Tab: Schedule & Availability -->
			<div class="ems-portal-tab-content <?php echo 'schedule' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-schedule" role="tabpanel">
				<?php $this->render_schedule_tab( $presenter_user_id, $events ); ?>
			</div>

			<!-- Tab: Headshot -->
			<div class="ems-portal-tab-content <?php echo 'headshot' === $active_tab ? 'ems-portal-tab-content--active' : ''; ?>" id="ems-tab-headshot" role="tabpanel">
				<?php $this->render_headshot_tab( $presenter_user_id, $headshot ); ?>
			</div>
		</div>

		<?php $this->render_portal_scripts( $presenter_user_id ); ?>

		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// ADMIN PRESENTER PICKER
	// =========================================================================

	/**
	 * Render admin presenter picker
	 *
	 * @since 1.7.0
	 * @return string HTML.
	 */
	private function render_admin_presenter_picker() {
		$presenters = get_users( array(
			'role'    => 'ems_presenter',
			'orderby' => 'display_name',
			'order'   => 'ASC',
		) );

		$current_url = remove_query_arg( 'presenter_id' );

		ob_start();
		?>
		<div class="ems-presenter-portal">
			<div class="ems-presenter-header">
				<h1><?php esc_html_e( 'Presenter Portal — Admin View', 'event-management-system' ); ?></h1>
				<p class="ems-presenter-description"><?php esc_html_e( 'Select a presenter to view their dashboard.', 'event-management-system' ); ?></p>
			</div>

			<?php if ( empty( $presenters ) ) : ?>
				<div class="ems-notice ems-notice-warning">
					<?php esc_html_e( 'No presenters have been created yet. Assign a presenter to a session to get started.', 'event-management-system' ); ?>
				</div>
			<?php else : ?>
				<div class="ems-admin-presenter-picker" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 20px;">
					<?php foreach ( $presenters as $presenter ) :
						$sessions = $this->presenter_portal->get_presenter_sessions( $presenter->ID );
						$headshot = $this->presenter_portal->get_presenter_headshot( $presenter->ID );
						$view_url = add_query_arg( 'presenter_id', $presenter->ID, $current_url );
						?>
						<a href="<?php echo esc_url( $view_url ); ?>" class="ems-presenter-picker-card" style="
							display: block; padding: 16px; border: 1px solid #c3c4c7; border-radius: 6px;
							text-decoration: none; color: inherit; background: #fff;
							transition: border-color 0.2s, box-shadow 0.2s;
						" onmouseover="this.style.borderColor='#2271b1';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'" onmouseout="this.style.borderColor='#c3c4c7';this.style.boxShadow='none'">
							<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
								<?php if ( $headshot['url'] ) : ?>
									<img src="<?php echo esc_url( $headshot['url'] ); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%;">
								<?php else : ?>
									<div style="width: 48px; height: 48px; border-radius: 50%; background: #dcdcde; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #646970; flex-shrink: 0;">
										<?php echo esc_html( strtoupper( substr( $presenter->display_name, 0, 1 ) ) ); ?>
									</div>
								<?php endif; ?>
								<div>
									<strong style="font-size: 15px;"><?php echo esc_html( $presenter->display_name ); ?></strong>
									<span style="display: block; font-size: 12px; color: #646970;"><?php echo esc_html( $presenter->user_email ); ?></span>
								</div>
							</div>
							<div style="font-size: 12px; color: #646970;">
								<?php
								echo esc_html( sprintf(
									_n( '%d session', '%d sessions', count( $sessions ), 'event-management-system' ),
									count( $sessions )
								) );
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

	// =========================================================================
	// TAB RENDERERS
	// =========================================================================

	/**
	 * Render dashboard tab
	 *
	 * @since 1.7.0
	 */
	private function render_dashboard_tab( $user_id, $sessions, $statistics, $events ) {
		?>
		<!-- Statistics -->
		<div class="ems-sponsor-statistics">
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['session_count'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Sessions', 'event-management-system' ); ?></div>
			</div>
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['event_count'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Events', 'event-management-system' ); ?></div>
			</div>
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['file_count'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Files Uploaded', 'event-management-system' ); ?></div>
			</div>
			<div class="ems-stat-card">
				<div class="ems-stat-value"><?php echo esc_html( $statistics['total_downloads'] ); ?></div>
				<div class="ems-stat-label"><?php esc_html_e( 'Total Downloads', 'event-management-system' ); ?></div>
			</div>
		</div>

		<!-- Upcoming Sessions -->
		<div class="ems-presenter-sessions-overview">
			<h2><?php esc_html_e( 'Your Sessions', 'event-management-system' ); ?></h2>
			<?php if ( empty( $sessions ) ) : ?>
				<p class="ems-no-results"><?php esc_html_e( 'You have not been assigned to any sessions yet.', 'event-management-system' ); ?></p>
			<?php else : ?>
				<div class="ems-presenter-session-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
					<?php foreach ( $sessions as $session ) : ?>
						<div class="ems-presenter-session-card" style="border: 1px solid #c3c4c7; border-radius: 6px; padding: 16px; background: #fff;">
							<h3 style="margin: 0 0 8px 0; font-size: 15px;"><?php echo esc_html( $session['session_title'] ); ?></h3>
							<div style="font-size: 13px; color: #646970; margin-bottom: 4px;">
								<strong><?php esc_html_e( 'Event:', 'event-management-system' ); ?></strong>
								<?php echo esc_html( $session['event_title'] ); ?>
							</div>
							<?php if ( $session['start_datetime'] ) : ?>
								<div style="font-size: 13px; color: #646970; margin-bottom: 4px;">
									<strong><?php esc_html_e( 'When:', 'event-management-system' ); ?></strong>
									<?php
									echo esc_html( EMS_Date_Helper::format( $session['start_datetime'], 'M j, Y g:i A' ) );
									if ( $session['end_datetime'] ) {
										echo ' - ' . esc_html( EMS_Date_Helper::format( $session['end_datetime'], 'g:i A' ) );
									}
									?>
								</div>
							<?php endif; ?>
							<?php if ( $session['location'] ) : ?>
								<div style="font-size: 13px; color: #646970; margin-bottom: 4px;">
									<strong><?php esc_html_e( 'Location:', 'event-management-system' ); ?></strong>
									<?php echo esc_html( $session['location'] ); ?>
								</div>
							<?php endif; ?>
							<?php if ( $session['session_type'] ) : ?>
								<div style="font-size: 13px; color: #646970;">
									<strong><?php esc_html_e( 'Type:', 'event-management-system' ); ?></strong>
									<?php
									$types = EMS_Schedule_Builder::SESSION_TYPES;
									echo esc_html( isset( $types[ $session['session_type'] ] ) ? $types[ $session['session_type'] ] : ucfirst( $session['session_type'] ) );
									?>
								</div>
							<?php endif; ?>
							<?php if ( 'publish' !== $session['session_status'] ) : ?>
								<div style="margin-top: 8px;">
									<span style="display: inline-block; background: #dba617; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 3px; text-transform: uppercase;">
										<?php echo esc_html( ucfirst( $session['session_status'] ) ); ?>
									</span>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Convenor-provided files -->
		<?php
		$convenor_files = $this->presenter_portal->get_convenor_files_for_presenter( $user_id );
		if ( ! empty( $convenor_files ) ) :
		?>
			<div class="ems-presenter-convenor-files" style="margin-top: 24px;">
				<h2><?php esc_html_e( 'Files from Convenors', 'event-management-system' ); ?></h2>
				<table class="ems-files-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File Name', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Category', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Size', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Uploaded', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $convenor_files as $file ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $file->file_name ); ?></strong>
									<?php if ( $file->description ) : ?>
										<br><small><?php echo esc_html( $file->description ); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( isset( EMS_Presenter_Portal::FILE_CATEGORIES[ $file->file_category ] ) ? EMS_Presenter_Portal::FILE_CATEGORIES[ $file->file_category ] : $file->file_category ); ?></td>
								<td><?php echo esc_html( size_format( $file->file_size ) ); ?></td>
								<td><?php echo esc_html( EMS_Date_Helper::format( $file->upload_date ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'ems_download_presenter_file' => $file->id ), home_url() ), 'ems_download_presenter_file_' . $file->id ) ); ?>" class="ems-button ems-button-small">
										<?php esc_html_e( 'Download', 'event-management-system' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render profile tab with structured bio fields
	 *
	 * @since 1.7.0
	 */
	private function render_profile_tab( $user_id, $bio ) {
		$user       = get_user_by( 'ID', $user_id );
		$bio_fields = EMS_Presenter_Portal::BIO_FIELDS;
		?>
		<div class="ems-presenter-profile-section">
			<div class="ems-profile-header">
				<h2><?php esc_html_e( 'Presenter Profile', 'event-management-system' ); ?></h2>
				<button type="button" class="ems-button ems-button-small ems-presenter-profile-edit-toggle">
					<?php esc_html_e( 'Edit Profile', 'event-management-system' ); ?>
				</button>
			</div>

			<div class="ems-profile-section" style="margin-bottom: 16px;">
				<div class="ems-profile-grid">
					<div class="ems-profile-field">
						<span class="ems-profile-label"><?php esc_html_e( 'Name', 'event-management-system' ); ?></span>
						<span class="ems-profile-value"><?php echo esc_html( $user->display_name ); ?></span>
					</div>
					<div class="ems-profile-field">
						<span class="ems-profile-label"><?php esc_html_e( 'Email', 'event-management-system' ); ?></span>
						<span class="ems-profile-value"><?php echo esc_html( $user->user_email ); ?></span>
					</div>
				</div>
			</div>

			<!-- View Mode -->
			<div class="ems-presenter-profile-view">
				<?php foreach ( $bio_fields as $key => $field ) :
					$value = isset( $bio[ $key ] ) ? $bio[ $key ] : '';
					if ( is_array( $value ) ) {
						$display = implode( ', ', $value );
					} elseif ( 'url' === $field['type'] && $value ) {
						$display = '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';
					} else {
						$display = esc_html( $value );
					}
				?>
					<div class="ems-profile-field" style="margin-bottom: 12px;">
						<span class="ems-profile-label" style="display: block; font-weight: 600; margin-bottom: 2px; font-size: 13px; color: #1d2327;"><?php echo esc_html( $field['label'] ); ?></span>
						<span class="ems-profile-value" style="color: #50575e;"><?php echo $display ? $display : '<em style="color: #a7aaad;">' . esc_html__( 'Not provided', 'event-management-system' ) . '</em>'; ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Edit Mode (Initially Hidden) -->
			<div class="ems-presenter-profile-edit ems-hidden">
				<form id="ems-presenter-profile-form" class="ems-form">
					<input type="hidden" name="action" value="ems_save_presenter_bio">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ems_public_nonce' ) ); ?>">
					<input type="hidden" name="presenter_user_id" value="<?php echo esc_attr( $user_id ); ?>">

					<?php foreach ( $bio_fields as $key => $field ) :
						$value = isset( $bio[ $key ] ) ? $bio[ $key ] : '';
					?>
						<div class="ems-form-group" style="margin-bottom: 16px;">
							<label for="ems-bio-<?php echo esc_attr( $key ); ?>" style="display: block; font-weight: 600; margin-bottom: 4px;">
								<?php echo esc_html( $field['label'] ); ?>
							</label>

							<?php if ( 'textarea' === $field['type'] ) : ?>
								<textarea id="ems-bio-<?php echo esc_attr( $key ); ?>" name="bio[<?php echo esc_attr( $key ); ?>]" rows="<?php echo esc_attr( isset( $field['rows'] ) ? $field['rows'] : 3 ); ?>" class="widefat"><?php echo esc_textarea( is_array( $value ) ? implode( ', ', $value ) : $value ); ?></textarea>

							<?php elseif ( 'tags' === $field['type'] ) : ?>
								<input type="text" id="ems-bio-<?php echo esc_attr( $key ); ?>" name="bio[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( is_array( $value ) ? implode( ', ', $value ) : $value ); ?>" class="widefat ems-tags-input" data-placeholder="<?php echo esc_attr( $field['help'] ); ?>">
								<p class="description" style="font-size: 12px; color: #646970; margin-top: 2px;"><?php esc_html_e( 'Separate entries with commas.', 'event-management-system' ); ?></p>

							<?php elseif ( 'url' === $field['type'] ) : ?>
								<input type="url" id="ems-bio-<?php echo esc_attr( $key ); ?>" name="bio[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_url( $value ); ?>" class="widefat" placeholder="https://">

							<?php else : ?>
								<input type="text" id="ems-bio-<?php echo esc_attr( $key ); ?>" name="bio[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="widefat">
							<?php endif; ?>

							<?php if ( ! empty( $field['help'] ) && 'tags' !== $field['type'] ) : ?>
								<p class="description" style="font-size: 12px; color: #646970; margin-top: 2px;"><?php echo esc_html( $field['help'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>

					<div class="ems-form-actions" style="margin-top: 16px;">
						<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Save Profile', 'event-management-system' ); ?></button>
						<button type="button" class="ems-button ems-presenter-profile-edit-toggle"><?php esc_html_e( 'Cancel', 'event-management-system' ); ?></button>
					</div>
					<div class="ems-presenter-profile-save-status"></div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render sessions tab with file management
	 *
	 * @since 1.7.0
	 */
	private function render_sessions_tab( $user_id, $sessions ) {
		if ( empty( $sessions ) ) {
			echo '<p class="ems-no-results">' . esc_html__( 'You have not been assigned to any sessions yet.', 'event-management-system' ) . '</p>';
			return;
		}
		?>
		<div class="ems-presenter-sessions-list">
			<?php foreach ( $sessions as $session ) :
				$files = $this->presenter_portal->get_session_files( $session['session_id'] );
			?>
				<div class="ems-presenter-session-detail" style="border: 1px solid #c3c4c7; border-radius: 6px; padding: 20px; margin-bottom: 16px; background: #fff;">
					<div class="ems-event-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
						<div>
							<h3 style="margin: 0 0 4px 0;"><?php echo esc_html( $session['session_title'] ); ?></h3>
							<p style="margin: 0; font-size: 13px; color: #646970;">
								<?php echo esc_html( $session['event_title'] ); ?>
								<?php if ( $session['start_datetime'] ) : ?>
									&middot; <?php echo esc_html( EMS_Date_Helper::format( $session['start_datetime'], 'M j, Y g:i A' ) ); ?>
								<?php endif; ?>
								<?php if ( $session['location'] ) : ?>
									&middot; <?php echo esc_html( $session['location'] ); ?>
								<?php endif; ?>
							</p>
						</div>
						<button type="button" class="ems-button ems-button-small ems-toggle-session-files" data-session-id="<?php echo esc_attr( $session['session_id'] ); ?>">
							<?php esc_html_e( 'Manage Files', 'event-management-system' ); ?>
						</button>
					</div>

					<!-- Files Section -->
					<div class="ems-session-files" id="ems-session-files-<?php echo esc_attr( $session['session_id'] ); ?>" style="display: none;">
						<!-- Upload Form -->
						<div class="ems-file-upload-section" style="background: #f6f7f7; padding: 16px; border-radius: 4px; margin-bottom: 16px;">
							<h4 style="margin: 0 0 12px 0;"><?php esc_html_e( 'Upload File', 'event-management-system' ); ?></h4>
							<form class="ems-presenter-file-upload-form" data-session-id="<?php echo esc_attr( $session['session_id'] ); ?>" data-event-id="<?php echo esc_attr( $session['event_id'] ); ?>">
								<div class="ems-form-group" style="margin-bottom: 8px;">
									<label><?php esc_html_e( 'File:', 'event-management-system' ); ?></label>
									<input type="file" name="presenter_file" required>
								</div>
								<div class="ems-form-group" style="margin-bottom: 8px;">
									<label><?php esc_html_e( 'Category:', 'event-management-system' ); ?></label>
									<select name="file_category">
										<?php foreach ( EMS_Presenter_Portal::FILE_CATEGORIES as $cat_key => $cat_label ) :
											if ( 'convenor' === $cat_key ) continue; // Presenters can't upload as "convenor"
										?>
											<option value="<?php echo esc_attr( $cat_key ); ?>"><?php echo esc_html( $cat_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="ems-form-group" style="margin-bottom: 8px;">
									<label><?php esc_html_e( 'Description:', 'event-management-system' ); ?></label>
									<textarea name="file_description" rows="2" class="widefat"></textarea>
								</div>
								<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Upload', 'event-management-system' ); ?></button>
								<div class="ems-upload-status"></div>
							</form>
						</div>

						<!-- File List -->
						<?php if ( empty( $files ) ) : ?>
							<p class="ems-no-files"><?php esc_html_e( 'No files uploaded yet.', 'event-management-system' ); ?></p>
						<?php else : ?>
							<table class="ems-files-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'File', 'event-management-system' ); ?></th>
										<th><?php esc_html_e( 'Category', 'event-management-system' ); ?></th>
										<th><?php esc_html_e( 'Size', 'event-management-system' ); ?></th>
										<th><?php esc_html_e( 'Uploaded', 'event-management-system' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $files as $file ) : ?>
										<tr data-file-id="<?php echo esc_attr( $file->id ); ?>">
											<td>
												<strong><?php echo esc_html( $file->file_name ); ?></strong>
												<?php if ( $file->description ) : ?>
													<br><small><?php echo esc_html( $file->description ); ?></small>
												<?php endif; ?>
												<?php if ( 'convenor' === $file->uploaded_by ) : ?>
													<br><small style="color: #2271b1;"><?php esc_html_e( 'From convenor', 'event-management-system' ); ?></small>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( isset( EMS_Presenter_Portal::FILE_CATEGORIES[ $file->file_category ] ) ? EMS_Presenter_Portal::FILE_CATEGORIES[ $file->file_category ] : $file->file_category ); ?></td>
											<td><?php echo esc_html( size_format( $file->file_size ) ); ?></td>
											<td><?php echo esc_html( EMS_Date_Helper::format( $file->upload_date ) ); ?></td>
											<td>
												<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'ems_download_presenter_file' => $file->id ), home_url() ), 'ems_download_presenter_file_' . $file->id ) ); ?>" class="ems-button ems-button-small">
													<?php esc_html_e( 'Download', 'event-management-system' ); ?>
												</a>
												<?php if ( absint( $file->user_id ) === get_current_user_id() || current_user_can( 'manage_options' ) ) : ?>
													<button type="button" class="ems-button ems-button-small ems-button-danger ems-delete-presenter-file" data-file-id="<?php echo esc_attr( $file->id ); ?>">
														<?php esc_html_e( 'Delete', 'event-management-system' ); ?>
													</button>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render schedule & availability tab
	 *
	 * @since 1.7.0
	 */
	private function render_schedule_tab( $user_id, $events ) {
		if ( empty( $events ) ) {
			echo '<p class="ems-no-results">' . esc_html__( 'No events found. You will see the schedule once you are assigned to a session.', 'event-management-system' ) . '</p>';
			return;
		}

		$schedule_display = new EMS_Schedule_Display();
		?>
		<div class="ems-presenter-schedule-section">
			<?php foreach ( $events as $event ) :
				$event_id   = $event['event_id'];
				$timeslots  = $this->presenter_portal->get_event_timeslots( $event_id );
				$prefs      = $this->presenter_portal->get_presenter_availability( $user_id, $event_id );
			?>
				<div class="ems-presenter-event-schedule" style="margin-bottom: 32px;">
					<h2><?php echo esc_html( $event['event_title'] ); ?></h2>

					<!-- Schedule View -->
					<div class="ems-schedule-view" style="margin-bottom: 20px;">
						<h3><?php esc_html_e( 'Event Schedule', 'event-management-system' ); ?></h3>
						<?php echo $schedule_display->get_schedule_html( $event_id, array( 'view' => 'list', 'show_filters' => false, 'show_search' => false ) ); ?>
					</div>

					<!-- Availability Preferences -->
					<?php if ( ! empty( $timeslots ) ) : ?>
						<div class="ems-availability-section" style="background: #f6f7f7; padding: 20px; border-radius: 6px;">
							<h3 style="margin-top: 0;"><?php esc_html_e( 'Your Availability Preferences', 'event-management-system' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Indicate your preference for each timeslot. This helps convenors schedule your session at a suitable time.', 'event-management-system' ); ?></p>

							<form class="ems-presenter-availability-form" data-event-id="<?php echo esc_attr( $event_id ); ?>">
								<table class="ems-files-table" style="background: #fff;">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Timeslot', 'event-management-system' ); ?></th>
											<th><?php esc_html_e( 'Session', 'event-management-system' ); ?></th>
											<th><?php esc_html_e( 'Preference', 'event-management-system' ); ?></th>
											<th><?php esc_html_e( 'Note', 'event-management-system' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $timeslots as $i => $slot ) :
											$slot_key = $slot['start'] . '|' . $slot['end'];
											$pref     = isset( $prefs[ $slot_key ] ) ? $prefs[ $slot_key ]['preference'] : 'available';
											$note     = isset( $prefs[ $slot_key ] ) ? $prefs[ $slot_key ]['note'] : '';
										?>
											<tr>
												<td>
													<?php
													echo esc_html( EMS_Date_Helper::format( $slot['start'], 'M j, g:i A' ) );
													echo ' - ';
													echo esc_html( EMS_Date_Helper::format( $slot['end'], 'g:i A' ) );
													?>
													<input type="hidden" name="slots[<?php echo esc_attr( $i ); ?>][start]" value="<?php echo esc_attr( $slot['start'] ); ?>">
													<input type="hidden" name="slots[<?php echo esc_attr( $i ); ?>][end]" value="<?php echo esc_attr( $slot['end'] ); ?>">
												</td>
												<td style="font-size: 12px;"><?php echo esc_html( $slot['title'] ); ?></td>
												<td>
													<select name="slots[<?php echo esc_attr( $i ); ?>][preference]" style="min-width: 120px;">
														<?php foreach ( EMS_Presenter_Portal::AVAILABILITY_OPTIONS as $opt_key => $opt_label ) : ?>
															<option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $pref, $opt_key ); ?>><?php echo esc_html( $opt_label ); ?></option>
														<?php endforeach; ?>
													</select>
												</td>
												<td>
													<input type="text" name="slots[<?php echo esc_attr( $i ); ?>][note]" value="<?php echo esc_attr( $note ); ?>" placeholder="<?php esc_attr_e( 'Optional note...', 'event-management-system' ); ?>" style="width: 100%;">
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>

								<div style="margin-top: 12px;">
									<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Save Preferences', 'event-management-system' ); ?></button>
								</div>
								<div class="ems-availability-save-status"></div>
							</form>
						</div>
					<?php else : ?>
						<p class="ems-no-results"><?php esc_html_e( 'No timeslots have been scheduled for this event yet.', 'event-management-system' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render headshot tab
	 *
	 * @since 1.7.0
	 */
	private function render_headshot_tab( $user_id, $headshot ) {
		$is_admin = current_user_can( 'manage_options' ) || current_user_can( 'manage_ems_events' );
		?>
		<div class="ems-presenter-headshot-section">
			<h2><?php esc_html_e( 'Profile Photo / Headshot', 'event-management-system' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Upload a professional headshot that will be used on the event programme and website.', 'event-management-system' ); ?></p>

			<!-- Current Headshot -->
			<div class="ems-headshot-current" style="margin: 20px 0; text-align: center;">
				<?php if ( $headshot['url'] ) : ?>
					<img src="<?php echo esc_url( $headshot['url'] ); ?>" alt="<?php esc_attr_e( 'Current headshot', 'event-management-system' ); ?>" style="max-width: 250px; max-height: 250px; border-radius: 6px; border: 2px solid #c3c4c7; object-fit: cover;">
					<div style="margin-top: 8px; font-size: 13px; color: #646970;">
						<?php if ( 'pending_approval' === $headshot['status'] ) : ?>
							<span style="color: #dba617; font-weight: 600;"><?php esc_html_e( 'Pending your approval', 'event-management-system' ); ?></span>
							<?php if ( 'convenor' === $headshot['uploaded_by'] ) : ?>
								<br><small><?php esc_html_e( 'Uploaded by convenor', 'event-management-system' ); ?></small>
							<?php endif; ?>
						<?php elseif ( 'approved' === $headshot['status'] ) : ?>
							<span style="color: #00a32a; font-weight: 600;"><?php esc_html_e( 'Approved', 'event-management-system' ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( 'pending_approval' === $headshot['status'] && ! $is_admin ) : ?>
						<div style="margin-top: 12px;">
							<button type="button" class="ems-button ems-button-primary ems-headshot-approve" data-action="approve">
								<?php esc_html_e( 'Approve', 'event-management-system' ); ?>
							</button>
							<button type="button" class="ems-button ems-button-danger ems-headshot-approve" data-action="reject">
								<?php esc_html_e( 'Reject', 'event-management-system' ); ?>
							</button>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<div style="width: 200px; height: 200px; margin: 0 auto; background: #f0f0f1; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 2px dashed #c3c4c7;">
						<span style="color: #a7aaad; font-size: 14px;"><?php esc_html_e( 'No photo uploaded', 'event-management-system' ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<!-- Upload Form -->
			<div class="ems-headshot-upload" style="max-width: 500px; margin: 0 auto;">
				<h3><?php echo $headshot['url'] ? esc_html__( 'Replace Headshot', 'event-management-system' ) : esc_html__( 'Upload Headshot', 'event-management-system' ); ?></h3>
				<form id="ems-presenter-headshot-form" enctype="multipart/form-data">
					<input type="hidden" name="action" value="ems_save_presenter_headshot">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ems_public_nonce' ) ); ?>">
					<input type="hidden" name="presenter_user_id" value="<?php echo esc_attr( $user_id ); ?>">

					<div class="ems-form-group">
						<input type="file" name="headshot_file" accept="image/jpeg,image/png" required>
						<p class="description"><?php esc_html_e( 'Accepted formats: JPG, PNG. Max size: 5MB. Recommended: at least 400x400 pixels.', 'event-management-system' ); ?></p>
					</div>
					<button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Upload Photo', 'event-management-system' ); ?></button>
					<div class="ems-headshot-upload-status"></div>
				</form>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// JAVASCRIPT
	// =========================================================================

	/**
	 * Render portal JavaScript
	 *
	 * @since 1.7.0
	 * @param int $user_id Presenter user ID.
	 */
	private function render_portal_scripts( $user_id ) {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Tab navigation
			$('.ems-presenter-portal .ems-portal-tab').on('click', function() {
				var tabId = $(this).data('tab');
				$('.ems-presenter-portal .ems-portal-tab').removeClass('ems-portal-tab--active').attr('aria-selected', 'false');
				$(this).addClass('ems-portal-tab--active').attr('aria-selected', 'true');
				$('.ems-presenter-portal .ems-portal-tab-content').removeClass('ems-portal-tab-content--active');
				$('#ems-tab-' + tabId).addClass('ems-portal-tab-content--active');
				if (window.history && window.history.replaceState) {
					var url = new URL(window.location);
					url.searchParams.set('portal_tab', tabId);
					window.history.replaceState({}, '', url);
				}
			});

			// Profile edit toggle
			$('.ems-presenter-profile-edit-toggle').on('click', function() {
				$('.ems-presenter-profile-view').toggleClass('ems-hidden');
				$('.ems-presenter-profile-edit').toggleClass('ems-hidden');
			});

			// Save presenter bio
			$('#ems-presenter-profile-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $status = $form.find('.ems-presenter-profile-save-status');
				var $btn = $form.find('button[type="submit"]');
				$btn.prop('disabled', true);
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Saving profile...', 'event-management-system' ) ); ?></div>');
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: $form.serialize(),
					success: function(response) {
						if (response.success) {
							$status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
							setTimeout(function() { location.reload(); }, 1500);
						} else {
							$status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
						}
						$btn.prop('disabled', false);
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?></div>');
						$btn.prop('disabled', false);
					}
				});
			});

			// Toggle session files
			$('.ems-toggle-session-files').on('click', function() {
				var sessionId = $(this).data('session-id');
				$('#ems-session-files-' + sessionId).slideToggle();
			});

			// Upload presenter file
			$('.ems-presenter-file-upload-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $status = $form.find('.ems-upload-status');
				var $btn = $form.find('button[type="submit"]');
				var fileInput = $form.find('input[type="file"]')[0];
				if (!fileInput.files[0]) {
					$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'Please select a file.', 'event-management-system' ) ); ?></div>');
					return;
				}
				var formData = new FormData();
				formData.append('action', 'ems_upload_presenter_file');
				formData.append('nonce', ems_public.nonce);
				formData.append('session_id', $form.data('session-id'));
				formData.append('event_id', $form.data('event-id'));
				formData.append('presenter_file', fileInput.files[0]);
				formData.append('file_category', $form.find('select[name="file_category"]').val());
				formData.append('file_description', $form.find('textarea[name="file_description"]').val());
				$btn.prop('disabled', true);
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Uploading...', 'event-management-system' ) ); ?></div>');
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							$status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
							setTimeout(function() { location.reload(); }, 1500);
						} else {
							$status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
							$btn.prop('disabled', false);
						}
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?></div>');
						$btn.prop('disabled', false);
					}
				});
			});

			// Delete presenter file
			$('.ems-delete-presenter-file').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this file?', 'event-management-system' ) ); ?>')) return;
				var fileId = $(this).data('file-id');
				var $row = $(this).closest('tr');
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_delete_presenter_file',
						nonce: ems_public.nonce,
						file_id: fileId
					},
					success: function(response) {
						if (response.success) { $row.fadeOut(function() { $row.remove(); }); }
						else { alert(response.data); }
					},
					error: function() { alert('<?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?>'); }
				});
			});

			// Headshot upload
			$('#ems-presenter-headshot-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $status = $form.find('.ems-headshot-upload-status');
				var $btn = $form.find('button[type="submit"]');
				var fileInput = $form.find('input[type="file"]')[0];
				if (!fileInput.files[0]) return;
				var formData = new FormData($form[0]);
				$btn.prop('disabled', true);
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Uploading...', 'event-management-system' ) ); ?></div>');
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							$status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
							setTimeout(function() { location.reload(); }, 1500);
						} else {
							$status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
							$btn.prop('disabled', false);
						}
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?></div>');
						$btn.prop('disabled', false);
					}
				});
			});

			// Headshot approve/reject
			$('.ems-headshot-approve').on('click', function() {
				var action = $(this).data('action');
				if ('reject' === action && !confirm('<?php echo esc_js( __( 'Are you sure you want to reject this headshot?', 'event-management-system' ) ); ?>')) return;
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: {
						action: 'ems_respond_presenter_headshot',
						nonce: ems_public.nonce,
						presenter_user_id: <?php echo absint( $user_id ); ?>,
						headshot_action: action
					},
					success: function(response) {
						if (response.success) { location.reload(); }
						else { alert(response.data); }
					}
				});
			});

			// Availability form
			$('.ems-presenter-availability-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $status = $form.find('.ems-availability-save-status');
				var $btn = $form.find('button[type="submit"]');
				var eventId = $form.data('event-id');
				$btn.prop('disabled', true);
				$status.html('<div class="ems-notice ems-notice-info"><?php echo esc_js( __( 'Saving...', 'event-management-system' ) ); ?></div>');
				var formData = $form.serializeArray();
				formData.push({name: 'action', value: 'ems_save_presenter_availability'});
				formData.push({name: 'nonce', value: ems_public.nonce});
				formData.push({name: 'event_id', value: eventId});
				formData.push({name: 'presenter_user_id', value: <?php echo absint( $user_id ); ?>});
				$.ajax({
					url: ems_public.ajax_url,
					type: 'POST',
					data: $.param(formData),
					success: function(response) {
						if (response.success) {
							$status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
						} else {
							$status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
						}
						$btn.prop('disabled', false);
					},
					error: function() {
						$status.html('<div class="ems-notice ems-notice-error"><?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?></div>');
						$btn.prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
	}

	// =========================================================================
	// AJAX HANDLERS
	// =========================================================================

	/**
	 * AJAX: Save presenter bio
	 *
	 * @since 1.7.0
	 */
	public function ajax_save_presenter_bio() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		$user_id = isset( $_POST['presenter_user_id'] ) ? absint( $_POST['presenter_user_id'] ) : 0;
		$bio     = isset( $_POST['bio'] ) ? $_POST['bio'] : array();

		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid presenter.', 'event-management-system' ) );
		}

		// Verify permission
		if ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_ems_events' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'event-management-system' ) );
		}

		$result = $this->presenter_portal->update_presenter_bio( $user_id, $bio );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Upload presenter file
	 *
	 * @since 1.7.0
	 */
	public function ajax_upload_presenter_file() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		if ( empty( $_FILES['presenter_file'] ) ) {
			wp_send_json_error( __( 'No file provided.', 'event-management-system' ) );
		}

		$session_id  = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
		$event_id    = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$category    = isset( $_POST['file_category'] ) ? sanitize_text_field( $_POST['file_category'] ) : 'slides';
		$description = isset( $_POST['file_description'] ) ? sanitize_textarea_field( $_POST['file_description'] ) : '';

		$result = $this->presenter_portal->upload_presenter_file(
			$_FILES['presenter_file'],
			$session_id,
			$event_id,
			$category,
			array( 'description' => $description )
		);

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Delete presenter file
	 *
	 * @since 1.7.0
	 */
	public function ajax_delete_presenter_file() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		$file_id = isset( $_POST['file_id'] ) ? absint( $_POST['file_id'] ) : 0;

		if ( ! $file_id ) {
			wp_send_json_error( __( 'Invalid file.', 'event-management-system' ) );
		}

		$result = $this->presenter_portal->delete_presenter_file( $file_id );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Save presenter headshot
	 *
	 * @since 1.7.0
	 */
	public function ajax_save_presenter_headshot() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		$user_id = isset( $_POST['presenter_user_id'] ) ? absint( $_POST['presenter_user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid presenter.', 'event-management-system' ) );
		}

		if ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_ems_events' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'event-management-system' ) );
		}

		if ( empty( $_FILES['headshot_file'] ) ) {
			wp_send_json_error( __( 'No file provided.', 'event-management-system' ) );
		}

		// Validate the image
		$file = $_FILES['headshot_file'];
		$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
			wp_send_json_error( __( 'Only JPG and PNG images are accepted.', 'event-management-system' ) );
		}

		if ( $file['size'] > 5242880 ) {
			wp_send_json_error( __( 'Image must be under 5MB.', 'event-management-system' ) );
		}

		// Use WordPress media handling
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'headshot_file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		}

		$uploaded_by = ( current_user_can( 'manage_options' ) || current_user_can( 'manage_ems_events' ) ) && get_current_user_id() !== $user_id
			? 'convenor'
			: 'presenter';

		$result = $this->presenter_portal->update_presenter_headshot( $user_id, $attachment_id, $uploaded_by );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Respond to headshot (approve/reject)
	 *
	 * @since 1.7.0
	 */
	public function ajax_respond_presenter_headshot() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		$user_id = isset( $_POST['presenter_user_id'] ) ? absint( $_POST['presenter_user_id'] ) : 0;
		$action  = isset( $_POST['headshot_action'] ) ? sanitize_text_field( $_POST['headshot_action'] ) : '';

		if ( ! $user_id || ! in_array( $action, array( 'approve', 'reject' ), true ) ) {
			wp_send_json_error( __( 'Invalid request.', 'event-management-system' ) );
		}

		// Only the presenter themselves can approve/reject
		if ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'event-management-system' ) );
		}

		$result = $this->presenter_portal->respond_to_headshot( $user_id, $action );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Save availability preferences
	 *
	 * @since 1.7.0
	 */
	public function ajax_save_presenter_availability() {
		check_ajax_referer( 'ems_public_nonce', 'nonce' );

		$user_id  = isset( $_POST['presenter_user_id'] ) ? absint( $_POST['presenter_user_id'] ) : 0;
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$slots    = isset( $_POST['slots'] ) ? $_POST['slots'] : array();

		if ( ! $user_id || ! $event_id ) {
			wp_send_json_error( __( 'Invalid request.', 'event-management-system' ) );
		}

		if ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_ems_events' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'event-management-system' ) );
		}

		$result = $this->presenter_portal->save_presenter_availability( $user_id, $event_id, $slots );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}
}
