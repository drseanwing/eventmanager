<?php
/**
 * The core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
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
 * The core plugin class.
 *
 * @since 1.0.0
 */
class EMS_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    EMS_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->version     = EMS_VERSION;
		$this->plugin_name = 'event-management-system';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_utility_hooks();
		$this->define_custom_post_types();
		$this->define_user_roles();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_cron_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// Load the loader class
		require_once EMS_PLUGIN_DIR . 'includes/class-ems-loader.php';

		// Load utilities
		require_once EMS_PLUGIN_DIR . 'includes/utilities/class-ems-logger.php';
		require_once EMS_PLUGIN_DIR . 'includes/utilities/class-ems-validator.php';
		require_once EMS_PLUGIN_DIR . 'includes/utilities/class-ems-security.php';
		require_once EMS_PLUGIN_DIR . 'includes/utilities/class-ems-user-helper.php';
		require_once EMS_PLUGIN_DIR . 'includes/utilities/class-ems-date-helper.php';

		// Initialize logger
		$this->logger = EMS_Logger::instance();

		// Load custom post types
		require_once EMS_PLUGIN_DIR . 'includes/custom-post-types/class-ems-cpt-event.php';
		require_once EMS_PLUGIN_DIR . 'includes/custom-post-types/class-ems-cpt-abstract.php';
		require_once EMS_PLUGIN_DIR . 'includes/custom-post-types/class-ems-cpt-session.php';
		require_once EMS_PLUGIN_DIR . 'includes/custom-post-types/class-ems-cpt-sponsor.php';

		// Load user roles
		require_once EMS_PLUGIN_DIR . 'includes/user-roles/class-ems-roles.php';

		// Load registration system
		require_once EMS_PLUGIN_DIR . 'includes/registration/class-ems-registration.php';
		require_once EMS_PLUGIN_DIR . 'includes/registration/class-ems-ticketing.php';
		require_once EMS_PLUGIN_DIR . 'includes/registration/class-ems-waitlist.php';
		require_once EMS_PLUGIN_DIR . 'includes/registration/class-ems-payment-gateway.php';

		// Load abstract system
		require_once EMS_PLUGIN_DIR . 'includes/abstracts/class-ems-abstract-submission.php';
		require_once EMS_PLUGIN_DIR . 'includes/abstracts/class-ems-abstract-review.php';

		// Load schedule system
		require_once EMS_PLUGIN_DIR . 'includes/schedule/class-ems-schedule-builder.php';
		require_once EMS_PLUGIN_DIR . 'includes/schedule/class-ems-schedule-display.php';
		require_once EMS_PLUGIN_DIR . 'includes/schedule/class-ems-session-registration.php';

		// Load file management
		require_once EMS_PLUGIN_DIR . 'includes/files/class-ems-file-manager.php';
		require_once EMS_PLUGIN_DIR . 'includes/files/class-ems-file-access-control.php';

		// Load notification system
		require_once EMS_PLUGIN_DIR . 'includes/notifications/class-ems-email-manager.php';

		// Load collaboration
		require_once EMS_PLUGIN_DIR . 'includes/collaboration/class-ems-sponsor-portal.php';

		// Load admin
		require_once EMS_PLUGIN_DIR . 'admin/class-ems-admin.php';

		// Load public
		require_once EMS_PLUGIN_DIR . 'public/class-ems-public.php';

		// Initialize the loader
		$this->loader = new EMS_Loader();

		$this->logger->debug( 'All dependencies loaded successfully', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
		$this->loader->add_action( 'plugins_loaded', $this, 'maybe_upgrade_database' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'event-management-system',
			false,
			dirname( EMS_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Check if database needs upgrading and run migrations if needed.
	 *
	 * This runs on plugins_loaded to handle plugin updates without
	 * requiring deactivation/reactivation.
	 *
	 * @since 1.3.0
	 */
	public function maybe_upgrade_database() {
		$current_version = get_option( 'ems_db_version', '1.0.0' );
		
		if ( version_compare( $current_version, EMS_VERSION, '<' ) ) {
			$this->run_database_upgrades();
			update_option( 'ems_db_version', EMS_VERSION );
		}
	}

	/**
	 * Run database upgrades for plugin updates.
	 *
	 * @since 1.3.0
	 */
	private function run_database_upgrades() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_registrations';
		
		// Check if table exists first
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		if ( ! $table_exists ) {
			return; // Table doesn't exist, skip upgrades
		}

		// v1.3.0: Add cancellation fields
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}" );
		
		if ( ! in_array( 'cancelled_at', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN cancelled_at DATETIME NULL" );
		}
		if ( ! in_array( 'cancelled_by', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN cancelled_by VARCHAR(20) NULL" );
		}
		if ( ! in_array( 'cancellation_reason', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN cancellation_reason TEXT NULL" );
		}

		$this->logger->info( 'Database upgraded to version ' . EMS_VERSION, EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Register utility hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_utility_hooks() {
		// Schedule log cleanup
		$this->loader->add_action( 'ems_cleanup_logs', $this->logger, 'cleanup_old_logs' );

		// File cleanup
		$file_manager = new EMS_File_Manager();
		$this->loader->add_action( 'ems_cleanup_temp_files', $file_manager, 'cleanup_temp_files' );
		
		// User helper - manages EMS user restrictions
		$user_helper = new EMS_User_Helper();
		$user_helper->init_hooks();
	}

	/**
	 * Register custom post types and taxonomies.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_custom_post_types() {
		$cpt_event    = new EMS_CPT_Event();
		$cpt_abstract = new EMS_CPT_Abstract();
		$cpt_session  = new EMS_CPT_Session();
		$cpt_sponsor  = new EMS_CPT_Sponsor();

		$this->loader->add_action( 'init', $cpt_event, 'register_post_type' );
		$this->loader->add_action( 'init', $cpt_event, 'register_taxonomies' );

		$this->loader->add_action( 'init', $cpt_abstract, 'register_post_type' );

		$this->loader->add_action( 'init', $cpt_session, 'register_post_type' );

		$this->loader->add_action( 'init', $cpt_sponsor, 'register_post_type' );
		$this->loader->add_action( 'init', $cpt_sponsor, 'register_taxonomies' );
	}

	/**
	 * Register user roles and capabilities.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_user_roles() {
		$roles = new EMS_Roles();
		$this->loader->add_action( 'init', $roles, 'maybe_create_roles', 5 );
	}

	/**
	 * Register all admin-related hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new EMS_Admin( $this->get_plugin_name(), $this->get_version() );

		// Enqueue admin scripts and styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Admin menu
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// AJAX handlers
		$this->loader->add_action( 'wp_ajax_ems_get_registrations', $plugin_admin, 'ajax_get_registrations' );
		$this->loader->add_action( 'wp_ajax_ems_assign_reviewer', $plugin_admin, 'ajax_assign_reviewer' );
		$this->loader->add_action( 'wp_ajax_ems_save_schedule', $plugin_admin, 'ajax_save_schedule' );
		$this->loader->add_action( 'wp_ajax_ems_upload_file', $plugin_admin, 'ajax_upload_file' );

		// Admin post handlers
		$this->loader->add_action( 'admin_post_ems_update_abstract_status', $plugin_admin, 'handle_abstract_status_update' );
	}

	/**
	 * Register all public-facing hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new EMS_Public( $this->get_plugin_name(), $this->get_version() );

		// Enqueue public scripts and styles
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register shortcodes
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// AJAX handlers for public forms
		$this->loader->add_action( 'wp_ajax_ems_submit_registration', $plugin_public, 'ajax_submit_registration' );
		$this->loader->add_action( 'wp_ajax_nopriv_ems_submit_registration', $plugin_public, 'ajax_submit_registration' );

		$this->loader->add_action( 'wp_ajax_ems_cancel_registration', $plugin_public, 'ajax_cancel_registration' );

		$this->loader->add_action( 'wp_ajax_ems_submit_abstract', $plugin_public, 'ajax_submit_abstract' );

		$this->loader->add_action( 'wp_ajax_ems_register_session', $plugin_public, 'ajax_register_session' );
		$this->loader->add_action( 'wp_ajax_ems_cancel_session', $plugin_public, 'ajax_cancel_session' );

		// iCal export handler
		$this->loader->add_action( 'template_redirect', $plugin_public, 'handle_ical_export' );

		// REST API endpoints
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_rest_routes' );
	}

	/**
	 * Register cron job hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_cron_hooks() {
		$email_manager = new EMS_Email_Manager();

		// Event reminders
		$this->loader->add_action( 'ems_send_event_reminders', $email_manager, 'send_event_reminders' );

		// Session reminders
		$this->loader->add_action( 'ems_send_session_reminders', $email_manager, 'send_session_reminders' );
	}

	/**
	 * Run the loader to execute all hooks.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
		$this->logger->debug( 'Plugin core initialized and running', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 *
	 * @since  1.0.0
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks.
	 *
	 * @since  1.0.0
	 * @return EMS_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
