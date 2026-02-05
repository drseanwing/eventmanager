<?php
/**
 * PHPUnit Bootstrap File for EMS Plugin Tests
 *
 * This file bootstraps the WordPress test environment for PHPUnit tests.
 * For a complete WordPress test environment, install WordPress test suite:
 *
 * bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
 *
 * @package EventManagementSystem
 * @subpackage Tests
 */

// Define test environment constants
define( 'EMS_PLUGIN_DIR', dirname( __DIR__ ) . '/plugin/' );
define( 'EMS_TESTS_DIR', __DIR__ );

// Load WordPress test environment if available
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// If WordPress test suite is available, load it
if ( file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	require_once $_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin being tested.
	 */
	function _manually_load_plugin() {
		require EMS_PLUGIN_DIR . 'event-management-system.php';
	}
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	// Start up the WP testing environment
	require $_tests_dir . '/includes/bootstrap.php';
} else {
	// Fallback: Load WordPress core functions manually
	echo "Warning: WordPress test suite not found. Tests will run in limited mode.\n";
	echo "Install WordPress test suite for full integration testing.\n\n";

	// Define WordPress constants if not already defined
	if ( ! defined( 'WPINC' ) ) {
		define( 'WPINC', 'wp-includes' );
	}

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/wordpress/' );
	}

	// Load required plugin files
	require_once EMS_PLUGIN_DIR . 'includes/class-ems-logger.php';
	require_once EMS_PLUGIN_DIR . 'includes/sponsor/class-ems-sponsorship-levels.php';
	require_once EMS_PLUGIN_DIR . 'includes/utilities/class-ems-validator.php';
	require_once EMS_PLUGIN_DIR . 'includes/sponsor/class-ems-sponsor-meta.php';
}
