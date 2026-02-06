<?php
/**
 * Presenter Portal
 *
 * Handles presenter dashboard functionality including:
 * - View assigned sessions
 * - Manage structured bio / profile
 * - Upload slides, resources, and headshot
 * - Download convenor-provided files
 * - View event schedule (including draft)
 * - Indicate timeslot availability preferences
 *
 * @package    EventManagementSystem
 * @subpackage Collaboration
 * @since      1.7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Presenter Portal Class
 *
 * @since 1.7.0
 */
class EMS_Presenter_Portal {

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
	 * Allowed file types for presenter uploads
	 *
	 * @var array
	 */
	private $allowed_types = array(
		'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
		'jpg', 'jpeg', 'png', 'gif', 'zip', 'mp4', 'mov',
	);

	/**
	 * Allowed image types for headshot uploads
	 *
	 * @var array
	 */
	private $allowed_image_types = array( 'jpg', 'jpeg', 'png' );

	/**
	 * Maximum file size in bytes (25MB)
	 *
	 * @var int
	 */
	private $max_file_size = 26214400;

	/**
	 * Maximum headshot size in bytes (5MB)
	 *
	 * @var int
	 */
	private $max_headshot_size = 5242880;

	/**
	 * Bio field definitions for structured profile
	 *
	 * @var array
	 */
	const BIO_FIELDS = array(
		'professional_background' => array(
			'label' => 'Professional Background',
			'type'  => 'textarea',
			'help'  => 'Describe your professional background, current role, and experience.',
			'rows'  => 4,
		),
		'qualifications' => array(
			'label' => 'Qualifications',
			'type'  => 'tags',
			'help'  => 'Enter your qualifications (e.g., MBBS, PhD, FRACP).',
		),
		'specialties' => array(
			'label' => 'Areas of Specialty',
			'type'  => 'tags',
			'help'  => 'Enter your areas of clinical or research specialty.',
		),
		'research_interests' => array(
			'label' => 'Research Interests',
			'type'  => 'tags',
			'help'  => 'Enter your current research interests or focus areas.',
		),
		'affiliations' => array(
			'label' => 'Institutional Affiliations',
			'type'  => 'textarea',
			'help'  => 'List your hospital, university, or organisational affiliations.',
			'rows'  => 3,
		),
		'notable_achievements' => array(
			'label' => 'Notable Achievements',
			'type'  => 'textarea',
			'help'  => 'Awards, publications, grants, or other notable achievements.',
			'rows'  => 3,
		),
		'fun_fact' => array(
			'label' => 'Fun Fact / Quirky Trivia',
			'type'  => 'text',
			'help'  => 'Something unexpected or fun about you that attendees might enjoy.',
		),
		'hobbies' => array(
			'label' => 'Hobbies & Interests Outside Work',
			'type'  => 'tags',
			'help'  => 'What do you enjoy doing outside of work?',
		),
		'social_linkedin' => array(
			'label' => 'LinkedIn Profile URL',
			'type'  => 'url',
			'help'  => 'Your public LinkedIn profile URL.',
		),
		'social_twitter' => array(
			'label' => 'X / Twitter Handle',
			'type'  => 'text',
			'help'  => 'Your X/Twitter handle (e.g., @username).',
		),
		'social_orcid' => array(
			'label' => 'ORCID iD',
			'type'  => 'text',
			'help'  => 'Your ORCID identifier (e.g., 0000-0002-1825-0097).',
		),
	);

	/**
	 * Availability preference options
	 *
	 * @var array
	 */
	const AVAILABILITY_OPTIONS = array(
		'preferred'   => 'Preferred',
		'available'   => 'Available',
		'unavailable' => 'Not Available',
	);

	/**
	 * File category options
	 *
	 * @var array
	 */
	const FILE_CATEGORIES = array(
		'slides'       => 'Presentation Slides',
		'handout'      => 'Handout / Notes',
		'resource'     => 'Additional Resource',
		'convenor'     => 'Convenor Document',
	);

	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		$this->logger        = EMS_Logger::instance();
		$this->file_manager  = new EMS_File_Manager();
		$this->email_manager = new EMS_Email_Manager();
		$this->security      = new EMS_Security();

		$this->max_file_size    = apply_filters( 'ems_presenter_max_file_size', $this->max_file_size );
		$this->max_headshot_size = apply_filters( 'ems_presenter_max_headshot_size', $this->max_headshot_size );
		$this->allowed_types    = apply_filters( 'ems_presenter_allowed_file_types', $this->allowed_types );

		$this->logger->info( 'EMS_Presenter_Portal initialized', EMS_Logger::CONTEXT_GENERAL );
	}

	// =========================================================================
	// SESSION & EVENT RETRIEVAL
	// =========================================================================

	/**
	 * Get sessions assigned to a presenter
	 *
	 * @since 1.7.0
	 * @param int $user_id Presenter user ID (0 = current user).
	 * @return array Array of session data.
	 */
	public function get_presenter_sessions( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		try {
			$sessions = get_posts( array(
				'post_type'      => 'ems_session',
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => 'presenter_user_id',
						'value' => absint( $user_id ),
						'type'  => 'NUMERIC',
					),
				),
				'orderby'        => 'meta_value',
				'meta_key'       => 'start_datetime',
				'order'          => 'ASC',
			) );

			$result = array();
			foreach ( $sessions as $session ) {
				$event_id = get_post_meta( $session->ID, 'event_id', true );
				$event    = $event_id ? get_post( $event_id ) : null;

				$result[] = array(
					'session_id'     => $session->ID,
					'session_title'  => $session->post_title,
					'session_status' => $session->post_status,
					'session_type'   => get_post_meta( $session->ID, 'session_type', true ),
					'start_datetime' => get_post_meta( $session->ID, 'start_datetime', true ),
					'end_datetime'   => get_post_meta( $session->ID, 'end_datetime', true ),
					'location'       => get_post_meta( $session->ID, 'location', true ),
					'track'          => get_post_meta( $session->ID, 'track', true ),
					'event_id'       => $event_id,
					'event_title'    => $event ? $event->post_title : '',
				);
			}

			return $result;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_presenter_sessions: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array();
		}
	}

	/**
	 * Get unique event IDs for presenter's sessions
	 *
	 * @since 1.7.0
	 * @param int $user_id Presenter user ID.
	 * @return array Array of event data arrays.
	 */
	public function get_presenter_events( $user_id = 0 ) {
		$sessions  = $this->get_presenter_sessions( $user_id );
		$event_ids = array();
		$events    = array();

		foreach ( $sessions as $session ) {
			$eid = absint( $session['event_id'] );
			if ( $eid && ! in_array( $eid, $event_ids, true ) ) {
				$event_ids[] = $eid;
				$events[]    = array(
					'event_id'    => $eid,
					'event_title' => $session['event_title'],
					'event_date'  => get_post_meta( $eid, 'event_start_date', true ),
				);
			}
		}

		return $events;
	}

	/**
	 * Get presenter statistics
	 *
	 * @since 1.7.0
	 * @param int $user_id Presenter user ID.
	 * @return array Statistics array.
	 */
	public function get_presenter_statistics( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );

		try {
			$sessions = $this->get_presenter_sessions( $user_id );

			$file_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ems_presenter_files WHERE user_id = %d",
				$user_id
			) );

			$total_downloads = $wpdb->get_var( $wpdb->prepare(
				"SELECT COALESCE(SUM(downloads), 0) FROM {$wpdb->prefix}ems_presenter_files WHERE user_id = %d",
				$user_id
			) );

			$events = $this->get_presenter_events( $user_id );

			return array(
				'session_count'   => count( $sessions ),
				'event_count'     => count( $events ),
				'file_count'      => intval( $file_count ),
				'total_downloads' => intval( $total_downloads ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_presenter_statistics: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'session_count'   => 0,
				'event_count'     => 0,
				'file_count'      => 0,
				'total_downloads' => 0,
			);
		}
	}

	// =========================================================================
	// BIO / PROFILE MANAGEMENT
	// =========================================================================

	/**
	 * Get presenter bio data
	 *
	 * @since 1.7.0
	 * @param int $user_id User ID.
	 * @return array Bio field values keyed by field name.
	 */
	public function get_presenter_bio( $user_id ) {
		$user_id = absint( $user_id );
		$bio     = array();

		foreach ( self::BIO_FIELDS as $key => $field ) {
			$value = get_user_meta( $user_id, '_ems_presenter_' . $key, true );
			if ( 'tags' === $field['type'] && is_string( $value ) ) {
				$value = $value ? array_map( 'trim', explode( ',', $value ) ) : array();
			}
			$bio[ $key ] = $value;
		}

		return $bio;
	}

	/**
	 * Update presenter bio data
	 *
	 * @since 1.7.0
	 * @param int   $user_id User ID.
	 * @param array $data    Bio field values.
	 * @return array Result with success status and message.
	 */
	public function update_presenter_bio( $user_id, $data ) {
		$user_id     = absint( $user_id );
		$current_uid = get_current_user_id();

		// Only the presenter themselves or an admin can update
		if ( $current_uid !== $user_id && ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_ems_events' ) ) {
			$this->logger->warning(
				"Unauthorized bio update attempt: user {$current_uid} tried to edit presenter {$user_id}",
				EMS_Logger::CONTEXT_SECURITY
			);
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to edit this profile.', 'event-management-system' ),
			);
		}

		$updated = 0;
		foreach ( self::BIO_FIELDS as $key => $field ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$value = $data[ $key ];

			// Sanitize based on type
			switch ( $field['type'] ) {
				case 'textarea':
					$value = sanitize_textarea_field( $value );
					break;
				case 'url':
					$value = esc_url_raw( $value );
					break;
				case 'tags':
					if ( is_array( $value ) ) {
						$value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
					} else {
						$value = sanitize_text_field( $value );
					}
					break;
				default:
					$value = sanitize_text_field( $value );
			}

			update_user_meta( $user_id, '_ems_presenter_' . $key, $value );
			$updated++;
		}

		if ( 0 === $updated ) {
			return array(
				'success' => false,
				'message' => __( 'No valid fields to update.', 'event-management-system' ),
			);
		}

		$this->logger->info(
			"Presenter {$user_id} bio updated ({$updated} fields) by user {$current_uid}",
			EMS_Logger::CONTEXT_GENERAL
		);

		return array(
			'success' => true,
			'message' => __( 'Profile updated successfully.', 'event-management-system' ),
		);
	}

	// =========================================================================
	// HEADSHOT / PROFILE PHOTO
	// =========================================================================

	/**
	 * Get presenter headshot data
	 *
	 * @since 1.7.0
	 * @param int $user_id User ID.
	 * @return array Headshot data with attachment_id, url, approval_status.
	 */
	public function get_presenter_headshot( $user_id ) {
		$user_id       = absint( $user_id );
		$attachment_id = get_user_meta( $user_id, '_ems_presenter_headshot_id', true );
		$status        = get_user_meta( $user_id, '_ems_presenter_headshot_status', true );
		$uploaded_by   = get_user_meta( $user_id, '_ems_presenter_headshot_uploaded_by', true );

		$url = '';
		if ( $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'medium' );
		}

		return array(
			'attachment_id' => absint( $attachment_id ),
			'url'           => $url,
			'status'        => $status ? $status : 'none',
			'uploaded_by'   => $uploaded_by ? $uploaded_by : '',
		);
	}

	/**
	 * Update presenter headshot
	 *
	 * @since 1.7.0
	 * @param int    $user_id       User ID.
	 * @param int    $attachment_id WordPress attachment ID.
	 * @param string $uploaded_by   Who uploaded: 'presenter' or 'convenor'.
	 * @return array Result with success status and message.
	 */
	public function update_presenter_headshot( $user_id, $attachment_id, $uploaded_by = 'presenter' ) {
		$user_id       = absint( $user_id );
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id ) {
			$attachment = get_post( $attachment_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return array(
					'success' => false,
					'message' => __( 'Invalid image selection.', 'event-management-system' ),
				);
			}

			// Verify it's an image
			$mime = get_post_mime_type( $attachment_id );
			if ( strpos( $mime, 'image/' ) !== 0 ) {
				return array(
					'success' => false,
					'message' => __( 'Selected file is not an image.', 'event-management-system' ),
				);
			}
		}

		update_user_meta( $user_id, '_ems_presenter_headshot_id', $attachment_id );
		update_user_meta( $user_id, '_ems_presenter_headshot_uploaded_by', sanitize_text_field( $uploaded_by ) );

		// If convenor uploads, set status to pending_approval so presenter can approve
		// If presenter uploads, auto-approve
		if ( 'convenor' === $uploaded_by ) {
			update_user_meta( $user_id, '_ems_presenter_headshot_status', 'pending_approval' );
		} else {
			update_user_meta( $user_id, '_ems_presenter_headshot_status', 'approved' );
		}

		$this->logger->info(
			"Presenter {$user_id} headshot updated by {$uploaded_by}",
			EMS_Logger::CONTEXT_GENERAL
		);

		return array(
			'success' => true,
			'message' => __( 'Headshot updated successfully.', 'event-management-system' ),
		);
	}

	/**
	 * Approve or reject a convenor-uploaded headshot
	 *
	 * @since 1.7.0
	 * @param int    $user_id User ID.
	 * @param string $action  'approve' or 'reject'.
	 * @return array Result with success status and message.
	 */
	public function respond_to_headshot( $user_id, $action ) {
		$user_id = absint( $user_id );

		if ( 'approve' === $action ) {
			update_user_meta( $user_id, '_ems_presenter_headshot_status', 'approved' );
			$message = __( 'Headshot approved.', 'event-management-system' );
		} elseif ( 'reject' === $action ) {
			// Remove the headshot
			delete_user_meta( $user_id, '_ems_presenter_headshot_id' );
			update_user_meta( $user_id, '_ems_presenter_headshot_status', 'rejected' );
			update_user_meta( $user_id, '_ems_presenter_headshot_uploaded_by', '' );
			$message = __( 'Headshot rejected. Please upload a new photo.', 'event-management-system' );
		} else {
			return array(
				'success' => false,
				'message' => __( 'Invalid action.', 'event-management-system' ),
			);
		}

		$this->logger->info(
			"Presenter {$user_id} headshot {$action}d",
			EMS_Logger::CONTEXT_GENERAL
		);

		return array(
			'success' => true,
			'message' => $message,
		);
	}

	// =========================================================================
	// FILE MANAGEMENT
	// =========================================================================

	/**
	 * Upload a presenter file (slides, resources, etc.)
	 *
	 * @since 1.7.0
	 * @param array  $file_data    File data from $_FILES.
	 * @param int    $session_id   Session post ID.
	 * @param int    $event_id     Event post ID.
	 * @param string $category     File category (slides, handout, resource, convenor).
	 * @param array  $metadata     Additional metadata.
	 * @return array Result with success status.
	 */
	public function upload_presenter_file( $file_data, $session_id, $event_id, $category = 'slides', $metadata = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_presenter_files';

		try {
			$session_id = absint( $session_id );
			$event_id   = absint( $event_id );
			$user_id    = get_current_user_id();
			$category   = sanitize_text_field( $category );

			if ( ! array_key_exists( $category, self::FILE_CATEGORIES ) ) {
				$category = 'resource';
			}

			// Check permissions
			if ( ! current_user_can( 'upload_ems_presenter_files' ) && ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_ems_events' ) ) {
				return array(
					'success' => false,
					'message' => __( 'You do not have permission to upload files.', 'event-management-system' ),
				);
			}

			// Validate file
			$validation = $this->validate_presenter_file( $file_data );
			if ( ! $validation['valid'] ) {
				return array(
					'success' => false,
					'message' => $validation['message'],
				);
			}

			// Upload directory
			$upload_dir = EMS_UPLOAD_DIR . 'presenters/' . $user_id . '/';
			if ( ! file_exists( $upload_dir ) ) {
				wp_mkdir_p( $upload_dir );
			}

			// Generate unique filename
			$file_extension  = strtolower( pathinfo( $file_data['name'], PATHINFO_EXTENSION ) );
			$file_name       = sanitize_file_name( pathinfo( $file_data['name'], PATHINFO_FILENAME ) );
			$unique_filename = $file_name . '_' . time() . '.' . $file_extension;
			$file_path       = $upload_dir . $unique_filename;

			if ( ! move_uploaded_file( $file_data['tmp_name'], $file_path ) ) {
				throw new Exception( 'Failed to move uploaded file' );
			}

			chmod( $file_path, 0644 );

			$uploaded_by = ( current_user_can( 'manage_options' ) || current_user_can( 'manage_ems_events' ) )
				? 'convenor'
				: 'presenter';

			$result = $wpdb->insert(
				$table_name,
				array(
					'session_id'    => $session_id,
					'event_id'      => $event_id,
					'user_id'       => $user_id,
					'file_name'     => $unique_filename,
					'file_path'     => $file_path,
					'file_type'     => $file_extension,
					'file_size'     => $file_data['size'],
					'file_category' => $category,
					'description'   => isset( $metadata['description'] ) ? sanitize_textarea_field( $metadata['description'] ) : '',
					'upload_date'   => current_time( 'mysql' ),
					'uploaded_by'   => $uploaded_by,
					'visibility'    => isset( $metadata['visibility'] ) ? sanitize_text_field( $metadata['visibility'] ) : 'private',
					'downloads'     => 0,
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
			);

			if ( false === $result ) {
				if ( file_exists( $file_path ) ) {
					unlink( $file_path );
				}
				throw new Exception( 'Database insert failed' );
			}

			$file_id = $wpdb->insert_id;

			$this->logger->info(
				"Presenter file uploaded: ID {$file_id}, Session {$session_id}, Event {$event_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'file_id' => $file_id,
				'message' => __( 'File uploaded successfully.', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in upload_presenter_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'Failed to upload file.', 'event-management-system' ),
			);
		}
	}

	/**
	 * Validate presenter file upload
	 *
	 * @since 1.7.0
	 * @param array $file File data from $_FILES.
	 * @return array Validation result.
	 */
	private function validate_presenter_file( $file ) {
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
			return array(
				'valid'   => false,
				'message' => __( 'File upload error.', 'event-management-system' ),
			);
		}

		if ( $file['size'] > $this->max_file_size ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'File size exceeds maximum allowed (%s).', 'event-management-system' ),
					size_format( $this->max_file_size )
				),
			);
		}

		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_extension, $this->allowed_types, true ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					__( 'File type .%s is not allowed.', 'event-management-system' ),
					esc_html( $file_extension )
				),
			);
		}

		return array(
			'valid'   => true,
			'message' => '',
		);
	}

	/**
	 * Get files for a session
	 *
	 * @since 1.7.0
	 * @param int    $session_id Session post ID.
	 * @param string $category   Optional category filter.
	 * @return array Array of file objects.
	 */
	public function get_session_files( $session_id, $category = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_presenter_files';

		try {
			$query  = "SELECT * FROM {$table_name} WHERE session_id = %d";
			$params = array( absint( $session_id ) );

			if ( $category ) {
				$query   .= " AND file_category = %s";
				$params[] = sanitize_text_field( $category );
			}

			$query .= " ORDER BY upload_date DESC";

			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
			return $results ? $results : array();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_session_files: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array();
		}
	}

	/**
	 * Get all files for a presenter across all sessions
	 *
	 * @since 1.7.0
	 * @param int $user_id User ID.
	 * @return array Array of file objects.
	 */
	public function get_presenter_files( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_presenter_files';

		try {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY upload_date DESC",
				absint( $user_id )
			) );
			return $results ? $results : array();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_presenter_files: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array();
		}
	}

	/**
	 * Get convenor-provided files for a presenter's sessions
	 *
	 * @since 1.7.0
	 * @param int $user_id Presenter user ID.
	 * @return array Array of file objects.
	 */
	public function get_convenor_files_for_presenter( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_presenter_files';

		try {
			$sessions = $this->get_presenter_sessions( $user_id );
			if ( empty( $sessions ) ) {
				return array();
			}

			$session_ids = wp_list_pluck( $sessions, 'session_id' );
			$placeholders = implode( ',', array_fill( 0, count( $session_ids ), '%d' ) );

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE session_id IN ({$placeholders}) AND uploaded_by = 'convenor' ORDER BY upload_date DESC",
				$session_ids
			) );

			return $results ? $results : array();

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in get_convenor_files_for_presenter: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array();
		}
	}

	/**
	 * Delete presenter file
	 *
	 * @since 1.7.0
	 * @param int $file_id File ID.
	 * @return array Result.
	 */
	public function delete_presenter_file( $file_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_presenter_files';

		try {
			$file = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				absint( $file_id )
			) );

			if ( ! $file ) {
				return array(
					'success' => false,
					'message' => __( 'File not found.', 'event-management-system' ),
				);
			}

			// Check permissions: owner, admin, or convenor
			if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_ems_events' ) && absint( $file->user_id ) !== get_current_user_id() ) {
				return array(
					'success' => false,
					'message' => __( 'You do not have permission to delete this file.', 'event-management-system' ),
				);
			}

			if ( file_exists( $file->file_path ) ) {
				unlink( $file->file_path );
			}

			$wpdb->delete( $table_name, array( 'id' => $file_id ), array( '%d' ) );

			$this->logger->info(
				"Presenter file deleted: ID {$file_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'File deleted successfully.', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in delete_presenter_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'Failed to delete file.', 'event-management-system' ),
			);
		}
	}

	/**
	 * Download presenter file with access control
	 *
	 * @since 1.7.0
	 * @param int $file_id File ID.
	 * @return void
	 */
	public function download_presenter_file( $file_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ems_presenter_files';

		try {
			$file = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				absint( $file_id )
			) );

			if ( ! $file ) {
				wp_die( esc_html__( 'File not found.', 'event-management-system' ) );
			}

			if ( ! $this->can_access_presenter_file( $file ) ) {
				wp_die( esc_html__( 'You do not have permission to access this file.', 'event-management-system' ) );
			}

			if ( ! file_exists( $file->file_path ) ) {
				wp_die( esc_html__( 'File not found on server.', 'event-management-system' ) );
			}

			$wpdb->query( $wpdb->prepare(
				"UPDATE {$table_name} SET downloads = downloads + 1 WHERE id = %d",
				$file_id
			) );

			$safe_filename = sanitize_file_name( basename( $file->file_name ) );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
			header( 'Content-Length: ' . filesize( $file->file_path ) );
			header( 'Cache-Control: no-cache' );
			readfile( $file->file_path );
			exit;

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in download_presenter_file: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			wp_die( esc_html__( 'Failed to download file.', 'event-management-system' ) );
		}
	}

	/**
	 * Check if current user can access a presenter file
	 *
	 * @since 1.7.0
	 * @param object $file File row object.
	 * @return bool
	 */
	private function can_access_presenter_file( $file ) {
		// Admins and convenors can access all files
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_ems_events' ) ) {
			return true;
		}

		$user_id = get_current_user_id();

		// File uploader can access
		if ( absint( $file->user_id ) === $user_id ) {
			return true;
		}

		// Presenter assigned to the session can access
		$presenter_user_id = get_post_meta( $file->session_id, 'presenter_user_id', true );
		if ( absint( $presenter_user_id ) === $user_id ) {
			return true;
		}

		return false;
	}

	// =========================================================================
	// TIMESLOT AVAILABILITY / PREFERENCES
	// =========================================================================

	/**
	 * Get timeslots for an event (generated from schedule)
	 *
	 * @since 1.7.0
	 * @param int $event_id Event ID.
	 * @return array Array of timeslot arrays with start/end.
	 */
	public function get_event_timeslots( $event_id ) {
		$event_id = absint( $event_id );

		$sessions = get_posts( array(
			'post_type'      => 'ems_session',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => 'event_id',
					'value' => $event_id,
					'type'  => 'NUMERIC',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => 'start_datetime',
			'order'          => 'ASC',
		) );

		$timeslots = array();
		$seen      = array();

		foreach ( $sessions as $session ) {
			$start = get_post_meta( $session->ID, 'start_datetime', true );
			$end   = get_post_meta( $session->ID, 'end_datetime', true );

			if ( ! $start || ! $end ) {
				continue;
			}

			$key = $start . '|' . $end;
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;

			$timeslots[] = array(
				'start'      => $start,
				'end'        => $end,
				'session_id' => $session->ID,
				'title'      => $session->post_title,
				'type'       => get_post_meta( $session->ID, 'session_type', true ),
				'location'   => get_post_meta( $session->ID, 'location', true ),
			);
		}

		return $timeslots;
	}

	/**
	 * Get presenter's availability preferences for an event
	 *
	 * @since 1.7.0
	 * @param int $user_id  User ID.
	 * @param int $event_id Event ID.
	 * @return array Array of preference rows keyed by "start|end".
	 */
	public function get_presenter_availability( $user_id, $event_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ems_presenter_availability';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d AND event_id = %d ORDER BY timeslot_start ASC",
			absint( $user_id ),
			absint( $event_id )
		) );

		$preferences = array();
		foreach ( $rows as $row ) {
			$key = $row->timeslot_start . '|' . $row->timeslot_end;
			$preferences[ $key ] = array(
				'preference' => $row->preference,
				'note'       => $row->note,
			);
		}

		return $preferences;
	}

	/**
	 * Save presenter availability preferences for an event
	 *
	 * @since 1.7.0
	 * @param int   $user_id  User ID.
	 * @param int   $event_id Event ID.
	 * @param array $slots    Array of slot preference data.
	 * @return array Result.
	 */
	public function save_presenter_availability( $user_id, $event_id, $slots ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'ems_presenter_availability';
		$user_id  = absint( $user_id );
		$event_id = absint( $event_id );

		try {
			// Clear existing preferences for this event
			$wpdb->delete(
				$table,
				array(
					'user_id'  => $user_id,
					'event_id' => $event_id,
				),
				array( '%d', '%d' )
			);

			// Insert new preferences
			foreach ( $slots as $slot ) {
				if ( empty( $slot['start'] ) || empty( $slot['end'] ) || empty( $slot['preference'] ) ) {
					continue;
				}

				if ( ! array_key_exists( $slot['preference'], self::AVAILABILITY_OPTIONS ) ) {
					continue;
				}

				$wpdb->insert(
					$table,
					array(
						'user_id'        => $user_id,
						'event_id'       => $event_id,
						'timeslot_start' => sanitize_text_field( $slot['start'] ),
						'timeslot_end'   => sanitize_text_field( $slot['end'] ),
						'preference'     => sanitize_text_field( $slot['preference'] ),
						'note'           => isset( $slot['note'] ) ? sanitize_textarea_field( $slot['note'] ) : '',
						'updated_at'     => current_time( 'mysql' ),
					),
					array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
				);
			}

			$this->logger->info(
				"Presenter {$user_id} availability saved for event {$event_id}",
				EMS_Logger::CONTEXT_GENERAL
			);

			return array(
				'success' => true,
				'message' => __( 'Availability preferences saved successfully.', 'event-management-system' ),
			);

		} catch ( Exception $e ) {
			$this->logger->error(
				'Exception in save_presenter_availability: ' . $e->getMessage(),
				EMS_Logger::CONTEXT_GENERAL
			);
			return array(
				'success' => false,
				'message' => __( 'Failed to save availability preferences.', 'event-management-system' ),
			);
		}
	}
}
