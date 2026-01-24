<?php
/**
 * File Management for Abstracts
 *
 * Handles file uploads, validation, storage, and retrieval for abstract
 * supplementary materials.
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/includes/files
 * @since      1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EMS_File_Manager
 *
 * Manages file operations including:
 * - Upload handling with validation
 * - File type and size restrictions
 * - Secure file storage
 * - File retrieval and downloads
 * - File deletion
 *
 * @since 1.0.0
 */
class EMS_File_Manager {

    /**
     * Logger instance
     *
     * @since 1.0.0
     * @var EMS_Logger
     */
    private $logger;

    /**
     * Validator instance
     *
     * @since 1.0.0
     * @var EMS_Validator
     */
    private $validator;

    /**
     * Security utility instance
     *
     * @since 1.0.0
     * @var EMS_Security
     */
    private $security;

    /**
     * File access control instance
     *
     * @since 1.0.0
     * @var EMS_File_Access_Control
     */
    private $access_control;

    /**
     * Allowed file types for abstract uploads
     *
     * @since 1.0.0
     * @var array
     */
    private $allowed_types = array(
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    );

    /**
     * Maximum file size in bytes (default: 10MB)
     *
     * @since 1.0.0
     * @var int
     */
    private $max_file_size = 10485760;

    /**
     * Constructor
     *
     * Initialize dependencies and set up class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = EMS_Logger::instance();
        $this->validator = new EMS_Validator();
        $this->security = new EMS_Security();
        $this->access_control = new EMS_File_Access_Control();

        // Allow custom file size limit via filter
        $this->max_file_size = apply_filters( 'ems_max_file_size', $this->max_file_size );
        
        // Allow custom file types via filter
        $this->allowed_types = apply_filters( 'ems_allowed_file_types', $this->allowed_types );
    }

    /**
     * Handle abstract file uploads
     *
     * Processes multiple file uploads for an abstract submission.
     *
     * @since 1.0.0
     * @param int   $abstract_id Abstract post ID.
     * @param array $files       Array of files from $_FILES.
     * @return array {
     *     Result array.
     *
     *     @type bool  $success  Whether upload succeeded.
     *     @type array $file_ids Array of attachment IDs (if successful).
     *     @type array $errors   Array of error messages (if any).
     *     @type string $message Overall status message.
     * }
     */
    public function handle_abstract_files( $abstract_id, $files ) {
        try {
            $this->logger->info(
                "Handling file uploads for abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Validate abstract exists
            $abstract = get_post( $abstract_id );
            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                return array(
                    'success' => false,
                    'message' => __( 'Invalid abstract', 'event-management-system' ),
                );
            }

            $file_ids = array();
            $errors = array();

            // Process each file
            foreach ( $files as $file ) {
                // Validate file
                $validation = $this->validate_file( $file );
                
                if ( ! $validation['valid'] ) {
                    $errors[] = $validation['message'];
                    $this->logger->warning(
                        "File validation failed: {$validation['message']}",
                        EMS_Logger::CONTEXT_GENERAL
                    );
                    continue;
                }

                // Upload file
                $upload_result = $this->upload_file( $file, $abstract_id );
                
                if ( $upload_result['success'] ) {
                    $file_ids[] = $upload_result['attachment_id'];
                    
                    $this->logger->info(
                        "File uploaded for abstract {$abstract_id}: {$upload_result['attachment_id']}",
                        EMS_Logger::CONTEXT_GENERAL
                    );
                } else {
                    $errors[] = $upload_result['message'];
                    
                    $this->logger->error(
                        "File upload failed: {$upload_result['message']}",
                        EMS_Logger::CONTEXT_GENERAL
                    );
                }
            }

            $success = ! empty( $file_ids );
            $message = $success 
                ? sprintf( __( '%d files uploaded successfully', 'event-management-system' ), count( $file_ids ) )
                : __( 'No files were uploaded', 'event-management-system' );

            if ( ! empty( $errors ) ) {
                $message .= ' ' . sprintf( __( '(%d errors)', 'event-management-system' ), count( $errors ) );
            }

            return array(
                'success'  => $success,
                'file_ids' => $file_ids,
                'errors'   => $errors,
                'message'  => $message,
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in handle_abstract_files: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred during file upload', 'event-management-system' ),
            );
        }
    }

    /**
     * Validate a file before upload
     *
     * Checks file type, size, and other security requirements.
     *
     * @since 1.0.0
     * @param array $file File array from $_FILES.
     * @return array {
     *     Validation result.
     *
     *     @type bool   $valid   Whether file is valid.
     *     @type string $message Error message if invalid.
     * }
     */
    public function validate_file( $file ) {
        // Check for upload errors
        if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
            return array(
                'valid'   => false,
                'message' => __( 'Invalid file upload', 'event-management-system' ),
            );
        }

        // Check specific error codes
        switch ( $file['error'] ) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return array(
                    'valid'   => false,
                    'message' => __( 'File exceeds maximum size', 'event-management-system' ),
                );
            case UPLOAD_ERR_NO_FILE:
                return array(
                    'valid'   => false,
                    'message' => __( 'No file was uploaded', 'event-management-system' ),
                );
            default:
                return array(
                    'valid'   => false,
                    'message' => __( 'Unknown upload error', 'event-management-system' ),
                );
        }

        // Check file size
        if ( $file['size'] > $this->max_file_size ) {
            return array(
                'valid'   => false,
                'message' => sprintf(
                    __( 'File size exceeds maximum of %s', 'event-management-system' ),
                    size_format( $this->max_file_size )
                ),
            );
        }

        // Check file extension
        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        
        if ( ! array_key_exists( $file_ext, $this->allowed_types ) ) {
            return array(
                'valid'   => false,
                'message' => sprintf(
                    __( 'File type .%s is not allowed', 'event-management-system' ),
                    $file_ext
                ),
            );
        }

        // Check MIME type
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime_type = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        $allowed_mime = $this->allowed_types[ $file_ext ];
        
        if ( $mime_type !== $allowed_mime ) {
            return array(
                'valid'   => false,
                'message' => __( 'File type does not match extension', 'event-management-system' ),
            );
        }

        // File is valid
        return array(
            'valid'   => true,
            'message' => __( 'File validated', 'event-management-system' ),
        );
    }

    /**
     * Upload a file and create attachment
     *
     * Handles the actual file upload and WordPress attachment creation.
     *
     * @since 1.0.0
     * @param array $file        File array from $_FILES.
     * @param int   $abstract_id Abstract post ID to attach file to.
     * @return array {
     *     Upload result.
     *
     *     @type bool   $success       Whether upload succeeded.
     *     @type int    $attachment_id WordPress attachment ID (if successful).
     *     @type string $message       Status or error message.
     * }
     */
    private function upload_file( $file, $abstract_id ) {
        try {
            // Set up upload overrides
            $upload_overrides = array(
                'test_form' => false,
                'mimes'     => $this->allowed_types,
            );

            // Handle the upload
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded = wp_handle_upload( $file, $upload_overrides );

            if ( isset( $uploaded['error'] ) ) {
                return array(
                    'success' => false,
                    'message' => $uploaded['error'],
                );
            }

            // Create attachment post
            $attachment_data = array(
                'post_mime_type' => $uploaded['type'],
                'post_title'     => sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_parent'    => $abstract_id,
            );

            $attachment_id = wp_insert_attachment( $attachment_data, $uploaded['file'], $abstract_id );

            if ( is_wp_error( $attachment_id ) ) {
                return array(
                    'success' => false,
                    'message' => $attachment_id->get_error_message(),
                );
            }

            // Generate attachment metadata
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
            wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

            // Store additional metadata
            update_post_meta( $attachment_id, '_ems_file_type', 'abstract_supplementary' );
            update_post_meta( $attachment_id, '_ems_upload_date', current_time( 'mysql' ) );

            return array(
                'success'       => true,
                'attachment_id' => $attachment_id,
                'message'       => __( 'File uploaded successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in upload_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'Upload failed', 'event-management-system' ),
            );
        }
    }

    /**
     * Get files for an abstract
     *
     * Retrieves all files attached to a specific abstract.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array {
     *     Result array.
     *
     *     @type bool  $success Whether retrieval succeeded.
     *     @type array $files   Array of file data.
     *     @type string $message Status message.
     * }
     */
    public function get_abstract_files( $abstract_id ) {
        try {
            $file_ids = get_post_meta( $abstract_id, 'supplementary_files', true );
            
            if ( empty( $file_ids ) || ! is_array( $file_ids ) ) {
                return array(
                    'success' => true,
                    'files'   => array(),
                    'message' => __( 'No files found', 'event-management-system' ),
                );
            }

            $files = array();

            foreach ( $file_ids as $file_id ) {
                $file_data = $this->get_file_data( $file_id );
                if ( $file_data['success'] ) {
                    $files[] = $file_data['data'];
                }
            }

            return array(
                'success' => true,
                'files'   => $files,
                'message' => sprintf( __( 'Found %d files', 'event-management-system' ), count( $files ) ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_abstract_files: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get file data
     *
     * Retrieves metadata for a specific file attachment.
     *
     * @since 1.0.0
     * @param int $file_id Attachment ID.
     * @return array {
     *     File data.
     *
     *     @type bool   $success Whether retrieval succeeded.
     *     @type array  $data    File information.
     *     @type string $message Status message.
     * }
     */
    public function get_file_data( $file_id ) {
        $attachment = get_post( $file_id );
        
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return array(
                'success' => false,
                'message' => __( 'File not found', 'event-management-system' ),
            );
        }

        return array(
            'success' => true,
            'data'    => array(
                'id'         => $file_id,
                'title'      => $attachment->post_title,
                'filename'   => basename( get_attached_file( $file_id ) ),
                'url'        => wp_get_attachment_url( $file_id ),
                'mime_type'  => $attachment->post_mime_type,
                'file_size'  => filesize( get_attached_file( $file_id ) ),
                'upload_date' => get_post_meta( $file_id, '_ems_upload_date', true ),
            ),
            'message' => __( 'File retrieved', 'event-management-system' ),
        );
    }

    /**
     * Delete a file
     *
     * Removes a file attachment and updates abstract metadata.
     *
     * @since 1.0.0
     * @param int $file_id     Attachment ID.
     * @param int $abstract_id Abstract post ID.
     * @return array Result array with success status.
     */
    public function delete_file( $file_id, $abstract_id ) {
        try {
            $this->logger->info(
                "Attempting to delete file {$file_id} from abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Verify user has permission
            $current_user = get_current_user_id();
            if ( ! $this->access_control->can_delete_file( $file_id, $current_user ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'You do not have permission to delete this file', 'event-management-system' ),
                );
            }

            // Delete attachment
            $deleted = wp_delete_attachment( $file_id, true );

            if ( false === $deleted ) {
                return array(
                    'success' => false,
                    'message' => __( 'Failed to delete file', 'event-management-system' ),
                );
            }

            // Update abstract metadata
            $file_ids = get_post_meta( $abstract_id, 'supplementary_files', true );
            if ( is_array( $file_ids ) ) {
                $file_ids = array_diff( $file_ids, array( $file_id ) );
                update_post_meta( $abstract_id, 'supplementary_files', array_values( $file_ids ) );
            }

            $this->logger->info(
                "File {$file_id} deleted successfully",
                EMS_Logger::CONTEXT_GENERAL
            );

            return array(
                'success' => true,
                'message' => __( 'File deleted successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in delete_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get file URL with access check
     *
     * Returns a download URL for a file if user has permission.
     *
     * @since 1.0.0
     * @param int $file_id Attachment ID.
     * @param int $user_id User ID requesting access.
     * @return array {
     *     URL result.
     *
     *     @type bool   $success Whether user has access.
     *     @type string $url     Download URL (if successful).
     *     @type string $message Status or error message.
     * }
     */
    public function get_file_url( $file_id, $user_id ) {
        try {
            // Check access permission
            if ( ! $this->access_control->can_access_file( $file_id, $user_id ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'You do not have permission to access this file', 'event-management-system' ),
                );
            }

            $url = wp_get_attachment_url( $file_id );
            
            if ( ! $url ) {
                return array(
                    'success' => false,
                    'message' => __( 'File URL not found', 'event-management-system' ),
                );
            }

            return array(
                'success' => true,
                'url'     => $url,
                'message' => __( 'File accessible', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_file_url: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get allowed file types
     *
     * Returns array of allowed file extensions and MIME types.
     *
     * @since 1.0.0
     * @return array Allowed file types.
     */
    public function get_allowed_types() {
        return $this->allowed_types;
    }

    /**
     * Get maximum file size
     *
     * Returns maximum allowed file size in bytes.
     *
     * @since 1.0.0
     * @return int Maximum file size in bytes.
     */
    public function get_max_file_size() {
        return $this->max_file_size;
    }
}
