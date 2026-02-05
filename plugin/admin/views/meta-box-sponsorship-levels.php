<?php
/**
 * Sponsorship Levels Management UI
 *
 * Inline template included by the Event sponsorship meta box.
 * Provides add / edit / delete / populate-defaults for levels.
 *
 * Variables available from the including scope:
 *   $post   - WP_Post object (current event)
 *   $levels - array of level row objects from wp_ems_sponsorship_levels
 *
 * @package    EventManagementSystem
 * @subpackage Admin/Views
 * @since      1.5.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="ems-levels-manager">
	<h3><?php esc_html_e( 'Manage Sponsorship Levels', 'event-management-system' ); ?></h3>

	<?php if ( empty( $levels ) ) : ?>
		<div id="ems-populate-defaults-wrapper" style="margin-bottom: 15px;">
			<p class="description">
				<?php esc_html_e( 'No sponsorship levels exist for this event. You can populate the default Bronze / Silver / Gold levels.', 'event-management-system' ); ?>
			</p>
			<button type="button" id="ems-populate-defaults-btn" class="button button-secondary" data-event-id="<?php echo esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Populate Defaults (Bronze / Silver / Gold)', 'event-management-system' ); ?>
			</button>
		</div>
	<?php endif; ?>

	<table id="ems-levels-table" class="wp-list-table widefat fixed striped" <?php echo empty( $levels ) ? 'style="display:none;"' : ''; ?>>
		<thead>
			<tr>
				<th style="width: 5%;"><?php esc_html_e( 'Order', 'event-management-system' ); ?></th>
				<th style="width: 15%;"><?php esc_html_e( 'Name', 'event-management-system' ); ?></th>
				<th style="width: 8%;"><?php esc_html_e( 'Colour', 'event-management-system' ); ?></th>
				<th style="width: 10%;"><?php esc_html_e( 'Value (AUD)', 'event-management-system' ); ?></th>
				<th style="width: 8%;"><?php esc_html_e( 'Slots', 'event-management-system' ); ?></th>
				<th style="width: 8%;"><?php esc_html_e( 'Filled', 'event-management-system' ); ?></th>
				<th style="width: 25%;"><?php esc_html_e( 'Recognition', 'event-management-system' ); ?></th>
				<th style="width: 12%;"><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
			</tr>
		</thead>
		<tbody id="ems-levels-tbody">
			<?php if ( ! empty( $levels ) ) : ?>
				<?php foreach ( $levels as $level ) : ?>
					<tr class="ems-level-row" data-level-id="<?php echo esc_attr( $level->id ); ?>">
						<td>
							<input type="number"
								class="ems-level-sort-order small-text"
								value="<?php echo esc_attr( $level->sort_order ); ?>"
								min="0"
								style="width: 50px;" />
						</td>
						<td>
							<input type="text"
								class="ems-level-name"
								value="<?php echo esc_attr( $level->level_name ); ?>"
								style="width: 100%;" />
						</td>
						<td>
							<input type="color"
								class="ems-level-colour"
								value="<?php echo esc_attr( $level->colour ); ?>" />
						</td>
						<td>
							<input type="number"
								class="ems-level-value small-text"
								value="<?php echo esc_attr( $level->value_aud ); ?>"
								min="0"
								step="0.01"
								style="width: 90px;" />
						</td>
						<td>
							<input type="number"
								class="ems-level-slots small-text"
								value="<?php echo esc_attr( $level->slots_total ); ?>"
								min="0"
								style="width: 60px;" />
						</td>
						<td>
							<span class="ems-level-filled"><?php echo esc_html( $level->slots_filled ); ?></span>
						</td>
						<td>
							<textarea class="ems-level-recognition" rows="2" style="width: 100%;"><?php echo esc_textarea( $level->recognition_text ); ?></textarea>
						</td>
						<td>
							<button type="button"
								class="button button-small ems-save-level-btn"
								data-level-id="<?php echo esc_attr( $level->id ); ?>">
								<?php esc_html_e( 'Save', 'event-management-system' ); ?>
							</button>
							<button type="button"
								class="button button-small button-link-delete ems-delete-level-btn"
								data-level-id="<?php echo esc_attr( $level->id ); ?>"
								<?php echo intval( $level->slots_filled ) > 0 ? 'disabled title="' . esc_attr__( 'Cannot delete: sponsors linked', 'event-management-system' ) . '"' : ''; ?>>
								<?php esc_html_e( 'Delete', 'event-management-system' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Add New Level Form -->
	<div id="ems-add-level-form" style="margin-top: 15px; padding: 12px; background: #f9f9f9; border: 1px solid #ddd;">
		<h4 style="margin-top: 0;"><?php esc_html_e( 'Add New Level', 'event-management-system' ); ?></h4>
		<div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
			<div>
				<label><?php esc_html_e( 'Name', 'event-management-system' ); ?></label><br>
				<input type="text" id="ems-new-level-name" class="regular-text" style="width: 150px;" />
			</div>
			<div>
				<label><?php esc_html_e( 'Colour', 'event-management-system' ); ?></label><br>
				<input type="color" id="ems-new-level-colour" value="#CD7F32" />
			</div>
			<div>
				<label><?php esc_html_e( 'Value (AUD)', 'event-management-system' ); ?></label><br>
				<input type="number" id="ems-new-level-value" class="small-text" min="0" step="0.01" style="width: 90px;" />
			</div>
			<div>
				<label><?php esc_html_e( 'Total Slots', 'event-management-system' ); ?></label><br>
				<input type="number" id="ems-new-level-slots" class="small-text" min="0" style="width: 60px;" />
			</div>
			<div>
				<label><?php esc_html_e( 'Sort Order', 'event-management-system' ); ?></label><br>
				<input type="number" id="ems-new-level-sort" class="small-text" value="0" min="0" style="width: 50px;" />
			</div>
			<div style="flex-basis: 100%;">
				<label><?php esc_html_e( 'Recognition Text', 'event-management-system' ); ?></label><br>
				<textarea id="ems-new-level-recognition" rows="2" style="width: 100%;"></textarea>
			</div>
			<div>
				<button type="button" id="ems-add-level-btn" class="button button-primary" data-event-id="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Add Level', 'event-management-system' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
