<?php
/**
 * Admin functionality
 *
 * Handles all admin-side functionality including:
 * - Dashboard with statistics
 * - Registration management
 * - Abstract review integration
 * - Settings management
 * - AJAX handlers
 *
 * @package    EventManagementSystem
 * @subpackage Admin
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin Class
 *
 * @since 1.0.0
 */
class EMS_Admin {
	
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin name.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->logger      = EMS_Logger::instance();
	}

	/**
	 * Enqueue admin styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, EMS_PLUGIN_URL . 'admin/css/ems-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, EMS_PLUGIN_URL . 'admin/js/ems-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'ems_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ems_admin_nonce' ),
		) );
	}

	/**
	 * Add admin menu pages
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Main menu
		add_menu_page(
			__( 'Event Management', 'event-management-system' ),
			__( 'Events', 'event-management-system' ),
			'manage_options',
			'ems-dashboard',
			array( $this, 'display_dashboard' ),
			'dashicons-calendar-alt',
			6
		);

		// Dashboard submenu
		add_submenu_page(
			'ems-dashboard',
			__( 'Dashboard', 'event-management-system' ),
			__( 'Dashboard', 'event-management-system' ),
			'manage_options',
			'ems-dashboard',
			array( $this, 'display_dashboard' )
		);

		// All Events submenu (links to CPT)
		add_submenu_page(
			'ems-dashboard',
			__( 'All Events', 'event-management-system' ),
			__( 'All Events', 'event-management-system' ),
			'manage_options',
			'edit.php?post_type=ems_event'
		);

		// Add New Event
		add_submenu_page(
			'ems-dashboard',
			__( 'Add New Event', 'event-management-system' ),
			__( 'Add New', 'event-management-system' ),
			'manage_options',
			'post-new.php?post_type=ems_event'
		);

		// Registrations
		add_submenu_page(
			'ems-dashboard',
			__( 'Registrations', 'event-management-system' ),
			__( 'Registrations', 'event-management-system' ),
			'manage_options',
			'ems-registrations',
			array( $this, 'display_registrations' )
		);

		// Sessions
		add_submenu_page(
			'ems-dashboard',
			__( 'Sessions', 'event-management-system' ),
			__( 'Sessions', 'event-management-system' ),
			'manage_options',
			'edit.php?post_type=ems_session'
		);

		// Abstracts submenu (links to CPT)
		add_submenu_page(
			'ems-dashboard',
			__( 'Abstracts', 'event-management-system' ),
			__( 'Abstracts', 'event-management-system' ),
			'manage_options',
			'edit.php?post_type=ems_abstract'
		);

		// Abstract Review Management
		add_submenu_page(
			'ems-dashboard',
			__( 'Abstract Review', 'event-management-system' ),
			__( 'Abstract Review', 'event-management-system' ),
			'manage_options',
			'ems-abstract-review',
			array( $this, 'display_abstract_review' )
		);

		// Reviewer Dashboard
		add_submenu_page(
			'ems-dashboard',
			__( 'My Reviews', 'event-management-system' ),
			__( 'My Reviews', 'event-management-system' ),
			'manage_options',
			'ems-reviewer-dashboard',
			array( $this, 'display_reviewer_dashboard' )
		);

		// Settings
		add_submenu_page(
			'ems-dashboard',
			__( 'Settings', 'event-management-system' ),
			__( 'Settings', 'event-management-system' ),
			'manage_options',
			'ems-settings',
			array( $this, 'display_settings' )
		);

		// User Management
		add_submenu_page(
			'ems-dashboard',
			__( 'User Roles', 'event-management-system' ),
			__( 'User Roles', 'event-management-system' ),
			'manage_options',
			'ems-user-roles',
			array( $this, 'display_user_roles' )
		);

		// Add Registration (hidden page - accessed via link)
		add_submenu_page(
			null, // Hidden from menu
			__( 'Add Registration', 'event-management-system' ),
			__( 'Add Registration', 'event-management-system' ),
			'manage_options',
			'ems-add-registration',
			array( $this, 'display_add_registration' )
		);

		// Abstract Detail (hidden page - accessed via link)
		add_submenu_page(
			null, // Hidden from menu
			__( 'Abstract Details', 'event-management-system' ),
			__( 'Abstract Details', 'event-management-system' ),
			'manage_options',
			'ems-abstract-detail',
			array( $this, 'display_abstract_detail' )
		);

		// Add Sponsor (visible submenu)
		add_submenu_page(
			'ems-dashboard',
			__( 'Add Sponsor', 'event-management-system' ),
			__( 'Add Sponsor', 'event-management-system' ),
			'manage_options',
			'ems-add-sponsor',
			array( $this, 'display_add_sponsor' )
		);

		// EOI Submissions (visible submenu)
		add_submenu_page(
			'ems-dashboard',
			__( 'EOI Submissions', 'event-management-system' ),
			__( 'EOI Submissions', 'event-management-system' ),
			'manage_options',
			'ems-eoi-submissions',
			array( $this, 'display_eoi_submissions' )
		);

		// EOI Detail (hidden page - accessed via link)
		add_submenu_page(
			null, // Hidden from menu
			__( 'EOI Details', 'event-management-system' ),
			__( 'EOI Details', 'event-management-system' ),
			'manage_options',
			'ems-eoi-detail',
			array( $this, 'display_eoi_detail' )
		);
	}

	// ==========================================
	// DASHBOARD
	// ==========================================

	/**
	 * Display main dashboard
	 *
	 * Shows overview statistics and quick actions.
	 *
	 * @since 1.0.0
	 */
	public function display_dashboard() {
		global $wpdb;
		
		// Get statistics
		$stats = $this->get_dashboard_stats();
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Event Management Dashboard', 'event-management-system' ); ?></h1>
			
			<!-- Quick Stats -->
			<div class="ems-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
				
				<div class="ems-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo esc_html( $stats['total_events'] ); ?></div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Total Events', 'event-management-system' ); ?></div>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ems_event' ) ); ?>" style="display: inline-block; margin-top: 10px; font-size: 12px;">
						<?php esc_html_e( 'View All →', 'event-management-system' ); ?>
					</a>
				</div>
				
				<div class="ems-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #5cb85c; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="font-size: 32px; font-weight: bold; color: #5cb85c;"><?php echo esc_html( $stats['total_registrations'] ); ?></div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Total Registrations', 'event-management-system' ); ?></div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-registrations' ) ); ?>" style="display: inline-block; margin-top: 10px; font-size: 12px;">
						<?php esc_html_e( 'Manage →', 'event-management-system' ); ?>
					</a>
				</div>
				
				<div class="ems-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #f0ad4e; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="font-size: 32px; font-weight: bold; color: #f0ad4e;"><?php echo esc_html( $stats['pending_abstracts'] ); ?></div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Abstracts', 'event-management-system' ); ?></div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-abstract-review' ) ); ?>" style="display: inline-block; margin-top: 10px; font-size: 12px;">
						<?php esc_html_e( 'Review →', 'event-management-system' ); ?>
					</a>
				</div>
				
				<div class="ems-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #5bc0de; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<div style="font-size: 32px; font-weight: bold; color: #5bc0de;"><?php echo esc_html( $stats['total_sessions'] ); ?></div>
					<div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Total Sessions', 'event-management-system' ); ?></div>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ems_session' ) ); ?>" style="display: inline-block; margin-top: 10px; font-size: 12px;">
						<?php esc_html_e( 'View All →', 'event-management-system' ); ?>
					</a>
				</div>
				
			</div>

			<!-- Quick Actions & Recent Activity -->
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
				
				<!-- Quick Actions -->
				<div class="ems-dashboard-section" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Quick Actions', 'event-management-system' ); ?></h2>
					
					<div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ems_event' ) ); ?>" class="button button-primary" style="text-align: center;">
							<?php esc_html_e( '+ Create New Event', 'event-management-system' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ems_session' ) ); ?>" class="button" style="text-align: center;">
							<?php esc_html_e( '+ Add Session', 'event-management-system' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-registrations' ) ); ?>" class="button" style="text-align: center;">
							<?php esc_html_e( 'View Registrations', 'event-management-system' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-abstract-review' ) ); ?>" class="button" style="text-align: center;">
							<?php esc_html_e( 'Review Abstracts', 'event-management-system' ); ?>
						</a>
					</div>
				</div>
				
				<!-- Recent Registrations -->
				<div class="ems-dashboard-section" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Recent Registrations', 'event-management-system' ); ?></h2>
					
					<?php
					$recent_registrations = $wpdb->get_results(
						"SELECT r.*, e.post_title as event_title 
						FROM {$wpdb->prefix}ems_registrations r
						LEFT JOIN {$wpdb->posts} e ON r.event_id = e.ID
						ORDER BY r.registration_date DESC 
						LIMIT 5"
					);
					
					if ( $recent_registrations ) :
					?>
						<table class="widefat" style="margin-top: 15px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'event-management-system' ); ?></th>
									<th><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
									<th><?php esc_html_e( 'Date', 'event-management-system' ); ?></th>
									<th><?php esc_html_e( 'Status', 'event-management-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_registrations as $reg ) : ?>
									<tr>
										<td><?php echo esc_html( $reg->first_name . ' ' . $reg->last_name ); ?></td>
										<td><?php echo esc_html( $reg->event_title ?: '—' ); ?></td>
										<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $reg->registration_date ) ) ); ?></td>
										<td>
											<span class="ems-status-badge ems-status-<?php echo esc_attr( $reg->status ); ?>">
												<?php echo esc_html( ucfirst( $reg->status ) ); ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="color: #666; margin-top: 15px;"><?php esc_html_e( 'No registrations yet.', 'event-management-system' ); ?></p>
					<?php endif; ?>
				</div>
				
			</div>
			
			<!-- Upcoming Events -->
			<div class="ems-dashboard-section" style="background: #fff; padding: 20px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Upcoming Events', 'event-management-system' ); ?></h2>
				
				<?php
				$upcoming_events = get_posts( array(
					'post_type'      => 'ems_event',
					'posts_per_page' => 5,
					'orderby'        => 'date',
					'order'          => 'DESC',
				) );
				
				if ( $upcoming_events ) :
				?>
					<table class="widefat" style="margin-top: 15px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Date', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Location', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Registrations', 'event-management-system' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $upcoming_events as $event ) : 
								$reg_count = $wpdb->get_var( $wpdb->prepare(
									"SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations WHERE event_id = %d AND status = 'active'",
									$event->ID
								) );
								$max_capacity = get_post_meta( $event->ID, 'event_max_capacity', true );
								$event_date = get_post_meta( $event->ID, 'event_start_date', true );
							?>
								<tr>
									<td><strong><?php echo esc_html( $event->post_title ); ?></strong></td>
									<td><?php echo $event_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event_date ) ) ) : '—'; ?></td>
									<td><?php echo esc_html( get_post_meta( $event->ID, 'event_location', true ) ?: '—' ); ?></td>
									<td>
										<?php echo esc_html( $reg_count ); ?>
										<?php if ( $max_capacity ) : ?>
											/ <?php echo esc_html( $max_capacity ); ?>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'event-management-system' ); ?></a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-registrations&event_id=' . $event->ID ) ); ?>" class="button button-small"><?php esc_html_e( 'Registrations', 'event-management-system' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p style="color: #666; margin-top: 15px;"><?php esc_html_e( 'No events found. Create your first event!', 'event-management-system' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ems_event' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Create Event', 'event-management-system' ); ?></a>
				<?php endif; ?>
			</div>
			
		</div>
		
		<style>
		.ems-status-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 500;
		}
		.ems-status-active { background: #d4edda; color: #155724; }
		.ems-status-pending { background: #fff3cd; color: #856404; }
		.ems-status-cancelled { background: #f8d7da; color: #721c24; }
		</style>
		<?php
	}

	/**
	 * Get dashboard statistics
	 *
	 * @since 1.0.0
	 * @return array Statistics array.
	 */
	private function get_dashboard_stats() {
		global $wpdb;
		
		$total_events = wp_count_posts( 'ems_event' );
		$total_sessions = wp_count_posts( 'ems_session' );
		$total_abstracts = wp_count_posts( 'ems_abstract' );
		
		return array(
			'total_events'        => isset( $total_events->publish ) ? $total_events->publish : 0,
			'total_sessions'      => isset( $total_sessions->publish ) ? $total_sessions->publish : 0,
			'total_registrations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations" ),
			'pending_abstracts'   => isset( $total_abstracts->publish ) ? $total_abstracts->publish : 0,
		);
	}

	// ==========================================
	// REGISTRATIONS
	// ==========================================

	/**
	 * Display registrations management page
	 *
	 * @since 1.0.0
	 */
	public function display_registrations() {
		global $wpdb;
		
		// Handle actions
		$this->handle_registration_actions();
		
		// Get filter parameters
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		$status   = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$paged    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = 20;
		$offset   = ( $paged - 1 ) * $per_page;
		
		// Build query
		$where = array( '1=1' );
		$params = array();
		
		if ( $event_id ) {
			$where[] = 'r.event_id = %d';
			$params[] = $event_id;
		}
		
		if ( $status ) {
			$where[] = 'r.status = %s';
			$params[] = $status;
		}
		
		if ( $search ) {
			$where[] = '(r.first_name LIKE %s OR r.last_name LIKE %s OR r.email LIKE %s)';
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $search_like;
			$params[] = $search_like;
			$params[] = $search_like;
		}
		
		$where_clause = implode( ' AND ', $where );
		
		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations r WHERE {$where_clause}";
		$total = empty( $params ) ? $wpdb->get_var( $count_query ) : $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );
		
		// Get registrations
		$query = "SELECT r.*, e.post_title as event_title 
				  FROM {$wpdb->prefix}ems_registrations r
				  LEFT JOIN {$wpdb->posts} e ON r.event_id = e.ID
				  WHERE {$where_clause}
				  ORDER BY r.registration_date DESC
				  LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;
		
		$registrations = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
		
		// Get all events for filter
		$events = get_posts( array(
			'post_type'      => 'ems_event',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		
		$total_pages = ceil( $total / $per_page );
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Event Registrations', 'event-management-system' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-add-registration' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'event-management-system' ); ?></a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ems-registrations&action=export' . ( $event_id ? '&event_id=' . $event_id : '' ) ), 'ems_export_registrations' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'event-management-system' ); ?></a>
			<hr class="wp-header-end">
			
			<!-- Filters -->
			<div class="tablenav top">
				<form method="get">
					<input type="hidden" name="page" value="ems-registrations">
					
					<div class="alignleft actions">
						<select name="event_id">
							<option value=""><?php esc_html_e( 'All Events', 'event-management-system' ); ?></option>
							<?php foreach ( $events as $event ) : ?>
								<option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event_id, $event->ID ); ?>>
									<?php echo esc_html( $event->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						
						<select name="status">
							<option value=""><?php esc_html_e( 'All Statuses', 'event-management-system' ); ?></option>
							<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'event-management-system' ); ?></option>
							<option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'event-management-system' ); ?></option>
							<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'event-management-system' ); ?></option>
						</select>
						
						<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'event-management-system' ); ?>">
					</div>
					
					<div class="alignright">
						<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search registrations...', 'event-management-system' ); ?>">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'event-management-system' ); ?>">
					</div>
				</form>
			</div>
			
			<!-- Results -->
			<?php if ( $registrations ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 5%;"><?php esc_html_e( 'ID', 'event-management-system' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Name', 'event-management-system' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Email', 'event-management-system' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Ticket', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Date', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Status', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $registrations as $reg ) : ?>
							<tr>
								<td><?php echo esc_html( $reg->id ); ?></td>
								<td>
									<strong><?php echo esc_html( $reg->first_name . ' ' . $reg->last_name ); ?></strong>
									<?php if ( ! empty( $reg->discipline ) ) : ?>
										<br><small style="color: #666;"><?php echo esc_html( $reg->discipline ); ?></small>
									<?php endif; ?>
								</td>
								<td><a href="mailto:<?php echo esc_attr( $reg->email ); ?>"><?php echo esc_html( $reg->email ); ?></a></td>
								<td><?php echo esc_html( $reg->event_title ?: '—' ); ?></td>
								<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $reg->ticket_type ) ) ); ?></td>
								<td>$<?php echo esc_html( number_format( $reg->amount_paid, 2 ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $reg->registration_date ) ) ); ?></td>
								<td>
									<span class="ems-status-badge ems-status-<?php echo esc_attr( $reg->status ); ?>">
										<?php echo esc_html( ucfirst( $reg->status ) ); ?>
									</span>
								</td>
								<td>
									<?php if ( 'active' === $reg->status ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ems-registrations&action=cancel&id=' . $reg->id ), 'cancel_registration_' . $reg->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this registration?', 'event-management-system' ); ?>');"><?php esc_html_e( 'Cancel', 'event-management-system' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php printf( esc_html__( '%s items', 'event-management-system' ), number_format_i18n( $total ) ); ?></span>
							<?php
							echo paginate_links( array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $paged,
							) );
							?>
						</div>
					</div>
				<?php endif; ?>
				
			<?php else : ?>
				<div class="notice notice-info inline" style="margin-top: 20px;">
					<p><?php esc_html_e( 'No registrations found.', 'event-management-system' ); ?></p>
				</div>
			<?php endif; ?>
			
		</div>
		
		<style>
		.ems-status-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 500;
		}
		.ems-status-active { background: #d4edda; color: #155724; }
		.ems-status-pending { background: #fff3cd; color: #856404; }
		.ems-status-cancelled { background: #f8d7da; color: #721c24; }
		</style>
		<?php
	}

	/**
	 * Handle registration actions (cancel, export)
	 *
	 * @since 1.0.0
	 */
	private function handle_registration_actions() {
		global $wpdb;

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		if ( 'cancel' === $action && isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			check_admin_referer( 'cancel_registration_' . $id );

			// Get reason from URL if provided
			$reason = isset( $_GET['reason'] ) ? sanitize_textarea_field( $_GET['reason'] ) : '';

			// Use the Registration class to properly handle cancellation
			$registration_handler = new EMS_Registration();
			$result = $registration_handler->cancel_registration( $id, 'admin', $reason );

			if ( $result['success'] ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Registration cancelled successfully. A notification email has been sent to the registrant.', 'event-management-system' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result['message'] ) . '</p></div>';
			}
		}

		if ( 'export' === $action ) {
			check_admin_referer( 'ems_export_registrations' );
			$this->export_registrations_csv();
		}
	}

	/**
	 * Export registrations to CSV
	 *
	 * @since 1.0.0
	 */
	private function export_registrations_csv() {
		global $wpdb;
		
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		
		$where = $event_id ? $wpdb->prepare( 'WHERE event_id = %d', $event_id ) : '';
		$registrations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ems_registrations {$where} ORDER BY registration_date DESC" );
		
		$filename = 'registrations-' . date( 'Y-m-d' ) . '.csv';
		
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		
		$output = fopen( 'php://output', 'w' );
		
		// Header row
		fputcsv( $output, array( 'ID', 'First Name', 'Last Name', 'Email', 'Discipline', 'Specialty', 'Seniority', 'Ticket Type', 'Amount', 'Payment Status', 'Registration Date', 'Status' ) );
		
		// Data rows
		foreach ( $registrations as $reg ) {
			fputcsv( $output, array(
				$reg->id,
				$reg->first_name,
				$reg->last_name,
				$reg->email,
				$reg->discipline,
				$reg->specialty,
				$reg->seniority,
				$reg->ticket_type,
				$reg->amount_paid,
				$reg->payment_status,
				$reg->registration_date,
				$reg->status,
			) );
		}
		
		fclose( $output );
		exit;
	}

	// ==========================================
	// ABSTRACT REVIEW
	// ==========================================

	/**
	 * Display abstract review management page
	 *
	 * @since 1.0.0
	 */
	public function display_abstract_review() {
		require_once EMS_PLUGIN_DIR . 'admin/views/abstract-review.php';
	}

	/**
	 * Display reviewer dashboard
	 *
	 * @since 1.0.0
	 */
	public function display_reviewer_dashboard() {
		require_once EMS_PLUGIN_DIR . 'admin/views/reviewer-dashboard.php';
	}

	// ==========================================
	// SETTINGS
	// ==========================================

	/**
	 * Display settings page
	 *
	 * @since 1.0.0
	 */
	public function display_settings() {
		// Handle form submission for settings
		if ( isset( $_POST['ems_save_settings'] ) && check_admin_referer( 'ems_settings_nonce' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'event-management-system' ) . '</p></div>';
		}
		
		// Handle Create Missing Pages
		if ( isset( $_POST['ems_create_missing_pages'] ) && check_admin_referer( 'ems_create_pages_nonce', 'ems_pages_nonce' ) ) {
			$created = $this->create_missing_pages();
			if ( $created > 0 ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . 
				     sprintf( 
					     /* translators: %d: number of pages created */
					     _n( '%d page created successfully.', '%d pages created successfully.', $created, 'event-management-system' ),
					     $created 
				     ) . '</p></div>';
			} else {
				echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'All standard pages already exist.', 'event-management-system' ) . '</p></div>';
			}
		}
		
		// Get current settings
		$settings = $this->get_settings();
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Event Management Settings', 'event-management-system' ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'ems_settings_nonce' ); ?>
				
				<div class="ems-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'General Settings', 'event-management-system' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row"><label for="ems_email_from_name"><?php esc_html_e( 'Email From Name', 'event-management-system' ); ?></label></th>
							<td>
								<input type="text" name="ems_email_from_name" id="ems_email_from_name" value="<?php echo esc_attr( $settings['email_from_name'] ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Name displayed in email notifications.', 'event-management-system' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ems_email_from_address"><?php esc_html_e( 'Email From Address', 'event-management-system' ); ?></label></th>
							<td>
								<input type="email" name="ems_email_from_address" id="ems_email_from_address" value="<?php echo esc_attr( $settings['email_from_address'] ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Email address used for sending notifications.', 'event-management-system' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="ems-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Registration Settings', 'event-management-system' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Waitlist', 'event-management-system' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ems_enable_waitlist" value="1" <?php checked( $settings['enable_waitlist'], true ); ?>>
									<?php esc_html_e( 'Allow participants to join waitlist when events are full', 'event-management-system' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Auto-Promote Waitlist', 'event-management-system' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ems_waitlist_auto_promote" value="1" <?php checked( $settings['waitlist_auto_promote'], true ); ?>>
									<?php esc_html_e( 'Automatically promote waitlist participants when spots open', 'event-management-system' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Registration Confirmation', 'event-management-system' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ems_registration_confirmation" value="1" <?php checked( $settings['registration_confirmation'], true ); ?>>
									<?php esc_html_e( 'Send email confirmation on successful registration', 'event-management-system' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ems_payment_gateway"><?php esc_html_e( 'Payment Method', 'event-management-system' ); ?></label></th>
							<td>
								<select name="ems_payment_gateway" id="ems_payment_gateway">
									<option value="invoice" <?php selected( $settings['payment_gateway'], 'invoice' ); ?>><?php esc_html_e( 'Invoice', 'event-management-system' ); ?></option>
									<option value="stripe" <?php selected( $settings['payment_gateway'], 'stripe' ); ?>><?php esc_html_e( 'Stripe', 'event-management-system' ); ?></option>
									<option value="paypal" <?php selected( $settings['payment_gateway'], 'paypal' ); ?>><?php esc_html_e( 'PayPal', 'event-management-system' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="ems-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Abstract Settings', 'event-management-system' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row"><label for="ems_abstract_min_reviewers"><?php esc_html_e( 'Minimum Reviewers', 'event-management-system' ); ?></label></th>
							<td>
								<input type="number" name="ems_abstract_min_reviewers" id="ems_abstract_min_reviewers" value="<?php echo esc_attr( $settings['abstract_min_reviewers'] ); ?>" min="1" max="10" class="small-text">
								<p class="description"><?php esc_html_e( 'Minimum number of reviewers required per abstract.', 'event-management-system' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ems_max_file_size"><?php esc_html_e( 'Max File Size (MB)', 'event-management-system' ); ?></label></th>
							<td>
								<input type="number" name="ems_max_file_size" id="ems_max_file_size" value="<?php echo esc_attr( $settings['max_file_size'] / 1048576 ); ?>" min="1" max="50" class="small-text">
								<p class="description"><?php esc_html_e( 'Maximum file size for uploads.', 'event-management-system' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="ems-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Logging Settings', 'event-management-system' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Logging', 'event-management-system' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ems_logging_enabled" value="1" <?php checked( $settings['logging_enabled'], true ); ?>>
									<?php esc_html_e( 'Enable debug logging', 'event-management-system' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ems_log_retention_days"><?php esc_html_e( 'Log Retention (Days)', 'event-management-system' ); ?></label></th>
							<td>
								<input type="number" name="ems_log_retention_days" id="ems_log_retention_days" value="<?php echo esc_attr( $settings['log_retention_days'] ); ?>" min="1" max="365" class="small-text">
								<p class="description"><?php esc_html_e( 'Number of days to keep log files.', 'event-management-system' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<p class="submit">
					<input type="submit" name="ems_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'event-management-system' ); ?>">
				</p>
			</form>
			
			<!-- Pages Management Section (Outside main form) -->
			<div class="ems-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Standard Pages', 'event-management-system' ); ?></h2>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'The Event Management System creates standard pages with shortcodes on activation. Use this section to verify page status or recreate missing pages.', 'event-management-system' ); ?>
				</p>
				
				<?php
				// Define standard pages
				$standard_pages = array(
					'ems_page_events' => array(
						'title'     => __( 'Events', 'event-management-system' ),
						'shortcode' => '[ems_upcoming_events]',
						'slug'      => 'events',
					),
					'ems_page_my_registrations' => array(
						'title'     => __( 'My Registrations', 'event-management-system' ),
						'shortcode' => '[ems_my_registrations]',
						'slug'      => 'my-registrations',
					),
					'ems_page_my_schedule' => array(
						'title'     => __( 'My Schedule', 'event-management-system' ),
						'shortcode' => '[ems_my_schedule]',
						'slug'      => 'my-schedule',
					),
					'ems_page_presenter_dashboard' => array(
						'title'     => __( 'Presenter Dashboard', 'event-management-system' ),
						'shortcode' => '[ems_presenter_dashboard]',
						'slug'      => 'presenter-dashboard',
					),
					'ems_page_submit_abstract' => array(
						'title'     => __( 'Submit Abstract', 'event-management-system' ),
						'shortcode' => __( '(informational)', 'event-management-system' ),
						'slug'      => 'submit-abstract',
					),
				);
				?>
				
				<table class="widefat" style="margin-bottom: 15px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Page', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Status', 'event-management-system' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $standard_pages as $option_key => $page_info ) : 
							$page_id = get_option( $option_key );
							$page_exists = false;
							$page_status = '';
							$page_url = '';
							
							if ( $page_id ) {
								$page = get_post( $page_id );
								if ( $page && 'trash' !== $page->post_status ) {
									$page_exists = true;
									$page_status = $page->post_status;
									$page_url = get_permalink( $page_id );
								}
							}
						?>
							<tr>
								<td><strong><?php echo esc_html( $page_info['title'] ); ?></strong></td>
								<td><code><?php echo esc_html( $page_info['shortcode'] ); ?></code></td>
								<td>
									<?php if ( $page_exists ) : ?>
										<span style="color: #00a32a;">
											<span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
											<?php 
											if ( 'publish' === $page_status ) {
												esc_html_e( 'Published', 'event-management-system' );
											} else {
												echo esc_html( ucfirst( $page_status ) );
											}
											?>
										</span>
									<?php else : ?>
										<span style="color: #d63638;">
											<span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
											<?php esc_html_e( 'Missing', 'event-management-system' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $page_exists ) : ?>
										<a href="<?php echo esc_url( $page_url ); ?>" class="button button-small" target="_blank">
											<?php esc_html_e( 'View', 'event-management-system' ); ?>
										</a>
										<a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'event-management-system' ); ?>
										</a>
									<?php else : ?>
										<span class="description"><?php esc_html_e( 'Use button below to create', 'event-management-system' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<form method="post" action="" style="display: inline-block;">
					<?php wp_nonce_field( 'ems_create_pages_nonce', 'ems_pages_nonce' ); ?>
					<input type="submit" name="ems_create_missing_pages" class="button button-secondary" value="<?php esc_attr_e( 'Create Missing Pages', 'event-management-system' ); ?>">
				</form>
				
				<p class="description" style="margin-top: 10px;">
					<?php esc_html_e( 'Note: This will only create pages that are missing. Existing pages will not be modified.', 'event-management-system' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get current settings
	 *
	 * @since 1.0.0
	 * @return array Settings array.
	 */
	private function get_settings() {
		return array(
			'email_from_name'          => get_option( 'ems_email_from_name', get_bloginfo( 'name' ) ),
			'email_from_address'       => get_option( 'ems_email_from_address', get_option( 'admin_email' ) ),
			'enable_waitlist'          => get_option( 'ems_enable_waitlist', true ),
			'waitlist_auto_promote'    => get_option( 'ems_waitlist_auto_promote', true ),
			'registration_confirmation'=> get_option( 'ems_registration_confirmation', true ),
			'payment_gateway'          => get_option( 'ems_payment_gateway', 'invoice' ),
			'abstract_min_reviewers'   => get_option( 'ems_abstract_min_reviewers', 2 ),
			'max_file_size'            => get_option( 'ems_max_file_size', 10485760 ),
			'logging_enabled'          => get_option( 'ems_logging_enabled', true ),
			'log_retention_days'       => get_option( 'ems_log_retention_days', 30 ),
		);
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 */
	private function save_settings() {
		update_option( 'ems_email_from_name', sanitize_text_field( $_POST['ems_email_from_name'] ?? '' ) );
		update_option( 'ems_email_from_address', sanitize_email( $_POST['ems_email_from_address'] ?? '' ) );
		update_option( 'ems_enable_waitlist', isset( $_POST['ems_enable_waitlist'] ) ? true : false );
		update_option( 'ems_waitlist_auto_promote', isset( $_POST['ems_waitlist_auto_promote'] ) ? true : false );
		update_option( 'ems_registration_confirmation', isset( $_POST['ems_registration_confirmation'] ) ? true : false );
		update_option( 'ems_payment_gateway', sanitize_text_field( $_POST['ems_payment_gateway'] ?? 'invoice' ) );
		update_option( 'ems_abstract_min_reviewers', absint( $_POST['ems_abstract_min_reviewers'] ?? 2 ) );
		update_option( 'ems_max_file_size', absint( $_POST['ems_max_file_size'] ?? 10 ) * 1048576 );
		update_option( 'ems_logging_enabled', isset( $_POST['ems_logging_enabled'] ) ? true : false );
		update_option( 'ems_log_retention_days', absint( $_POST['ems_log_retention_days'] ?? 30 ) );
		
		$this->logger->info( 'Settings updated by user ' . get_current_user_id(), EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Create missing standard pages
	 *
	 * Creates any standard EMS pages that are missing.
	 * Uses the same logic as the activator but only creates pages that don't exist.
	 *
	 * @since 1.2.0
	 * @return int Number of pages created.
	 */
	private function create_missing_pages() {
		$created_count = 0;
		
		// Define pages to create (same as activator)
		$pages = array(
			'events' => array(
				'title'   => __( 'Events', 'event-management-system' ),
				'content' => '[ems_upcoming_events]',
				'option'  => 'ems_page_events',
			),
			'my-registrations' => array(
				'title'   => __( 'My Registrations', 'event-management-system' ),
				'content' => '[ems_my_registrations]',
				'option'  => 'ems_page_my_registrations',
			),
			'my-schedule' => array(
				'title'   => __( 'My Schedule', 'event-management-system' ),
				'content' => '[ems_my_schedule]',
				'option'  => 'ems_page_my_schedule',
			),
			'presenter-dashboard' => array(
				'title'   => __( 'Presenter Dashboard', 'event-management-system' ),
				'content' => '[ems_presenter_dashboard]',
				'option'  => 'ems_page_presenter_dashboard',
			),
			'submit-abstract' => array(
				'title'   => __( 'Submit Abstract', 'event-management-system' ),
				'content' => '<!-- wp:paragraph -->
<p>' . __( 'To submit an abstract, please navigate to the specific event you wish to submit to and use the abstract submission form on that page.', 'event-management-system' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="' . esc_url( get_permalink( get_option( 'ems_page_events' ) ) ) . '">' . __( 'Browse upcoming events', 'event-management-system' ) . '</a></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>' . __( 'Already submitted?', 'event-management-system' ) . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __( 'Visit your Presenter Dashboard to view the status of your submissions:', 'event-management-system' ) . '</p>
<!-- /wp:paragraph -->

[ems_presenter_dashboard]',
				'option'  => 'ems_page_submit_abstract',
			),
		);

		foreach ( $pages as $slug => $page_data ) {
			// Check if page already exists (by option or by slug)
			$existing_page_id = get_option( $page_data['option'] );
			
			if ( $existing_page_id ) {
				// Check if page still exists
				$existing_page = get_post( $existing_page_id );
				if ( $existing_page && 'trash' !== $existing_page->post_status ) {
					continue; // Page exists, skip
				}
			}

			// Check by slug
			$existing_page = get_page_by_path( $slug );
			if ( $existing_page && 'trash' !== $existing_page->post_status ) {
				update_option( $page_data['option'], $existing_page->ID );
				continue;
			}

			// Create the page
			$page_id = wp_insert_post( array(
				'post_title'     => $page_data['title'],
				'post_name'      => $slug,
				'post_content'   => $page_data['content'],
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => get_current_user_id(),
				'comment_status' => 'closed',
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( $page_data['option'], $page_id );
				$created_count++;
				$this->logger->info( 
					sprintf( 'Created standard page: %s (ID: %d)', $page_data['title'], $page_id ),
					EMS_Logger::CONTEXT_GENERAL
				);
			}
		}

		return $created_count;
	}

	// ==========================================
	// AJAX HANDLERS
	// ==========================================

	/**
	 * AJAX: Get registrations
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_registrations() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'event-management-system' ) ) );
		}

		try {
			global $wpdb;

			$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
			$status   = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
			$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
			$offset   = ( $page - 1 ) * $per_page;

			$where = array( '1=1' );
			$params = array();

			if ( $event_id ) {
				$where[] = 'event_id = %d';
				$params[] = $event_id;
			}

			if ( $status ) {
				$where[] = 'status = %s';
				$params[] = $status;
			}

			$where_clause = implode( ' AND ', $where );

			// Get total count
			$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}ems_registrations WHERE {$where_clause}";
			$total = empty( $params ) ? $wpdb->get_var( $count_query ) : $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );

			// Get registrations
			$query = "SELECT * FROM {$wpdb->prefix}ems_registrations WHERE {$where_clause} ORDER BY registration_date DESC LIMIT %d OFFSET %d";
			$params[] = $per_page;
			$params[] = $offset;

			$registrations = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

			wp_send_json_success( array(
				'registrations' => $registrations,
				'total'         => intval( $total ),
				'page'          => $page,
				'per_page'      => $per_page,
				'total_pages'   => ceil( $total / $per_page ),
			) );

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_get_registrations error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Assign reviewer
	 *
	 * @since 1.0.0
	 */
	public function ajax_assign_reviewer() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'event-management-system' ) ) );
		}

		try {
			$abstract_id = isset( $_POST['abstract_id'] ) ? absint( $_POST['abstract_id'] ) : 0;
			$reviewer_id = isset( $_POST['reviewer_id'] ) ? absint( $_POST['reviewer_id'] ) : 0;

			if ( ! $abstract_id || ! $reviewer_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'event-management-system' ) ) );
			}

			$review_manager = new EMS_Abstract_Review();
			$result = $review_manager->assign_reviewer( $abstract_id, $reviewer_id );

			if ( $result['success'] ) {
				// Send notification email
				$email_manager = new EMS_Email_Manager();
				$email_manager->send_review_assigned_email( $abstract_id, $reviewer_id );

				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_assign_reviewer error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Save schedule
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_schedule() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'event-management-system' ) ) );
		}

		try {
			$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
			$raw_sessions = isset( $_POST['sessions'] ) ? $_POST['sessions'] : array();

			if ( ! $event_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid event ID', 'event-management-system' ) ) );
			}

			// Sanitize sessions array
			$sessions = array();
			if ( is_array( $raw_sessions ) ) {
				foreach ( $raw_sessions as $session ) {
					$sessions[] = array(
						'id'         => isset( $session['id'] ) ? absint( $session['id'] ) : 0,
						'title'      => isset( $session['title'] ) ? sanitize_text_field( $session['title'] ) : '',
						'start_time' => isset( $session['start_time'] ) ? sanitize_text_field( $session['start_time'] ) : '',
						'end_time'   => isset( $session['end_time'] ) ? sanitize_text_field( $session['end_time'] ) : '',
						'location'   => isset( $session['location'] ) ? sanitize_text_field( $session['location'] ) : '',
						'order'      => isset( $session['order'] ) ? absint( $session['order'] ) : 0,
					);
				}
			}

			$schedule_builder = new EMS_Schedule_Builder();
			$result = $schedule_builder->save_schedule( $event_id, $sessions );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_save_schedule error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Upload file
	 *
	 * @since 1.0.0
	 */
	public function ajax_upload_file() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'event-management-system' ) ) );
		}

		try {
			if ( empty( $_FILES['file'] ) ) {
				wp_send_json_error( array( 'message' => __( 'No file uploaded', 'event-management-system' ) ) );
			}

			$entity_type = isset( $_POST['entity_type'] ) ? sanitize_text_field( $_POST['entity_type'] ) : 'general';
			$entity_id   = isset( $_POST['entity_id'] ) ? absint( $_POST['entity_id'] ) : 0;

			$file_manager = new EMS_File_Manager();
			$result = $file_manager->upload_file( $_FILES['file'], $entity_type, $entity_id );

			if ( $result['success'] ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result );
			}

		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_upload_file error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred', 'event-management-system' ) ) );
		}
	}

	// ==========================================
	// USER ROLES MANAGEMENT
	// ==========================================

	/**
	 * Display user roles management page.
	 *
	 * Allows assigning EMS roles to users.
	 *
	 * @since 1.1.0
	 */
	public function display_user_roles() {
		// Handle form submissions
		$this->handle_user_role_actions();

		// Get EMS roles
		$ems_roles = EMS_Roles::get_ems_roles();

		// Get users with EMS roles
		$users_with_roles = array();
		foreach ( array_keys( $ems_roles ) as $role ) {
			$users_with_roles[ $role ] = EMS_Roles::get_users_by_role( $role );
		}

		// Get all users for assignment dropdown
		$all_users = get_users( array(
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'number'  => 100,
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'User Role Management', 'event-management-system' ); ?></h1>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
				
				<!-- Assign Role Form -->
				<div class="ems-admin-card" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Assign Role', 'event-management-system' ); ?></h2>
					
					<form method="post">
						<?php wp_nonce_field( 'ems_assign_role', 'ems_role_nonce' ); ?>
						<input type="hidden" name="ems_action" value="assign_role">
						
						<table class="form-table">
							<tr>
								<th><label for="user_id"><?php esc_html_e( 'User', 'event-management-system' ); ?></label></th>
								<td>
									<select name="user_id" id="user_id" required style="min-width: 250px;">
										<option value=""><?php esc_html_e( 'Select User...', 'event-management-system' ); ?></option>
										<?php foreach ( $all_users as $user ) : ?>
											<option value="<?php echo esc_attr( $user->ID ); ?>">
												<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="ems_role"><?php esc_html_e( 'Role', 'event-management-system' ); ?></label></th>
								<td>
									<select name="ems_role" id="ems_role" required>
										<option value=""><?php esc_html_e( 'Select Role...', 'event-management-system' ); ?></option>
										<?php foreach ( $ems_roles as $role_slug => $role_name ) : ?>
											<option value="<?php echo esc_attr( $role_slug ); ?>"><?php echo esc_html( $role_name ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Assign Role', 'event-management-system' ); ?></button>
						</p>
					</form>
				</div>

				<!-- Refresh Capabilities -->
				<div class="ems-admin-card" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Maintenance', 'event-management-system' ); ?></h2>
					
					<p><?php esc_html_e( 'If you are experiencing permission issues, you can refresh all capabilities.', 'event-management-system' ); ?></p>
					
					<form method="post">
						<?php wp_nonce_field( 'ems_refresh_caps', 'ems_caps_nonce' ); ?>
						<input type="hidden" name="ems_action" value="refresh_capabilities">
						<button type="submit" class="button" onclick="return confirm('<?php esc_attr_e( 'This will reset all EMS capabilities. Continue?', 'event-management-system' ); ?>');">
							<?php esc_html_e( 'Refresh Capabilities', 'event-management-system' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Current Role Assignments -->
			<div class="ems-admin-card" style="background: #fff; padding: 20px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Current Role Assignments', 'event-management-system' ); ?></h2>
				
				<?php foreach ( $ems_roles as $role_slug => $role_name ) : ?>
					<h3><?php echo esc_html( $role_name ); ?></h3>
					<?php if ( ! empty( $users_with_roles[ $role_slug ] ) ) : ?>
						<table class="widefat" style="margin-bottom: 20px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'event-management-system' ); ?></th>
									<th><?php esc_html_e( 'Email', 'event-management-system' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $users_with_roles[ $role_slug ] as $user ) : ?>
									<tr>
										<td><?php echo esc_html( $user->display_name ); ?></td>
										<td><?php echo esc_html( $user->user_email ); ?></td>
										<td>
											<form method="post" style="display: inline;">
												<?php wp_nonce_field( 'ems_remove_role', 'ems_remove_nonce' ); ?>
												<input type="hidden" name="ems_action" value="remove_role">
												<input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
												<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Remove this role from user?', 'event-management-system' ); ?>');">
													<?php esc_html_e( 'Remove', 'event-management-system' ); ?>
												</button>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="color: #666; margin-bottom: 20px;"><em><?php esc_html_e( 'No users assigned to this role.', 'event-management-system' ); ?></em></p>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle user role form actions.
	 *
	 * @since 1.1.0
	 */
	private function handle_user_role_actions() {
		if ( ! isset( $_POST['ems_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['ems_action'] );

		switch ( $action ) {
			case 'assign_role':
				if ( ! wp_verify_nonce( $_POST['ems_role_nonce'] ?? '', 'ems_assign_role' ) ) {
					return;
				}
				$user_id = absint( $_POST['user_id'] ?? 0 );
				$role    = sanitize_text_field( $_POST['ems_role'] ?? '' );
				if ( $user_id && $role && EMS_Roles::assign_role( $user_id, $role ) ) {
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Role assigned successfully.', 'event-management-system' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to assign role.', 'event-management-system' ) . '</p></div>';
				}
				break;

			case 'remove_role':
				if ( ! wp_verify_nonce( $_POST['ems_remove_nonce'] ?? '', 'ems_remove_role' ) ) {
					return;
				}
				$user_id = absint( $_POST['user_id'] ?? 0 );
				if ( $user_id && EMS_Roles::remove_ems_roles( $user_id ) ) {
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Role removed successfully.', 'event-management-system' ) . '</p></div>';
				}
				break;

			case 'refresh_capabilities':
				if ( ! wp_verify_nonce( $_POST['ems_caps_nonce'] ?? '', 'ems_refresh_caps' ) ) {
					return;
				}
				$roles = new EMS_Roles();
				$roles->refresh_capabilities();
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Capabilities refreshed successfully.', 'event-management-system' ) . '</p></div>';
				break;
		}
	}

	// ==========================================
	// ADD REGISTRATION
	// ==========================================

	/**
	 * Display add registration form.
	 *
	 * Allows manually adding registrations from admin.
	 *
	 * @since 1.1.0
	 */
	public function display_add_registration() {
		global $wpdb;

		// Handle form submission
		if ( isset( $_POST['ems_add_registration'] ) && wp_verify_nonce( $_POST['ems_reg_nonce'] ?? '', 'ems_add_registration' ) ) {
			$this->process_manual_registration();
		}

		// Get all events
		$events = get_posts( array(
			'post_type'      => 'ems_event',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add Registration', 'event-management-system' ); ?></h1>
			
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-registrations' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back to Registrations', 'event-management-system' ); ?></a>

			<form method="post" style="max-width: 600px; margin-top: 20px;">
				<?php wp_nonce_field( 'ems_add_registration', 'ems_reg_nonce' ); ?>
				
				<div class="ems-admin-card" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Registration Details', 'event-management-system' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th><label for="event_id"><?php esc_html_e( 'Event *', 'event-management-system' ); ?></label></th>
							<td>
								<select name="event_id" id="event_id" required style="min-width: 300px;">
									<option value=""><?php esc_html_e( 'Select Event...', 'event-management-system' ); ?></option>
									<?php foreach ( $events as $event ) : ?>
										<option value="<?php echo esc_attr( $event->ID ); ?>"><?php echo esc_html( $event->post_title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="email"><?php esc_html_e( 'Email *', 'event-management-system' ); ?></label></th>
							<td><input type="email" name="email" id="email" required class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="first_name"><?php esc_html_e( 'First Name *', 'event-management-system' ); ?></label></th>
							<td><input type="text" name="first_name" id="first_name" required class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="last_name"><?php esc_html_e( 'Last Name *', 'event-management-system' ); ?></label></th>
							<td><input type="text" name="last_name" id="last_name" required class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="discipline"><?php esc_html_e( 'Discipline', 'event-management-system' ); ?></label></th>
							<td><input type="text" name="discipline" id="discipline" class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="specialty"><?php esc_html_e( 'Specialty', 'event-management-system' ); ?></label></th>
							<td><input type="text" name="specialty" id="specialty" class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="seniority"><?php esc_html_e( 'Seniority', 'event-management-system' ); ?></label></th>
							<td><input type="text" name="seniority" id="seniority" class="regular-text"></td>
						</tr>
						<tr>
							<th><label for="ticket_type"><?php esc_html_e( 'Ticket Type', 'event-management-system' ); ?></label></th>
							<td>
								<select name="ticket_type" id="ticket_type">
									<option value="standard"><?php esc_html_e( 'Standard', 'event-management-system' ); ?></option>
									<option value="early_bird"><?php esc_html_e( 'Early Bird', 'event-management-system' ); ?></option>
									<option value="vip"><?php esc_html_e( 'VIP', 'event-management-system' ); ?></option>
									<option value="student"><?php esc_html_e( 'Student', 'event-management-system' ); ?></option>
									<option value="complimentary"><?php esc_html_e( 'Complimentary', 'event-management-system' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="amount"><?php esc_html_e( 'Amount Paid', 'event-management-system' ); ?></label></th>
							<td><input type="number" name="amount" id="amount" step="0.01" min="0" value="0" class="small-text"></td>
						</tr>
						<tr>
							<th><label for="payment_status"><?php esc_html_e( 'Payment Status', 'event-management-system' ); ?></label></th>
							<td>
								<select name="payment_status" id="payment_status">
									<option value="paid"><?php esc_html_e( 'Paid', 'event-management-system' ); ?></option>
									<option value="pending"><?php esc_html_e( 'Pending', 'event-management-system' ); ?></option>
									<option value="invoice"><?php esc_html_e( 'Invoice', 'event-management-system' ); ?></option>
									<option value="complimentary"><?php esc_html_e( 'Complimentary', 'event-management-system' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="send_email"><?php esc_html_e( 'Send Confirmation', 'event-management-system' ); ?></label></th>
							<td>
								<label>
									<input type="checkbox" name="send_email" id="send_email" value="1" checked>
									<?php esc_html_e( 'Send confirmation email to registrant', 'event-management-system' ); ?>
								</label>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="submit" name="ems_add_registration" class="button button-primary"><?php esc_html_e( 'Add Registration', 'event-management-system' ); ?></button>
					</p>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Process manual registration submission.
	 *
	 * @since 1.1.0
	 */
	private function process_manual_registration() {
		$registration_data = array(
			'event_id'       => absint( $_POST['event_id'] ?? 0 ),
			'email'          => sanitize_email( $_POST['email'] ?? '' ),
			'first_name'     => sanitize_text_field( $_POST['first_name'] ?? '' ),
			'last_name'      => sanitize_text_field( $_POST['last_name'] ?? '' ),
			'discipline'     => sanitize_text_field( $_POST['discipline'] ?? '' ),
			'specialty'      => sanitize_text_field( $_POST['specialty'] ?? '' ),
			'seniority'      => sanitize_text_field( $_POST['seniority'] ?? '' ),
			'ticket_type'    => sanitize_text_field( $_POST['ticket_type'] ?? 'standard' ),
			'amount'         => floatval( $_POST['amount'] ?? 0 ),
			'payment_status' => sanitize_text_field( $_POST['payment_status'] ?? 'pending' ),
			'user_id'        => 0,
		);

		// Validate required fields
		if ( empty( $registration_data['event_id'] ) || empty( $registration_data['email'] ) ||
		     empty( $registration_data['first_name'] ) || empty( $registration_data['last_name'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Please fill in all required fields.', 'event-management-system' ) . '</p></div>';
			return;
		}

		// Check if user exists
		$user = get_user_by( 'email', $registration_data['email'] );
		if ( $user ) {
			$registration_data['user_id'] = $user->ID;
		}

		// Process registration
		$registration = new EMS_Registration();
		$result = $registration->register_participant( $registration_data );

		if ( $result['success'] ) {
			// Send confirmation email if requested
			if ( ! empty( $_POST['send_email'] ) ) {
				$email_manager = new EMS_Email_Manager();
				$email_manager->send_registration_confirmation( $result['registration_id'] );
			}

			echo '<div class="notice notice-success"><p>' . 
			     sprintf( 
				     esc_html__( 'Registration added successfully. ID: %d', 'event-management-system' ), 
				     $result['registration_id'] 
			     ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=ems-registrations' ) ) . '">' . 
			     esc_html__( 'View all registrations', 'event-management-system' ) . '</a></p></div>';
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html( $result['message'] ) . '</p></div>';
		}
	}

	// ==========================================
	// ABSTRACT DETAIL
	// ==========================================

	/**
	 * Display abstract detail page.
	 *
	 * Shows full abstract details with review information.
	 *
	 * @since 1.1.0
	 */
	public function display_abstract_detail() {
		$abstract_id = absint( $_GET['abstract_id'] ?? 0 );
		
		if ( ! $abstract_id ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Abstract Not Found', 'event-management-system' ) . '</h1></div>';
			return;
		}

		$abstract = get_post( $abstract_id );
		if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Abstract Not Found', 'event-management-system' ) . '</h1></div>';
			return;
		}

		// Get abstract metadata
		$event_id      = get_post_meta( $abstract_id, 'event_id', true );
		$event         = $event_id ? get_post( $event_id ) : null;
		$abstract_type = get_post_meta( $abstract_id, 'abstract_type', true );
		$keywords      = get_post_meta( $abstract_id, 'keywords', true );
		$learning_obj  = get_post_meta( $abstract_id, 'learning_objectives', true );
		$presenter_bio = get_post_meta( $abstract_id, 'presenter_bio', true );
		$co_authors    = get_post_meta( $abstract_id, 'co_authors', true );
		$status        = get_post_meta( $abstract_id, 'abstract_status', true ) ?: 'pending';
		$author        = get_user_by( 'ID', $abstract->post_author );

		// Get reviews
		$review_handler = new EMS_Abstract_Review();
		$reviews_result = $review_handler->get_abstract_reviews( $abstract_id );
		$reviews = $reviews_result['success'] ? $reviews_result['reviews'] : array();
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Abstract Details', 'event-management-system' ); ?>
				<span class="ems-status-badge ems-status-<?php echo esc_attr( $status ); ?>" style="font-size: 14px; vertical-align: middle; margin-left: 10px;">
					<?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?>
				</span>
			</h1>
			
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-abstract-review&event_id=' . $event_id ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back to Review', 'event-management-system' ); ?></a>
			<a href="<?php echo esc_url( get_edit_post_link( $abstract_id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Abstract', 'event-management-system' ); ?></a>

			<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
				
				<!-- Main Content -->
				<div>
					<div class="ems-admin-card" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
						<h2 style="margin-top: 0;"><?php echo esc_html( $abstract->post_title ); ?></h2>
						
						<table class="ems-detail-table" style="width: 100%; margin-bottom: 20px;">
							<tr>
								<td style="width: 120px; font-weight: bold;"><?php esc_html_e( 'Type:', 'event-management-system' ); ?></td>
								<td><?php echo esc_html( ucfirst( $abstract_type ?: 'Not specified' ) ); ?></td>
							</tr>
							<tr>
								<td style="font-weight: bold;"><?php esc_html_e( 'Event:', 'event-management-system' ); ?></td>
								<td><?php echo $event ? esc_html( $event->post_title ) : '—'; ?></td>
							</tr>
							<tr>
								<td style="font-weight: bold;"><?php esc_html_e( 'Author:', 'event-management-system' ); ?></td>
								<td><?php echo $author ? esc_html( $author->display_name . ' (' . $author->user_email . ')' ) : '—'; ?></td>
							</tr>
							<tr>
								<td style="font-weight: bold;"><?php esc_html_e( 'Submitted:', 'event-management-system' ); ?></td>
								<td><?php echo esc_html( get_the_date( 'F j, Y g:i a', $abstract ) ); ?></td>
							</tr>
							<?php if ( $keywords ) : ?>
							<tr>
								<td style="font-weight: bold;"><?php esc_html_e( 'Keywords:', 'event-management-system' ); ?></td>
								<td><?php echo esc_html( $keywords ); ?></td>
							</tr>
							<?php endif; ?>
						</table>

						<h3><?php esc_html_e( 'Abstract Content', 'event-management-system' ); ?></h3>
						<div style="background: #f9f9f9; padding: 15px; border-left: 3px solid #2271b1; margin-bottom: 20px;">
							<?php echo wp_kses_post( wpautop( $abstract->post_content ) ); ?>
						</div>

						<?php if ( $learning_obj ) : ?>
							<h3><?php esc_html_e( 'Learning Objectives', 'event-management-system' ); ?></h3>
							<div style="margin-bottom: 20px;">
								<?php echo wp_kses_post( wpautop( $learning_obj ) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $presenter_bio ) : ?>
							<h3><?php esc_html_e( 'Presenter Bio', 'event-management-system' ); ?></h3>
							<div style="margin-bottom: 20px;">
								<?php echo wp_kses_post( wpautop( $presenter_bio ) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $co_authors && is_array( $co_authors ) ) : ?>
							<h3><?php esc_html_e( 'Co-Authors', 'event-management-system' ); ?></h3>
							<ul>
								<?php foreach ( $co_authors as $coauthor ) : ?>
									<li>
										<?php echo esc_html( $coauthor['name'] ); ?>
										<?php if ( ! empty( $coauthor['email'] ) ) : ?>
											(<?php echo esc_html( $coauthor['email'] ); ?>)
										<?php endif; ?>
										<?php if ( ! empty( $coauthor['affiliation'] ) ) : ?>
											- <?php echo esc_html( $coauthor['affiliation'] ); ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>

				<!-- Sidebar: Reviews & Actions -->
				<div>
					<!-- Quick Actions -->
					<div class="ems-admin-card" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Quick Actions', 'event-management-system' ); ?></h3>
						
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'ems_abstract_action', 'ems_action_nonce' ); ?>
							<input type="hidden" name="action" value="ems_update_abstract_status">
							<input type="hidden" name="abstract_id" value="<?php echo esc_attr( $abstract_id ); ?>">
							
							<p>
								<label for="new_status"><?php esc_html_e( 'Change Status:', 'event-management-system' ); ?></label>
								<select name="new_status" id="new_status" style="width: 100%;">
									<option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'event-management-system' ); ?></option>
									<option value="under_review" <?php selected( $status, 'under_review' ); ?>><?php esc_html_e( 'Under Review', 'event-management-system' ); ?></option>
									<option value="approved" <?php selected( $status, 'approved' ); ?>><?php esc_html_e( 'Approved', 'event-management-system' ); ?></option>
									<option value="revision_requested" <?php selected( $status, 'revision_requested' ); ?>><?php esc_html_e( 'Revision Requested', 'event-management-system' ); ?></option>
									<option value="rejected" <?php selected( $status, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'event-management-system' ); ?></option>
								</select>
							</p>
							<button type="submit" class="button button-primary" style="width: 100%;"><?php esc_html_e( 'Update Status', 'event-management-system' ); ?></button>
						</form>
					</div>

					<!-- Reviews -->
					<div class="ems-admin-card" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'Reviews', 'event-management-system' ); ?></h3>
						
						<?php if ( ! empty( $reviews ) ) : ?>
							<?php foreach ( $reviews as $review ) : 
								$reviewer = get_user_by( 'ID', $review['reviewer_id'] );
							?>
								<div style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
									<strong><?php echo $reviewer ? esc_html( $reviewer->display_name ) : esc_html__( 'Unknown Reviewer', 'event-management-system' ); ?></strong>
									<br>
									<span style="color: #666; font-size: 12px;">
										<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review['review_date'] ) ) ); ?>
									</span>
									<br>
									<strong><?php esc_html_e( 'Score:', 'event-management-system' ); ?></strong> <?php echo esc_html( $review['score'] ?? '—' ); ?>/10
									<br>
									<strong><?php esc_html_e( 'Recommendation:', 'event-management-system' ); ?></strong> <?php echo esc_html( ucfirst( $review['recommendation'] ?? '—' ) ); ?>
									<?php if ( ! empty( $review['comments'] ) ) : ?>
										<p style="margin-top: 10px; font-style: italic;"><?php echo esc_html( $review['comments'] ); ?></p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="color: #666;"><em><?php esc_html_e( 'No reviews yet.', 'event-management-system' ); ?></em></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		
		<style>
		.ems-status-pending { background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; }
		.ems-status-under_review { background: #cce5ff; color: #004085; padding: 3px 8px; border-radius: 3px; }
		.ems-status-approved { background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; }
		.ems-status-rejected { background: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 3px; }
		.ems-status-revision_requested { background: #e2e3e5; color: #383d41; padding: 3px 8px; border-radius: 3px; }
		</style>
		<?php
	}

	/**
	 * Handle abstract status update from admin form.
	 *
	 * @since 1.1.0
	 */
	public function handle_abstract_status_update() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['ems_action_nonce'] ?? '', 'ems_abstract_action' ) ) {
			wp_die( __( 'Security check failed.', 'event-management-system' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied.', 'event-management-system' ) );
		}

		$abstract_id = absint( $_POST['abstract_id'] ?? 0 );
		$new_status  = sanitize_text_field( $_POST['new_status'] ?? '' );

		if ( ! $abstract_id || ! $new_status ) {
			wp_die( __( 'Invalid parameters.', 'event-management-system' ) );
		}

		// Validate status
		$valid_statuses = array( 'pending', 'under_review', 'approved', 'revision_requested', 'rejected' );
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			wp_die( __( 'Invalid status.', 'event-management-system' ) );
		}

		// Update status
		$old_status = get_post_meta( $abstract_id, 'abstract_status', true );
		update_post_meta( $abstract_id, 'abstract_status', $new_status );

		// Log the change
		$this->logger->info( sprintf(
			'Abstract #%d status changed from %s to %s by user #%d',
			$abstract_id,
			$old_status ?: 'none',
			$new_status,
			get_current_user_id()
		), EMS_Logger::CONTEXT_GENERAL );

		// Send notification emails based on status change
		$email_manager = new EMS_Email_Manager();
		switch ( $new_status ) {
			case 'approved':
				$email_manager->send_abstract_approved_email( $abstract_id );
				break;
			case 'rejected':
				$email_manager->send_abstract_rejected_email( $abstract_id );
				break;
			case 'revision_requested':
				$email_manager->send_revision_request_email( $abstract_id );
				break;
		}

		// Get event_id for redirect
		$event_id = get_post_meta( $abstract_id, 'event_id', true );

		// Redirect back to abstract detail page
		wp_safe_redirect( admin_url( 'admin.php?page=ems-abstract-detail&abstract_id=' . $abstract_id . '&updated=1' ) );
		exit;
	}

	// ==========================================
	// SPONSOR MANAGEMENT
	// ==========================================

	/**
	 * Display the Add Sponsor page.
	 *
	 * Provides a form to manually create a sponsor user account
	 * and linked ems_sponsor post.
	 *
	 * @since 1.5.0
	 */
	public function display_add_sponsor() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'event-management-system' ) );
		}

		// Process form submission
		$message = '';
		$error   = '';
		if ( isset( $_POST['ems_add_sponsor_nonce'] ) && wp_verify_nonce( $_POST['ems_add_sponsor_nonce'], 'ems_add_sponsor' ) ) {
			$result = $this->process_add_sponsor();
			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
			} else {
				$message = sprintf(
					__( 'Sponsor created successfully. <a href="%s">Edit sponsor</a>', 'event-management-system' ),
					esc_url( get_edit_post_link( $result ) )
				);
			}
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add Sponsor Account', 'event-management-system' ); ?></h1>

			<?php if ( $message ) : ?>
				<div class="notice notice-success"><p><?php echo wp_kses_post( $message ); ?></p></div>
			<?php endif; ?>

			<?php if ( $error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'ems_add_sponsor', 'ems_add_sponsor_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="sponsor_legal_name"><?php esc_html_e( 'Legal Name', 'event-management-system' ); ?> <span class="required">*</span></label></th>
						<td><input type="text" id="sponsor_legal_name" name="sponsor_legal_name" value="<?php echo esc_attr( $_POST['sponsor_legal_name'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="sponsor_trading_name"><?php esc_html_e( 'Trading Name', 'event-management-system' ); ?></label></th>
						<td><input type="text" id="sponsor_trading_name" name="sponsor_trading_name" value="<?php echo esc_attr( $_POST['sponsor_trading_name'] ?? '' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="sponsor_abn"><?php esc_html_e( 'ABN', 'event-management-system' ); ?></label></th>
						<td><input type="text" id="sponsor_abn" name="sponsor_abn" value="<?php echo esc_attr( $_POST['sponsor_abn'] ?? '' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="sponsor_contact_email"><?php esc_html_e( 'Contact Email', 'event-management-system' ); ?> <span class="required">*</span></label></th>
						<td><input type="email" id="sponsor_contact_email" name="sponsor_contact_email" value="<?php echo esc_attr( $_POST['sponsor_contact_email'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="sponsor_contact_name"><?php esc_html_e( 'Contact Name', 'event-management-system' ); ?> <span class="required">*</span></label></th>
						<td><input type="text" id="sponsor_contact_name" name="sponsor_contact_name" value="<?php echo esc_attr( $_POST['sponsor_contact_name'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
				</table>

				<?php submit_button( __( 'Create Sponsor Account', 'event-management-system' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Process the manual sponsor account creation.
	 *
	 * Creates a WordPress user with ems_sponsor role, an ems_sponsor post,
	 * and links them via user meta.
	 *
	 * @since 1.5.0
	 * @return int|WP_Error Post ID on success, WP_Error on failure.
	 */
	private function process_add_sponsor() {
		$legal_name    = sanitize_text_field( $_POST['sponsor_legal_name'] ?? '' );
		$trading_name  = sanitize_text_field( $_POST['sponsor_trading_name'] ?? '' );
		$abn           = sanitize_text_field( $_POST['sponsor_abn'] ?? '' );
		$contact_email = sanitize_email( $_POST['sponsor_contact_email'] ?? '' );
		$contact_name  = sanitize_text_field( $_POST['sponsor_contact_name'] ?? '' );

		// Validate required fields
		if ( empty( $legal_name ) ) {
			return new WP_Error( 'missing_legal_name', __( 'Legal name is required.', 'event-management-system' ) );
		}
		if ( empty( $contact_email ) || ! is_email( $contact_email ) ) {
			return new WP_Error( 'invalid_email', __( 'A valid contact email is required.', 'event-management-system' ) );
		}
		if ( empty( $contact_name ) ) {
			return new WP_Error( 'missing_contact_name', __( 'Contact name is required.', 'event-management-system' ) );
		}

		// Check if user already exists
		$existing_user = get_user_by( 'email', $contact_email );
		if ( $existing_user ) {
			// Use existing user but ensure they have the sponsor role
			$existing_user->add_role( 'ems_sponsor' );
			$user_id = $existing_user->ID;
		} else {
			// Create WordPress user
			$username = sanitize_user( strtolower( str_replace( ' ', '.', $contact_name ) ) );
			// Ensure unique username
			$base_username = $username;
			$counter = 1;
			while ( username_exists( $username ) ) {
				$username = $base_username . $counter;
				$counter++;
			}

			$password = wp_generate_password( 16, true, true );
			$user_id = wp_insert_user( array(
				'user_login'   => $username,
				'user_email'   => $contact_email,
				'user_pass'    => $password,
				'display_name' => $contact_name,
				'role'         => 'ems_sponsor',
			) );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
		}

		// Create sponsor post
		$post_title = ! empty( $trading_name ) ? $trading_name : $legal_name;
		$post_id = wp_insert_post( array(
			'post_type'   => 'ems_sponsor',
			'post_title'  => $post_title,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		) );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set sponsor meta
		EMS_Sponsor_Meta::update_sponsor_meta( $post_id, array(
			'legal_name'    => $legal_name,
			'trading_name'  => $trading_name,
			'abn'           => $abn,
			'contact_email' => $contact_email,
			'contact_name'  => $contact_name,
		) );

		// Link user to sponsor post
		update_user_meta( $user_id, '_ems_sponsor_id', $post_id );
		update_post_meta( $post_id, '_ems_sponsor_user_id', $user_id );

		$this->logger->info(
			sprintf( 'Sponsor account created: post #%d, user #%d (%s)', $post_id, $user_id, $legal_name ),
			EMS_Logger::CONTEXT_GENERAL
		);

		return $post_id;
	}

	// ==========================================
	// EOI SUBMISSIONS
	// ==========================================

	/**
	 * Display the EOI Submissions list page.
	 *
	 * @since 1.5.0
	 */
	public function display_eoi_submissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'event-management-system' ) );
		}

		// Handle bulk actions
		$this->handle_eoi_bulk_actions();

		require_once EMS_PLUGIN_DIR . 'admin/views/admin-page-eoi-list.php';
	}

	/**
	 * Display the EOI Detail/Review page.
	 *
	 * @since 1.5.0
	 */
	public function display_eoi_detail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'event-management-system' ) );
		}

		require_once EMS_PLUGIN_DIR . 'admin/views/admin-page-eoi-detail.php';
	}

	/**
	 * Handle EOI bulk actions (approve/reject).
	 *
	 * @since 1.5.0
	 */
	private function handle_eoi_bulk_actions() {
		if ( ! isset( $_POST['ems_eoi_bulk_nonce'] ) || ! wp_verify_nonce( $_POST['ems_eoi_bulk_nonce'], 'ems_eoi_bulk_action' ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
		$eoi_ids = array_map( 'absint', $_POST['eoi_ids'] ?? array() );

		if ( empty( $action ) || empty( $eoi_ids ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'ems_sponsor_eoi';
		$levels_table = $wpdb->prefix . 'ems_sponsorship_levels';

		foreach ( $eoi_ids as $eoi_id ) {
			// Get the current EOI record
			$eoi = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $eoi_id ) );
			if ( ! $eoi ) {
				continue;
			}

			$current_status = $eoi->status;

			if ( 'approve' === $action ) {
				// Skip already approved
				if ( 'approved' === $current_status ) {
					continue;
				}

				$wpdb->update(
					$table,
					array(
						'status'      => 'approved',
						'reviewed_at' => current_time( 'mysql' ),
						'reviewed_by' => get_current_user_id(),
					),
					array( 'id' => $eoi_id ),
					array( '%s', '%s', '%d' ),
					array( '%d' )
				);

				// Increment slots if a level is assigned
				if ( ! empty( $eoi->level_id ) ) {
					$wpdb->query( $wpdb->prepare(
						"UPDATE {$levels_table} SET slots_filled = slots_filled + 1 WHERE id = %d AND (slots_total IS NULL OR slots_filled < slots_total)",
						absint( $eoi->level_id )
					) );
				}

				// Link sponsor to event
				$linked_sponsors = get_post_meta( $eoi->event_id, '_ems_linked_sponsors', true );
				if ( ! is_array( $linked_sponsors ) ) {
					$linked_sponsors = array();
				}
				$sponsor_id = absint( $eoi->sponsor_id );
				if ( ! in_array( $sponsor_id, $linked_sponsors, true ) ) {
					$linked_sponsors[] = $sponsor_id;
					update_post_meta( $eoi->event_id, '_ems_linked_sponsors', $linked_sponsors );
				}
				// Add reverse reference
				$linked_events = get_post_meta( $sponsor_id, '_ems_linked_events', true );
				if ( ! is_array( $linked_events ) ) {
					$linked_events = array();
				}
				if ( ! in_array( absint( $eoi->event_id ), $linked_events, true ) ) {
					$linked_events[] = absint( $eoi->event_id );
					update_post_meta( $sponsor_id, '_ems_linked_events', $linked_events );
				}

			} elseif ( 'reject' === $action ) {
				// If previously approved, need to decrement slots and unlink
				if ( 'approved' === $current_status ) {
					// Decrement slots if a level was assigned
					if ( ! empty( $eoi->level_id ) ) {
						$wpdb->query( $wpdb->prepare(
							"UPDATE {$levels_table} SET slots_filled = GREATEST(slots_filled - 1, 0) WHERE id = %d",
							absint( $eoi->level_id )
						) );
					}

					// Unlink sponsor from event
					$linked_sponsors = get_post_meta( $eoi->event_id, '_ems_linked_sponsors', true );
					if ( is_array( $linked_sponsors ) ) {
						$sponsor_id = absint( $eoi->sponsor_id );
						$linked_sponsors = array_diff( $linked_sponsors, array( $sponsor_id ) );
						update_post_meta( $eoi->event_id, '_ems_linked_sponsors', $linked_sponsors );
					}
					// Remove reverse reference
					$linked_events = get_post_meta( $eoi->sponsor_id, '_ems_linked_events', true );
					if ( is_array( $linked_events ) ) {
						$linked_events = array_diff( $linked_events, array( absint( $eoi->event_id ) ) );
						update_post_meta( $eoi->sponsor_id, '_ems_linked_events', $linked_events );
					}
				}

				$wpdb->update(
					$table,
					array(
						'status'      => 'rejected',
						'reviewed_at' => current_time( 'mysql' ),
						'reviewed_by' => get_current_user_id(),
					),
					array( 'id' => $eoi_id ),
					array( '%s', '%s', '%d' ),
					array( '%d' )
				);
			}
		}

		$this->logger->info(
			sprintf( 'Bulk EOI action "%s" applied to %d submissions', $action, count( $eoi_ids ) ),
			EMS_Logger::CONTEXT_GENERAL
		);
	}

	/**
	 * AJAX handler for EOI approval.
	 *
	 * Approves an EOI, links sponsor to event, decrements slots.
	 *
	 * @since 1.5.0
	 */
	public function ajax_eoi_approve() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		$eoi_id   = absint( $_POST['eoi_id'] ?? 0 );
		$level_id = absint( $_POST['level_id'] ?? 0 );

		if ( ! $eoi_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid EOI ID.', 'event-management-system' ) ) );
		}

		global $wpdb;
		$eoi_table    = $wpdb->prefix . 'ems_sponsor_eoi';
		$levels_table = $wpdb->prefix . 'ems_sponsorship_levels';

		// Get the EOI
		$eoi = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$eoi_table} WHERE id = %d", $eoi_id ) );
		if ( ! $eoi ) {
			wp_send_json_error( array( 'message' => __( 'EOI not found.', 'event-management-system' ) ) );
		}

		// Update EOI status
		$result = $wpdb->update(
			$eoi_table,
			array(
				'status'      => 'approved',
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => get_current_user_id(),
			),
			array( 'id' => $eoi_id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Database update failed.', 'event-management-system' ) ) );
		}

		// Decrement available slots if a level is assigned
		if ( $level_id ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$levels_table} SET slots_filled = slots_filled + 1 WHERE id = %d AND (slots_total IS NULL OR slots_filled < slots_total)",
				$level_id
			) );
		}

		// Link sponsor to event
		$linked_sponsors = get_post_meta( $eoi->event_id, '_ems_linked_sponsors', true );
		if ( ! is_array( $linked_sponsors ) ) {
			$linked_sponsors = array();
		}
		$sponsor_id = absint( $eoi->sponsor_id );
		if ( ! in_array( $sponsor_id, $linked_sponsors, true ) ) {
			$linked_sponsors[] = $sponsor_id;
			update_post_meta( $eoi->event_id, '_ems_linked_sponsors', $linked_sponsors );
		}
		// Add reverse reference
		$linked_events = get_post_meta( $sponsor_id, '_ems_linked_events', true );
		if ( ! is_array( $linked_events ) ) {
			$linked_events = array();
		}
		if ( ! in_array( absint( $eoi->event_id ), $linked_events, true ) ) {
			$linked_events[] = absint( $eoi->event_id );
			update_post_meta( $sponsor_id, '_ems_linked_events', $linked_events );
		}

		$this->logger->info(
			sprintf( 'EOI #%d approved for sponsor #%d, event #%d by user #%d', $eoi_id, $eoi->sponsor_id, $eoi->event_id, get_current_user_id() ),
			EMS_Logger::CONTEXT_GENERAL
		);

		wp_send_json_success( array( 'message' => __( 'EOI approved successfully.', 'event-management-system' ) ) );
	}

	/**
	 * AJAX handler for EOI rejection.
	 *
	 * @since 1.5.0
	 */
	public function ajax_eoi_reject() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		$eoi_id = absint( $_POST['eoi_id'] ?? 0 );
		$reason = sanitize_textarea_field( $_POST['reason'] ?? '' );

		if ( ! $eoi_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid EOI ID.', 'event-management-system' ) ) );
		}

		global $wpdb;
		$eoi_table = $wpdb->prefix . 'ems_sponsor_eoi';

		$result = $wpdb->update(
			$eoi_table,
			array(
				'status'       => 'rejected',
				'reviewed_at'  => current_time( 'mysql' ),
				'reviewed_by'  => get_current_user_id(),
				'review_notes' => $reason,
			),
			array( 'id' => $eoi_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Database update failed.', 'event-management-system' ) ) );
		}

		$this->logger->info(
			sprintf( 'EOI #%d rejected by user #%d. Reason: %s', $eoi_id, get_current_user_id(), $reason ),
			EMS_Logger::CONTEXT_GENERAL
		);

		wp_send_json_success( array( 'message' => __( 'EOI rejected.', 'event-management-system' ) ) );
	}

	/**
	 * AJAX handler for EOI request more info.
	 *
	 * @since 1.5.0
	 */
	public function ajax_eoi_request_info() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		$eoi_id  = absint( $_POST['eoi_id'] ?? 0 );
		$message = sanitize_textarea_field( $_POST['message'] ?? '' );

		if ( ! $eoi_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid EOI ID.', 'event-management-system' ) ) );
		}

		global $wpdb;
		$eoi_table = $wpdb->prefix . 'ems_sponsor_eoi';

		$result = $wpdb->update(
			$eoi_table,
			array(
				'status'       => 'info_requested',
				'reviewed_at'  => current_time( 'mysql' ),
				'reviewed_by'  => get_current_user_id(),
				'review_notes' => $message,
			),
			array( 'id' => $eoi_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Database update failed.', 'event-management-system' ) ) );
		}

		$this->logger->info(
			sprintf( 'More info requested for EOI #%d by user #%d', $eoi_id, get_current_user_id() ),
			EMS_Logger::CONTEXT_GENERAL
		);

		wp_send_json_success( array( 'message' => __( 'Information request sent.', 'event-management-system' ) ) );
	}

	// ==========================================
	// SPONSORSHIP AJAX HANDLERS
	// ==========================================

	/**
	 * AJAX: Save (add or update) a sponsorship level.
	 *
	 * Handles both adding new levels and updating existing ones.
	 *
	 * @since 1.5.0
	 */
	public function ajax_save_sponsorship_levels() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		try {
			$level_id = isset( $_POST['level_id'] ) ? absint( $_POST['level_id'] ) : 0;
			$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

			$data = array(
				'level_name'       => isset( $_POST['level_name'] ) ? sanitize_text_field( $_POST['level_name'] ) : '',
				'colour'           => isset( $_POST['colour'] ) ? sanitize_text_field( $_POST['colour'] ) : '#CD7F32',
				'value_aud'        => isset( $_POST['value_aud'] ) ? floatval( $_POST['value_aud'] ) : 0,
				'slots_total'      => isset( $_POST['slots_total'] ) ? absint( $_POST['slots_total'] ) : 0,
				'recognition_text' => isset( $_POST['recognition_text'] ) ? sanitize_textarea_field( $_POST['recognition_text'] ) : '',
				'sort_order'       => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
			);

			if ( empty( $data['level_name'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Level name is required.', 'event-management-system' ) ) );
			}

			$levels_handler = new EMS_Sponsorship_Levels();

			if ( $level_id ) {
				// Update existing level
				$result = $levels_handler->update_level( $level_id, $data );
				if ( $result ) {
					$level = $levels_handler->get_level( $level_id );
					wp_send_json_success( array(
						'message' => __( 'Level updated successfully.', 'event-management-system' ),
						'level'   => $level,
					) );
				} else {
					wp_send_json_error( array( 'message' => __( 'Failed to update level.', 'event-management-system' ) ) );
				}
			} else {
				// Add new level
				if ( ! $event_id ) {
					wp_send_json_error( array( 'message' => __( 'Event ID is required.', 'event-management-system' ) ) );
				}

				$new_id = $levels_handler->add_level( $event_id, $data );
				if ( $new_id ) {
					$level = $levels_handler->get_level( $new_id );
					wp_send_json_success( array(
						'message'  => __( 'Level added successfully.', 'event-management-system' ),
						'level'    => $level,
						'level_id' => $new_id,
					) );
				} else {
					wp_send_json_error( array( 'message' => __( 'Failed to add level.', 'event-management-system' ) ) );
				}
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_save_sponsorship_levels error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Populate default sponsorship levels (Bronze/Silver/Gold).
	 *
	 * @since 1.5.0
	 */
	public function ajax_populate_default_levels() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		try {
			$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

			if ( ! $event_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid event ID.', 'event-management-system' ) ) );
			}

			$levels_handler = new EMS_Sponsorship_Levels();

			// Check if levels already exist
			$existing = $levels_handler->get_event_levels( $event_id );
			if ( ! empty( $existing ) ) {
				wp_send_json_error( array( 'message' => __( 'Levels already exist for this event.', 'event-management-system' ) ) );
			}

			$created_ids = $levels_handler->populate_defaults( $event_id );

			if ( ! empty( $created_ids ) ) {
				$levels = $levels_handler->get_event_levels( $event_id );
				wp_send_json_success( array(
					'message' => sprintf(
						/* translators: %d: number of levels created */
						__( '%d default levels created.', 'event-management-system' ),
						count( $created_ids )
					),
					'levels' => $levels,
				) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to create default levels.', 'event-management-system' ) ) );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_populate_default_levels error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Delete a sponsorship level.
	 *
	 * @since 1.5.0
	 */
	public function ajax_delete_sponsorship_level() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		try {
			$level_id = isset( $_POST['level_id'] ) ? absint( $_POST['level_id'] ) : 0;

			if ( ! $level_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid level ID.', 'event-management-system' ) ) );
			}

			$levels_handler = new EMS_Sponsorship_Levels();
			$result         = $levels_handler->delete_level( $level_id );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array(
				'message' => __( 'Level deleted successfully.', 'event-management-system' ),
			) );
		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_delete_sponsorship_level error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Search sponsors by name (autocomplete).
	 *
	 * @since 1.5.0
	 */
	public function ajax_search_sponsors() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		try {
			$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

			if ( strlen( $search ) < 2 ) {
				wp_send_json_success( array( 'sponsors' => array() ) );
			}

			$sponsors = get_posts( array(
				'post_type'      => 'ems_sponsor',
				'posts_per_page' => 10,
				's'              => $search,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );

			$results = array();
			foreach ( $sponsors as $sponsor ) {
				$results[] = array(
					'id'    => $sponsor->ID,
					'title' => $sponsor->post_title,
				);
			}

			wp_send_json_success( array( 'sponsors' => $results ) );
		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_search_sponsors error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Link a sponsor to an event with optional level.
	 *
	 * @since 1.5.0
	 */
	public function ajax_link_sponsor_to_event() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		try {
			$event_id   = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
			$sponsor_id = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;
			$level_id   = isset( $_POST['level_id'] ) ? absint( $_POST['level_id'] ) : 0;

			if ( ! $event_id || ! $sponsor_id ) {
				wp_send_json_error( array( 'message' => __( 'Event ID and Sponsor ID are required.', 'event-management-system' ) ) );
			}

			// Verify sponsor post exists
			$sponsor = get_post( $sponsor_id );
			if ( ! $sponsor || 'ems_sponsor' !== $sponsor->post_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid sponsor.', 'event-management-system' ) ) );
			}

			// Get existing linked sponsors
			$linked = get_post_meta( $event_id, '_ems_linked_sponsors', true );
			if ( ! is_array( $linked ) ) {
				$linked = array();
			}

			// Check if already linked
			foreach ( $linked as $link ) {
				if ( intval( $link['sponsor_id'] ) === $sponsor_id ) {
					wp_send_json_error( array( 'message' => __( 'This sponsor is already linked to this event.', 'event-management-system' ) ) );
				}
			}

			// Check slot availability if a level is specified
			$level_obj = null;
			if ( $level_id ) {
				$levels_handler = new EMS_Sponsorship_Levels();
				$available      = $levels_handler->get_available_slots( $level_id );

				if ( false === $available ) {
					wp_send_json_error( array( 'message' => __( 'Invalid sponsorship level.', 'event-management-system' ) ) );
				}

				if ( 0 === $available ) {
					wp_send_json_error( array( 'message' => __( 'No available slots for this level.', 'event-management-system' ) ) );
				}

				// Increment filled slots
				$levels_handler->increment_slots_filled( $level_id );
				$level_obj = $levels_handler->get_level( $level_id );
			}

			// Add the link
			$linked[] = array(
				'sponsor_id'  => $sponsor_id,
				'level_id'    => $level_id,
				'linked_date' => current_time( 'mysql' ),
			);

			update_post_meta( $event_id, '_ems_linked_sponsors', $linked );

			// Also store a reverse reference on the sponsor
			$sponsor_events = get_post_meta( $sponsor_id, '_ems_linked_events', true );
			if ( ! is_array( $sponsor_events ) ) {
				$sponsor_events = array();
			}
			$sponsor_events[] = array(
				'event_id'    => $event_id,
				'level_id'    => $level_id,
				'linked_date' => current_time( 'mysql' ),
			);
			update_post_meta( $sponsor_id, '_ems_linked_events', $sponsor_events );

			$this->logger->info(
				sprintf( 'Sponsor %d linked to event %d (level: %d)', $sponsor_id, $event_id, $level_id ),
				EMS_Logger::CONTEXT_GENERAL
			);

			$response_data = array(
				'message'      => __( 'Sponsor linked successfully.', 'event-management-system' ),
				'sponsor_id'   => $sponsor_id,
				'sponsor_name' => $sponsor->post_title,
				'sponsor_url'  => get_edit_post_link( $sponsor_id, 'raw' ),
				'level_id'     => $level_id,
				'level_name'   => $level_obj ? $level_obj->level_name : '',
				'level_colour' => $level_obj ? $level_obj->colour : '',
				'linked_date'  => date_i18n( get_option( 'date_format' ) ),
			);

			wp_send_json_success( $response_data );
		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_link_sponsor_to_event error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred.', 'event-management-system' ) ) );
		}
	}

	/**
	 * AJAX: Unlink a sponsor from an event.
	 *
	 * @since 1.5.0
	 */
	public function ajax_unlink_sponsor_from_event() {
		check_ajax_referer( 'ems_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'event-management-system' ) ) );
		}

		try {
			$event_id   = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
			$sponsor_id = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;

			if ( ! $event_id || ! $sponsor_id ) {
				wp_send_json_error( array( 'message' => __( 'Event ID and Sponsor ID are required.', 'event-management-system' ) ) );
			}

			// Get existing linked sponsors
			$linked = get_post_meta( $event_id, '_ems_linked_sponsors', true );
			if ( ! is_array( $linked ) ) {
				$linked = array();
			}

			// Find and remove the link
			$found      = false;
			$level_id   = 0;
			$new_linked = array();

			foreach ( $linked as $link ) {
				if ( intval( $link['sponsor_id'] ) === $sponsor_id ) {
					$found    = true;
					$level_id = intval( $link['level_id'] ?? 0 );
				} else {
					$new_linked[] = $link;
				}
			}

			if ( ! $found ) {
				wp_send_json_error( array( 'message' => __( 'Sponsor is not linked to this event.', 'event-management-system' ) ) );
			}

			// Decrement level slots if applicable
			if ( $level_id ) {
				$levels_handler = new EMS_Sponsorship_Levels();
				$levels_handler->decrement_slots_filled( $level_id );
			}

			// Update event meta
			update_post_meta( $event_id, '_ems_linked_sponsors', $new_linked );

			// Update sponsor reverse reference
			$sponsor_events = get_post_meta( $sponsor_id, '_ems_linked_events', true );
			if ( is_array( $sponsor_events ) ) {
				$new_sponsor_events = array();
				foreach ( $sponsor_events as $se ) {
					if ( intval( $se['event_id'] ) !== $event_id ) {
						$new_sponsor_events[] = $se;
					}
				}
				update_post_meta( $sponsor_id, '_ems_linked_events', $new_sponsor_events );
			}

			$this->logger->info(
				sprintf( 'Sponsor %d unlinked from event %d', $sponsor_id, $event_id ),
				EMS_Logger::CONTEXT_GENERAL
			);

			wp_send_json_success( array(
				'message'  => __( 'Sponsor unlinked successfully.', 'event-management-system' ),
				'level_id' => $level_id,
			) );
		} catch ( Exception $e ) {
			$this->logger->error( 'ajax_unlink_sponsor_from_event error: ' . $e->getMessage(), EMS_Logger::CONTEXT_GENERAL );
			wp_send_json_error( array( 'message' => __( 'An error occurred.', 'event-management-system' ) ) );
		}
	}
}
