<?php
/**
 * Admin View: Reviewer Dashboard
 *
 * This template provides an interface for reviewers to:
 * - View assigned abstracts
 * - Submit reviews with scores and recommendations
 * - Track review completion status
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/admin/views
 * @since      1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize handlers
$submission_handler = new EMS_Abstract_Submission();
$review_handler = new EMS_Abstract_Review();

// Get current user
$reviewer_id = get_current_user_id();

// Get assigned abstracts
$assignments_result = $review_handler->get_reviewer_assignments( $reviewer_id );

// Handle review submission
if ( isset( $_POST['submit_review'] ) && isset( $_POST['ems_review_nonce'] ) ) {
    if ( wp_verify_nonce( $_POST['ems_review_nonce'], 'ems_submit_review' ) ) {
        $abstract_id = intval( $_POST['abstract_id'] );
        
        $review_data = array(
            'score'          => intval( $_POST['score'] ),
            'comments'       => sanitize_textarea_field( $_POST['comments'] ),
            'recommendation' => sanitize_text_field( $_POST['recommendation'] ),
        );

        $result = $review_handler->submit_review( $abstract_id, $reviewer_id, $review_data );

        if ( $result['success'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $result['message'] ) . '</p></div>';
            // Refresh assignments
            $assignments_result = $review_handler->get_reviewer_assignments( $reviewer_id );
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result['message'] ) . '</p></div>';
        }
    }
}

?>

<div class="wrap">
    <h1><?php _e( 'My Review Assignments', 'event-management-system' ); ?></h1>

    <?php if ( $assignments_result['success'] && ! empty( $assignments_result['assignments'] ) ) : ?>
        
        <!-- Statistics -->
        <div class="ems-stats-boxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <?php
            $pending_count = 0;
            $completed_count = 0;
            
            foreach ( $assignments_result['assignments'] as $assignment ) {
                if ( 'completed' === $assignment['status'] ) {
                    $completed_count++;
                } else {
                    $pending_count++;
                }
            }
            ?>
            
            <div style="background: #fff; padding: 15px; border-left: 4px solid #2271b1;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $assignments_result['total']; ?></div>
                <div><?php _e( 'Total Assignments', 'event-management-system' ); ?></div>
            </div>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #f0ad4e;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $pending_count; ?></div>
                <div><?php _e( 'Pending Reviews', 'event-management-system' ); ?></div>
            </div>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #5cb85c;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $completed_count; ?></div>
                <div><?php _e( 'Completed Reviews', 'event-management-system' ); ?></div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="ems-review-assignments">
            <?php foreach ( $assignments_result['assignments'] as $assignment ) : ?>
                <?php
                $abstract_result = $submission_handler->get_abstract( $assignment['abstract_id'] );
                $abstract = $abstract_result['success'] ? $abstract_result['data'] : null;
                $event_title = get_the_title( $assignment['event_id'] );
                ?>
                
                <div class="ems-review-card" style="background: #fff; border: 1px solid #ccd0d4; margin: 20px 0; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h2 style="margin: 0 0 10px 0;"><?php echo esc_html( $assignment['abstract_title'] ); ?></h2>
                            <p style="color: #666; margin: 0;">
                                <strong><?php _e( 'Event:', 'event-management-system' ); ?></strong> <?php echo esc_html( $event_title ); ?>
                            </p>
                        </div>
                        <div>
                            <?php if ( 'completed' === $assignment['status'] ) : ?>
                                <span style="background: #5cb85c; color: #fff; padding: 5px 12px; border-radius: 3px; font-size: 12px;">
                                    ✓ <?php _e( 'Review Complete', 'event-management-system' ); ?>
                                </span>
                            <?php else : ?>
                                <span style="background: #f0ad4e; color: #fff; padding: 5px 12px; border-radius: 3px; font-size: 12px;">
                                    ⏱ <?php _e( 'Pending Review', 'event-management-system' ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ( $abstract ) : ?>
                        <!-- Abstract Details -->
                        <div style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 3px solid #2271b1;">
                            <p style="margin: 0 0 10px 0;"><strong><?php _e( 'Type:', 'event-management-system' ); ?></strong> <?php echo esc_html( ucfirst( $abstract['abstract_type'] ) ); ?></p>
                            <p style="margin: 0 0 10px 0;"><strong><?php _e( 'Keywords:', 'event-management-system' ); ?></strong> <?php echo esc_html( $abstract['keywords'] ?: __( 'None', 'event-management-system' ) ); ?></p>
                            
                            <div style="margin-top: 15px;">
                                <strong><?php _e( 'Abstract:', 'event-management-system' ); ?></strong>
                                <div style="margin-top: 5px; white-space: pre-wrap;">
                                    <?php echo esc_html( $abstract['content'] ); ?>
                                </div>
                            </div>

                            <?php if ( ! empty( $abstract['learning_objectives'] ) ) : ?>
                                <div style="margin-top: 15px;">
                                    <strong><?php _e( 'Learning Objectives:', 'event-management-system' ); ?></strong>
                                    <div style="margin-top: 5px; white-space: pre-wrap;">
                                        <?php echo esc_html( $abstract['learning_objectives'] ); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( 'completed' === $assignment['status'] ) : ?>
                            <!-- Display submitted review -->
                            <div style="background: #d4edda; padding: 15px; margin: 15px 0; border-left: 3px solid #5cb85c;">
                                <h3 style="margin: 0 0 10px 0;"><?php _e( 'Your Review', 'event-management-system' ); ?></h3>
                                <p style="margin: 5px 0;"><strong><?php _e( 'Score:', 'event-management-system' ); ?></strong> <?php echo esc_html( $assignment['score'] ); ?>/10</p>
                                <p style="margin: 5px 0;"><strong><?php _e( 'Recommendation:', 'event-management-system' ); ?></strong> <?php echo esc_html( ucfirst( $assignment['recommendation'] ) ); ?></p>
                                <p style="margin: 5px 0;"><strong><?php _e( 'Submitted:', 'event-management-system' ); ?></strong> <?php echo esc_html( EMS_Date_Helper::format( $assignment['review_date'] ) ); ?></p>
                            </div>
                        <?php else : ?>
                            <!-- Review Form -->
                            <div style="background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 3px solid #f0ad4e;">
                                <h3 style="margin: 0 0 15px 0;"><?php _e( 'Submit Your Review', 'event-management-system' ); ?></h3>
                                
                                <form method="post">
                                    <?php wp_nonce_field( 'ems_submit_review', 'ems_review_nonce' ); ?>
                                    <input type="hidden" name="abstract_id" value="<?php echo esc_attr( $assignment['abstract_id'] ); ?>">

                                    <div style="margin-bottom: 15px;">
                                        <label for="score_<?php echo esc_attr( $assignment['review_id'] ); ?>" style="display: block; margin-bottom: 5px; font-weight: bold;">
                                            <?php _e( 'Score (1-10) *', 'event-management-system' ); ?>
                                        </label>
                                        <select name="score" id="score_<?php echo esc_attr( $assignment['review_id'] ); ?>" required style="width: 100px;">
                                            <option value=""><?php _e( 'Select...', 'event-management-system' ); ?></option>
                                            <?php for ( $i = 1; $i <= 10; $i++ ) : ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <small style="display: block; margin-top: 5px; color: #666;">
                                            <?php _e( '1 = Poor, 10 = Excellent', 'event-management-system' ); ?>
                                        </small>
                                    </div>

                                    <div style="margin-bottom: 15px;">
                                        <label for="recommendation_<?php echo esc_attr( $assignment['review_id'] ); ?>" style="display: block; margin-bottom: 5px; font-weight: bold;">
                                            <?php _e( 'Recommendation *', 'event-management-system' ); ?>
                                        </label>
                                        <select name="recommendation" id="recommendation_<?php echo esc_attr( $assignment['review_id'] ); ?>" required style="width: 200px;">
                                            <option value=""><?php _e( 'Select...', 'event-management-system' ); ?></option>
                                            <option value="approve"><?php _e( 'Approve', 'event-management-system' ); ?></option>
                                            <option value="revise"><?php _e( 'Request Revision', 'event-management-system' ); ?></option>
                                            <option value="reject"><?php _e( 'Reject', 'event-management-system' ); ?></option>
                                        </select>
                                    </div>

                                    <div style="margin-bottom: 15px;">
                                        <label for="comments_<?php echo esc_attr( $assignment['review_id'] ); ?>" style="display: block; margin-bottom: 5px; font-weight: bold;">
                                            <?php _e( 'Comments', 'event-management-system' ); ?>
                                        </label>
                                        <textarea name="comments" id="comments_<?php echo esc_attr( $assignment['review_id'] ); ?>" rows="6" style="width: 100%;"></textarea>
                                        <small style="display: block; margin-top: 5px; color: #666;">
                                            <?php _e( 'Comments are required for rejection or revision requests', 'event-management-system' ); ?>
                                        </small>
                                    </div>

                                    <button type="submit" name="submit_review" class="button button-primary">
                                        <?php _e( 'Submit Review', 'event-management-system' ); ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="notice notice-error inline">
                            <p><?php _e( 'Abstract data could not be loaded.', 'event-management-system' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
        <div class="notice notice-info inline">
            <p><?php _e( 'You have no review assignments at this time.', 'event-management-system' ); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.ems-review-card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.ems-review-card select,
.ems-review-card textarea {
    padding: 6px 8px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.ems-review-card textarea {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}
</style>
