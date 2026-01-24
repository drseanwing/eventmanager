# EMS Plugin Core

**Document Purpose:** Contains all bootstrap and utility files for the Event Management System plugin  
**Production Path:** `event-management-system/` and `event-management-system/includes/`  
**Last Updated:** January 2026

---

## Table of Contents

1. [Main Plugin File](#main-plugin-file) - `event-management-system.php`
2. [Core Class](#core-class) - `class-ems-core.php`
3. [Loader Class](#loader-class) - `class-ems-loader.php`
4. [Activator Class](#activator-class) - `class-ems-activator.php`
5. [Deactivator Class](#deactivator-class) - `class-ems-deactivator.php`
6. [Logger Class](#logger-class) - `class-ems-logger.php`
7. [Roles Class](#roles-class) - `class-ems-roles.php`
8. [Security Class](#security-class) - `class-ems-security.php`
9. [Validator Class](#validator-class) - `class-ems-validator.php`
10. [User Helper Class](#user-helper-class) - `class-ems-user-helper.php`
11. [Date Helper Class](#date-helper-class) - `class-ems-date-helper.php`

---

## Main Plugin File

**File:** `event-management-system.php`  
**Production Path:** `event-management-system/event-management-system.php`

```php
<?php
/**
 * Plugin Name: Event Management System
 * Plugin URI: https://github.com/yourusername/event-management-system
 * Description: Comprehensive event management system for conferences, courses, and seminars with registration, abstract submission, schedule building, and sponsor collaboration.
 * Version: 1.3.0
 * Author: Royal Brisbane & Women's Hospital
 * Author URI: https://www.health.qld.gov.au/rbwh
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-management-system
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package EventManagementSystem
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'EMS_VERSION', '1.3.0' );

/**
 * Plugin base directory path
 */
define( 'EMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin base directory URL
 */
define( 'EMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename
 */
define( 'EMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Log directory path
 */
define( 'EMS_LOG_DIR', WP_CONTENT_DIR . '/uploads/ems-logs/' );

/**
 * Upload directory path for EMS files
 */
define( 'EMS_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/ems/' );

/**
 * Maximum file upload size in bytes (25MB)
 */
define( 'EMS_MAX_UPLOAD_SIZE', 26214400 );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ems-activator.php
 */
function activate_event_management_system() {
	require_once EMS_PLUGIN_DIR . 'includes/class-ems-activator.php';
	EMS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ems-deactivator.php
 */
function deactivate_event_management_system() {
	require_once EMS_PLUGIN_DIR . 'includes/class-ems-deactivator.php';
	EMS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_event_management_system' );
register_deactivation_hook( __FILE__, 'deactivate_event_management_system' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require EMS_PLUGIN_DIR . 'includes/class-ems-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_event_management_system() {
	$plugin = new EMS_Core();
	$plugin->run();
}

run_event_management_system();
```

---

## Core Class

**File:** `class-ems-core.php`  
**Production Path:** `event-management-system/includes/class-ems-core.php`

**Key Features:**
- Plugin orchestration
- Dependency loading
- Hook registration
- Database auto-upgrade (v1.3.0+)

```php
<?php
/**
 * The core plugin class
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    EventManagementSystem
 * @subpackage Includes
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;
	private $logger;

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

	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
		$this->loader->add_action( 'plugins_loaded', $this, 'maybe_upgrade_database' );
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'event-management-system',
			false,
			dirname( EMS_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Check if database needs upgrading and run migrations if needed.
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

	private function define_utility_hooks() {
		$this->loader->add_action( 'ems_cleanup_logs', $this->logger, 'cleanup_old_logs' );

		$file_manager = new EMS_File_Manager();
		$this->loader->add_action( 'ems_cleanup_temp_files', $file_manager, 'cleanup_temp_files' );
		
		$user_helper = new EMS_User_Helper();
		$user_helper->init_hooks();
	}

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

	private function define_user_roles() {
		$roles = new EMS_Roles();
		$this->loader->add_action( 'init', $roles, 'maybe_create_roles', 5 );
	}

	private function define_admin_hooks() {
		$plugin_admin = new EMS_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// AJAX handlers
		$this->loader->add_action( 'wp_ajax_ems_get_registrations', $plugin_admin, 'ajax_get_registrations' );
		$this->loader->add_action( 'wp_ajax_ems_assign_reviewer', $plugin_admin, 'ajax_assign_reviewer' );
		$this->loader->add_action( 'wp_ajax_ems_save_schedule', $plugin_admin, 'ajax_save_schedule' );
		$this->loader->add_action( 'wp_ajax_ems_upload_file', $plugin_admin, 'ajax_upload_file' );
		$this->loader->add_action( 'admin_post_ems_update_abstract_status', $plugin_admin, 'handle_abstract_status_update' );
	}

	private function define_public_hooks() {
		$plugin_public = new EMS_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// AJAX handlers
		$this->loader->add_action( 'wp_ajax_ems_submit_registration', $plugin_public, 'ajax_submit_registration' );
		$this->loader->add_action( 'wp_ajax_nopriv_ems_submit_registration', $plugin_public, 'ajax_submit_registration' );
		$this->loader->add_action( 'wp_ajax_ems_cancel_registration', $plugin_public, 'ajax_cancel_registration' );
		$this->loader->add_action( 'wp_ajax_ems_submit_abstract', $plugin_public, 'ajax_submit_abstract' );
		$this->loader->add_action( 'wp_ajax_ems_register_session', $plugin_public, 'ajax_register_session' );
		$this->loader->add_action( 'wp_ajax_ems_cancel_session', $plugin_public, 'ajax_cancel_session' );

		$this->loader->add_action( 'template_redirect', $plugin_public, 'handle_ical_export' );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_rest_routes' );
	}

	private function define_cron_hooks() {
		$email_manager = new EMS_Email_Manager();
		$this->loader->add_action( 'ems_send_event_reminders', $email_manager, 'send_event_reminders' );
		$this->loader->add_action( 'ems_send_session_reminders', $email_manager, 'send_session_reminders' );
	}

	public function run() {
		$this->loader->run();
		$this->logger->debug( 'Plugin core initialized and running', EMS_Logger::CONTEXT_GENERAL );
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
```

---

## Loader Class

**File:** `class-ems-loader.php`  
**Production Path:** `event-management-system/includes/class-ems-loader.php`

```php
<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    EventManagementSystem
 * @subpackage Includes
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Loader {

	protected $actions;
	protected $filters;
	private $logger;

	public function __construct() {
		$this->actions = array();
		$this->filters = array();
		$this->logger  = EMS_Logger::instance();
	}

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return $hooks;
	}

	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		$this->logger->debug(
			sprintf(
				'Loader initialized with %d actions and %d filters',
				count( $this->actions ),
				count( $this->filters )
			),
			EMS_Logger::CONTEXT_GENERAL
		);
	}

	public function get_actions_count() {
		return count( $this->actions );
	}

	public function get_filters_count() {
		return count( $this->filters );
	}

	public function get_actions() {
		return $this->actions;
	}

	public function get_filters() {
		return $this->filters;
	}
}
```

---

## Deactivator Class

**File:** `class-ems-deactivator.php`  
**Production Path:** `event-management-system/includes/class-ems-deactivator.php`

```php
<?php
/**
 * Fired during plugin deactivation
 *
 * @package    EventManagementSystem
 * @subpackage Includes
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Deactivator {

	public static function deactivate() {
		$logger = EMS_Logger::instance();
		$logger->info( 'Plugin deactivation started', EMS_Logger::CONTEXT_GENERAL );

		try {
			self::clear_cron_jobs();
			$logger->info( 'Cron jobs cleared', EMS_Logger::CONTEXT_GENERAL );

			flush_rewrite_rules();
			$logger->info( 'Rewrite rules flushed', EMS_Logger::CONTEXT_GENERAL );

			self::clear_transients();
			$logger->info( 'Transients cleared', EMS_Logger::CONTEXT_GENERAL );

			update_option( 'ems_deactivated_at', time() );

			$logger->info( 'Plugin deactivation completed successfully', EMS_Logger::CONTEXT_GENERAL );

		} catch ( Exception $e ) {
			$logger->error(
				'Plugin deactivation error: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL,
				array( 'trace' => $e->getTraceAsString() )
			);
		}
	}

	private static function clear_cron_jobs() {
		$cron_jobs = array(
			'ems_cleanup_logs',
			'ems_cleanup_temp_files',
			'ems_send_event_reminders',
			'ems_send_session_reminders',
		);

		foreach ( $cron_jobs as $job ) {
			$timestamp = wp_next_scheduled( $job );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $job );
			}
			wp_clear_scheduled_hook( $job );
		}
	}

	private static function clear_transients() {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ems_%' 
			OR option_name LIKE '_transient_timeout_ems_%'"
		);

		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ems_rate_limit_%' 
			OR option_name LIKE '_transient_timeout_ems_rate_limit_%'"
		);
	}
}
```

---

## Logger Class

**File:** `class-ems-logger.php`  
**Production Path:** `event-management-system/includes/utilities/class-ems-logger.php`

**Features:**
- PSR-3 compatible log levels
- Context-based log file separation
- Automatic log rotation
- Singleton pattern

```php
<?php
/**
 * Logger utility class
 *
 * @package    EventManagementSystem
 * @subpackage Utilities
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Logger {

	// Log levels (PSR-3 compatible)
	const EMERGENCY = 'EMERGENCY';
	const ALERT     = 'ALERT';
	const CRITICAL  = 'CRITICAL';
	const ERROR     = 'ERROR';
	const WARNING   = 'WARNING';
	const NOTICE    = 'NOTICE';
	const INFO      = 'INFO';
	const DEBUG     = 'DEBUG';

	// Log contexts
	const CONTEXT_GENERAL      = 'general';
	const CONTEXT_REGISTRATION = 'registration';
	const CONTEXT_PAYMENT      = 'payment';
	const CONTEXT_EMAIL        = 'email';
	const CONTEXT_SECURITY     = 'security';
	const CONTEXT_ABSTRACT     = 'abstract';
	const CONTEXT_SCHEDULE     = 'schedule';
	const CONTEXT_FILES        = 'files';

	private static $instance = null;
	private $enabled = true;
	private $min_level = self::DEBUG;
	private $retention_days = 30;

	private $level_priority = array(
		self::DEBUG     => 0,
		self::INFO      => 1,
		self::NOTICE    => 2,
		self::WARNING   => 3,
		self::ERROR     => 4,
		self::CRITICAL  => 5,
		self::ALERT     => 6,
		self::EMERGENCY => 7,
	);

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->ensure_log_directory();
		$this->enabled        = get_option( 'ems_logging_enabled', true );
		$this->min_level      = get_option( 'ems_logging_min_level', self::DEBUG );
		$this->retention_days = get_option( 'ems_log_retention_days', 30 );

		if ( ! wp_next_scheduled( 'ems_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'ems_cleanup_logs' );
		}
	}

	private function ensure_log_directory() {
		if ( ! file_exists( EMS_LOG_DIR ) ) {
			if ( ! wp_mkdir_p( EMS_LOG_DIR ) ) {
				error_log( 'EMS: Failed to create log directory: ' . EMS_LOG_DIR );
				return false;
			}
		}

		$htaccess_file = EMS_LOG_DIR . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			file_put_contents( $htaccess_file, "Order deny,allow\nDeny from all\n" );
		}

		$index_file = EMS_LOG_DIR . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '<?php // Silence is golden' );
		}

		return true;
	}

	public function log( $level, $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		if ( ! $this->enabled ) {
			return false;
		}

		if ( ! $this->should_log_level( $level ) ) {
			return false;
		}

		$level   = $this->sanitize_level( $level );
		$context = $this->sanitize_context( $context );
		$message = $this->sanitize_message( $message );

		$log_entry = $this->format_log_entry( $level, $message, $context, $data );
		$log_file = $this->get_log_file( $context, $level );
		$result = $this->write_to_log( $log_file, $log_entry );

		// Also log errors to consolidated error log
		if ( in_array( $level, array( self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY ) ) ) {
			$error_log_file = $this->get_log_file( 'errors', $level );
			$this->write_to_log( $error_log_file, $log_entry );
		}

		return $result;
	}

	private function should_log_level( $level ) {
		$level_priority     = isset( $this->level_priority[ $level ] ) ? $this->level_priority[ $level ] : 0;
		$min_level_priority = isset( $this->level_priority[ $this->min_level ] ) ? $this->level_priority[ $this->min_level ] : 0;
		return $level_priority >= $min_level_priority;
	}

	private function sanitize_level( $level ) {
		$valid_levels = array(
			self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR,
			self::WARNING, self::NOTICE, self::INFO, self::DEBUG,
		);
		return in_array( $level, $valid_levels ) ? $level : self::INFO;
	}

	private function sanitize_context( $context ) {
		$valid_contexts = array(
			self::CONTEXT_GENERAL, self::CONTEXT_REGISTRATION, self::CONTEXT_PAYMENT,
			self::CONTEXT_EMAIL, self::CONTEXT_SECURITY, self::CONTEXT_ABSTRACT,
			self::CONTEXT_SCHEDULE, self::CONTEXT_FILES,
		);
		return in_array( $context, $valid_contexts ) ? $context : self::CONTEXT_GENERAL;
	}

	private function sanitize_message( $message ) {
		$message = preg_replace( '/\s+/', ' ', $message );
		$message = trim( $message );
		if ( strlen( $message ) > 1000 ) {
			$message = substr( $message, 0, 997 ) . '...';
		}
		return $message;
	}

	private function format_log_entry( $level, $message, $context, $data ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$user_id   = get_current_user_id();
		$user_info = $user_id ? "User $user_id" : 'Guest';

		$log_entry = sprintf(
			'[%s] [%s] [%s] [%s] %s',
			$timestamp, $level, strtoupper( $context ), $user_info, $message
		);

		if ( ! empty( $data ) ) {
			$log_entry .= ' | Data: ' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
		}

		return $log_entry . PHP_EOL;
	}

	private function get_log_file( $context, $level ) {
		$date     = current_time( 'Y-m-d' );
		$filename = sprintf( 'ems-%s-%s.log', $context, $date );
		return EMS_LOG_DIR . $filename;
	}

	private function write_to_log( $file, $entry ) {
		$result = @file_put_contents( $file, $entry, FILE_APPEND | LOCK_EX );
		if ( false === $result ) {
			error_log( 'EMS Log Write Failed: ' . $entry );
			return false;
		}
		return true;
	}

	public function cleanup_old_logs() {
		$deleted = 0;
		if ( ! is_dir( EMS_LOG_DIR ) ) {
			return $deleted;
		}

		$cutoff_timestamp = strtotime( "-{$this->retention_days} days" );
		$files = glob( EMS_LOG_DIR . 'ems-*.log' );
		if ( empty( $files ) ) {
			return $deleted;
		}

		foreach ( $files as $file ) {
			if ( ! is_file( $file ) ) {
				continue;
			}
			if ( filemtime( $file ) < $cutoff_timestamp ) {
				if ( @unlink( $file ) ) {
					$deleted++;
				}
			}
		}

		$this->log( self::INFO, "Log cleanup completed. Deleted $deleted old log file(s).", self::CONTEXT_GENERAL );
		return $deleted;
	}

	// Convenience methods
	public function emergency( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::EMERGENCY, $message, $context, $data );
	}

	public function alert( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::ALERT, $message, $context, $data );
	}

	public function critical( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::CRITICAL, $message, $context, $data );
	}

	public function error( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::ERROR, $message, $context, $data );
	}

	public function warning( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::WARNING, $message, $context, $data );
	}

	public function notice( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::NOTICE, $message, $context, $data );
	}

	public function info( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::INFO, $message, $context, $data );
	}

	public function debug( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::DEBUG, $message, $context, $data );
	}

	public function get_log_contents( $context = self::CONTEXT_GENERAL, $date = null ) {
		if ( null === $date ) {
			$date = current_time( 'Y-m-d' );
		}
		$filename = sprintf( 'ems-%s-%s.log', $context, $date );
		$filepath = EMS_LOG_DIR . $filename;
		if ( ! file_exists( $filepath ) ) {
			return false;
		}
		return file_get_contents( $filepath );
	}

	public function get_log_files( $context = null ) {
		$log_files = array();
		if ( ! is_dir( EMS_LOG_DIR ) ) {
			return $log_files;
		}

		$pattern = $context ? "ems-{$context}-*.log" : 'ems-*.log';
		$files   = glob( EMS_LOG_DIR . $pattern );
		if ( empty( $files ) ) {
			return $log_files;
		}

		foreach ( $files as $file ) {
			$log_files[] = array(
				'filename' => basename( $file ),
				'path'     => $file,
				'size'     => filesize( $file ),
				'modified' => filemtime( $file ),
			);
		}

		usort( $log_files, function ( $a, $b ) {
			return $b['modified'] - $a['modified'];
		} );

		return $log_files;
	}
}
```

---

## Roles Class

**File:** `class-ems-roles.php`  
**Production Path:** `event-management-system/includes/utilities/class-ems-roles.php`

**Roles Created:**
- `ems_convenor` - Event Convenor (full event management)
- `ems_reviewer` - Abstract Reviewer (review assigned abstracts)
- `ems_presenter` - Presenter (submit/manage own abstracts)
- `ems_participant` - Event Participant (view/register)
- `ems_sponsor` - Event Sponsor (sponsor portal access)

```php
<?php
/**
 * User Roles Management
 *
 * @package    EventManagementSystem
 * @subpackage UserRoles
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class EMS_Roles {

	private $logger;
	const CAPS_VERSION = '1.3.0';

	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	public function maybe_create_roles() {
		$current_version = get_option( 'ems_caps_version', '0' );
		
		if ( version_compare( $current_version, self::CAPS_VERSION, '<' ) ) {
			$this->create_roles();
			$this->add_admin_capabilities();
			update_option( 'ems_caps_version', self::CAPS_VERSION );
			$this->logger->info( 'Roles and capabilities updated to version ' . self::CAPS_VERSION, EMS_Logger::CONTEXT_GENERAL );
		}
	}

	public function refresh_capabilities() {
		$this->remove_all_capabilities();
		$this->create_roles();
		$this->add_admin_capabilities();
		update_option( 'ems_caps_version', self::CAPS_VERSION );
		$this->logger->info( 'Capabilities force refreshed', EMS_Logger::CONTEXT_GENERAL );
	}

	private function create_roles() {
		$this->create_convenor_role();
		$this->create_reviewer_role();
		$this->create_presenter_role();
		$this->create_participant_role();
		$this->create_sponsor_role();
	}

	private function create_convenor_role() {
		remove_role( 'ems_convenor' );
		
		add_role(
			'ems_convenor',
			__( 'Event Convenor', 'event-management-system' ),
			array(
				'read'                          => true,
				'upload_files'                  => true,
				'edit_ems_event'                => true,
				'read_ems_event'                => true,
				'delete_ems_event'              => true,
				'edit_ems_events'               => true,
				'edit_others_ems_events'        => true,
				'publish_ems_events'            => true,
				'read_private_ems_events'       => true,
				'delete_ems_events'             => true,
				'delete_private_ems_events'     => true,
				'delete_published_ems_events'   => true,
				'delete_others_ems_events'      => true,
				'edit_private_ems_events'       => true,
				'edit_published_ems_events'     => true,
				'edit_ems_session'              => true,
				'read_ems_session'              => true,
				'delete_ems_session'            => true,
				'edit_ems_sessions'             => true,
				'edit_others_ems_sessions'      => true,
				'publish_ems_sessions'          => true,
				'read_private_ems_sessions'     => true,
				'delete_ems_sessions'           => true,
				'delete_private_ems_sessions'   => true,
				'delete_published_ems_sessions' => true,
				'delete_others_ems_sessions'    => true,
				'edit_private_ems_sessions'     => true,
				'edit_published_ems_sessions'   => true,
				'edit_ems_abstract'             => true,
				'read_ems_abstract'             => true,
				'edit_ems_abstracts'            => true,
				'edit_others_ems_abstracts'     => true,
				'read_private_ems_abstracts'    => true,
				'view_ems_registrations'        => true,
				'export_ems_registrations'      => true,
				'add_ems_registration'          => true,
				'cancel_ems_registration'       => true,
				'review_ems_abstracts'          => true,
				'approve_ems_abstracts'         => true,
				'reject_ems_abstracts'          => true,
				'manage_ems_schedule'           => true,
				'publish_ems_schedule'          => true,
				'manage_ems_settings'           => false,
			)
		);
	}

	private function create_reviewer_role() {
		remove_role( 'ems_reviewer' );
		
		add_role(
			'ems_reviewer',
			__( 'Abstract Reviewer', 'event-management-system' ),
			array(
				'read'                       => true,
				'read_ems_event'             => true,
				'read_ems_events'            => true,
				'read_ems_abstract'          => true,
				'read_ems_abstracts'         => true,
				'edit_ems_abstract'          => true,
				'read_private_ems_abstracts' => true,
				'review_ems_abstracts'       => true,
			)
		);
	}

	private function create_presenter_role() {
		remove_role( 'ems_presenter' );
		
		add_role(
			'ems_presenter',
			__( 'Presenter', 'event-management-system' ),
			array(
				'read'                    => true,
				'upload_files'            => true,
				'read_ems_event'          => true,
				'read_ems_events'         => true,
				'edit_ems_abstract'       => true,
				'read_ems_abstract'       => true,
				'delete_ems_abstract'     => true,
				'edit_ems_abstracts'      => false,
				'publish_ems_abstracts'   => false,
				'submit_ems_abstract'     => true,
				'edit_own_ems_abstract'   => true,
				'delete_own_ems_abstract' => true,
				'upload_ems_files'        => true,
				'view_own_ems_sessions'   => true,
			)
		);
	}

	private function create_participant_role() {
		remove_role( 'ems_participant' );
		
		add_role(
			'ems_participant',
			__( 'Event Participant', 'event-management-system' ),
			array(
				'read'                        => true,
				'read_ems_event'              => true,
				'read_ems_events'             => true,
				'read_ems_session'            => true,
				'read_ems_sessions'           => true,
				'read_published_ems_schedule' => true,
				'register_for_ems_event'      => true,
				'register_for_ems_session'    => true,
				'download_ems_public_files'   => true,
			)
		);
	}

	private function create_sponsor_role() {
		remove_role( 'ems_sponsor' );
		
		add_role(
			'ems_sponsor',
			__( 'Event Sponsor', 'event-management-system' ),
			array(
				'read'                       => true,
				'upload_files'               => true,
				'access_ems_sponsor_portal'  => true,
				'upload_ems_sponsor_files'   => true,
				'download_ems_sponsor_files' => true,
				'view_linked_ems_events'     => true,
			)
		);
	}

	private function add_admin_capabilities() {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			$this->logger->error( 'Administrator role not found', EMS_Logger::CONTEXT_GENERAL );
			return;
		}

		$caps = $this->get_all_capabilities();
		foreach ( $caps as $cap ) {
			$admin->add_cap( $cap );
		}
		$this->logger->debug( 'Administrator capabilities updated', EMS_Logger::CONTEXT_GENERAL );
	}

	public function get_all_capabilities() {
		return array(
			'edit_ems_event', 'read_ems_event', 'delete_ems_event',
			'edit_ems_events', 'edit_others_ems_events', 'publish_ems_events',
			'read_private_ems_events', 'delete_ems_events', 'delete_private_ems_events',
			'delete_published_ems_events', 'delete_others_ems_events',
			'edit_private_ems_events', 'edit_published_ems_events',
			'edit_ems_session', 'read_ems_session', 'delete_ems_session',
			'edit_ems_sessions', 'edit_others_ems_sessions', 'publish_ems_sessions',
			'read_private_ems_sessions', 'delete_ems_sessions', 'delete_private_ems_sessions',
			'delete_published_ems_sessions', 'delete_others_ems_sessions',
			'edit_private_ems_sessions', 'edit_published_ems_sessions',
			'edit_ems_abstract', 'read_ems_abstract', 'delete_ems_abstract',
			'edit_ems_abstracts', 'edit_others_ems_abstracts', 'publish_ems_abstracts',
			'read_private_ems_abstracts', 'delete_ems_abstracts', 'delete_private_ems_abstracts',
			'delete_published_ems_abstracts', 'delete_others_ems_abstracts',
			'edit_private_ems_abstracts', 'edit_published_ems_abstracts',
			'view_ems_registrations', 'export_ems_registrations', 'add_ems_registration',
			'cancel_ems_registration', 'review_ems_abstracts', 'approve_ems_abstracts',
			'reject_ems_abstracts', 'manage_ems_schedule', 'publish_ems_schedule',
			'manage_ems_settings', 'manage_ems_users', 'submit_ems_abstract',
			'upload_ems_files', 'access_ems_sponsor_portal',
		);
	}

	private function remove_all_capabilities() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$caps = $this->get_all_capabilities();
		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	public static function get_ems_roles() {
		return array(
			'ems_convenor'    => __( 'Event Convenor', 'event-management-system' ),
			'ems_reviewer'    => __( 'Abstract Reviewer', 'event-management-system' ),
			'ems_presenter'   => __( 'Presenter', 'event-management-system' ),
			'ems_participant' => __( 'Event Participant', 'event-management-system' ),
			'ems_sponsor'     => __( 'Event Sponsor', 'event-management-system' ),
		);
	}

	public static function assign_role( $user_id, $role ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$ems_roles = array_keys( self::get_ems_roles() );
		if ( ! in_array( $role, $ems_roles, true ) ) {
			return false;
		}

		foreach ( $ems_roles as $ems_role ) {
			$user->remove_role( $ems_role );
		}

		$user->add_role( $role );
		return true;
	}

	public static function remove_ems_roles( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$ems_roles = array_keys( self::get_ems_roles() );
		foreach ( $ems_roles as $ems_role ) {
			$user->remove_role( $ems_role );
		}
		return true;
	}

	public static function get_users_by_role( $role ) {
		return get_users( array(
			'role'    => $role,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		) );
	}

	public static function get_user_ems_role( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		$ems_roles = array_keys( self::get_ems_roles() );
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $ems_roles, true ) ) {
				return $role;
			}
		}
		return false;
	}
}
```

---

## Remaining Utility Classes

The remaining utility classes (Security, Validator, User Helper, Date Helper) follow similar patterns. View them in the project directory:

- `class-ems-security.php` - CSRF protection, rate limiting, capability checks
- `class-ems-validator.php` - Input validation for all forms
- `class-ems-user-helper.php` - User management utilities
- `class-ems-date-helper.php` - Date formatting and timezone handling

---

## Activator Class

**File:** `class-ems-activator.php`  
**Production Path:** `event-management-system/includes/class-ems-activator.php`

*Note: Full code in project directory. Key responsibilities:*

- Create database tables (registrations, waitlist, reviews, notifications)
- Set up directory structure with security
- Register post types for rewrite rules
- Set default options
- Schedule cron jobs
- Create standard pages with shortcodes
- Initialize user roles

**Database Schema:** See `project-manifest.md` for complete table definitions.

---

## Usage Notes

### Loading Order
1. Main plugin file defines constants
2. Activator runs on activation (creates tables, directories, roles)
3. Core class loads all dependencies
4. Loader registers all actions/filters
5. `run()` executes all hooks

### Logging
```php
// Get logger instance
$logger = EMS_Logger::instance();

// Log with context
$logger->info( 'Registration created', EMS_Logger::CONTEXT_REGISTRATION, array( 'id' => $reg_id ) );
$logger->error( 'Payment failed', EMS_Logger::CONTEXT_PAYMENT, array( 'error' => $message ) );
```

### Role Checks
```php
// Check capability
if ( current_user_can( 'review_ems_abstracts' ) ) {
    // Show review interface
}

// Get users by role
$reviewers = EMS_Roles::get_users_by_role( 'ems_reviewer' );
```
