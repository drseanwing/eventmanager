<?php
/**
 * EOI Detail / Review Admin Page
 *
 * Shows the full details of an Expression of Interest submission
 * and provides approve/reject/request-info actions.
 *
 * @package    EventManagementSystem
 * @subpackage Admin/Views
 * @since      1.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$eoi_id    = isset( $_GET['eoi_id'] ) ? absint( $_GET['eoi_id'] ) : 0;
$eoi_table = $wpdb->prefix . 'ems_sponsor_eoi';

if ( ! $eoi_id ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'No EOI ID specified.', 'event-management-system' ) . '</p></div></div>';
	return;
}

$eoi = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$eoi_table} WHERE id = %d", $eoi_id ) );

if ( ! $eoi ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'EOI submission not found.', 'event-management-system' ) . '</p></div></div>';
	return;
}

// Get related data
$sponsor_post  = get_post( $eoi->sponsor_id );
$event_post    = get_post( $eoi->event_id );
$sponsor_name  = $sponsor_post ? $sponsor_post->post_title : __( 'Unknown Sponsor', 'event-management-system' );
$event_name    = $event_post ? $event_post->post_title : __( 'Unknown Event', 'event-management-system' );

// Get sponsorship levels for the event (for approval dropdown)
$levels_table = $wpdb->prefix . 'ems_sponsorship_levels';
$levels = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM {$levels_table} WHERE event_id = %d AND enabled = 1 ORDER BY sort_order ASC",
	$eoi->event_id
) );

// Get reviewer info
$reviewer_name = '';
if ( $eoi->reviewed_by ) {
	$reviewer = get_user_by( 'id', $eoi->reviewed_by );
	$reviewer_name = $reviewer ? $reviewer->display_name : __( 'Unknown', 'event-management-system' );
}

// Status labels and colours
$statuses = array(
	'pending'        => __( 'Pending', 'event-management-system' ),
	'approved'       => __( 'Approved', 'event-management-system' ),
	'rejected'       => __( 'Rejected', 'event-management-system' ),
	'withdrawn'      => __( 'Withdrawn', 'event-management-system' ),
	'info_requested' => __( 'Info Requested', 'event-management-system' ),
);
$status_label = isset( $statuses[ $eoi->status ] ) ? $statuses[ $eoi->status ] : ucfirst( $eoi->status );
?>
<div class="wrap">
	<h1>
		<?php
		printf(
			/* translators: %s: EOI ID number */
			esc_html__( 'EOI Submission #%s', 'event-management-system' ),
			esc_html( $eoi_id )
		);
		?>
		<span class="ems-status-badge ems-status-<?php echo esc_attr( $eoi->status ); ?>" style="font-size: 14px; vertical-align: middle; margin-left: 10px;">
			<?php echo esc_html( $status_label ); ?>
		</span>
	</h1>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=ems-eoi-submissions' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '&larr; Back to EOI List', 'event-management-system' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'EOI updated successfully.', 'event-management-system' ); ?></p></div>
	<?php endif; ?>

	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">

		<!-- Main Content -->
		<div>
			<!-- Sponsor & Event Info -->
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Submission Overview', 'event-management-system' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Sponsor', 'event-management-system' ); ?></th>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $eoi->sponsor_id ) ); ?>">
									<?php echo esc_html( $sponsor_name ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Event', 'event-management-system' ); ?></th>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $eoi->event_id ) ); ?>">
									<?php echo esc_html( $event_name ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Preferred Level', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->preferred_level ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Estimated Value', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->estimated_value ? esc_html( '$' . number_format( $eoi->estimated_value, 2 ) ) : '—'; ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Submitted', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $eoi->submitted_at ) ) ); ?></td>
						</tr>
						<?php if ( $eoi->reviewed_at ) : ?>
							<tr>
								<th><?php esc_html_e( 'Reviewed', 'event-management-system' ); ?></th>
								<td>
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $eoi->reviewed_at ) ) ); ?>
									<?php if ( $reviewer_name ) : ?>
										<?php
										printf(
											/* translators: %s: Reviewer name */
											esc_html__( 'by %s', 'event-management-system' ),
											esc_html( $reviewer_name )
										);
										?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endif; ?>
						<?php if ( $eoi->review_notes ) : ?>
							<tr>
								<th><?php esc_html_e( 'Review Notes', 'event-management-system' ); ?></th>
								<td><?php echo esc_html( $eoi->review_notes ); ?></td>
							</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>

			<!-- Contribution Details -->
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Contribution Details', 'event-management-system' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Nature of Contribution', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->contribution_nature ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Conditional Outcome', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->conditional_outcome ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Speaker Nomination', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->speaker_nomination ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Duration Interest', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->duration_interest ?: '—' ); ?></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Exclusivity & Recognition -->
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Exclusivity & Recognition', 'event-management-system' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Exclusivity Requested', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->exclusivity_requested ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<?php if ( $eoi->exclusivity_requested ) : ?>
							<tr>
								<th><?php esc_html_e( 'Exclusivity Category', 'event-management-system' ); ?></th>
								<td><?php echo esc_html( $eoi->exclusivity_category ?: '—' ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Exclusivity Conditions', 'event-management-system' ); ?></th>
								<td><?php echo esc_html( $eoi->exclusivity_conditions ?: '—' ); ?></td>
							</tr>
						<?php endif; ?>
						<tr>
							<th><?php esc_html_e( 'Shared Presence Accepted', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->shared_presence_accepted ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Competitor Objections', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->competitor_objections ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Recognition Elements', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->recognition_elements ?: '—' ); ?></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Trade Display & Activities -->
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Trade Display & Activities', 'event-management-system' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Trade Display Requested', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->trade_display_requested ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<?php if ( $eoi->trade_display_requested ) : ?>
							<tr>
								<th><?php esc_html_e( 'Display Requirements', 'event-management-system' ); ?></th>
								<td><?php echo esc_html( $eoi->trade_display_requirements ?: '—' ); ?></td>
							</tr>
						<?php endif; ?>
						<tr>
							<th><?php esc_html_e( 'Representatives Count', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->representatives_count ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Product Samples', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->product_samples ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Promotional Material', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->promotional_material ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Delegate Data Collection', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->delegate_data_collection ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Social Media Expectations', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->social_media_expectations ?: '—' ); ?></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Content & Compliance -->
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Content & Compliance', 'event-management-system' ); ?></span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Content Independence Acknowledged', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->content_independence_acknowledged ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Content Influence', 'event-management-system' ); ?></th>
							<td><?php echo esc_html( $eoi->content_influence ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Therapeutic Claims', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->therapeutic_claims ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Material Review Required', 'event-management-system' ); ?></th>
							<td><?php echo $eoi->material_review_required ? esc_html__( 'Yes', 'event-management-system' ) : esc_html__( 'No', 'event-management-system' ); ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<!-- Sidebar: Actions -->
		<div>
			<?php if ( 'pending' === $eoi->status || 'info_requested' === $eoi->status ) : ?>

				<!-- Approve -->
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Approve', 'event-management-system' ); ?></span></h2>
					<div class="inside">
						<div id="ems-eoi-approve-form">
							<?php if ( $levels ) : ?>
								<p>
									<label for="ems_approve_level"><strong><?php esc_html_e( 'Assign Level:', 'event-management-system' ); ?></strong></label><br>
									<select id="ems_approve_level" style="width: 100%; margin-top: 5px;">
										<option value=""><?php esc_html_e( '-- Select Level --', 'event-management-system' ); ?></option>
										<?php foreach ( $levels as $level ) : ?>
											<?php
											$available = '';
											if ( $level->slots_total ) {
												$remaining = $level->slots_total - $level->slots_filled;
												$available = sprintf( ' (%d/%d filled)', $level->slots_filled, $level->slots_total );
											}
											?>
											<option value="<?php echo esc_attr( $level->id ); ?>">
												<?php echo esc_html( $level->level_name . $available ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'No sponsorship levels configured for this event.', 'event-management-system' ); ?></p>
							<?php endif; ?>
							<button type="button" class="button button-primary" id="ems-eoi-approve-btn" style="width: 100%; text-align: center; margin-top: 10px;">
								<?php esc_html_e( 'Approve EOI', 'event-management-system' ); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Reject -->
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Reject', 'event-management-system' ); ?></span></h2>
					<div class="inside">
						<div id="ems-eoi-reject-form">
							<p>
								<label for="ems_reject_reason"><strong><?php esc_html_e( 'Reason:', 'event-management-system' ); ?></strong></label><br>
								<textarea id="ems_reject_reason" rows="4" style="width: 100%; margin-top: 5px;"></textarea>
							</p>
							<button type="button" class="button" id="ems-eoi-reject-btn" style="width: 100%; text-align: center; color: #a00;">
								<?php esc_html_e( 'Reject EOI', 'event-management-system' ); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Request More Info -->
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Request More Info', 'event-management-system' ); ?></span></h2>
					<div class="inside">
						<div id="ems-eoi-info-form">
							<p>
								<label for="ems_info_message"><strong><?php esc_html_e( 'Message:', 'event-management-system' ); ?></strong></label><br>
								<textarea id="ems_info_message" rows="4" style="width: 100%; margin-top: 5px;"></textarea>
							</p>
							<button type="button" class="button" id="ems-eoi-info-btn" style="width: 100%; text-align: center;">
								<?php esc_html_e( 'Request Information', 'event-management-system' ); ?>
							</button>
						</div>
					</div>
				</div>

			<?php else : ?>
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( 'Status', 'event-management-system' ); ?></span></h2>
					<div class="inside">
						<p>
							<?php
							printf(
								/* translators: %s: EOI status */
								esc_html__( 'This EOI has been %s.', 'event-management-system' ),
								'<strong>' . esc_html( strtolower( $status_label ) ) . '</strong>'
							);
							?>
						</p>
						<?php if ( $eoi->reviewed_at ) : ?>
							<p class="description">
								<?php
								printf(
									/* translators: 1: Date, 2: Reviewer name */
									esc_html__( 'Reviewed on %1$s by %2$s', 'event-management-system' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $eoi->reviewed_at ) ) ),
									esc_html( $reviewer_name )
								);
								?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Sponsor Profile Link -->
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Sponsor Profile', 'event-management-system' ); ?></span></h2>
				<div class="inside">
					<p>
						<a href="<?php echo esc_url( get_edit_post_link( $eoi->sponsor_id ) ); ?>" class="button" style="width: 100%; text-align: center;">
							<?php esc_html_e( 'View Full Sponsor Profile', 'event-management-system' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>

	</div>
</div>

<style>
.ems-status-badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 500;
}
.ems-status-pending { background: #fff3cd; color: #856404; }
.ems-status-approved { background: #d4edda; color: #155724; }
.ems-status-rejected { background: #f8d7da; color: #721c24; }
.ems-status-withdrawn { background: #e2e3e5; color: #383d41; }
.ems-status-info_requested { background: #cce5ff; color: #004085; }
</style>

<script>
jQuery(document).ready(function($) {
	var eoiId = <?php echo intval( $eoi_id ); ?>;
	var nonce = ems_admin.nonce;
	var ajaxUrl = ems_admin.ajax_url;

	// Approve
	$('#ems-eoi-approve-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to approve this EOI?', 'event-management-system' ) ); ?>')) {
			return;
		}

		var levelId = $('#ems_approve_level').val() || 0;
		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Processing...', 'event-management-system' ) ); ?>');

		$.post(ajaxUrl, {
			action: 'ems_eoi_approve',
			nonce: nonce,
			eoi_id: eoiId,
			level_id: levelId
		}, function(response) {
			if (response.success) {
				window.location.href = window.location.href + '&updated=1';
			} else {
				alert(response.data.message || '<?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?>');
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Approve EOI', 'event-management-system' ) ); ?>');
			}
		}).fail(function() {
			alert('<?php echo esc_js( __( 'Request failed. Please try again.', 'event-management-system' ) ); ?>');
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Approve EOI', 'event-management-system' ) ); ?>');
		});
	});

	// Reject
	$('#ems-eoi-reject-btn').on('click', function() {
		var reason = $('#ems_reject_reason').val();
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to reject this EOI?', 'event-management-system' ) ); ?>')) {
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Processing...', 'event-management-system' ) ); ?>');

		$.post(ajaxUrl, {
			action: 'ems_eoi_reject',
			nonce: nonce,
			eoi_id: eoiId,
			reason: reason
		}, function(response) {
			if (response.success) {
				window.location.href = window.location.href + '&updated=1';
			} else {
				alert(response.data.message || '<?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?>');
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Reject EOI', 'event-management-system' ) ); ?>');
			}
		}).fail(function() {
			alert('<?php echo esc_js( __( 'Request failed. Please try again.', 'event-management-system' ) ); ?>');
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Reject EOI', 'event-management-system' ) ); ?>');
		});
	});

	// Request Info
	$('#ems-eoi-info-btn').on('click', function() {
		var message = $('#ems_info_message').val();
		if (!message) {
			alert('<?php echo esc_js( __( 'Please enter a message.', 'event-management-system' ) ); ?>');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'event-management-system' ) ); ?>');

		$.post(ajaxUrl, {
			action: 'ems_eoi_request_info',
			nonce: nonce,
			eoi_id: eoiId,
			message: message
		}, function(response) {
			if (response.success) {
				window.location.href = window.location.href + '&updated=1';
			} else {
				alert(response.data.message || '<?php echo esc_js( __( 'An error occurred.', 'event-management-system' ) ); ?>');
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Request Information', 'event-management-system' ) ); ?>');
			}
		}).fail(function() {
			alert('<?php echo esc_js( __( 'Request failed. Please try again.', 'event-management-system' ) ); ?>');
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Request Information', 'event-management-system' ) ); ?>');
		});
	});
});
</script>
