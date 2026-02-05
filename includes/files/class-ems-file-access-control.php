<?php
/**
 * File Access Control
 *
 * Manages permissions for file access, uploads, and deletions.
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
 * Class EMS_File_Access_Control
 *
 * Handles file permission checks for:
 * - File viewing/downloading
 * - File uploads
 * - File deletions
 * - Access level determination
 *
 * @since 1.0.0
 */
class EMS_File_Access_Control {

    /**
     * Logger instance
     *
     * @since 1.0.0
     * @var EMS_Logger
     */
    private $logger;

    /**
     * Security utility instance
     *
     * @since 1.0.0
     * @var EMS_Security
     */
    private $security;

    /**
     * Constructor
     *
     * Initialize dependencies and set up class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = EMS_Logger::instance();
        $this->security = new EMS_Security();
    }

    /**
     * Check if user can access a file
     *
     * Determines if a user has permission to view/download a file.
     *
     * Access rules:
     * - Admins: Full access
     * - Convenors: Access to all abstracts in their events
     * - Reviewers: Access to abstracts they're assigned to review
     * - Primary authors: Access to their own abstract files
     * - Public: No access (abstracts are private)
     *
     * @since 1.0.0
     * @param int $file_id Attachment ID.
     * @param int $user_id User ID requesting access.
     * @return bool True if user can access file, false otherwise.
     */
    public function can_access_file( $file_id, $user_id ) {
        try {
            // Admins have full access
            if ( user_can( $user_id, 'manage_options' ) ) {
                return true;
            }

            // Get file parent (abstract)
            $file = get_post( $file_id );
            if ( ! $file || 'attachment' !== $file->post_type ) {
                $this->logger->warning(
                    "Invalid file ID for access check: {$file_id}",
                    EMS_Logger::CONTEXT_SECURITY
                );
                return false;
            }

            $abstract_id = $file->post_parent;
            $abstract = get_post( $abstract_id );

            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                $this->logger->warning(
                    "File {$file_id} not attached to valid abstract",
                    EMS_Logger::CONTEXT_SECURITY
                );
                return false;
            }

            // Check if user is primary author
            $primary_author = get_post_meta( $abstract_id, 'primary_author_id', true );
            if ( $primary_author == $user_id ) {
                return true;
            }

            // Check if user is a co-author
            $co_authors = get_post_meta( $abstract_id, 'co_authors', true );
            if ( is_array( $co_authors ) ) {
                foreach ( $co_authors as $co_author ) {
                    if ( isset( $co_author['user_id'] ) && $co_author['user_id'] == $user_id ) {
                        return true;
                    }
                }
            }

            // Check if user is an assigned reviewer
            if ( user_can( $user_id, 'review_ems_abstracts' ) ) {
                $assigned_reviewers = get_post_meta( $abstract_id, 'assigned_reviewers', true );
                if ( is_array( $assigned_reviewers ) && in_array( $user_id, $assigned_reviewers ) ) {
                    return true;
                }
            }

            // Check if user is a convenor for this event
            if ( user_can( $user_id, 'manage_ems_events' ) ) {
                // Convenors have access to all abstracts in their assigned events
                $abstract_event_id = get_post_meta( $abstract_id, 'abstract_event_id', true );
                
                // If no event assigned or user is admin, grant access
                if ( empty( $abstract_event_id ) || user_can( $user_id, 'manage_options' ) ) {
                    return true;
                }
                
                // Check if user is a convenor for this specific event
                $event_convenors = get_post_meta( $abstract_event_id, 'event_convenors', true );
                if ( is_array( $event_convenors ) && in_array( $user_id, $event_convenors ) ) {
                    return true;
                }
                
                // Check if user is the event author
                $event = get_post( $abstract_event_id );
                if ( $event && intval( $event->post_author ) === intval( $user_id ) ) {
                    return true;
                }
            }

            // No access granted
            $this->logger->info(
                "Access denied to file {$file_id} for user {$user_id}",
                EMS_Logger::CONTEXT_SECURITY
            );
            
            return false;

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in can_access_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return false;
        }
    }

    /**
     * Check if user can upload files
     *
     * Determines if a user has permission to upload files in a specific context.
     *
     * @since 1.0.0
     * @param string $context  Context for upload (e.g., 'abstract_submission').
     * @param int    $user_id  User ID.
     * @param int    $related_id Related post ID (e.g., abstract_id).
     * @return bool True if user can upload, false otherwise.
     */
    public function can_upload_file( $context, $user_id, $related_id = 0 ) {
        try {
            // Admins can always upload
            if ( user_can( $user_id, 'manage_options' ) ) {
                return true;
            }

            switch ( $context ) {
                case 'abstract_submission':
                    // Check if user is submitting their own abstract
                    if ( $related_id > 0 ) {
                        $primary_author = get_post_meta( $related_id, 'primary_author_id', true );
                        if ( $primary_author == $user_id ) {
                            return true;
                        }
                    }
                    
                    // Users with submit capability can upload for new abstracts
                    return user_can( $user_id, 'submit_ems_abstracts' );

                case 'presenter_bio':
                    // Presenters can upload their bio images
                    return user_can( $user_id, 'submit_ems_abstracts' );

                default:
                    $this->logger->warning(
                        "Unknown upload context: {$context}",
                        EMS_Logger::CONTEXT_SECURITY
                    );
                    return false;
            }

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in can_upload_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return false;
        }
    }

    /**
     * Check if user can delete a file
     *
     * Determines if a user has permission to delete a specific file.
     *
     * @since 1.0.0
     * @param int $file_id Attachment ID.
     * @param int $user_id User ID.
     * @return bool True if user can delete file, false otherwise.
     */
    public function can_delete_file( $file_id, $user_id ) {
        try {
            // Admins can delete any file
            if ( user_can( $user_id, 'manage_options' ) ) {
                return true;
            }

            // Get file and abstract
            $file = get_post( $file_id );
            if ( ! $file || 'attachment' !== $file->post_type ) {
                return false;
            }

            $abstract_id = $file->post_parent;
            $abstract = get_post( $abstract_id );

            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                return false;
            }

            // Check if abstract is still editable
            $review_status = get_post_meta( $abstract_id, 'review_status', true );
            if ( in_array( $review_status, array( 'approved', 'rejected' ), true ) ) {
                // Cannot delete files from finalized abstracts
                return false;
            }

            // Primary author can delete their own files
            $primary_author = get_post_meta( $abstract_id, 'primary_author_id', true );
            if ( $primary_author == $user_id ) {
                return true;
            }

            // Convenors can delete files from abstracts in their events
            if ( user_can( $user_id, 'manage_ems_events' ) ) {
                return true;
            }

            return false;

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in can_delete_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return false;
        }
    }

    /**
     * Get file access level
     *
     * Returns the access level a user has for a specific file.
     *
     * @since 1.0.0
     * @param int $file_id Attachment ID.
     * @param int $user_id User ID.
     * @return string Access level: 'none', 'view', 'edit', or 'admin'.
     */
    public function get_file_access_level( $file_id, $user_id ) {
        try {
            // Admin level
            if ( user_can( $user_id, 'manage_options' ) ) {
                return 'admin';
            }

            // No access if can't view
            if ( ! $this->can_access_file( $file_id, $user_id ) ) {
                return 'none';
            }

            // Edit level (can delete)
            if ( $this->can_delete_file( $file_id, $user_id ) ) {
                return 'edit';
            }

            // View-only level
            return 'view';

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_file_access_level: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return 'none';
        }
    }

    /**
     * Check if file is publicly accessible
     *
     * Determines if a file should be accessible without authentication.
     *
     * @since 1.0.0
     * @param int $file_id Attachment ID.
     * @return bool True if file is public, false otherwise.
     */
    public function is_file_public( $file_id ) {
        try {
            $file = get_post( $file_id );
            if ( ! $file ) {
                return false;
            }

            $file_type = get_post_meta( $file_id, '_ems_file_type', true );

            // Define public file types
            $public_types = array(
                'event_poster',
                'event_brochure',
                'public_schedule',
            );

            return in_array( $file_type, $public_types, true );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in is_file_public: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return false;
        }
    }

    /**
     * Log file access attempt
     *
     * Records file access attempts for security auditing.
     *
     * @since 1.0.0
     * @param int    $file_id   Attachment ID.
     * @param int    $user_id   User ID.
     * @param string $action    Action attempted (view, download, delete).
     * @param bool   $granted   Whether access was granted.
     * @return void
     */
    public function log_file_access( $file_id, $user_id, $action, $granted ) {
        try {
            $log_message = sprintf(
                'File access %s: User %d attempted to %s file %d',
                $granted ? 'granted' : 'denied',
                $user_id,
                $action,
                $file_id
            );

            if ( $granted ) {
                $this->logger->info( $log_message, EMS_Logger::CONTEXT_SECURITY );
            } else {
                $this->logger->warning( $log_message, EMS_Logger::CONTEXT_SECURITY );
            }

        } catch ( Exception $e ) {
            // Don't throw exception for logging failures
            $this->logger->error(
                'Exception in log_file_access: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
        }
    }

    /**
     * Validate file access token
     *
     * Validates a temporary access token for file downloads.
     * Useful for email links or time-limited access.
     *
     * @since 1.0.0
     * @param string $token   Access token.
     * @param int    $file_id Attachment ID.
     * @return bool True if token is valid, false otherwise.
     */
    public function validate_access_token( $token, $file_id ) {
        try {
            // Get stored token
            $stored_token = get_transient( "ems_file_token_{$file_id}" );

            if ( ! $stored_token ) {
                $this->logger->warning(
                    "No token found for file {$file_id}",
                    EMS_Logger::CONTEXT_SECURITY
                );
                return false;
            }

            // Verify token matches
            if ( ! hash_equals( $stored_token, $token ) ) {
                $this->logger->warning(
                    "Invalid token for file {$file_id}",
                    EMS_Logger::CONTEXT_SECURITY
                );
                return false;
            }

            return true;

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in validate_access_token: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return false;
        }
    }

    /**
     * Generate file access token
     *
     * Creates a temporary access token for a file.
     *
     * @since 1.0.0
     * @param int $file_id   Attachment ID.
     * @param int $user_id   User ID for whom token is generated.
     * @param int $expiration Expiration time in seconds (default: 1 hour).
     * @return string|false Access token or false on failure.
     */
    public function generate_access_token( $file_id, $user_id, $expiration = 3600 ) {
        try {
            // Generate secure token
            $token = wp_generate_password( 32, false );

            // Store token as transient
            $transient_key = "ems_file_token_{$file_id}_{$user_id}";
            set_transient( $transient_key, $token, $expiration );

            $this->logger->info(
                "Access token generated for file {$file_id}, user {$user_id}",
                EMS_Logger::CONTEXT_SECURITY
            );

            return $token;

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in generate_access_token: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_SECURITY
            );
            return false;
        }
    }
}
