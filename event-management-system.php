<?php
/**
 * Plugin Name: Event Management System
 * Plugin URI: https://github.com/drseanwing/eventmanager
 * Description: Comprehensive event management system for conferences, courses, and seminars with registration, abstract submission, schedule building, and sponsor collaboration.
 * Version: 1.6.0
 * Author: Royal Brisbane & Women's Hospital
 * Author URI: https://www.health.qld.gov.au/rbwh
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-management-system
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/drseanwing/eventmanager
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
define( 'EMS_VERSION', '1.6.0' );

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
