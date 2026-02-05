<?php
/**
 * EOI Submissions Admin List Page
 *
 * Displays a paginated, filterable list of all Expression of Interest
 * submissions from sponsors.
 *
 * @package    EventManagementSystem
 * @subpackage Admin/Views
 * @since      1.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$eoi_table = $wpdb->prefix . 'ems_sponsor_eoi';

// Filter parameters
$filter_event  = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$paged         = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$per_page      = 20;
$offset        = ( $paged - 1 ) * $per_page;

// Build query conditions
$where  = array( '1=1' );
$params = array();

if ( $filter_event ) {
	$where[]  = 'eoi.event_id = %d';
	$params[] = $filter_event;
}

if ( $filter_status ) {
	$where[]  = 'eoi.status = %s';
	$params[] = $filter_status;
}

$where_clause = implode( ' AND ', $where );

// Count total
$count_sql = "SELECT COUNT(*) FROM {$eoi_table} eoi WHERE {$where_clause}";
$total = empty( $params )
	? $wpdb->get_var( $count_sql )
	: $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

$total_pages = ceil( $total / $per_page );

// Fetch results
$query = "SELECT eoi.*,
		sp.post_title AS sponsor_name,
		ev.post_title AS event_name
	FROM {$eoi_table} eoi
	LEFT JOIN {$wpdb->posts} sp ON eoi.sponsor_id = sp.ID
	LEFT JOIN {$wpdb->posts} ev ON eoi.event_id = ev.ID
	WHERE {$where_clause}
	ORDER BY eoi.submitted_at DESC
	LIMIT %d OFFSET %d";

$query_params   = $params;
$query_params[] = $per_page;
$query_params[] = $offset;

$submissions = $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );

// Get events for filter dropdown
$events = get_posts( array(
	'post_type'      => 'ems_event',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );

// Status options
$statuses = array(
	'pending'        => __( 'Pending', 'event-management-system' ),
	'approved'       => __( 'Approved', 'event-management-system' ),
	'rejected'       => __( 'Rejected', 'event-management-system' ),
	'withdrawn'      => __( 'Withdrawn', 'event-management-system' ),
	'info_requested' => __( 'Info Requested', 'event-management-system' ),
);
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'EOI Submissions', 'event-management-system' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['bulk_updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Bulk action completed successfully.', 'event-management-system' ); ?></p></div>
	<?php endif; ?>

	<!-- Filters -->
	<div class="tablenav top">
		<form method="get">
			<input type="hidden" name="page" value="ems-eoi-submissions">

			<div class="alignleft actions">
				<label for="ems-filter-event" class="screen-reader-text"><?php esc_html_e( 'Filter by event', 'event-management-system' ); ?></label>
				<select name="event_id" id="ems-filter-event">
					<option value=""><?php esc_html_e( 'All Events', 'event-management-system' ); ?></option>
					<?php foreach ( $events as $event ) : ?>
						<option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $filter_event, $event->ID ); ?>>
							<?php echo esc_html( $event->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="ems-filter-status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'event-management-system' ); ?></label>
				<select name="status" id="ems-filter-status">
					<option value=""><?php esc_html_e( 'All Statuses', 'event-management-system' ); ?></option>
					<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_status, $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'event-management-system' ); ?>">
			</div>
		</form>
	</div>

	<!-- Bulk Actions Form -->
	<form method="post">
		<?php wp_nonce_field( 'ems_eoi_bulk_action', 'ems_eoi_bulk_nonce' ); ?>

		<div class="tablenav top" style="margin-top: 0;">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector" class="screen-reader-text"><?php esc_html_e( 'Bulk actions', 'event-management-system' ); ?></label>
				<select name="bulk_action" id="bulk-action-selector">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'event-management-system' ); ?></option>
					<option value="approve"><?php esc_html_e( 'Approve', 'event-management-system' ); ?></option>
					<option value="reject"><?php esc_html_e( 'Reject', 'event-management-system' ); ?></option>
				</select>
				<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'event-management-system' ); ?>">
			</div>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						printf(
							/* translators: %s: Number of items */
							_n( '%s item', '%s items', $total, 'event-management-system' ),
							number_format_i18n( $total )
						);
						?>
					</span>
					<span class="pagination-links">
						<?php if ( $paged > 1 ) : ?>
							<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>">
								&lsaquo;
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled">&lsaquo;</span>
						<?php endif; ?>

						<span class="paging-input">
							<?php echo esc_html( $paged ); ?>
							<?php esc_html_e( 'of', 'event-management-system' ); ?>
							<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
						</span>

						<?php if ( $paged < $total_pages ) : ?>
							<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>">
								&rsaquo;
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled">&rsaquo;</span>
						<?php endif; ?>
					</span>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $submissions ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<label for="cb-select-all" class="screen-reader-text"><?php esc_html_e( 'Select all', 'event-management-system' ); ?></label>
							<input type="checkbox" id="cb-select-all">
						</td>
						<th><?php esc_html_e( 'Sponsor', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Preferred Level', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Est. Value', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Status', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Submitted', 'event-management-system' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $submissions as $eoi ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<label for="cb-select-<?php echo esc_attr( $eoi->id ); ?>" class="screen-reader-text">
									<?php
									printf(
										/* translators: %d: EOI ID */
										esc_html__( 'Select EOI #%d', 'event-management-system' ),
										$eoi->id
									);
									?>
								</label>
								<input type="checkbox" name="eoi_ids[]" id="cb-select-<?php echo esc_attr( $eoi->id ); ?>" value="<?php echo esc_attr( $eoi->id ); ?>">
							</th>
							<td>
								<strong>
									<a href="<?php echo esc_url( get_edit_post_link( $eoi->sponsor_id ) ); ?>">
										<?php echo esc_html( $eoi->sponsor_name ?: __( 'Unknown', 'event-management-system' ) ); ?>
									</a>
								</strong>
							</td>
							<td><?php echo esc_html( $eoi->event_name ?: __( 'Unknown', 'event-management-system' ) ); ?></td>
							<td><?php echo esc_html( $eoi->preferred_level ?: '—' ); ?></td>
							<td>
								<?php
								if ( $eoi->estimated_value ) {
									echo esc_html( '$' . number_format( $eoi->estimated_value, 2 ) );
								} else {
									echo '—';
								}
								?>
							</td>
							<td>
								<?php
								$status_class = 'ems-status-' . esc_attr( $eoi->status );
								$status_label = isset( $statuses[ $eoi->status ] ) ? $statuses[ $eoi->status ] : ucfirst( $eoi->status );
								?>
								<span class="ems-status-badge <?php echo esc_attr( $status_class ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $eoi->submitted_at ) ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-eoi-detail&eoi_id=' . $eoi->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'View', 'event-management-system' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="notice notice-info" style="margin-top: 20px;">
				<p><?php esc_html_e( 'No EOI submissions found.', 'event-management-system' ); ?></p>
			</div>
		<?php endif; ?>
	</form>
</div>
