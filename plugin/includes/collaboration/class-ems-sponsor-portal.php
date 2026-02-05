<?php
/**
 * Sponsor Portal
 *
 * Handles sponsor file sharing and collaboration.
 * Provides sponsors with a dashboard to:
 * - View linked events
 * - Upload files for events
 * - Manage uploaded files
 * - Download shared files
 *
 * @package    EventManagementSystem
 * @subpackage Collaboration
 * @since      1.0.0
 * @version    1.4.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sponsor Portal Class
 *
 * Manages sponsor portal functionality including file uploads,
 * event associations, and sponsor dashboard display.
 *
 * @since 1.0.0
 */
class EMS_Sponsor_Portal {
	
	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * File manager instance
	 *
	 * @var EMS_File_Manager
	 */
	private $file_manager;

	/**
	 * Email manager instance
	 *
	 * @var EMS_Email_Manager
	 */
	private $email_manager;

	/**
	 * Security utility instance
	 *
	 * @var EMS_Security
	 */
	private $security;

	/**
	 * Validator instance
	 *
	 * @var EMS_Validator
	 */
	private $validator;

	/**
	 * Allowed file types for sponsor uploads
	 *
	 * @var array
	 */
	private $allowed_types = array(
		'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
		'jpg', 'jpeg', 'png', 'gif', 'zip', 'mp4', 'mov'
	);

	/**
	 * Maximum file size in bytes (25MB)
	 *
	 * @var int
	 */
	private $max_file_size = 26214400;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
		$this->file_manager = new EMS_File_Manager();
		$this->email_manager = new EMS_Email_Manager();
		$this->security = new EMS_Security();
		$this->validator = new EMS_Validator();

		// Allow custom file size limit via filter
		$this->max_file_size = apply_filters( 'ems_sponsor_max_file_size', $this->max_file_size );
		
		// Allow custom file types via filter
		$this->allowed_types = apply_filters( 'ems_sponsor_allowed_file_types', $this->allowed_types );

		$this->logger->info( 'EMS_Sponsor_Portal initialized', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Link sponsor to event
	 *
	 * Creates association between a sponsor post and an event.
	 *
	 * @since 1.4.0
	 * @param int    $sponsor_id   Sponsor post ID.
	 * @param int    $event_id     Event post ID.
	 * @param string $sponsor_level Optional sponsor level (e.g., 'Gold', 'Silver').
	 * @return array Result with success status and message
	 */
	public function link_sponsor_to_event( $sponsor_id, $event_id, $sponsor_level = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_events';

		try {
			// Validate inputs
			$sponsor_id = absint( $sponsor_id );
			$event_id = absint( $event_id );

			// Verify sponsor and event exist
			$sponsor = get_post( $sponsor_id );
			$event = get_post( $event_id );

			if ( ! $sponsor || 'ems_sponsor' !== $sponsor->post_type ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid sponsor', 'event-management-system' ),
				);
			}

			if ( ! $event || 'ems_event' !== $event->post_type ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid event', 'event-management-system' ),
				);
			}

			// Check if already linked
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE sponsor_id = %d AND event_id = %d",
				$sponsor_id,
				$event_id
			) );

			if ( $existing ) {
				// Update sponsor level if different
				if ( $sponsor_level ) {
					$wpdb->update(
						$table_name,
						array( 'sponsor_level' => sanitize_text_field( $sponsor_level ) ),
						array( 'id' => $existing ),
						array( '%s' ),
						array( '%d' )
					);
				}

				return array(
					'success' => true,
					'message' => __( 'Sponsor already linked to event', 'event-management-system' ),
				);
			}

			// Create link
			$result = $wpdb->insert(
				$table_name,
				array(
					'sponsor_id'    => $sponsor_id,
					'event_id'      => $event_id,
					'sponsor_level' => sanitize_text_field( $sponsor_level ),
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				throw new Exception( 'Database insert failed' );
			}

			$this->logger->info(
				"Sponsor {$sponsor_id} linked to event {$event_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Sponsor linked to event successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in link_sponsor_to_event: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => false,
				'message' => __( 'Failed to link sponsor to event', 'event-management-system' ),
			);
		}
	}

	/**
	 * Unlink sponsor from event
	 *
	 * @since 1.4.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @param int $event_id   Event post ID.
	 * @return array Result with success status and message
	 */
	public function unlink_sponsor_from_event( $sponsor_id, $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_events';

		try {
			$result = $wpdb->delete(
				$table_name,
				array(
					'sponsor_id' => absint( $sponsor_id ),
					'event_id'   => absint( $event_id ),
				),
				array( '%d', '%d' )
			);

			if ( false === $result || 0 === $result ) {
				return array(
					'success' => false,
					'message' => __( 'Sponsor link not found', 'event-management-system' ),
				);
			}

			$this->logger->info(
				"Sponsor {$sponsor_id} unlinked from event {$event_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Sponsor unlinked from event successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in unlink_sponsor_from_event: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => false,
				'message' => __( 'Failed to unlink sponsor from event', 'event-management-system' ),
			);
		}
	}

	/**
	 * Get events linked to sponsor
	 *
	 * @since 1.4.0
	 * @param int $sponsor_id Sponsor post ID or 0 for current user's sponsor.
	 * @return array Array of event data
	 */
	public function get_sponsor_events( $sponsor_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_events';

		try {
			// If no sponsor ID provided, get from current user
			if ( 0 === $sponsor_id ) {
				$sponsor_id = $this->get_user_sponsor_id( get_current_user_id() );
				if ( ! $sponsor_id ) {
					return array();
				}
			}

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE sponsor_id = %d ORDER BY created_at DESC",
				absint( $sponsor_id )
			) );

			$events = array();
			foreach ( $results as $row ) {
				$event = get_post( $row->event_id );
				if ( $event ) {
					$events[] = array(
						'event_id'      => $row->event_id,
						'event_title'   => $event->post_title,
						'event_date'    => get_post_meta( $row->event_id, 'event_start_date', true ),
						'sponsor_level' => $row->sponsor_level,
						'linked_at'     => $row->created_at,
					);
				}
			}

			return $events;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_sponsor_events: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array();
		}
	}

	/**
	 * Get sponsors linked to event
	 *
	 * @since 1.4.0
	 * @param int $event_id Event post ID.
	 * @return array Array of sponsor data
	 */
	public function get_event_sponsors( $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_events';

		try {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE event_id = %d ORDER BY created_at ASC",
				absint( $event_id )
			) );

			$sponsors = array();
			foreach ( $results as $row ) {
				$sponsor = get_post( $row->sponsor_id );
				if ( $sponsor ) {
					$sponsors[] = array(
						'sponsor_id'    => $row->sponsor_id,
						'sponsor_title' => $sponsor->post_title,
						'sponsor_level' => $row->sponsor_level,
						'linked_at'     => $row->created_at,
					);
				}
			}

			return $sponsors;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_event_sponsors: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array();
		}
	}

	/**
	 * Upload sponsor file
	 *
	 * @since 1.4.0
	 * @param array $file_data File data from $_FILES.
	 * @param int   $sponsor_id Sponsor post ID.
	 * @param int   $event_id Event post ID.
	 * @param array $metadata Additional metadata (description, visibility).
	 * @return array Result with success status and file_id or message
	 */
	public function upload_sponsor_file( $file_data, $sponsor_id, $event_id, $metadata = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_files';

		try {
			// Validate inputs
			$sponsor_id = absint( $sponsor_id );
			$event_id = absint( $event_id );
			$user_id = get_current_user_id();

			// Check if user has sponsor capability
			if ( ! current_user_can( 'upload_ems_sponsor_files' ) ) {
				return array(
					'success' => false,
					'message' => __( 'You do not have permission to upload files', 'event-management-system' ),
				);
			}

			// Validate file
			$validation = $this->validate_sponsor_file( $file_data );
			if ( ! $validation['valid'] ) {
				return array(
					'success' => false,
					'message' => $validation['message'],
				);
			}

			// Verify sponsor and event exist and are linked
			$is_linked = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}ems_sponsor_events WHERE sponsor_id = %d AND event_id = %d",
				$sponsor_id,
				$event_id
			) );

			if ( ! $is_linked ) {
				return array(
					'success' => false,
					'message' => __( 'Sponsor is not linked to this event', 'event-management-system' ),
				);
			}

			// Handle file upload
			$upload_dir = EMS_UPLOAD_DIR . 'sponsors/' . $sponsor_id . '/';
			if ( ! file_exists( $upload_dir ) ) {
				wp_mkdir_p( $upload_dir );
			}

			// Generate unique filename
			$file_extension = strtolower( pathinfo( $file_data['name'], PATHINFO_EXTENSION ) );
			$file_name = sanitize_file_name( pathinfo( $file_data['name'], PATHINFO_FILENAME ) );
			$unique_filename = $file_name . '_' . time() . '.' . $file_extension;
			$file_path = $upload_dir . $unique_filename;

			// Move uploaded file
			if ( ! move_uploaded_file( $file_data['tmp_name'], $file_path ) ) {
				throw new Exception( 'Failed to move uploaded file' );
			}

			// Set appropriate permissions
			chmod( $file_path, 0644 );

			// Insert file record
			$result = $wpdb->insert(
				$table_name,
				array(
					'sponsor_id'  => $sponsor_id,
					'event_id'    => $event_id,
					'user_id'     => $user_id,
					'file_name'   => $unique_filename,
					'file_path'   => $file_path,
					'file_type'   => $file_extension,
					'file_size'   => $file_data['size'],
					'description' => isset( $metadata['description'] ) ? sanitize_textarea_field( $metadata['description'] ) : '',
					'visibility'  => isset( $metadata['visibility'] ) ? sanitize_text_field( $metadata['visibility'] ) : 'private',
					'upload_date' => current_time( 'mysql' ),
					'downloads'   => 0,
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d' )
			);

			if ( false === $result ) {
				// Clean up uploaded file if database insert failed
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				throw new Exception( 'Database insert failed' );
			}

			$file_id = $wpdb->insert_id;

			$this->logger->info(
				"Sponsor file uploaded: ID {$file_id}, Sponsor {$sponsor_id}, Event {$event_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			// Send notification to event organizers
			$this->notify_file_upload( $file_id, $sponsor_id, $event_id );

			return array(
				'success' => true,
				'file_id' => $file_id,
				'message' => __( 'File uploaded successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in upload_sponsor_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => false,
				'message' => __( 'Failed to upload file', 'event-management-system' ),
			);
		}
	}

	/**
	 * Validate sponsor file upload
	 *
	 * @since 1.4.0
	 * @param array $file File data from $_FILES.
	 * @return array Validation result with 'valid' and 'message' keys
	 */
	private function validate_sponsor_file( $file ) {
		// Check for upload errors
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
			return array(
				'valid'   => false,
				'message' => __( 'File upload error', 'event-management-system' ),
			);
		}

		// Check file size
		if ( $file['size'] > $this->max_file_size ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'File size exceeds maximum allowed (%s)', 'event-management-system' ),
					size_format( $this->max_file_size )
				),
			);
		}

		// Check file type
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_extension, $this->allowed_types, true ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'File type .%s is not allowed', 'event-management-system' ),
					esc_html( $file_extension )
				),
			);
		}

		// Additional MIME type check
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		// Verify MIME type matches extension (basic check)
		$allowed_mime_types = array(
			'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'image/jpeg', 'image/png', 'image/gif', 'application/zip', 'video/mp4', 'video/quicktime'
		);

		if ( ! in_array( $mime_type, $allowed_mime_types, true ) ) {
			$this->logger->warning(
				"Suspicious file upload attempt: MIME type {$mime_type} for file {$file['name']}",
				EMS_Logger::CONTEXT_SECURITY
			);

			return array(
				'valid'   => false,
				'message' => __( 'File type verification failed', 'event-management-system' ),
			);
		}

		return array(
			'valid'   => true,
			'message' => '',
		);
	}

	/**
	 * Get sponsor files
	 *
	 * @since 1.4.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @param int $event_id Optional event ID to filter by.
	 * @return array Array of file data
	 */
	public function get_sponsor_files( $sponsor_id, $event_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_files';

		try {
			$query = "SELECT * FROM {$table_name} WHERE sponsor_id = %d";
			$params = array( absint( $sponsor_id ) );

			if ( $event_id > 0 ) {
				$query .= " AND event_id = %d";
				$params[] = absint( $event_id );
			}

			$query .= " ORDER BY upload_date DESC";

			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

			return $results ? $results : array();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_sponsor_files: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array();
		}
	}

	/**
	 * Delete sponsor file
	 *
	 * @since 1.4.0
	 * @param int $file_id File ID.
	 * @return array Result with success status and message
	 */
	public function delete_sponsor_file( $file_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_files';

		try {
			// Get file record
			$file = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				absint( $file_id )
			) );

			if ( ! $file ) {
				return array(
					'success' => false,
					'message' => __( 'File not found', 'event-management-system' ),
				);
			}

			// Check permissions
			if ( ! current_user_can( 'manage_options' ) && $file->user_id !== get_current_user_id() ) {
				return array(
					'success' => false,
					'message' => __( 'You do not have permission to delete this file', 'event-management-system' ),
				);
			}

			// Delete physical file
			if ( file_exists( $file->file_path ) ) {
				if ( ! unlink( $file->file_path ) ) {
					$this->logger->warning(
						"Failed to delete physical file: {$file->file_path}",
						EMS_Logger::CONTEXT_GENERAL
					);
				}
			}

			// Delete database record
			$result = $wpdb->delete(
				$table_name,
				array( 'id' => $file_id ),
				array( '%d' )
			);

			if ( false === $result ) {
				throw new Exception( 'Database delete failed' );
			}

			$this->logger->info(
				"Sponsor file deleted: ID {$file_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'File deleted successfully', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in delete_sponsor_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => false,
				'message' => __( 'Failed to delete file', 'event-management-system' ),
			);
		}
	}

	/**
	 * Download sponsor file
	 *
	 * Serves file for download with access control.
	 *
	 * @since 1.4.0
	 * @param int $file_id File ID.
	 * @return void
	 */
	public function download_sponsor_file( $file_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_files';

		try {
			// Get file record
			$file = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				absint( $file_id )
			) );

			if ( ! $file ) {
				wp_die( esc_html__( 'File not found', 'event-management-system' ) );
			}

			// Check access permissions
			if ( ! $this->can_access_sponsor_file( $file_id, get_current_user_id() ) ) {
				wp_die( esc_html__( 'You do not have permission to access this file', 'event-management-system' ) );
			}

			// Verify file exists
			if ( ! file_exists( $file->file_path ) ) {
				$this->logger->error(
					"Physical file not found: {$file->file_path}",
					EMS_Logger::CONTEXT_GENERAL
				);
				wp_die( esc_html__( 'File not found on server', 'event-management-system' ) );
			}

			// Increment download counter
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$table_name} SET downloads = downloads + 1 WHERE id = %d",
				$file_id
			) );

			// Serve file
			$safe_filename = sanitize_file_name( basename( $file->file_name ) );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
			header( 'Content-Length: ' . filesize( $file->file_path ) );
			header( 'Cache-Control: no-cache' );

			readfile( $file->file_path );

			$this->logger->info(
				"Sponsor file downloaded: ID {$file_id} by user " . get_current_user_id(),
				EMS_Logger::CONTEXT_GENERAL
			);

			exit;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in download_sponsor_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			wp_die( esc_html__( 'Failed to download file', 'event-management-system' ) );
		}
	}

	/**
	 * Check if user can access sponsor file
	 *
	 * @since 1.4.0
	 * @param int $file_id File ID.
	 * @param int $user_id User ID.
	 * @return bool True if user can access file
	 */
	public function can_access_sponsor_file( $file_id, $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_sponsor_files';

		// Admins and convenors can access all files
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_ems_events' ) ) {
			return true;
		}

		// Get file record
		$file = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d",
			absint( $file_id )
		) );

		if ( ! $file ) {
			return false;
		}

		// File uploader can access
		if ( $file->user_id === $user_id ) {
			return true;
		}

		// Check if user is associated with the sponsor
		$sponsor_id = $this->get_user_sponsor_id( $user_id );
		if ( $sponsor_id && absint( $sponsor_id ) === absint( $file->sponsor_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get sponsor ID for user
	 *
	 * Finds the sponsor post associated with a user (if any).
	 * Uses post_author or custom user meta.
	 *
	 * @since 1.4.0
	 * @param int $user_id User ID.
	 * @return int|false Sponsor post ID or false if not found
	 */
	public function get_user_sponsor_id( $user_id ) {
		// Check custom user meta first
		$sponsor_id = get_user_meta( $user_id, '_ems_sponsor_id', true );
		if ( $sponsor_id ) {
			return absint( $sponsor_id );
		}

		// Check if user is author of any sponsor posts
		$sponsors = get_posts( array(
			'post_type'      => 'ems_sponsor',
			'author'         => $user_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		if ( ! empty( $sponsors ) ) {
			return $sponsors[0];
		}

		return false;
	}

	/**
	 * Send notification for file upload
	 *
	 * Notifies event organizers when a sponsor uploads a file.
	 *
	 * @since 1.4.0
	 * @param int $file_id File ID.
	 * @param int $sponsor_id Sponsor post ID.
	 * @param int $event_id Event post ID.
	 * @return void
	 */
	private function notify_file_upload( $file_id, $sponsor_id, $event_id ) {
		try {
			// Get event author (organizer)
			$event = get_post( $event_id );
			$organizer = get_user_by( 'ID', $event->post_author );

			if ( ! $organizer ) {
				return;
			}

			$sponsor = get_post( $sponsor_id );
			$file = $this->get_sponsor_files( $sponsor_id, $event_id );

			// Simple email notification
			$subject = sprintf(
				__( '[%s] New file uploaded by sponsor', 'event-management-system' ),
				get_bloginfo( 'name' )
			);

			$message = sprintf(
				__( "A new file has been uploaded by sponsor %s for event: %s\n\nPlease review it in the admin area.", 'event-management-system' ),
				$sponsor->post_title,
				$event->post_title
			);

			wp_mail( $organizer->user_email, $subject, $message );

			$this->logger->info(
				"File upload notification sent for file {$file_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in notify_file_upload: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
		}
	}

	/**
	 * Update sponsor profile from portal
	 *
	 * Allows a sponsor to update their own organisation profile fields.
	 * Validates that the current user owns the sponsor post.
	 *
	 * @since 1.5.0
	 * @param int   $sponsor_id Sponsor post ID.
	 * @param array $data       Associative array of profile data.
	 * @return array Result with success status and message.
	 */
	public function update_sponsor_profile( $sponsor_id, $data ) {
		$sponsor_id = absint( $sponsor_id );
		$user_id    = get_current_user_id();

		// Verify sponsor exists
		$sponsor = get_post( $sponsor_id );
		if ( ! $sponsor || 'ems_sponsor' !== $sponsor->post_type ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid sponsor profile.', 'event-management-system' ),
			);
		}

		// Verify current user owns this sponsor
		$user_sponsor_id = $this->get_user_sponsor_id( $user_id );
		if ( ! $user_sponsor_id || absint( $user_sponsor_id ) !== $sponsor_id ) {
			$this->logger->warning(
				"Unauthorized profile update attempt: user {$user_id} tried to edit sponsor {$sponsor_id}",
				EMS_Logger::CONTEXT_SECURITY
			);

			return array(
				'success' => false,
				'message' => __( 'You do not have permission to edit this profile.', 'event-management-system' ),
			);
		}

		// Whitelist of editable fields from portal (organisation + contact only)
		$allowed_fields = array(
			'legal_name', 'trading_name', 'abn', 'acn',
			'registered_address', 'website_url', 'country', 'parent_company',
			'contact_name', 'contact_role', 'contact_email', 'contact_phone',
			'signatory_name', 'signatory_title', 'signatory_email',
			'marketing_contact_name', 'marketing_contact_email',
		);

		$filtered_data = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, $allowed_fields, true ) ) {
				$filtered_data[ $key ] = $value;
			}
		}

		if ( empty( $filtered_data ) ) {
			return array(
				'success' => false,
				'message' => __( 'No valid fields to update.', 'event-management-system' ),
			);
		}

		// Validate ABN if provided
		if ( ! empty( $filtered_data['abn'] ) ) {
			$abn_result = EMS_Validator::validate_abn( $filtered_data['abn'] );
			if ( is_wp_error( $abn_result ) ) {
				return array(
					'success' => false,
					'message' => $abn_result->get_error_message(),
				);
			}
		}

		// Validate ACN if provided
		if ( ! empty( $filtered_data['acn'] ) ) {
			$acn_result = EMS_Validator::validate_acn( $filtered_data['acn'] );
			if ( is_wp_error( $acn_result ) ) {
				return array(
					'success' => false,
					'message' => $acn_result->get_error_message(),
				);
			}
		}

		// Use EMS_Sponsor_Meta::update_sponsor_meta for proper sanitization
		$result = EMS_Sponsor_Meta::update_sponsor_meta( $sponsor_id, $filtered_data );

		if ( is_wp_error( $result ) ) {
			$this->logger->error(
				'Profile update failed: ' . $result->get_error_message(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		$this->logger->info(
			"Sponsor {$sponsor_id} profile updated by user {$user_id}",
			EMS_Logger::CONTEXT_GENERAL
		);

		return array(
			'success' => true,
			'message' => __( 'Profile updated successfully.', 'event-management-system' ),
		);
	}

	/**
	 * Get EOI submissions for a sponsor
	 *
	 * @since 1.5.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @return array Array of EOI row objects.
	 */
	public function get_sponsor_eois( $sponsor_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ems_sponsor_eoi';

		try {
			$sponsor_id = absint( $sponsor_id );
			if ( ! $sponsor_id ) {
				return array();
			}

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE sponsor_id = %d ORDER BY submitted_at DESC",
				$sponsor_id
			) );

			return $results ? $results : array();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_sponsor_eois: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array();
		}
	}

	/**
	 * Get a single EOI by ID with sponsor ownership check
	 *
	 * @since 1.5.0
	 * @param int $eoi_id     EOI record ID.
	 * @param int $sponsor_id Sponsor post ID to verify ownership.
	 * @return object|null EOI row object or null.
	 */
	public function get_sponsor_eoi( $eoi_id, $sponsor_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ems_sponsor_eoi';

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d AND sponsor_id = %d",
			absint( $eoi_id ),
			absint( $sponsor_id )
		) );
	}

	/**
	 * Get sponsor statistics
	 *
	 * Returns statistics for sponsor dashboard.
	 *
	 * @since 1.4.0
	 * @param int $sponsor_id Sponsor post ID.
	 * @return array Statistics array
	 */
	public function get_sponsor_statistics( $sponsor_id ) {
		global $wpdb;

		try {
			$sponsor_id = absint( $sponsor_id );

			// Count linked events
			$event_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_sponsor_events WHERE sponsor_id = %d",
				$sponsor_id
			) );

			// Count uploaded files
			$file_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_sponsor_files WHERE sponsor_id = %d",
				$sponsor_id
			) );

			// Total downloads
			$total_downloads = $wpdb->get_var( $wpdb->prepare(
				"SELECT SUM(downloads) FROM {$wpdb->prefix}ems_sponsor_files WHERE sponsor_id = %d",
				$sponsor_id
			) );

			// Total file size
			$total_size = $wpdb->get_var( $wpdb->prepare(
				"SELECT SUM(file_size) FROM {$wpdb->prefix}ems_sponsor_files WHERE sponsor_id = %d",
				$sponsor_id
			) );

			return array(
				'event_count'     => intval( $event_count ),
				'file_count'      => intval( $file_count ),
				'total_downloads' => intval( $total_downloads ),
				'total_size'      => intval( $total_size ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_sponsor_statistics: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'event_count'     => 0,
				'file_count'      => 0,
				'total_downloads' => 0,
				'total_size'      => 0,
			);
		}
	}
}
