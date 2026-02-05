<?php
/**
 * Abstract Review Management
 *
 * Handles reviewer assignment, review submission, and decision processing
 * for abstract approvals.
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
 * Class EMS_Abstract_Review
 *
 * Manages the abstract review workflow including:
 * - Reviewer assignment
 * - Review submission
 * - Score tracking
 * - Majority decision processing
 * - Approval/rejection/revision workflows
 *
 * @since 1.0.0
 */
class EMS_Abstract_Review {

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
     * Database table name for reviews
     *
     * @since 1.0.0
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     *
     * Initialize dependencies and set up class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        
        $this->logger = EMS_Logger::instance();
        $this->validator = new EMS_Validator();
        $this->security = new EMS_Security();
        $this->email_manager = new EMS_Email_Manager();
        $this->table_name = $wpdb->prefix . 'ems_abstract_reviews';
    }

    /**
     * Assign reviewers to an abstract
     *
     * Assigns one or more reviewers to an abstract and sends notification emails.
     * Minimum 2 reviewers required for majority decision.
     *
     * @since 1.0.0
     * @param int   $abstract_id  Abstract post ID.
     * @param array $reviewer_ids Array of user IDs to assign as reviewers.
     * @return array {
     *     Result array.
     *
     *     @type bool   $success      Whether assignment succeeded.
     *     @type int    $assigned     Number of reviewers assigned.
     *     @type string $message      Status or error message.
     * }
     */
    public function assign_reviewers( $abstract_id, $reviewer_ids ) {
        global $wpdb;
        
        try {
            $this->logger->info(
                "Assigning reviewers to abstract {$abstract_id}",
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

            // Validate reviewer IDs
            if ( empty( $reviewer_ids ) || ! is_array( $reviewer_ids ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'No reviewers provided', 'event-management-system' ),
                );
            }

            // Minimum 2 reviewers for majority decision
            if ( count( $reviewer_ids ) < 2 ) {
                return array(
                    'success' => false,
                    'message' => __( 'At least 2 reviewers are required', 'event-management-system' ),
                );
            }

            // Verify all users have reviewer capability
            foreach ( $reviewer_ids as $reviewer_id ) {
                $user = get_userdata( $reviewer_id );
                if ( ! $user || ! user_can( $reviewer_id, 'review_ems_abstracts' ) ) {
                    return array(
                        'success' => false,
                        'message' => sprintf(
                            __( 'User ID %d does not have reviewer permissions', 'event-management-system' ),
                            $reviewer_id
                        ),
                    );
                }
            }

            $assigned_count = 0;
            $current_time = current_time( 'mysql' );

            foreach ( $reviewer_ids as $reviewer_id ) {
                $reviewer_id = intval( $reviewer_id );

                // Check if already assigned
                $existing = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$this->table_name} 
                        WHERE abstract_id = %d AND reviewer_id = %d",
                        $abstract_id,
                        $reviewer_id
                    )
                );

                if ( $existing ) {
                    $this->logger->info(
                        "Reviewer {$reviewer_id} already assigned to abstract {$abstract_id}",
                        EMS_Logger::CONTEXT_GENERAL
                    );
                    continue;
                }

                // Insert review assignment
                $result = $wpdb->insert(
                    $this->table_name,
                    array(
                        'abstract_id'   => $abstract_id,
                        'reviewer_id'   => $reviewer_id,
                        'assigned_date' => $current_time,
                        'status'        => 'pending',
                    ),
                    array( '%d', '%d', '%s', '%s' )
                );

                if ( false === $result ) {
                    $this->logger->error(
                        "Failed to assign reviewer {$reviewer_id} to abstract {$abstract_id}: " . $wpdb->last_error,
                        EMS_Logger::CONTEXT_GENERAL
                    );
                    continue;
                }

                $assigned_count++;

                // Send email notification
                $this->email_manager->send_review_assigned_email( $abstract_id, $reviewer_id );

                $this->logger->info(
                    "Reviewer {$reviewer_id} assigned to abstract {$abstract_id}",
                    EMS_Logger::CONTEXT_GENERAL
                );
            }

            // Update abstract metadata
            $all_reviewers = get_post_meta( $abstract_id, 'assigned_reviewers', true ) ?: array();
            $all_reviewers = array_unique( array_merge( $all_reviewers, $reviewer_ids ) );
            update_post_meta( $abstract_id, 'assigned_reviewers', $all_reviewers );

            // Update review status if this is first assignment
            $current_status = get_post_meta( $abstract_id, 'review_status', true );
            if ( 'pending' === $current_status ) {
                update_post_meta( $abstract_id, 'review_status', 'under_review' );
            }

            // Fire action hook
            do_action( 'ems_reviewers_assigned', $abstract_id, $reviewer_ids );

            return array(
                'success'  => true,
                'assigned' => $assigned_count,
                'message'  => sprintf(
                    __( '%d reviewers assigned successfully', 'event-management-system' ),
                    $assigned_count
                ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in assign_reviewers: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred during assignment', 'event-management-system' ),
            );
        }
    }

    /**
     * Submit a review for an abstract
     *
     * Records a reviewer's score, comments, and recommendation.
     *
     * @since 1.0.0
     * @param int   $abstract_id Abstract post ID.
     * @param int   $reviewer_id Reviewer user ID.
     * @param array $review_data {
     *     Review submission data.
     *
     *     @type int    $score          Score from 1-10.
     *     @type string $comments       Review comments (required for reject/revise).
     *     @type string $recommendation approve|reject|revise.
     * }
     * @return array {
     *     Result array.
     *
     *     @type bool   $success   Whether submission succeeded.
     *     @type int    $review_id Database review ID.
     *     @type string $message   Status or error message.
     * }
     */
    public function submit_review( $abstract_id, $reviewer_id, $review_data ) {
        global $wpdb;
        
        try {
            $this->logger->info(
                "Reviewer {$reviewer_id} submitting review for abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Validate required fields
            if ( empty( $review_data['score'] ) || empty( $review_data['recommendation'] ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'Score and recommendation are required', 'event-management-system' ),
                );
            }

            // Validate score range
            $score = intval( $review_data['score'] );
            if ( $score < 1 || $score > 10 ) {
                return array(
                    'success' => false,
                    'message' => __( 'Score must be between 1 and 10', 'event-management-system' ),
                );
            }

            // Validate recommendation
            $allowed_recommendations = array( 'approve', 'reject', 'revise' );
            $recommendation = sanitize_text_field( $review_data['recommendation'] );
            
            if ( ! in_array( $recommendation, $allowed_recommendations, true ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'Invalid recommendation', 'event-management-system' ),
                );
            }

            // Comments required for reject or revise
            $comments = isset( $review_data['comments'] ) ? sanitize_textarea_field( $review_data['comments'] ) : '';
            
            if ( in_array( $recommendation, array( 'reject', 'revise' ), true ) && empty( $comments ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'Comments are required for rejection or revision requests', 'event-management-system' ),
                );
            }

            // Verify reviewer is assigned to this abstract
            $review_record = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} 
                    WHERE abstract_id = %d AND reviewer_id = %d",
                    $abstract_id,
                    $reviewer_id
                )
            );

            if ( ! $review_record ) {
                return array(
                    'success' => false,
                    'message' => __( 'You are not assigned to review this abstract', 'event-management-system' ),
                );
            }

            // Check if already reviewed
            if ( 'completed' === $review_record->status ) {
                return array(
                    'success' => false,
                    'message' => __( 'You have already submitted a review for this abstract', 'event-management-system' ),
                );
            }

            // Update review record
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'review_date'    => current_time( 'mysql' ),
                    'score'          => $score,
                    'comments'       => $comments,
                    'recommendation' => $recommendation,
                    'status'         => 'completed',
                ),
                array(
                    'id' => $review_record->id,
                ),
                array( '%s', '%d', '%s', '%s', '%s' ),
                array( '%d' )
            );

            if ( false === $result ) {
                $this->logger->error(
                    "Failed to update review {$review_record->id}: " . $wpdb->last_error,
                    EMS_Logger::CONTEXT_GENERAL
                );
                return array(
                    'success' => false,
                    'message' => __( 'Failed to save review', 'event-management-system' ),
                );
            }

            $this->logger->info(
                "Review submitted for abstract {$abstract_id} by reviewer {$reviewer_id}: {$recommendation}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Update abstract recommendation counts
            if ( 'approve' === $recommendation ) {
                $current_count = get_post_meta( $abstract_id, 'approved_count', true ) ?: 0;
                update_post_meta( $abstract_id, 'approved_count', $current_count + 1 );
            } elseif ( 'reject' === $recommendation ) {
                $current_count = get_post_meta( $abstract_id, 'rejected_count', true ) ?: 0;
                update_post_meta( $abstract_id, 'rejected_count', $current_count + 1 );
            }

            // Check if review is complete and process decision
            $completion_check = $this->check_review_completion( $abstract_id );
            if ( $completion_check['complete'] ) {
                $this->process_review_decision( $abstract_id );
            }

            // Fire action hook
            do_action( 'ems_review_submitted', $abstract_id, $reviewer_id, $review_data );

            return array(
                'success'   => true,
                'review_id' => $review_record->id,
                'message'   => __( 'Review submitted successfully', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in submit_review: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred during submission', 'event-management-system' ),
            );
        }
    }

    /**
     * Get a specific review
     *
     * Retrieves review details by review ID.
     *
     * @since 1.0.0
     * @param int $review_id Review database ID.
     * @return array {
     *     Result array.
     *
     *     @type bool   $success Whether retrieval succeeded.
     *     @type object $data    Review data object.
     *     @type string $message Status or error message.
     * }
     */
    public function get_review( $review_id ) {
        global $wpdb;
        
        try {
            $review = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE id = %d",
                    $review_id
                )
            );

            if ( ! $review ) {
                return array(
                    'success' => false,
                    'message' => __( 'Review not found', 'event-management-system' ),
                );
            }

            return array(
                'success' => true,
                'data'    => $review,
                'message' => __( 'Review retrieved', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_review: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get all reviews for an abstract
     *
     * Retrieves all reviews submitted for a specific abstract.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array {
     *     Result array.
     *
     *     @type bool  $success Whether retrieval succeeded.
     *     @type array $reviews Array of review objects.
     *     @type int   $total   Total number of reviews.
     *     @type string $message Status or error message.
     * }
     */
    public function get_abstract_reviews( $abstract_id ) {
        global $wpdb;
        
        try {
            $reviews = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} 
                    WHERE abstract_id = %d 
                    ORDER BY assigned_date ASC",
                    $abstract_id
                )
            );

            // Enrich with reviewer names
            foreach ( $reviews as &$review ) {
                $user = get_userdata( $review->reviewer_id );
                $review->reviewer_name = $user ? $user->display_name : __( 'Unknown', 'event-management-system' );
            }

            return array(
                'success' => true,
                'reviews' => $reviews,
                'total'   => count( $reviews ),
                'message' => sprintf(
                    __( 'Found %d reviews', 'event-management-system' ),
                    count( $reviews )
                ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_abstract_reviews: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get reviewer assignments
     *
     * Retrieves all abstracts assigned to a specific reviewer.
     *
     * @since 1.0.0
     * @param int $reviewer_id Reviewer user ID.
     * @return array {
     *     Result array.
     *
     *     @type bool  $success     Whether retrieval succeeded.
     *     @type array $assignments Array of assignment data.
     *     @type int   $total       Total assignments.
     *     @type string $message    Status or error message.
     * }
     */
    public function get_reviewer_assignments( $reviewer_id ) {
        global $wpdb;
        
        try {
            $reviews = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} 
                    WHERE reviewer_id = %d 
                    ORDER BY assigned_date DESC",
                    $reviewer_id
                )
            );

            $assignments = array();

            foreach ( $reviews as $review ) {
                $abstract = get_post( $review->abstract_id );
                if ( ! $abstract ) {
                    continue;
                }

                $assignments[] = array(
                    'review_id'       => $review->id,
                    'abstract_id'     => $review->abstract_id,
                    'abstract_title'  => $abstract->post_title,
                    'event_id'        => get_post_meta( $review->abstract_id, 'abstract_event_id', true ),
                    'assigned_date'   => $review->assigned_date,
                    'review_date'     => $review->review_date,
                    'status'          => $review->status,
                    'score'           => $review->score,
                    'recommendation'  => $review->recommendation,
                );
            }

            return array(
                'success'     => true,
                'assignments' => $assignments,
                'total'       => count( $assignments ),
                'message'     => sprintf(
                    __( 'Found %d assignments', 'event-management-system' ),
                    count( $assignments )
                ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_reviewer_assignments: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Check review completion status
     *
     * Determines if all assigned reviewers have submitted their reviews.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array {
     *     Completion status.
     *
     *     @type bool $complete  Whether all reviews are complete.
     *     @type int  $completed Number of completed reviews.
     *     @type int  $total     Total assigned reviewers.
     * }
     */
    public function check_review_completion( $abstract_id ) {
        global $wpdb;
        
        try {
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} 
                    WHERE abstract_id = %d",
                    $abstract_id
                )
            );

            $completed = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} 
                    WHERE abstract_id = %d AND status = 'completed'",
                    $abstract_id
                )
            );

            return array(
                'complete'  => ( $total > 0 && $total == $completed ),
                'completed' => intval( $completed ),
                'total'     => intval( $total ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in check_review_completion: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'complete'  => false,
                'completed' => 0,
                'total'     => 0,
            );
        }
    }

    /**
     * Process review decision
     *
     * Calculates majority decision and updates abstract status accordingly.
     * Called automatically when all reviews are complete.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array Result array with decision outcome.
     */
    public function process_review_decision( $abstract_id ) {
        try {
            $this->logger->info(
                "Processing review decision for abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Get all reviews
            $reviews_result = $this->get_abstract_reviews( $abstract_id );
            if ( ! $reviews_result['success'] || empty( $reviews_result['reviews'] ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'No reviews found', 'event-management-system' ),
                );
            }

            $reviews = $reviews_result['reviews'];
            $total_reviews = count( $reviews );

            // Count recommendations
            $approve_count = 0;
            $reject_count = 0;
            $revise_count = 0;

            foreach ( $reviews as $review ) {
                if ( 'completed' !== $review->status ) {
                    continue;
                }

                switch ( $review->recommendation ) {
                    case 'approve':
                        $approve_count++;
                        break;
                    case 'reject':
                        $reject_count++;
                        break;
                    case 'revise':
                        $revise_count++;
                        break;
                }
            }

            // Calculate majority (more than half)
            $majority_threshold = ceil( $total_reviews / 2 );

            // Determine decision
            $decision = '';
            $decision_message = '';

            if ( $approve_count >= $majority_threshold ) {
                $decision = 'approved';
                $decision_message = sprintf(
                    __( 'Abstract approved by majority (%d of %d reviewers)', 'event-management-system' ),
                    $approve_count,
                    $total_reviews
                );
                $this->approve_abstract( $abstract_id );
            } elseif ( $reject_count >= $majority_threshold ) {
                $decision = 'rejected';
                $decision_message = sprintf(
                    __( 'Abstract rejected by majority (%d of %d reviewers)', 'event-management-system' ),
                    $reject_count,
                    $total_reviews
                );
                $this->reject_abstract( $abstract_id );
            } else {
                $decision = 'revision_requested';
                $decision_message = sprintf(
                    __( 'Revision requested (no clear majority: %d approve, %d reject, %d revise)', 'event-management-system' ),
                    $approve_count,
                    $reject_count,
                    $revise_count
                );
                $this->request_revision( $abstract_id, $decision_message );
            }

            $this->logger->info(
                "Decision for abstract {$abstract_id}: {$decision}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Fire action hook
            do_action( 'ems_review_decision_processed', $abstract_id, $decision );

            return array(
                'success' => true,
                'decision' => $decision,
                'message' => $decision_message,
                'counts' => array(
                    'approve' => $approve_count,
                    'reject'  => $reject_count,
                    'revise'  => $revise_count,
                ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in process_review_decision: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred processing decision', 'event-management-system' ),
            );
        }
    }

    /**
     * Request revision for an abstract
     *
     * Updates status to request changes from the author.
     *
     * @since 1.0.0
     * @param int    $abstract_id Abstract post ID.
     * @param string $comments    Revision comments from reviewers.
     * @return array Result array with success status.
     */
    public function request_revision( $abstract_id, $comments ) {
        try {
            update_post_meta( $abstract_id, 'review_status', 'revision_requested' );
            update_post_meta( $abstract_id, 'revision_comments', $comments );
            update_post_meta( $abstract_id, 'revision_requested_date', current_time( 'mysql' ) );

            // Send email to author
            $this->email_manager->send_revision_requested_email( $abstract_id );

            $this->logger->info(
                "Revision requested for abstract {$abstract_id}",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Fire action hook
            do_action( 'ems_revision_requested', $abstract_id );

            return array(
                'success' => true,
                'message' => __( 'Revision requested', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in request_revision: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Approve an abstract
     *
     * Marks abstract as approved and sends notification.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array Result array with success status.
     */
    public function approve_abstract( $abstract_id ) {
        try {
            update_post_meta( $abstract_id, 'review_status', 'approved' );
            update_post_meta( $abstract_id, 'approval_date', current_time( 'mysql' ) );

            // Send email to author
            $this->email_manager->send_abstract_approved_email( $abstract_id );

            $this->logger->info(
                "Abstract {$abstract_id} approved",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Fire action hook
            do_action( 'ems_abstract_approved', $abstract_id );

            return array(
                'success' => true,
                'message' => __( 'Abstract approved', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in approve_abstract: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Reject an abstract
     *
     * Marks abstract as rejected and sends notification.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array Result array with success status.
     */
    public function reject_abstract( $abstract_id ) {
        try {
            update_post_meta( $abstract_id, 'review_status', 'rejected' );
            update_post_meta( $abstract_id, 'rejection_date', current_time( 'mysql' ) );

            // Send email to author
            $this->email_manager->send_abstract_rejected_email( $abstract_id );

            $this->logger->info(
                "Abstract {$abstract_id} rejected",
                EMS_Logger::CONTEXT_GENERAL
            );

            // Fire action hook
            do_action( 'ems_abstract_rejected', $abstract_id );

            return array(
                'success' => true,
                'message' => __( 'Abstract rejected', 'event-management-system' ),
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in reject_abstract: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array(
                'success' => false,
                'message' => __( 'An error occurred', 'event-management-system' ),
            );
        }
    }

    /**
     * Get review statistics for an abstract
     *
     * Provides summary statistics about reviews.
     *
     * @since 1.0.0
     * @param int $abstract_id Abstract post ID.
     * @return array Statistics array.
     */
    public function get_review_statistics( $abstract_id ) {
        global $wpdb;
        
        try {
            $reviews = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT score, recommendation FROM {$this->table_name} 
                    WHERE abstract_id = %d AND status = 'completed'",
                    $abstract_id
                )
            );

            if ( empty( $reviews ) ) {
                return array(
                    'total'           => 0,
                    'completed'       => 0,
                    'average_score'   => 0,
                    'recommendations' => array(),
                );
            }

            $scores = array();
            $recommendations = array(
                'approve' => 0,
                'reject'  => 0,
                'revise'  => 0,
            );

            foreach ( $reviews as $review ) {
                if ( ! is_null( $review->score ) ) {
                    $scores[] = intval( $review->score );
                }
                if ( isset( $recommendations[ $review->recommendation ] ) ) {
                    $recommendations[ $review->recommendation ]++;
                }
            }

            return array(
                'total'           => count( $reviews ),
                'completed'       => count( $reviews ),
                'average_score'   => ! empty( $scores ) ? round( array_sum( $scores ) / count( $scores ), 2 ) : 0,
                'recommendations' => $recommendations,
            );

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in get_review_statistics: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );
            return array();
        }
    }
}
