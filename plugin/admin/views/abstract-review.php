<?php
/**
 * Admin View: Abstract Review Management
 *
 * This template provides an interface for:
 * - Viewing submitted abstracts
 * - Assigning reviewers
 * - Viewing review status
 * - Processing review decisions
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

// Get event ID from query string
$event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;

// Get filter parameters
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$type_filter = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';

// Get abstracts
$abstracts_result = $event_id > 0 
    ? $submission_handler->get_event_abstracts( $event_id, array(
        'status' => $status_filter,
        'type'   => $type_filter,
    ))
    : array( 'success' => false, 'abstracts' => array() );

// Get all events for dropdown
$events = get_posts( array(
    'post_type'      => 'ems_event',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
) );

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Abstract Review Management', 'event-management-system' ); ?></h1>
    
    <hr class="wp-header-end">

    <!-- Event and Filter Selection -->
    <div class="ems-admin-filters" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ccd0d4;">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? '' ); ?>">
            
            <div style="display: flex; gap: 15px; align-items: end;">
                <div>
                    <label for="event_id"><?php _e( 'Event:', 'event-management-system' ); ?></label><br>
                    <select name="event_id" id="event_id" style="min-width: 200px;">
                        <option value=""><?php _e( 'Select Event...', 'event-management-system' ); ?></option>
                        <?php foreach ( $events as $event ) : ?>
                            <option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event_id, $event->ID ); ?>>
                                <?php echo esc_html( $event->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="status"><?php _e( 'Status:', 'event-management-system' ); ?></label><br>
                    <select name="status" id="status">
                        <option value=""><?php _e( 'All Statuses', 'event-management-system' ); ?></option>
                        <option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php _e( 'Pending', 'event-management-system' ); ?></option>
                        <option value="under_review" <?php selected( $status_filter, 'under_review' ); ?>><?php _e( 'Under Review', 'event-management-system' ); ?></option>
                        <option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php _e( 'Approved', 'event-management-system' ); ?></option>
                        <option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>><?php _e( 'Rejected', 'event-management-system' ); ?></option>
                        <option value="revision_requested" <?php selected( $status_filter, 'revision_requested' ); ?>><?php _e( 'Revision Requested', 'event-management-system' ); ?></option>
                    </select>
                </div>

                <div>
                    <label for="type"><?php _e( 'Type:', 'event-management-system' ); ?></label><br>
                    <select name="type" id="type">
                        <option value=""><?php _e( 'All Types', 'event-management-system' ); ?></option>
                        <option value="oral" <?php selected( $type_filter, 'oral' ); ?>><?php _e( 'Oral', 'event-management-system' ); ?></option>
                        <option value="poster" <?php selected( $type_filter, 'poster' ); ?>><?php _e( 'Poster', 'event-management-system' ); ?></option>
                        <option value="workshop" <?php selected( $type_filter, 'workshop' ); ?>><?php _e( 'Workshop', 'event-management-system' ); ?></option>
                    </select>
                </div>

                <button type="submit" class="button"><?php _e( 'Filter', 'event-management-system' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Statistics -->
    <?php if ( $event_id > 0 && $abstracts_result['success'] ) : ?>
        <div class="ems-stats-boxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <?php
            $stats = array(
                'total'              => 0,
                'pending'            => 0,
                'under_review'       => 0,
                'approved'           => 0,
                'rejected'           => 0,
                'revision_requested' => 0,
            );

            foreach ( $abstracts_result['abstracts'] as $abstract ) {
                $stats['total']++;
                if ( isset( $stats[ $abstract['review_status'] ] ) ) {
                    $stats[ $abstract['review_status'] ]++;
                }
            }
            ?>
            
            <div style="background: #fff; padding: 15px; border-left: 4px solid #2271b1;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $stats['total']; ?></div>
                <div><?php _e( 'Total Submissions', 'event-management-system' ); ?></div>
            </div>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #f0ad4e;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $stats['pending']; ?></div>
                <div><?php _e( 'Pending Review', 'event-management-system' ); ?></div>
            </div>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #5bc0de;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $stats['under_review']; ?></div>
                <div><?php _e( 'Under Review', 'event-management-system' ); ?></div>
            </div>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #5cb85c;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $stats['approved']; ?></div>
                <div><?php _e( 'Approved', 'event-management-system' ); ?></div>
            </div>

            <div style="background: #fff; padding: 15px; border-left: 4px solid #d9534f;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $stats['rejected']; ?></div>
                <div><?php _e( 'Rejected', 'event-management-system' ); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Abstracts Table -->
    <?php if ( $event_id > 0 ) : ?>
        <?php if ( $abstracts_result['success'] && ! empty( $abstracts_result['abstracts'] ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;"><?php _e( 'Title', 'event-management-system' ); ?></th>
                        <th><?php _e( 'Author', 'event-management-system' ); ?></th>
                        <th><?php _e( 'Type', 'event-management-system' ); ?></th>
                        <th><?php _e( 'Status', 'event-management-system' ); ?></th>
                        <th><?php _e( 'Reviews', 'event-management-system' ); ?></th>
                        <th><?php _e( 'Score', 'event-management-system' ); ?></th>
                        <th><?php _e( 'Actions', 'event-management-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $abstracts_result['abstracts'] as $abstract ) : ?>
                        <?php
                        // Get review statistics
                        $review_stats = $review_handler->get_review_statistics( $abstract['id'] );
                        $reviews_complete = $review_stats['total'] > 0 ? $review_stats['completed'] : 0;
                        $total_reviews = $review_stats['total'];
                        $avg_score = $review_stats['average_score'];
                        
                        // Status badge color
                        $status_colors = array(
                            'pending'            => '#f0ad4e',
                            'under_review'       => '#5bc0de',
                            'approved'           => '#5cb85c',
                            'rejected'           => '#d9534f',
                            'revision_requested' => '#f0ad4e',
                        );
                        $status_color = $status_colors[ $abstract['review_status'] ] ?? '#6c757d';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $abstract['title'] ); ?></strong>
                            </td>
                            <td><?php echo esc_html( $abstract['author_name'] ); ?></td>
                            <td><?php echo esc_html( ucfirst( $abstract['abstract_type'] ) ); ?></td>
                            <td>
                                <span class="ems-badge" style="background: <?php echo esc_attr( $status_color ); ?>; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $abstract['review_status'] ) ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ( $total_reviews > 0 ) {
                                    echo esc_html( "{$reviews_complete}/{$total_reviews}" );
                                } else {
                                    echo '<span style="color: #999;">' . __( 'Not assigned', 'event-management-system' ) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ( $avg_score > 0 ) {
                                    echo '<strong>' . esc_html( number_format( $avg_score, 1 ) ) . '</strong>/10';
                                } else {
                                    echo '<span style="color: #999;">—</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=ems-abstract-detail&abstract_id={$abstract['id']}" ) ); ?>" class="button button-small">
                                    <?php _e( 'View', 'event-management-system' ); ?>
                                </a>
                                
                                <?php if ( in_array( $abstract['review_status'], array( 'pending', 'under_review' ) ) ) : ?>
                                    <a href="<?php echo esc_url( admin_url( "admin.php?page=ems-assign-reviewers&abstract_id={$abstract['id']}" ) ); ?>" class="button button-small">
                                        <?php _e( 'Assign Reviewers', 'event-management-system' ); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info inline">
                <p><?php _e( 'No abstracts found for the selected criteria.', 'event-management-system' ); ?></p>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="notice notice-info inline">
            <p><?php _e( 'Please select an event to view abstracts.', 'event-management-system' ); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.ems-admin-filters select,
.ems-admin-filters input {
    height: 30px;
}

.wp-list-table th {
    padding: 8px 10px !important;
}

.wp-list-table td {
    padding: 10px !important;
}

.button-small {
    padding: 2px 8px !important;
    height: auto !important;
    line-height: 1.5 !important;
    font-size: 12px !important;
}
</style>
