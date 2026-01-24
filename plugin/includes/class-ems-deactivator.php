<?php
/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0.0
 */
class EMS_Deactivator {

	/**
	 * Plugin deactivation handler
	 *
	 * Clears scheduled cron jobs, flushes rewrite rules, and performs cleanup.
	 * Note: We don't delete database tables or files on deactivation - only on uninstall.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Log deactivation
		$logger = EMS_Logger::instance();
		$logger->info( 'Plugin deactivation started', EMS_Logger::CONTEXT_GENERAL );

		try {
			// Clear all scheduled cron jobs
			self::clear_cron_jobs();
			$logger->info( 'Cron jobs cleared', EMS_Logger::CONTEXT_GENERAL );

			// Flush rewrite rules
			flush_rewrite_rules();
			$logger->info( 'Rewrite rules flushed', EMS_Logger::CONTEXT_GENERAL );

			// Clear transients
			self::clear_transients();
			$logger->info( 'Transients cleared', EMS_Logger::CONTEXT_GENERAL );

			// Store deactivation timestamp
			update_option( 'ems_deactivated_at', time() );

			$logger->info( 'Plugin deactivation completed successfully', EMS_Logger::CONTEXT_GENERAL );

		} catch ( Exception $e ) {
			$logger->error(
				'Plugin deactivation error: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL,
				array(
					'trace' => $e->getTraceAsString(),
				)
			);
		}
	}

	/**
	 * Clear all scheduled cron jobs
	 *
	 * @since 1.0.0
	 */
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
			// Also clear any hooks that might remain
			wp_clear_scheduled_hook( $job );
		}
	}

	/**
	 * Clear plugin-related transients
	 *
	 * @since 1.0.0
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all EMS transients (they start with 'ems_')
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ems_%' 
			OR option_name LIKE '_transient_timeout_ems_%'"
		);

		// Clear rate limit transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ems_rate_limit_%' 
			OR option_name LIKE '_transient_timeout_ems_rate_limit_%'"
		);
	}
}
