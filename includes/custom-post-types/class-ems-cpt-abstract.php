<?php
/**
 * Abstract Custom Post Type
 *
 * Registers and manages the Abstract custom post type for presenter submissions.
 *
 * @package    EventManagementSystem
 * @subpackage CustomPostTypes
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract CPT class.
 *
 * @since 1.0.0
 */
class EMS_CPT_Abstract {

	/**
	 * Post type slug
	 */
	const POST_TYPE = 'ems_abstract';

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
	}

	/**
	 * Register the Abstract post type
	 */
	public function register_post_type() {
		$labels = array(
			'name'          => _x( 'Abstracts', 'Post Type General Name', 'event-management-system' ),
			'singular_name' => _x( 'Abstract', 'Post Type Singular Name', 'event-management-system' ),
			'menu_name'     => __( 'Abstracts', 'event-management-system' ),
			'all_items'     => __( 'All Abstracts', 'event-management-system' ),
			'add_new_item'  => __( 'Add New Abstract', 'event-management-system' ),
			'edit_item'     => __( 'Edit Abstract', 'event-management-system' ),
			'view_item'     => __( 'View Abstract', 'event-management-system' ),
		);

		$args = array(
			'label'               => __( 'Abstract', 'event-management-system' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'author', 'custom-fields', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => array( 'ems_abstract', 'ems_abstracts' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		);

		register_post_type( self::POST_TYPE, $args );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		$this->logger->debug( 'Abstract post type registered', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ems_abstract_details',
			__( 'Abstract Details', 'event-management-system' ),
			array( $this, 'render_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ems_presenter_details',
			__( 'Presenter Details', 'event-management-system' ),
			array( $this, 'render_presenter_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ems_abstract_review',
			__( 'Review Status', 'event-management-system' ),
			array( $this, 'render_review_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render details meta box
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'ems_abstract_details', 'ems_abstract_nonce' );

		$event_id      = get_post_meta( $post->ID, 'abstract_event_id', true );
		$type          = get_post_meta( $post->ID, 'abstract_type', true );
		$keywords      = get_post_meta( $post->ID, 'keywords', true );
		$objectives    = get_post_meta( $post->ID, 'learning_objectives', true );
		$audience      = get_post_meta( $post->ID, 'target_audience', true );
		$presenter_bio = get_post_meta( $post->ID, 'presenter_bio', true );

		// Get events for dropdown
		$events = get_posts( array(
			'post_type'      => 'ems_event',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		?>
		<table class="form-table">
			<tr>
				<th><label for="abstract_event_id"><?php esc_html_e( 'Event', 'event-management-system' ); ?></label></th>
				<td>
					<select name="abstract_event_id" id="abstract_event_id" class="regular-text" required>
						<option value=""><?php esc_html_e( 'Select Event', 'event-management-system' ); ?></option>
						<?php foreach ( $events as $event ) : ?>
							<option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event_id, $event->ID ); ?>>
								<?php echo esc_html( $event->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="abstract_type"><?php esc_html_e( 'Type', 'event-management-system' ); ?></label></th>
				<td>
					<select name="abstract_type" id="abstract_type" class="regular-text">
						<option value="oral" <?php selected( $type, 'oral' ); ?>><?php esc_html_e( 'Oral Presentation', 'event-management-system' ); ?></option>
						<option value="poster" <?php selected( $type, 'poster' ); ?>><?php esc_html_e( 'Poster', 'event-management-system' ); ?></option>
						<option value="workshop" <?php selected( $type, 'workshop' ); ?>><?php esc_html_e( 'Workshop', 'event-management-system' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="keywords"><?php esc_html_e( 'Keywords', 'event-management-system' ); ?></label></th>
				<td>
					<input type="text" name="keywords" id="keywords" value="<?php echo esc_attr( $keywords ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Comma-separated keywords', 'event-management-system' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="learning_objectives"><?php esc_html_e( 'Learning Objectives', 'event-management-system' ); ?></label></th>
				<td>
					<textarea name="learning_objectives" id="learning_objectives" rows="5" class="large-text"><?php echo esc_textarea( $objectives ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="target_audience"><?php esc_html_e( 'Target Audience', 'event-management-system' ); ?></label></th>
				<td>
					<input type="text" name="target_audience" id="target_audience" value="<?php echo esc_attr( $audience ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="presenter_bio"><?php esc_html_e( 'Presenter Bio', 'event-management-system' ); ?></label></th>
				<td>
					<textarea name="presenter_bio" id="presenter_bio" rows="5" class="large-text"><?php echo esc_textarea( $presenter_bio ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render presenter details meta box
	 *
	 * Allows admin to select existing user or create new presenter.
	 *
	 * @since 1.2.0
	 * @param WP_Post $post The post object.
	 */
	public function render_presenter_meta_box( $post ) {
		$author_id = $post->post_author;
		$author = get_user_by( 'ID', $author_id );
		
		// Get stored presenter details (for new presenter option)
		$presenter_email = get_post_meta( $post->ID, '_presenter_email', true );
		$presenter_first_name = get_post_meta( $post->ID, '_presenter_first_name', true );
		$presenter_last_name = get_post_meta( $post->ID, '_presenter_last_name', true );
		
		// Get list of potential presenters (users with presenter capability or any user)
		$users = get_users( array(
			'orderby' => 'display_name',
			'order'   => 'ASC',
		) );
		?>
		<table class="form-table">
			<tr>
				<th><label for="presenter_selection"><?php esc_html_e( 'Presenter', 'event-management-system' ); ?></label></th>
				<td>
					<select name="presenter_selection" id="presenter_selection" class="regular-text" style="min-width: 250px;">
						<option value="existing"><?php esc_html_e( 'Select existing user', 'event-management-system' ); ?></option>
						<option value="new" <?php selected( ! empty( $presenter_email ) && ! $author ); ?>><?php esc_html_e( 'Create new presenter', 'event-management-system' ); ?></option>
					</select>
				</td>
			</tr>
			
			<!-- Existing User Selection -->
			<tr class="presenter-existing-row">
				<th><label for="presenter_user_id"><?php esc_html_e( 'Select User', 'event-management-system' ); ?></label></th>
				<td>
					<select name="presenter_user_id" id="presenter_user_id" class="regular-text" style="min-width: 250px;">
						<?php foreach ( $users as $user ) : ?>
							<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $author_id, $user->ID ); ?>>
								<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if ( $author ) : ?>
						<p class="description">
							<?php 
							printf( 
								esc_html__( 'Current presenter: %s (%s)', 'event-management-system' ),
								esc_html( $author->display_name ),
								esc_html( $author->user_email )
							); 
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			
			<!-- New Presenter Fields -->
			<tr class="presenter-new-row" style="display: none;">
				<th><label for="new_presenter_email"><?php esc_html_e( 'Email', 'event-management-system' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="email" name="new_presenter_email" id="new_presenter_email" value="<?php echo esc_attr( $presenter_email ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Required. A user account will be created if one does not exist.', 'event-management-system' ); ?></p>
				</td>
			</tr>
			<tr class="presenter-new-row" style="display: none;">
				<th><label for="new_presenter_first_name"><?php esc_html_e( 'First Name', 'event-management-system' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="new_presenter_first_name" id="new_presenter_first_name" value="<?php echo esc_attr( $presenter_first_name ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr class="presenter-new-row" style="display: none;">
				<th><label for="new_presenter_last_name"><?php esc_html_e( 'Last Name', 'event-management-system' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="new_presenter_last_name" id="new_presenter_last_name" value="<?php echo esc_attr( $presenter_last_name ); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
		
		<script>
		jQuery(document).ready(function($) {
			function togglePresenterFields() {
				var selection = $('#presenter_selection').val();
				if (selection === 'new') {
					$('.presenter-existing-row').hide();
					$('.presenter-new-row').show();
				} else {
					$('.presenter-existing-row').show();
					$('.presenter-new-row').hide();
				}
			}
			
			$('#presenter_selection').on('change', togglePresenterFields);
			togglePresenterFields(); // Initial state
		});
		</script>
		<?php
	}

	/**
	 * Render review status meta box
	 */
	public function render_review_meta_box( $post ) {
		$status         = get_post_meta( $post->ID, 'review_status', true );
		$approved_count = get_post_meta( $post->ID, 'approved_count', true );
		$rejected_count = get_post_meta( $post->ID, 'rejected_count', true );
		?>
		<p>
			<strong><?php esc_html_e( 'Status:', 'event-management-system' ); ?></strong><br />
			<?php
			$status_labels = array(
				'pending'       => __( 'Pending', 'event-management-system' ),
				'under_review'  => __( 'Under Review', 'event-management-system' ),
				'approved'      => __( 'Approved', 'event-management-system' ),
				'rejected'      => __( 'Rejected', 'event-management-system' ),
			);
			echo esc_html( isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : __( 'Pending', 'event-management-system' ) );
			?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Reviews:', 'event-management-system' ); ?></strong><br />
			<?php echo esc_html( sprintf( __( 'Approved: %d', 'event-management-system' ), $approved_count ? $approved_count : 0 ) ); ?><br />
			<?php echo esc_html( sprintf( __( 'Rejected: %d', 'event-management-system' ), $rejected_count ? $rejected_count : 0 ) ); ?>
		</p>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * Handles saving abstract details and presenter assignment.
	 * If a new presenter is specified, creates user account automatically.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['ems_abstract_nonce'] ) || ! wp_verify_nonce( $_POST['ems_abstract_nonce'], 'ems_abstract_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save standard abstract fields
		$fields = array(
			'abstract_event_id'     => 'absint',
			'abstract_type'         => 'sanitize_text_field',
			'keywords'              => 'sanitize_text_field',
			'learning_objectives'   => 'sanitize_textarea_field',
			'target_audience'       => 'sanitize_text_field',
			'presenter_bio'         => 'sanitize_textarea_field',
		);

		foreach ( $fields as $field => $sanitize ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, call_user_func( $sanitize, $_POST[ $field ] ) );
			}
		}

		// Handle presenter assignment
		$presenter_selection = isset( $_POST['presenter_selection'] ) ? sanitize_text_field( $_POST['presenter_selection'] ) : 'existing';
		$new_author_id = null;

		if ( 'new' === $presenter_selection ) {
			// Creating new presenter
			$presenter_email = isset( $_POST['new_presenter_email'] ) ? sanitize_email( $_POST['new_presenter_email'] ) : '';
			$presenter_first = isset( $_POST['new_presenter_first_name'] ) ? sanitize_text_field( $_POST['new_presenter_first_name'] ) : '';
			$presenter_last = isset( $_POST['new_presenter_last_name'] ) ? sanitize_text_field( $_POST['new_presenter_last_name'] ) : '';

			// Store for reference (in case user needs to edit later)
			update_post_meta( $post_id, '_presenter_email', $presenter_email );
			update_post_meta( $post_id, '_presenter_first_name', $presenter_first );
			update_post_meta( $post_id, '_presenter_last_name', $presenter_last );

			if ( ! empty( $presenter_email ) && ! empty( $presenter_first ) && ! empty( $presenter_last ) ) {
				// Use User Helper to create or get presenter
				$user_helper = new EMS_User_Helper();
				$user_result = $user_helper->create_or_get_presenter(
					array(
						'email'      => $presenter_email,
						'first_name' => $presenter_first,
						'last_name'  => $presenter_last,
						'bio'        => get_post_meta( $post_id, 'presenter_bio', true ),
					),
					true // Send notification for new users
				);

				if ( $user_result['success'] ) {
					$new_author_id = $user_result['user_id'];
					$this->logger->info(
						sprintf( 
							'Presenter user %d %s for abstract %d from admin',
							$user_result['user_id'],
							$user_result['created'] ? 'created' : 'found',
							$post_id
						),
						EMS_Logger::CONTEXT_ABSTRACT
					);

					// Clear the temp fields since user was created
					delete_post_meta( $post_id, '_presenter_email' );
					delete_post_meta( $post_id, '_presenter_first_name' );
					delete_post_meta( $post_id, '_presenter_last_name' );
				} else {
					$this->logger->error(
						'Failed to create presenter from admin: ' . $user_result['message'],
						EMS_Logger::CONTEXT_ABSTRACT
					);
				}
			}
		} else {
			// Using existing user
			$presenter_user_id = isset( $_POST['presenter_user_id'] ) ? absint( $_POST['presenter_user_id'] ) : 0;
			if ( $presenter_user_id > 0 ) {
				$new_author_id = $presenter_user_id;
				
				// Ensure the user has presenter role
				$user = get_user_by( 'ID', $presenter_user_id );
				if ( $user && ! in_array( 'ems_presenter', $user->roles, true ) ) {
					// Add presenter role if they don't have it (unless they're admin)
					if ( ! in_array( 'administrator', $user->roles, true ) ) {
						if ( in_array( 'ems_participant', $user->roles, true ) ) {
							$user->remove_role( 'ems_participant' );
						}
						$user->add_role( 'ems_presenter' );
						$this->logger->info(
							sprintf( 'Added presenter role to user %d for abstract %d', $presenter_user_id, $post_id ),
							EMS_Logger::CONTEXT_ABSTRACT
						);
					}
				}
			}

			// Clear any stored new presenter fields
			delete_post_meta( $post_id, '_presenter_email' );
			delete_post_meta( $post_id, '_presenter_first_name' );
			delete_post_meta( $post_id, '_presenter_last_name' );
		}

		// Update post author if changed
		if ( $new_author_id && $new_author_id !== $post->post_author ) {
			// Use wp_update_post but remove this hook temporarily to prevent infinite loop
			remove_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10 );
			wp_update_post( array(
				'ID'          => $post_id,
				'post_author' => $new_author_id,
			) );
			add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

			// Store primary author meta
			update_post_meta( $post_id, 'primary_author_id', $new_author_id );

			$this->logger->info(
				sprintf( 'Updated abstract %d author to user %d', $post_id, $new_author_id ),
				EMS_Logger::CONTEXT_ABSTRACT
			);
		}

		$this->logger->info(
			"Abstract meta saved: {$post_id}",
			EMS_Logger::CONTEXT_ABSTRACT,
			array( 'post_id' => $post_id )
		);
	}
}
