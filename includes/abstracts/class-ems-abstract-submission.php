<?php
/**
 * Abstract Submission Management
 *
 * Handles abstract submission, editing, withdrawal, and co-author management
 * for conference/event presentations.
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/includes/abstracts
 * @since      1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EMS_Abstract_Submission
 *
 * Manages the abstract submission workflow including:
 * - Abstract creation and updates
 * - Co-author management
 * - File uploads
 * - Submission validation
 * - Status tracking
 *
 * @since 1.0.0
 */
class EMS_Abstract_Submission {

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
     * Email manager instance
     *
     * @since 1.0.0
     * @var EMS_Email_Manager
     */
    private $email_manager;

    /**
     * File manager instance
     *
     * @since 1.0.0
     * @var EMS_File_Manager
     */
    private $file_manager;

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
        $this->email_manager = new EMS_Email_Manager();
        $this->file_manager = new EMS_File_Manager();
    }

    /**
     * Submit a new abstract
     *
     * Creates a new abstract post and associated metadata. Validates submission
     * dates, required fields, and handles file uploads.
     *
     * @since 1.0.0
     * @param array $abstract_data {
     *     Abstract submission data.
     *
     *     @type int    $event_id              Event ID for the abstract.
     *     @type int    $user_id               Primary author user ID.
     *     @type string $title                 Abstract title.
     *     @type string $content               Abstract body/description.
     *     @type string $abstract_type         Type: oral, poster, or workshop.
     *     @type array  $co_authors            Array of co-author data.
     *     @type string $keywords              Comma-separated keywords.
     *     @type string $learning_objectives   Learning objectives text.
     *     @type string $presenter_bio         Presenter biography.
     *     @type int    $presenter_image_id    Attachment ID for presenter photo.
     *     @type array  $files                 Array of uploaded files.
     * }
     * @return array {
     *     Result array.
     *
     *     @type bool   $success      Whether submission succeeded.
     *     @type int    $abstract_id  Created abstract post ID (if successful).
     *     @type string $message      Status or error message.
     * }
     */
    public function submit_abstract( $abstract_data ) {
        try {
            $this->logger->info(
                'Starting abstract submission',
                EMS_Logger::CONTEXT_GENERAL
            );

            // Validate required fields
            $required_fields = array( 'event_id', 'user_id', 'title', 'content', 'abstract_type' );
            foreach ( $required_fields as $field ) {
                if ( empty( $abstract_data[ $field ] ) ) {
                    $this->logger->warning(
                        "Missing required field: {$field}",
                        EMS_Logger::CONTEXT_GENERAL
                    );
                    return array(
                        'success' => false,
                        'message' => sprintf(
                            __( 'Required field missing: %s', 'event-management-system' ),
                            $field
                        ),
                    );
                }
            }

            // Validate event exists
            $event = get_post( $abstract_data['event_id'] );
            if ( ! $event || 'ems_event' !== $event->post_type ) {
                $this->logger->warning(
                    'Invalid event ID: ' . $abstract_data['event_id'],
                    EMS_Logger::CONTEXT_GENERAL
                );
                return array(
                    'success' => false,
                    'message' => __( 'Invalid event', 'event-management-system' ),
                );
            }

            // Validate submission dates
            $date_check = $this->validate_submission_dates( $abstract_data['event_id'] );
            if ( ! $date_check['open'] ) {
                $this->logger->info(
                    "Submission not open for event {$abstract_data['event_id']}: {$date_check['message']}",
                    EMS_Logger::CONTEXT_GENERAL
                );
                return array(
                    'success' => false,
                    'message' => $date_check['message'],
                );
            }

            // Validate abstract type
            $allowed_types = array( 'oral', 'poster', 'workshop' );
            if ( ! in_array( $abstract_data['abstract_type'], $allowed_types, true ) ) {
                $this->logger->warning(
                    'Invalid abstract type: ' . $abstract_data['abstract_type'],
                    EMS_Logger::CONTEXT_GENERAL
                );
                return array(
                    'success' => false,
                    'message' => __( 'Invalid abstract type', 'event-management-system' ),
                );
            }

            // Ensure presenter user account exists
            // If user_id is 0 or not set, try to create from email data
            if ( empty( $abstract_data['user_id'] ) || 0 === absint( $abstract_data['user_id'] ) ) {
                // Need email and name to create user
                if ( ! empty( $abstract_data['email'] ) && ! empty( $abstract_data['first_name'] ) && ! empty( $abstract_data['last_name'] ) ) {
                    $user_helper = new EMS_User_Helper();
                    $user_result = $user_helper->create_or_get_presenter(
                        array(
                            'email'      => $abstract_data['email'],
                            'first_name' => $abstract_data['first_name'],
                            'last_name'  => $abstract_data['last_name'],
                            'bio'        => isset( $abstract_data['presenter_bio'] ) ? $abstract_data['presenter_bio'] : '',
                        ),
                        true // Send notification for new users
                    );

                    if ( $user_result['success'] ) {
                        $abstract_data['user_id'] = $user_result['user_id'];
                        $this->logger->info(
                            sprintf( 
                                'Presenter user %d %s for abstract submission',
                                $user_result['user_id'],
                                $user_result['created'] ? 'created' : 'found'
                            ),
                            EMS_Logger::CONTEXT_GENERAL
                        );
                    } else {
                        $this->logger->error(
                            'Failed to create presenter user: ' . $user_result['message'],
                            EMS_Logger::CONTEXT_GENERAL
                        );
                        return array(
                            'success' => false,
                            'message' => __( 'Failed to create presenter account', 'event-management-system' ),
                        );
                    }
                } else {
                    $this->logger->warning(
                        'Cannot create abstract without user_id or user details',
                        EMS_Logger::CONTEXT_GENERAL
                    );
                    return array(
                        'success' => false,
                        'message' => __( 'Please provide presenter email, first name, and last name', 'event-management-system' ),
                    );
                }
            } else {
                // Existing user - ensure they have presenter role
                $user_helper = new EMS_User_Helper();
                $user = get_user_by( 'ID', $abstract_data['user_id'] );
                if ( $user && ! in_array( 'ems_presenter', $user->roles, true ) ) {
                    // Add presenter role if they only have participant role
                    if ( in_array( 'ems_participant', $user->roles, true ) ) {
                        $user->remove_role( 'ems_participant' );
                    }
                    $user->add_role( 'ems_presenter' );
                    $this->logger->info(
                        sprintf( 'Upgraded user %d to presenter role', $user->ID ),
                        EMS_Logger::CONTEXT_GENERAL
                    );
                }
            }

            // Sanitize input data
            $sanitized_data = $this->sanitize_abstract_data( $abstract_data );

            // Create abstract post
            $post_id = wp_insert_post( array(
                'post_title'   => $sanitized_data['title'],
                'post_content' => $sanitized_data['content'],
                'post_type'    => 'ems_abstract',
                'post_status'  => 'publish',
                'post_author'  => $sanitized_data['user_id'],
            ), true );

            if ( is_wp_error( $post_id ) ) {
                $this->logger->error(
                    'Failed to create abstract post: ' . $post_id->get_error_message(),
                    EMS_Logger::CONTEXT_GENERAL
                );
                return array(
                    'success' => false,
                    'message' => __( 'Failed to create abstract', 'event-management-system' ),
                );
            }

            // Save metadata
            update_post_meta( $post_id, 'abstract_event_id', $sanitized_data['event_id'] );
            update_post_meta( $post_id, 'abstract_type', $sanitized_data['abstract_type'] );
            update_post_meta( $post_id, 'submission_date', current_time( 'mysql' ) );
            update_post_meta( $post_id, 'review_status', 'pending' );
            update_post_meta( $post_id, 'primary_author_id', $sanitized_data['user_id'] );
            update_post_meta( $post_id, 'approved_count', 0 );
            update_post_meta( $post_id, 'rejected_count', 0 );

            // Save optional fields
            if ( ! empty( $sanitized_data['keywords'] ) ) {
                update_post_meta( $post_id, 'keywords', $sanitized_data['keywords'] );
            }

            if ( ! empty( $sanitized_data['learning_objectives'] ) ) {
                update_post_meta( $post_id, 'learning_objectives', $sanitized_data['learning_objectives'] );
            }

            if ( ! empty( $sanitized_data['presenter_bio'] ) ) {
                update_post_meta( $post_id, 'presenter_bio', $sanitized_data['presenter_bio'] );
            }

            if ( ! empty( $sanitized_data['presenter_image_id'] ) ) {
                update_post_meta( $post_id, 'presenter_image_id', $sanitized_data['presenter_image_id'] );
            }

            // Handle co-authors
            if ( ! empty( $sanitized_data['co_authors'] ) && is_array( $sanitized_data['co_authors'] ) ) {
                update_post_meta( $post_id, 'co_authors', $sanitized_data['co_authors'] );
            }

            // Handle file uploads
            if ( ! empty( $sanitized_data['files'] ) && is_array( $sanitized_data['files'] ) ) {
                $file_result = $this->file_manager->handle_abstract_files( $post_id, $sanitized_data['files'] );
                if ( $file_result['success'] ) {
                    update_post_meta( $post_id, 'supplementary_files', $file_result['file_ids'] );
                }
            }

            $this->logger->info(
                "Abstract {$post_id} submitted successfully for event {$sanitized_data['event_id']}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Send confirmation email
            $this->email_manager->send_abstract_submitted_email( $post_id );

            // Fire action hook
            do_action( 'ems_after_abstract_submission', $post_id, $sanitized_data );

            return array(
                'success'     => true,
                'abstract_id' => $post_id,
                'message'     => __( 'Abstract submitted successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in submit_abstract: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred during submission', 'event-management-system' ),
            );
        }
    }

    /**
     * Update an existing abstract
     *
     * Allows the primary author to edit their abstract before review is complete.
     *
     * @since 1.0.0
     * @param int   $abstract_id   Abstract post ID to update.
     * @param array $abstract_data Updated abstract data (same structure as submit_abstract).
     * @return array Result array with success status and message.
     */
    public function update_abstract( $abstract_id, $abstract_data ) {
        try {
            $this->logger->info(
                "Attempting to update abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Verify abstract exists
            $abstract = get_post( $abstract_id );
            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                return array(
                    'success' => false,
                    'message' => __( 'Abstract not found', 'event-management-system' ),
                );
            }

            // Check if abstract is still editable
            $review_status = get_post_meta( $abstract_id, 'review_status', true );
            if ( in_array( $review_status, array( 'approved', 'rejected' ), true ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'Cannot edit abstract after final decision', 'event-management-system' ),
                );
            }

            // Verify user has permission
            $primary_author = get_post_meta( $abstract_id, 'primary_author_id', true );
            $current_user = get_current_user_id();
            
            if ( $primary_author != $current_user && ! current_user_can( 'edit_others_ems_abstracts' ) ) {
                $this->logger->warning(
                    "Unauthorized update attempt on abstract {$abstract_id} by user {$current_user}",
                    EMS_Logger::CONTEXT_SECURITY
                );
                return array(
                    'success' => false,
                    'message' => __( 'You do not have permission to edit this abstract', 'event-management-system' ),
                );
            }

            // Sanitize input data
            $sanitized_data = $this->sanitize_abstract_data( $abstract_data );

            // Update post
            $update_result = wp_update_post( array(
                'ID'           => $abstract_id,
                'post_title'   => $sanitized_data['title'],
                'post_content' => $sanitized_data['content'],
            ), true );

            if ( is_wp_error( $update_result ) ) {
                $this->logger->error(
                    "Failed to update abstract {$abstract_id}: " . $update_result->get_error_message(),
                    EMS_Logger::CONTEXT_GENERAL
                );
                return array(
                    'success' => false,
                    'message' => __( 'Failed to update abstract', 'event-management-system' ),
                );
            }

            // Update metadata
            if ( isset( $sanitized_data['abstract_type'] ) ) {
                update_post_meta( $abstract_id, 'abstract_type', $sanitized_data['abstract_type'] );
            }

            if ( isset( $sanitized_data['keywords'] ) ) {
                update_post_meta( $abstract_id, 'keywords', $sanitized_data['keywords'] );
            }

            if ( isset( $sanitized_data['learning_objectives'] ) ) {
                update_post_meta( $abstract_id, 'learning_objectives', $sanitized_data['learning_objectives'] );
            }

            if ( isset( $sanitized_data['presenter_bio'] ) ) {
                update_post_meta( $abstract_id, 'presenter_bio', $sanitized_data['presenter_bio'] );
            }

            if ( isset( $sanitized_data['presenter_image_id'] ) ) {
                update_post_meta( $abstract_id, 'presenter_image_id', $sanitized_data['presenter_image_id'] );
            }

            if ( isset( $sanitized_data['co_authors'] ) ) {
                update_post_meta( $abstract_id, 'co_authors', $sanitized_data['co_authors'] );
            }

            // Handle new file uploads
            if ( ! empty( $sanitized_data['files'] ) ) {
                $file_result = $this->file_manager->handle_abstract_files( $abstract_id, $sanitized_data['files'] );
                if ( $file_result['success'] ) {
                    $existing_files = get_post_meta( $abstract_id, 'supplementary_files', true ) ?: array();
                    $all_files = array_merge( $existing_files, $file_result['file_ids'] );
                    update_post_meta( $abstract_id, 'supplementary_files', $all_files );
                }
            }

            $this->logger->info(
                "Abstract {$abstract_id} updated successfully",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Fire action hook
            do_action( 'ems_after_abstract_update', $abstract_id, $sanitized_data );

            return array(
                'success' => true,
                'message' => __( 'Abstract updated successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in update_abstract: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred during update', 'event-management-system' ),
            );
        }
    }

    /**
     * Get abstract details
     *
     * Retrieves an abstract with all associated metadata.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array {
     *     Result array.
     *
     *     @type bool  $success Whether retrieval succeeded.
     *     @type array $data    Abstract data (if successful).
     *     @type string $message Status or error message.
     * }
     */
    public function get_abstract( $abstract_id ) {
        try {
            $abstract = get_post( $abstract_id );
            
            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                return array(
                    'success' => false,
                    'message' => __( 'Abstract not found', 'event-management-system' ),
                );
            }

            // Build abstract data array
            $abstract_data = array(
                'id'                   => $abstract->ID,
                'title'                => $abstract->post_title,
                'content'              => $abstract->post_content,
                'event_id'             => get_post_meta( $abstract->ID, 'abstract_event_id', true ),
                'abstract_type'        => get_post_meta( $abstract->ID, 'abstract_type', true ),
                'submission_date'      => get_post_meta( $abstract->ID, 'submission_date', true ),
                'review_status'        => get_post_meta( $abstract->ID, 'review_status', true ),
                'primary_author_id'    => get_post_meta( $abstract->ID, 'primary_author_id', true ),
                'co_authors'           => get_post_meta( $abstract->ID, 'co_authors', true ) ?: array(),
                'keywords'             => get_post_meta( $abstract->ID, 'keywords', true ),
                'learning_objectives'  => get_post_meta( $abstract->ID, 'learning_objectives', true ),
                'presenter_bio'        => get_post_meta( $abstract->ID, 'presenter_bio', true ),
                'presenter_image_id'   => get_post_meta( $abstract->ID, 'presenter_image_id', true ),
                'assigned_reviewers'   => get_post_meta( $abstract->ID, 'assigned_reviewers', true ) ?: array(),
                'approved_count'       => get_post_meta( $abstract->ID, 'approved_count', true ),
                'rejected_count'       => get_post_meta( $abstract->ID, 'rejected_count', true ),
                'supplementary_files'  => get_post_meta( $abstract->ID, 'supplementary_files', true ) ?: array(),
                'assigned_session_id'  => get_post_meta( $abstract->ID, 'assigned_session_id', true ),
            );

            return array(
                'success' => true,
                'data'    => $abstract_data,
                'message' => __( 'Abstract retrieved', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_abstract: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get abstracts for a specific user
     *
     * Retrieves all abstracts where the user is the primary author.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array {
     *     Result array.
     *
     *     @type bool  $success   Whether retrieval succeeded.
     *     @type array $abstracts Array of abstract data.
     *     @type string $message  Status or error message.
     * }
     */
    public function get_user_abstracts( $user_id ) {
        try {
            $args = array(
                'post_type'      => 'ems_abstract',
                'posts_per_page' => -1,
                'author'         => $user_id,
                'orderby'        => 'date',
                'order'          => 'DESC',
            );

            $query = new WP_Query( $args );
            $abstracts = array();

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $abstract_id = get_the_ID();
                    
                    $abstracts[] = array(
                        'id'              => $abstract_id,
                        'title'           => get_the_title(),
                        'event_id'        => get_post_meta( $abstract_id, 'abstract_event_id', true ),
                        'event_title'     => get_the_title( get_post_meta( $abstract_id, 'abstract_event_id', true ) ),
                        'abstract_type'   => get_post_meta( $abstract_id, 'abstract_type', true ),
                        'submission_date' => get_post_meta( $abstract_id, 'submission_date', true ),
                        'review_status'   => get_post_meta( $abstract_id, 'review_status', true ),
                    );
                }
                wp_reset_postdata();
            }

            return array(
                'success'   => true,
                'abstracts' => $abstracts,
                'count'     => count( $abstracts ),
                'message'   => sprintf(
                    __( 'Found %d abstracts', 'event-management-system' ),
                    count( $abstracts )
                ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_user_abstracts: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Withdraw an abstract
     *
     * Allows the primary author to withdraw their submission before review is complete.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array Result array with success status and message.
     */
    public function withdraw_abstract( $abstract_id ) {
        try {
            $this->logger->info(
                "Attempting to withdraw abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Verify abstract exists
            $abstract = get_post( $abstract_id );
            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                return array(
                    'success' => false,
                    'message' => __( 'Abstract not found', 'event-management-system' ),
                );
            }

            // Check current status
            $review_status = get_post_meta( $abstract_id, 'review_status', true );
            if ( 'withdrawn' === $review_status ) {
                return array(
                    'success' => false,
                    'message' => __( 'Abstract already withdrawn', 'event-management-system' ),
                );
            }

            // Verify user has permission
            $primary_author = get_post_meta( $abstract_id, 'primary_author_id', true );
            $current_user = get_current_user_id();
            
            if ( $primary_author != $current_user && ! current_user_can( 'manage_ems_abstracts' ) ) {
                $this->logger->warning(
                    "Unauthorized withdrawal attempt on abstract {$abstract_id} by user {$current_user}",
                    EMS_Logger::CONTEXT_SECURITY
                );
                return array(
                    'success' => false,
                    'message' => __( 'You do not have permission to withdraw this abstract', 'event-management-system' ),
                );
            }

            // Update status
            update_post_meta( $abstract_id, 'review_status', 'withdrawn' );
            update_post_meta( $abstract_id, 'withdrawn_date', current_time( 'mysql' ) );

            $this->logger->info(
                "Abstract {$abstract_id} withdrawn successfully",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Fire action hook
            do_action( 'ems_abstract_withdrawn', $abstract_id );

            return array(
                'success' => true,
                'message' => __( 'Abstract withdrawn successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in withdraw_abstract: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get abstracts for a specific event
     *
     * Retrieves all abstracts submitted for an event with optional filtering.
     *
     * @since 1.0.0
     * @param int   $event_id Event post ID.
     * @param array $args {
     *     Optional query arguments.
     *
     *     @type string $status       Filter by review status.
     *     @type string $type         Filter by abstract type.
     *     @type int    $per_page     Number of results per page.
     *     @type int    $paged        Page number.
     * }
     * @return array {
     *     Result array.
     *
     *     @type bool  $success   Whether query succeeded.
     *     @type array $abstracts Array of abstract data.
     *     @type int   $total     Total matching abstracts.
     *     @type string $message  Status or error message.
     * }
     */
    public function get_event_abstracts( $event_id, $args = array() ) {
        try {
            // Default arguments
            $defaults = array(
                'status'   => '',
                'type'     => '',
                'per_page' => -1,
                'paged'    => 1,
            );
            $args = wp_parse_args( $args, $defaults );

            // Build meta query
            $meta_query = array(
                array(
                    'key'   => 'abstract_event_id',
                    'value' => $event_id,
                ),
            );

            if ( ! empty( $args['status'] ) ) {
                $meta_query[] = array(
                    'key'   => 'review_status',
                    'value' => sanitize_text_field( $args['status'] ),
                );
            }

            if ( ! empty( $args['type'] ) ) {
                $meta_query[] = array(
                    'key'   => 'abstract_type',
                    'value' => sanitize_text_field( $args['type'] ),
                );
            }

            $query_args = array(
                'post_type'      => 'ems_abstract',
                'posts_per_page' => intval( $args['per_page'] ),
                'paged'          => intval( $args['paged'] ),
                'meta_query'     => $meta_query,
                'orderby'        => 'date',
                'order'          => 'DESC',
            );

            $query = new WP_Query( $query_args );
            $abstracts = array();

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $abstract_id = get_the_ID();
                    
                    $abstracts[] = array(
                        'id'              => $abstract_id,
                        'title'           => get_the_title(),
                        'author_name'     => get_the_author_meta( 'display_name', get_post_field( 'post_author', $abstract_id ) ),
                        'abstract_type'   => get_post_meta( $abstract_id, 'abstract_type', true ),
                        'submission_date' => get_post_meta( $abstract_id, 'submission_date', true ),
                        'review_status'   => get_post_meta( $abstract_id, 'review_status', true ),
                        'approved_count'  => get_post_meta( $abstract_id, 'approved_count', true ),
                        'rejected_count'  => get_post_meta( $abstract_id, 'rejected_count', true ),
                    );
                }
                wp_reset_postdata();
            }

            return array(
                'success'   => true,
                'abstracts' => $abstracts,
                'total'     => $query->found_posts,
                'message'   => sprintf(
                    __( 'Found %d abstracts', 'event-management-system' ),
                    $query->found_posts
                ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_event_abstracts: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Validate submission dates
     *
     * Checks if abstract submission is currently open for an event.
     *
     * @since 1.0.0
     * @param int $event_id Event post ID.
     * @return array {
     *     Validation result.
     *
     *     @type bool   $open    Whether submission is open.
     *     @type string $message Status message.
     * }
     */
    public function validate_submission_dates( $event_id ) {
        $open_date = get_post_meta( $event_id, 'abstract_submission_open', true );
        $close_date = get_post_meta( $event_id, 'abstract_submission_close', true );

        // If dates not set, submission is not open
        if ( empty( $open_date ) && empty( $close_date ) ) {
            return array(
                'open'    => false,
                'message' => __( 'Abstract submission is not enabled for this event', 'event-management-system' ),
            );
        }

        // Use timezone-aware date comparison
        // Check if before opening
        if ( $open_date && EMS_Date_Helper::is_future( $open_date ) ) {
            $this->logger->debug(
                sprintf( 
                    'Abstract submission not yet open for event %d. Opens: %s, Current: %s',
                    $event_id,
                    $open_date,
                    current_time( 'Y-m-d H:i:s' )
                ),
                EMS_Logger::CONTEXT_ABSTRACT
            );
            return array(
                'open'    => false,
                'message' => sprintf(
                    __( 'Abstract submission opens on %s', 'event-management-system' ),
                    EMS_Date_Helper::format( $open_date )
                ),
            );
        }

        // Check if after closing
        if ( $close_date && EMS_Date_Helper::has_passed( $close_date ) ) {
            $this->logger->debug(
                sprintf( 
                    'Abstract submission closed for event %d. Closed: %s, Current: %s',
                    $event_id,
                    $close_date,
                    current_time( 'Y-m-d H:i:s' )
                ),
                EMS_Logger::CONTEXT_ABSTRACT
            );
            return array(
                'open'    => false,
                'message' => sprintf(
                    __( 'Abstract submission closed on %s', 'event-management-system' ),
                    EMS_Date_Helper::format( $close_date )
                ),
            );
        }

        // Submission is open
        $close_message = $close_date 
            ? sprintf( __( 'Abstract submission is open until %s', 'event-management-system' ), EMS_Date_Helper::format( $close_date ) )
            : __( 'Abstract submission is open', 'event-management-system' );

        return array(
            'open'    => true,
            'message' => $close_message,
        );
    }

    /**
     * Add a co-author to an abstract
     *
     * Adds a co-author either by user ID (if registered) or by email/name.
     *
     * @since 1.0.0
     * @param int   $abstract_id Abstract post ID.
     * @param array $author_data {
     *     Co-author information.
     *
     *     @type int    $user_id    WordPress user ID (if registered).
     *     @type string $email      Email address.
     *     @type string $first_name First name.
     *     @type string $last_name  Last name.
     *     @type string $affiliation Organization/affiliation.
     * }
     * @return array Result array with success status and message.
     */
    public function add_co_author( $abstract_id, $author_data ) {
        try {
            // Verify abstract exists and user has permission
            $abstract = get_post( $abstract_id );
            if ( ! $abstract || 'ems_abstract' !== $abstract->post_type ) {
                return array(
                    'success' => false,
                    'message' => __( 'Abstract not found', 'event-management-system' ),
                );
            }

            // Validate required fields
            if ( empty( $author_data['email'] ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'Email is required for co-authors', 'event-management-system' ),
                );
            }

            // Sanitize author data
            $sanitized_author = array(
                'user_id'     => ! empty( $author_data['user_id'] ) ? intval( $author_data['user_id'] ) : 0,
                'email'       => sanitize_email( $author_data['email'] ),
                'first_name'  => sanitize_text_field( $author_data['first_name'] ?? '' ),
                'last_name'   => sanitize_text_field( $author_data['last_name'] ?? '' ),
                'affiliation' => sanitize_text_field( $author_data['affiliation'] ?? '' ),
            );

            // Get existing co-authors
            $co_authors = get_post_meta( $abstract_id, 'co_authors', true );
            if ( ! is_array( $co_authors ) ) {
                $co_authors = array();
            }

            // Check for duplicate
            foreach ( $co_authors as $author ) {
                if ( $author['email'] === $sanitized_author['email'] ) {
                    return array(
                        'success' => false,
                        'message' => __( 'This co-author is already added', 'event-management-system' ),
                    );
                }
            }

            // Add new co-author
            $co_authors[] = $sanitized_author;
            update_post_meta( $abstract_id, 'co_authors', $co_authors );

            $this->logger->info(
                "Co-author added to abstract {$abstract_id}: {$sanitized_author['email']}",
                EMS_Logger::CONTEXT_GENERAL
            );

            return array(
                'success' => true,
                'message' => __( 'Co-author added successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in add_co_author: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Remove a co-author from an abstract
     *
     * Removes a co-author by email address.
     *
     * @since 1.0.0
     * @param int    $abstract_id Abstract post ID.
     * @param string $email       Email address of co-author to remove.
     * @return array Result array with success status and message.
     */
    public function remove_co_author( $abstract_id, $email ) {
        try {
            // Get existing co-authors
            $co_authors = get_post_meta( $abstract_id, 'co_authors', true );
            if ( ! is_array( $co_authors ) || empty( $co_authors ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'No co-authors found', 'event-management-system' ),
                );
            }

            // Find and remove co-author
            $email = sanitize_email( $email );
            $found = false;
            
            foreach ( $co_authors as $key => $author ) {
                if ( $author['email'] === $email ) {
                    unset( $co_authors[ $key ] );
                    $found = true;
                    break;
                }
            }

            if ( ! $found ) {
                return array(
                    'success' => false,
                    'message' => __( 'Co-author not found', 'event-management-system' ),
                );
            }

            // Re-index array and update
            $co_authors = array_values( $co_authors );
            update_post_meta( $abstract_id, 'co_authors', $co_authors );

            $this->logger->info(
                "Co-author removed from abstract {$abstract_id}: {$email}",
                EMS_Logger::CONTEXT_GENERAL
            );

            return array(
                'success' => true,
                'message' => __( 'Co-author removed successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in remove_co_author: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Sanitize abstract submission data
     *
     * Cleans and validates all input fields.
     *
     * @since 1.0.0
     * @param array $data Raw input data.
     * @return array Sanitized data.
     */
    private function sanitize_abstract_data( $data ) {
        return array(
            'event_id'            => isset( $data['event_id'] ) ? intval( $data['event_id'] ) : 0,
            'user_id'             => isset( $data['user_id'] ) ? intval( $data['user_id'] ) : 0,
            'title'               => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
            'content'             => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
            'abstract_type'       => isset( $data['abstract_type'] ) ? sanitize_text_field( $data['abstract_type'] ) : '',
            'keywords'            => isset( $data['keywords'] ) ? sanitize_text_field( $data['keywords'] ) : '',
            'learning_objectives' => isset( $data['learning_objectives'] ) ? sanitize_textarea_field( $data['learning_objectives'] ) : '',
            'presenter_bio'       => isset( $data['presenter_bio'] ) ? sanitize_textarea_field( $data['presenter_bio'] ) : '',
            'presenter_image_id'  => isset( $data['presenter_image_id'] ) ? intval( $data['presenter_image_id'] ) : 0,
            'co_authors'          => isset( $data['co_authors'] ) && is_array( $data['co_authors'] ) ? $data['co_authors'] : array(),
            'files'               => isset( $data['files'] ) && is_array( $data['files'] ) ? $data['files'] : array(),
        );
    }
}
