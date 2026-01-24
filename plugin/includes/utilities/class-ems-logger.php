<?php
/**
 * Logger utility class
 *
 * Provides comprehensive logging functionality with multiple severity levels,
 * separate log files for different contexts, and automatic log rotation.
 *
 * @package    EventManagementSystem
 * @subpackage Utilities
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Logger class.
 *
 * Handles all logging operations for the plugin with support for multiple
 * log levels, contexts, and automatic file rotation.
 *
 * @since 1.0.0
 */
class EMS_Logger {

	/**
	 * Log levels (PSR-3 compatible)
	 */
	const EMERGENCY = 'EMERGENCY'; // System is unusable
	const ALERT     = 'ALERT';     // Action must be taken immediately
	const CRITICAL  = 'CRITICAL';  // Critical conditions
	const ERROR     = 'ERROR';     // Error conditions
	const WARNING   = 'WARNING';   // Warning conditions
	const NOTICE    = 'NOTICE';    // Normal but significant condition
	const INFO      = 'INFO';      // Informational messages
	const DEBUG     = 'DEBUG';     // Debug-level messages

	/**
	 * Log contexts (determines which log file to write to)
	 */
	const CONTEXT_GENERAL      = 'general';
	const CONTEXT_REGISTRATION = 'registration';
	const CONTEXT_PAYMENT      = 'payment';
	const CONTEXT_EMAIL        = 'email';
	const CONTEXT_SECURITY     = 'security';
	const CONTEXT_ABSTRACT     = 'abstract';
	const CONTEXT_SCHEDULE     = 'schedule';
	const CONTEXT_FILES        = 'files';

	/**
	 * Singleton instance
	 *
	 * @var EMS_Logger
	 */
	private static $instance = null;

	/**
	 * Whether logging is enabled
	 *
	 * @var bool
	 */
	private $enabled = true;

	/**
	 * Minimum log level to record
	 *
	 * @var string
	 */
	private $min_level = self::DEBUG;

	/**
	 * Log retention period in days
	 *
	 * @var int
	 */
	private $retention_days = 30;

	/**
	 * Priority levels for comparison
	 *
	 * @var array
	 */
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

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return EMS_Logger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Ensure log directory exists
		$this->ensure_log_directory();

		// Load settings from WordPress options
		$this->enabled        = get_option( 'ems_logging_enabled', true );
		$this->min_level      = get_option( 'ems_logging_min_level', self::DEBUG );
		$this->retention_days = get_option( 'ems_log_retention_days', 30 );

		// Schedule log cleanup
		if ( ! wp_next_scheduled( 'ems_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'ems_cleanup_logs' );
		}
	}

	/**
	 * Ensure log directory exists and is protected
	 *
	 * @since 1.0.0
	 * @return bool Whether directory is ready
	 */
	private function ensure_log_directory() {
		if ( ! file_exists( EMS_LOG_DIR ) ) {
			if ( ! wp_mkdir_p( EMS_LOG_DIR ) ) {
				error_log( 'EMS: Failed to create log directory: ' . EMS_LOG_DIR );
				return false;
			}
		}

		// Create .htaccess to protect log files
		$htaccess_file = EMS_LOG_DIR . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Order deny,allow\nDeny from all\n";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Create index.php to prevent directory listing
		$index_file = EMS_LOG_DIR . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '<?php // Silence is golden' );
		}

		return true;
	}

	/**
	 * Log a message
	 *
	 * @since 1.0.0
	 * @param string $level   Log level (use class constants)
	 * @param string $message Log message
	 * @param string $context Log context (determines log file)
	 * @param array  $data    Additional data to log (optional)
	 * @return bool Whether logging was successful
	 */
	public function log( $level, $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		// Check if logging is enabled
		if ( ! $this->enabled ) {
			return false;
		}

		// Check if this level should be logged
		if ( ! $this->should_log_level( $level ) ) {
			return false;
		}

		// Sanitize inputs
		$level   = $this->sanitize_level( $level );
		$context = $this->sanitize_context( $context );
		$message = $this->sanitize_message( $message );

		// Build log entry
		$log_entry = $this->format_log_entry( $level, $message, $context, $data );

		// Determine log file
		$log_file = $this->get_log_file( $context, $level );

		// Write to log file
		$result = $this->write_to_log( $log_file, $log_entry );

		// Also log errors to a consolidated error log
		if ( in_array( $level, array( self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY ) ) ) {
			$error_log_file = $this->get_log_file( 'errors', $level );
			$this->write_to_log( $error_log_file, $log_entry );
		}

		return $result;
	}

	/**
	 * Check if a level should be logged based on minimum level
	 *
	 * @since 1.0.0
	 * @param string $level Level to check
	 * @return bool
	 */
	private function should_log_level( $level ) {
		$level_priority     = isset( $this->level_priority[ $level ] ) ? $this->level_priority[ $level ] : 0;
		$min_level_priority = isset( $this->level_priority[ $this->min_level ] ) ? $this->level_priority[ $this->min_level ] : 0;

		return $level_priority >= $min_level_priority;
	}

	/**
	 * Sanitize log level
	 *
	 * @since 1.0.0
	 * @param string $level Level to sanitize
	 * @return string Valid level
	 */
	private function sanitize_level( $level ) {
		$valid_levels = array(
			self::EMERGENCY,
			self::ALERT,
			self::CRITICAL,
			self::ERROR,
			self::WARNING,
			self::NOTICE,
			self::INFO,
			self::DEBUG,
		);

		return in_array( $level, $valid_levels ) ? $level : self::INFO;
	}

	/**
	 * Sanitize log context
	 *
	 * @since 1.0.0
	 * @param string $context Context to sanitize
	 * @return string Valid context
	 */
	private function sanitize_context( $context ) {
		$valid_contexts = array(
			self::CONTEXT_GENERAL,
			self::CONTEXT_REGISTRATION,
			self::CONTEXT_PAYMENT,
			self::CONTEXT_EMAIL,
			self::CONTEXT_SECURITY,
			self::CONTEXT_ABSTRACT,
			self::CONTEXT_SCHEDULE,
			self::CONTEXT_FILES,
		);

		return in_array( $context, $valid_contexts ) ? $context : self::CONTEXT_GENERAL;
	}

	/**
	 * Sanitize log message
	 *
	 * @since 1.0.0
	 * @param string $message Message to sanitize
	 * @return string Sanitized message
	 */
	private function sanitize_message( $message ) {
		// Remove any line breaks and excessive whitespace
		$message = preg_replace( '/\s+/', ' ', $message );
		$message = trim( $message );

		// Limit message length
		if ( strlen( $message ) > 1000 ) {
			$message = substr( $message, 0, 997 ) . '...';
		}

		return $message;
	}

	/**
	 * Format log entry
	 *
	 * @since 1.0.0
	 * @param string $level   Log level
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return string Formatted log entry
	 */
	private function format_log_entry( $level, $message, $context, $data ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$user_id   = get_current_user_id();
		$user_info = $user_id ? "User $user_id" : 'Guest';

		$log_entry = sprintf(
			'[%s] [%s] [%s] [%s] %s',
			$timestamp,
			$level,
			strtoupper( $context ),
			$user_info,
			$message
		);

		// Add additional data if provided
		if ( ! empty( $data ) ) {
			$log_entry .= ' | Data: ' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
		}

		return $log_entry . PHP_EOL;
	}

	/**
	 * Get log file path
	 *
	 * @since 1.0.0
	 * @param string $context Log context
	 * @param string $level   Log level
	 * @return string Log file path
	 */
	private function get_log_file( $context, $level ) {
		$date     = current_time( 'Y-m-d' );
		$filename = sprintf( 'ems-%s-%s.log', $context, $date );
		return EMS_LOG_DIR . $filename;
	}

	/**
	 * Write to log file
	 *
	 * @since 1.0.0
	 * @param string $file  Log file path
	 * @param string $entry Log entry
	 * @return bool Whether write was successful
	 */
	private function write_to_log( $file, $entry ) {
		// Attempt to write to file
		$result = @file_put_contents( $file, $entry, FILE_APPEND | LOCK_EX );

		if ( false === $result ) {
			// Fall back to error_log if file write fails
			error_log( 'EMS Log Write Failed: ' . $entry );
			return false;
		}

		return true;
	}

	/**
	 * Clean up old log files
	 *
	 * @since 1.0.0
	 * @return int Number of files deleted
	 */
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

			$file_time = filemtime( $file );
			if ( $file_time < $cutoff_timestamp ) {
				if ( @unlink( $file ) ) {
					$deleted++;
				}
			}
		}

		$this->log(
			self::INFO,
			"Log cleanup completed. Deleted $deleted old log file(s).",
			self::CONTEXT_GENERAL
		);

		return $deleted;
	}

	/**
	 * Convenience method: Log emergency
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function emergency( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::EMERGENCY, $message, $context, $data );
	}

	/**
	 * Convenience method: Log alert
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function alert( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::ALERT, $message, $context, $data );
	}

	/**
	 * Convenience method: Log critical
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function critical( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::CRITICAL, $message, $context, $data );
	}

	/**
	 * Convenience method: Log error
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function error( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::ERROR, $message, $context, $data );
	}

	/**
	 * Convenience method: Log warning
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function warning( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::WARNING, $message, $context, $data );
	}

	/**
	 * Convenience method: Log notice
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function notice( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::NOTICE, $message, $context, $data );
	}

	/**
	 * Convenience method: Log info
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function info( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::INFO, $message, $context, $data );
	}

	/**
	 * Convenience method: Log debug
	 *
	 * @since 1.0.0
	 * @param string $message Log message
	 * @param string $context Log context
	 * @param array  $data    Additional data
	 * @return bool
	 */
	public function debug( $message, $context = self::CONTEXT_GENERAL, $data = array() ) {
		return $this->log( self::DEBUG, $message, $context, $data );
	}

	/**
	 * Get log file contents
	 *
	 * @since 1.0.0
	 * @param string $context Log context
	 * @param string $date    Date (Y-m-d format, defaults to today)
	 * @return string|false Log file contents or false on failure
	 */
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

	/**
	 * Get list of available log files
	 *
	 * @since 1.0.0
	 * @param string $context Optional context filter
	 * @return array Array of log file information
	 */
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

		// Sort by modified time, newest first
		usort(
			$log_files,
			function ( $a, $b ) {
				return $b['modified'] - $a['modified'];
			}
		);

		return $log_files;
	}
}
