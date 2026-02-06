<?php
/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    EventManagementSystem
 * @subpackage Includes
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0.0
 */
class EMS_Activator {

	/**
	 * Plugin activation handler
	 *
	 * Creates database tables, sets up directories, registers custom post types,
	 * and initializes default options.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		global $wpdb;

		// Set up logging first (before any operations)
		self::setup_logging();

		// Log activation
		$logger = EMS_Logger::instance();
		$logger->info( 'Plugin activation started', EMS_Logger::CONTEXT_GENERAL );

		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
			deactivate_plugins( EMS_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Event Management System requires WordPress 5.8 or higher.', 'event-management-system' ),
				esc_html__( 'Plugin Activation Error', 'event-management-system' ),
				array( 'back_link' => true )
			);
		}

		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( EMS_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Event Management System requires PHP 7.4 or higher.', 'event-management-system' ),
				esc_html__( 'Plugin Activation Error', 'event-management-system' ),
				array( 'back_link' => true )
			);
		}

		try {
			// Create custom database tables
			self::create_database_tables();
			$logger->info( 'Database tables created successfully', EMS_Logger::CONTEXT_GENERAL );

			// Set up directory structure
			self::setup_directories();
			$logger->info( 'Directory structure created successfully', EMS_Logger::CONTEXT_GENERAL );

			// Register custom post types (needed for rewrite rules)
			self::register_post_types();

			// Flush rewrite rules
			flush_rewrite_rules();
			$logger->info( 'Rewrite rules flushed', EMS_Logger::CONTEXT_GENERAL );

			// Set default options
			self::set_default_options();
			$logger->info( 'Default options initialized', EMS_Logger::CONTEXT_GENERAL );

			// Schedule cron jobs
			self::schedule_cron_jobs();
			$logger->info( 'Cron jobs scheduled', EMS_Logger::CONTEXT_GENERAL );

			// Initialize user roles and capabilities
			require_once EMS_PLUGIN_DIR . 'includes/user-roles/class-ems-roles.php';
			$roles = new EMS_Roles();
			$roles->refresh_capabilities();
			$logger->info( 'User roles and capabilities initialized', EMS_Logger::CONTEXT_GENERAL );

			// Create standard pages
			self::create_pages();
			$logger->info( 'Standard pages created', EMS_Logger::CONTEXT_GENERAL );

			// Set activation timestamp
			update_option( 'ems_activated_at', time() );
			update_option( 'ems_version', EMS_VERSION );

			$logger->info( 'Plugin activation completed successfully', EMS_Logger::CONTEXT_GENERAL );

		} catch ( Exception $e ) {
			$logger->critical(
				'Plugin activation failed: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL,
				array(
					'trace' => $e->getTraceAsString(),
				)
			);

			// Rollback if possible
			self::rollback_activation();

			wp_die(
				esc_html( 'Activation failed: ' . $e->getMessage() ),
				esc_html__( 'Plugin Activation Error', 'event-management-system' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create custom database tables
	 *
	 * @since 1.0.0
	 */
	private static function create_database_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix    = $wpdb->prefix;

		// Registrations table
		$sql_registrations = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_registrations (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			email VARCHAR(100) NOT NULL,
			first_name VARCHAR(100) NOT NULL,
			last_name VARCHAR(100) NOT NULL,
			discipline VARCHAR(50) NOT NULL,
			specialty VARCHAR(100) NOT NULL,
			seniority VARCHAR(50) NOT NULL,
			ticket_type VARCHAR(50) NOT NULL,
			ticket_quantity INT DEFAULT 1,
			promo_code VARCHAR(50) NULL,
			amount_paid DECIMAL(10,2) DEFAULT 0.00,
			payment_status VARCHAR(20) DEFAULT 'pending',
			payment_reference VARCHAR(255) NULL,
			registration_date DATETIME NOT NULL,
			status VARCHAR(20) DEFAULT 'active',
			special_requirements TEXT NULL,
			cancelled_at DATETIME NULL,
			cancelled_by VARCHAR(20) NULL,
			cancellation_reason TEXT NULL,
			INDEX event_idx (event_id),
			INDEX user_idx (user_id),
			INDEX email_idx (email),
			INDEX status_idx (status),
			INDEX registration_date_idx (registration_date)
		) $charset_collate;";

		// Session registrations table
		$sql_session_registrations = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_session_registrations (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			session_id BIGINT UNSIGNED NOT NULL,
			registration_id BIGINT UNSIGNED NOT NULL,
			registered_date DATETIME NOT NULL,
			attendance_status VARCHAR(20) DEFAULT 'registered',
			UNIQUE KEY unique_session_registration (session_id, registration_id),
			INDEX session_idx (session_id),
			INDEX registration_idx (registration_id)
		) $charset_collate;";

		// Waitlist table
		$sql_waitlist = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_waitlist (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_id BIGINT UNSIGNED NOT NULL,
			session_id BIGINT UNSIGNED NULL,
			email VARCHAR(100) NOT NULL,
			first_name VARCHAR(100) NOT NULL,
			last_name VARCHAR(100) NOT NULL,
			discipline VARCHAR(50) NOT NULL,
			specialty VARCHAR(100) NOT NULL,
			seniority VARCHAR(50) NOT NULL,
			added_date DATETIME NOT NULL,
			notified BOOLEAN DEFAULT FALSE,
			position INT NOT NULL,
			INDEX event_idx (event_id),
			INDEX session_idx (session_id),
			INDEX position_idx (position),
			INDEX added_date_idx (added_date)
		) $charset_collate;";

		// Abstract reviews table
		$sql_abstract_reviews = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_abstract_reviews (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			abstract_id BIGINT UNSIGNED NOT NULL,
			reviewer_id BIGINT UNSIGNED NOT NULL,
			assigned_date DATETIME NOT NULL,
			review_date DATETIME NULL,
			score INT NULL,
			comments TEXT NULL,
			recommendation VARCHAR(20) NULL,
			status VARCHAR(20) DEFAULT 'pending',
			UNIQUE KEY unique_review (abstract_id, reviewer_id),
			INDEX abstract_idx (abstract_id),
			INDEX reviewer_idx (reviewer_id),
			INDEX status_idx (status)
		) $charset_collate;";

		// Notification log table
		$sql_notification_log = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_notification_log (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			notification_type VARCHAR(50) NOT NULL,
			recipient_email VARCHAR(100) NOT NULL,
			subject VARCHAR(255) NOT NULL,
			related_id BIGINT UNSIGNED NULL,
			related_type VARCHAR(50) NULL,
			sent_date DATETIME NOT NULL,
			status VARCHAR(20) NOT NULL,
			error_message TEXT NULL,
			INDEX type_idx (notification_type),
			INDEX recipient_idx (recipient_email),
			INDEX sent_date_idx (sent_date),
			INDEX status_idx (status)
		) $charset_collate;";

		// Sponsor event associations table (Phase 4)
		$sql_sponsor_events = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_sponsor_events (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			sponsor_id BIGINT UNSIGNED NOT NULL,
			event_id BIGINT UNSIGNED NOT NULL,
			sponsor_level VARCHAR(50) NULL,
			created_at DATETIME NOT NULL,
			UNIQUE KEY unique_sponsor_event (sponsor_id, event_id),
			INDEX sponsor_idx (sponsor_id),
			INDEX event_idx (event_id)
		) $charset_collate;";

		// Sponsor files table (Phase 4)
		$sql_sponsor_files = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_sponsor_files (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			sponsor_id BIGINT UNSIGNED NOT NULL,
			event_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			file_name VARCHAR(255) NOT NULL,
			file_path VARCHAR(500) NOT NULL,
			file_type VARCHAR(50) NOT NULL,
			file_size BIGINT NOT NULL,
			description TEXT NULL,
			upload_date DATETIME NOT NULL,
			visibility VARCHAR(20) DEFAULT 'private',
			downloads INT DEFAULT 0,
			INDEX sponsor_idx (sponsor_id),
			INDEX event_idx (event_id),
			INDEX user_idx (user_id),
			INDEX upload_date_idx (upload_date)
		) $charset_collate;";

		// Sponsorship levels table (Phase 4)
		$sql_sponsorship_levels = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_sponsorship_levels (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_id BIGINT UNSIGNED NOT NULL,
			level_name VARCHAR(100) NOT NULL,
			level_slug VARCHAR(100) NOT NULL,
			colour VARCHAR(7) NOT NULL DEFAULT '#CD7F32',
			value_aud DECIMAL(10,2) DEFAULT NULL,
			slots_total INT DEFAULT NULL,
			slots_filled INT NOT NULL DEFAULT 0,
			recognition_text TEXT DEFAULT NULL,
			sort_order INT NOT NULL DEFAULT 0,
			enabled TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			KEY event_id (event_id),
			KEY event_level (event_id, level_slug)
		) $charset_collate;";

		// Sponsor EOI table (Phase 4)
		$sql_sponsor_eoi = "CREATE TABLE IF NOT EXISTS {$table_prefix}ems_sponsor_eoi (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			sponsor_id BIGINT UNSIGNED NOT NULL,
			event_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			preferred_level VARCHAR(100) DEFAULT NULL,
			estimated_value DECIMAL(10,2) DEFAULT NULL,
			contribution_nature TEXT DEFAULT NULL,
			conditional_outcome TEXT DEFAULT NULL,
			speaker_nomination TEXT DEFAULT NULL,
			duration_interest VARCHAR(50) DEFAULT NULL,
			exclusivity_requested TINYINT(1) NOT NULL DEFAULT 0,
			exclusivity_category VARCHAR(255) DEFAULT NULL,
			shared_presence_accepted TINYINT(1) NOT NULL DEFAULT 0,
			competitor_objections TEXT DEFAULT NULL,
			exclusivity_conditions TEXT DEFAULT NULL,
			recognition_elements TEXT DEFAULT NULL,
			trade_display_requested TINYINT(1) NOT NULL DEFAULT 0,
			trade_display_requirements TEXT DEFAULT NULL,
			representatives_count INT DEFAULT NULL,
			product_samples TINYINT(1) NOT NULL DEFAULT 0,
			promotional_material TINYINT(1) NOT NULL DEFAULT 0,
			delegate_data_collection TINYINT(1) NOT NULL DEFAULT 0,
			social_media_expectations TEXT DEFAULT NULL,
			content_independence_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
			content_influence TEXT DEFAULT NULL,
			therapeutic_claims TINYINT(1) NOT NULL DEFAULT 0,
			material_review_required TINYINT(1) NOT NULL DEFAULT 0,
			submitted_at DATETIME NOT NULL,
			reviewed_at DATETIME DEFAULT NULL,
			reviewed_by BIGINT UNSIGNED DEFAULT NULL,
			review_notes TEXT DEFAULT NULL,
			KEY sponsor_id (sponsor_id),
			KEY event_id (event_id),
			KEY status (status),
			UNIQUE KEY sponsor_event (sponsor_id, event_id)
		) $charset_collate;";

		// Require WordPress upgrade functionality
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Execute table creation
		dbDelta( $sql_registrations );
		dbDelta( $sql_session_registrations );
		dbDelta( $sql_waitlist );
		dbDelta( $sql_abstract_reviews );
		dbDelta( $sql_notification_log );
		dbDelta( $sql_sponsor_events );
		dbDelta( $sql_sponsor_files );
		dbDelta( $sql_sponsorship_levels );
		dbDelta( $sql_sponsor_eoi );

		// Run migrations for existing installations
		self::run_migrations( $table_prefix );

		// Store database version
		update_option( 'ems_db_version', EMS_VERSION );
	}

	/**
	 * Run database migrations
	 *
	 * Handles adding new columns to existing tables for upgrades.
	 *
	 * @since 1.3.0
	 * @param string $table_prefix Database table prefix.
	 */
	private static function run_migrations( $table_prefix ) {
		global $wpdb;

		// Migration: Add cancellation fields to registrations table (v1.3.0)
		$table_name = $table_prefix . 'ems_registrations';
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}" );
		
		if ( ! in_array( 'cancelled_at', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN cancelled_at DATETIME NULL AFTER special_requirements" );
		}
		if ( ! in_array( 'cancelled_by', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN cancelled_by VARCHAR(20) NULL AFTER cancelled_at" );
		}
		if ( ! in_array( 'cancellation_reason', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN cancellation_reason TEXT NULL AFTER cancelled_by" );
		}
	}

	/**
	 * Set up directory structure
	 *
	 * @since 1.0.0
	 */
	private static function setup_directories() {
		$directories = array(
			EMS_LOG_DIR,
			EMS_UPLOAD_DIR,
			EMS_UPLOAD_DIR . 'events/',
			EMS_UPLOAD_DIR . 'presenters/',
			EMS_UPLOAD_DIR . 'sponsors/',
			EMS_UPLOAD_DIR . 'temp/',
		);

		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					throw new Exception( "Failed to create directory: {$dir}" );
				}

				// Create .htaccess to protect directories (except public folders)
				if ( strpos( $dir, '/public' ) === false ) {
					$htaccess_file = $dir . '.htaccess';
					if ( ! file_exists( $htaccess_file ) ) {
						$htaccess_content = "Order deny,allow\nDeny from all\n";
						file_put_contents( $htaccess_file, $htaccess_content );
					}
				}

				// Create index.php to prevent directory listing
				$index_file = $dir . 'index.php';
				if ( ! file_exists( $index_file ) ) {
					file_put_contents( $index_file, '<?php // Silence is golden' );
				}
			}
		}
	}

	/**
	 * Set up logging system
	 *
	 * @since 1.0.0
	 */
	private static function setup_logging() {
		// Ensure log directory exists
		if ( ! file_exists( EMS_LOG_DIR ) ) {
			wp_mkdir_p( EMS_LOG_DIR );

			// Protect log directory
			$htaccess = EMS_LOG_DIR . '.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
			}

			$index = EMS_LOG_DIR . 'index.php';
			if ( ! file_exists( $index ) ) {
				file_put_contents( $index, '<?php // Silence is golden' );
			}
		}
	}

	/**
	 * Register custom post types temporarily for activation
	 *
	 * @since 1.0.0
	 */
	private static function register_post_types() {
		// Event post type
		register_post_type(
			'ems_event',
			array(
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => array( 'slug' => 'events' ),
			)
		);

		// Abstract post type
		register_post_type(
			'ems_abstract',
			array(
				'public'      => false,
				'has_archive' => false,
			)
		);

		// Session post type
		register_post_type(
			'ems_session',
			array(
				'public'      => false,
				'has_archive' => false,
			)
		);

		// Sponsor post type
		register_post_type(
			'ems_sponsor',
			array(
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => array( 'slug' => 'sponsors' ),
			)
		);
	}

	/**
	 * Set default plugin options
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'ems_logging_enabled'           => true,
			'ems_logging_min_level'         => EMS_Logger::DEBUG,
			'ems_log_retention_days'        => 30,
			'ems_max_file_size'             => EMS_MAX_UPLOAD_SIZE,
			'ems_allowed_file_types'        => array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip' ),
			'ems_email_from_name'           => get_bloginfo( 'name' ),
			'ems_email_from_address'        => get_option( 'admin_email' ),
			'ems_enable_waitlist'           => true,
			'ems_waitlist_auto_promote'     => true,
			'ems_registration_confirmation' => true,
			'ems_abstract_min_reviewers'    => 2,
			'ems_payment_gateway'           => 'invoice',
		);

		foreach ( $default_options as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Create standard pages with shortcodes.
	 *
	 * @since 1.1.0
	 */
	private static function create_pages() {
		// Define pages to create
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
				'content' => '[ems_abstract_submission]

<!-- wp:heading {"level":3} -->
<h3>' . __( 'Your Submissions', 'event-management-system' ) . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __( 'View the status of your submitted abstracts:', 'event-management-system' ) . '</p>
<!-- /wp:paragraph -->

[ems_presenter_dashboard]',
				'option'  => 'ems_page_submit_abstract',
			),
			'sponsor-portal' => array(
				'title'   => __( 'Sponsor Portal', 'event-management-system' ),
				'content' => '[ems_sponsor_portal]',
				'option'  => 'ems_page_sponsor_portal',
			),
			'sponsor-onboarding' => array(
				'title'   => __( 'Become a Sponsor', 'event-management-system' ),
				'content' => '[ems_sponsor_onboarding]',
				'option'  => 'ems_page_sponsor_onboarding',
			),
			'sponsor-eoi' => array(
				'title'   => __( 'Sponsorship Expression of Interest', 'event-management-system' ),
				'content' => '[ems_sponsor_eoi]',
				'option'  => 'ems_page_sponsor_eoi',
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
				'post_author'    => 1,
				'comment_status' => 'closed',
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( $page_data['option'], $page_id );
			}
		}

		// Store reference to events page for archive
		$events_page_id = get_option( 'ems_page_events' );
		if ( $events_page_id ) {
			// Update content to link to actual events page
			$submit_abstract_id = get_option( 'ems_page_submit_abstract' );
			if ( $submit_abstract_id ) {
				$content = get_post_field( 'post_content', $submit_abstract_id );
				$content = str_replace( '[ems_events_link]', get_permalink( $events_page_id ), $content );
				wp_update_post( array(
					'ID'           => $submit_abstract_id,
					'post_content' => $content,
				) );
			}
		}
	}

	/**
	 * Schedule cron jobs
	 *
	 * @since 1.0.0
	 */
	private static function schedule_cron_jobs() {
		// Schedule log cleanup (daily)
		if ( ! wp_next_scheduled( 'ems_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'ems_cleanup_logs' );
		}

		// Schedule temp files cleanup (daily)
		if ( ! wp_next_scheduled( 'ems_cleanup_temp_files' ) ) {
			wp_schedule_event( time(), 'daily', 'ems_cleanup_temp_files' );
		}

		// Schedule event reminders (hourly)
		if ( ! wp_next_scheduled( 'ems_send_event_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'ems_send_event_reminders' );
		}

		// Schedule session reminders (hourly)
		if ( ! wp_next_scheduled( 'ems_send_session_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'ems_send_session_reminders' );
		}
	}

	/**
	 * Rollback activation on error
	 *
	 * @since 1.0.0
	 */
	private static function rollback_activation() {
		global $wpdb;

		// Remove database tables
		$tables = array(
			$wpdb->prefix . 'ems_registrations',
			$wpdb->prefix . 'ems_session_registrations',
			$wpdb->prefix . 'ems_waitlist',
			$wpdb->prefix . 'ems_abstract_reviews',
			$wpdb->prefix . 'ems_notification_log',
			$wpdb->prefix . 'ems_sponsor_events',
			$wpdb->prefix . 'ems_sponsor_files',
			$wpdb->prefix . 'ems_sponsorship_levels',
			$wpdb->prefix . 'ems_sponsor_eoi',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Remove options
		delete_option( 'ems_activated_at' );
		delete_option( 'ems_version' );
		delete_option( 'ems_db_version' );

		// Clear scheduled cron jobs
		wp_clear_scheduled_hook( 'ems_cleanup_logs' );
		wp_clear_scheduled_hook( 'ems_cleanup_temp_files' );
		wp_clear_scheduled_hook( 'ems_send_event_reminders' );
		wp_clear_scheduled_hook( 'ems_send_session_reminders' );
	}
}
