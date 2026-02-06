<?php
/**
 * Date Helper Utility
 *
 * Provides timezone-aware date comparison and formatting for the EMS plugin.
 * Handles datetime-local input values correctly with WordPress timezone settings.
 *
 * @package    EventManagementSystem
 * @subpackage Utilities
 * @since      1.3.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * EMS Date Helper Class
 *
 * @since 1.3.0
 */
class EMS_Date_Helper {

	/**
	 * Convert a datetime-local string to Unix timestamp in WordPress timezone
	 *
	 * datetime-local inputs store values like "2024-01-16T10:00" which represent
	 * local time (the user's browser time). We need to interpret these as being
	 * in the WordPress site's configured timezone.
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime string (e.g., "2024-01-16T10:00" or "2024-01-16").
	 * @return int|false Unix timestamp or false on failure.
	 */
	public static function to_timestamp( $datetime_string ) {
		if ( empty( $datetime_string ) ) {
			return false;
		}

		try {
			// Get WordPress timezone
			$wp_timezone = wp_timezone();
			
			// Normalize datetime-local format (convert T to space if present)
			$normalized = str_replace( 'T', ' ', $datetime_string );
			
			// If no time component, add midnight
			if ( strlen( $normalized ) === 10 ) { // Just date: YYYY-MM-DD
				$normalized .= ' 00:00:00';
			} elseif ( strlen( $normalized ) === 16 ) { // Date + HH:MM
				$normalized .= ':00';
			}
			
			// Create DateTime object in WordPress timezone
			$dt = new DateTime( $normalized, $wp_timezone );
			
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			// Fallback to strtotime if DateTime fails
			return strtotime( $datetime_string );
		}
	}

	/**
	 * Get current timestamp in WordPress timezone
	 *
	 * @since 1.3.0
	 * @return int Unix timestamp.
	 */
	public static function current_timestamp() {
		return current_time( 'timestamp', false );
	}

	/**
	 * Check if a datetime has passed (is in the past)
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime to check.
	 * @return bool True if datetime is in the past.
	 */
	public static function has_passed( $datetime_string ) {
		if ( empty( $datetime_string ) ) {
			return false;
		}

		$timestamp = self::to_timestamp( $datetime_string );
		if ( false === $timestamp ) {
			return false;
		}

		return $timestamp < self::current_timestamp();
	}

	/**
	 * Check if a datetime is in the future (hasn't arrived yet)
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime to check.
	 * @return bool True if datetime is in the future.
	 */
	public static function is_future( $datetime_string ) {
		if ( empty( $datetime_string ) ) {
			return false;
		}

		$timestamp = self::to_timestamp( $datetime_string );
		if ( false === $timestamp ) {
			return false;
		}

		return $timestamp > self::current_timestamp();
	}

	/**
	 * Check if current time is within a date range
	 *
	 * @since 1.3.0
	 * @param string $start_datetime Start datetime string (empty = no start limit).
	 * @param string $end_datetime   End datetime string (empty = no end limit).
	 * @return bool True if currently within range.
	 */
	public static function is_within_range( $start_datetime, $end_datetime ) {
		$now = self::current_timestamp();

		// Check start date
		if ( ! empty( $start_datetime ) ) {
			$start_ts = self::to_timestamp( $start_datetime );
			if ( false !== $start_ts && $start_ts > $now ) {
				return false; // Not yet started
			}
		}

		// Check end date
		if ( ! empty( $end_datetime ) ) {
			$end_ts = self::to_timestamp( $end_datetime );
			if ( false !== $end_ts && $end_ts < $now ) {
				return false; // Already ended
			}
		}

		return true;
	}

	/**
	 * Format datetime for display
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime string.
	 * @param string $format          PHP date format (default: WordPress date + time format).
	 * @return string Formatted date string.
	 */
	public static function format( $datetime_string, $format = '' ) {
		if ( empty( $datetime_string ) ) {
			return '';
		}

		if ( empty( $format ) ) {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}

		$timestamp = self::to_timestamp( $datetime_string );
		if ( false === $timestamp ) {
			return $datetime_string; // Return original if conversion fails
		}

		return wp_date( $format, $timestamp );
	}

	/**
	 * Format datetime for date-only display
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime string.
	 * @return string Formatted date string.
	 */
	public static function format_date( $datetime_string ) {
		return self::format( $datetime_string, get_option( 'date_format' ) );
	}

	/**
	 * Format datetime for time-only display
	 *
	 * @since 1.6.0
	 * @param string $datetime_string The datetime string.
	 * @return string Formatted time string.
	 */
	public static function format_time( $datetime_string ) {
		return self::format( $datetime_string, get_option( 'time_format' ) );
	}

	/**
	 * Get time until/since a datetime
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime string.
	 * @return string Human-readable time difference.
	 */
	public static function time_diff( $datetime_string ) {
		if ( empty( $datetime_string ) ) {
			return '';
		}

		$timestamp = self::to_timestamp( $datetime_string );
		if ( false === $timestamp ) {
			return '';
		}

		return human_time_diff( $timestamp, self::current_timestamp() );
	}

	/**
	 * Debug helper: Output timezone information
	 *
	 * Useful for debugging timezone issues.
	 *
	 * @since 1.3.0
	 * @param string $datetime_string The datetime string to debug.
	 * @return array Debug information.
	 */
	public static function debug_datetime( $datetime_string ) {
		$wp_timezone = wp_timezone();
		
		return array(
			'input'              => $datetime_string,
			'wp_timezone'        => $wp_timezone->getName(),
			'wp_offset'          => $wp_timezone->getOffset( new DateTime() ) / 3600,
			'current_time'       => current_time( 'Y-m-d H:i:s' ),
			'current_timestamp'  => self::current_timestamp(),
			'input_timestamp'    => self::to_timestamp( $datetime_string ),
			'is_past'            => self::has_passed( $datetime_string ),
			'is_future'          => self::is_future( $datetime_string ),
			'formatted'          => self::format( $datetime_string ),
		);
	}
}
