<?php
/**
 * Session Custom Post Type
 *
 * Registers and manages the Session custom post type with meta boxes,
 * admin columns, and quick edit functionality.
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
 * Session CPT Class
 *
 * @since 1.0.0
 */
class EMS_CPT_Session {

	/**
	 * Post type key
	 */
	const POST_TYPE = 'ems_session';

	/**
	 * Logger instance
	 *
	 * @var EMS_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->logger = EMS_Logger::instance();
		
		// Add meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );
		
		// Admin columns
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * Register post type
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Sessions', 'Post Type General Name', 'event-management-system' ),
			'singular_name'         => _x( 'Session', 'Post Type Singular Name', 'event-management-system' ),
			'menu_name'             => __( 'Sessions', 'event-management-system' ),
			'name_admin_bar'        => __( 'Session', 'event-management-system' ),
			'archives'              => __( 'Session Archives', 'event-management-system' ),
			'attributes'            => __( 'Session Attributes', 'event-management-system' ),
			'parent_item_colon'     => __( 'Parent Session:', 'event-management-system' ),
			'all_items'             => __( 'All Sessions', 'event-management-system' ),
			'add_new_item'          => __( 'Add New Session', 'event-management-system' ),
			'add_new'               => __( 'Add New', 'event-management-system' ),
			'new_item'              => __( 'New Session', 'event-management-system' ),
			'edit_item'             => __( 'Edit Session', 'event-management-system' ),
			'update_item'           => __( 'Update Session', 'event-management-system' ),
			'view_item'             => __( 'View Session', 'event-management-system' ),
			'view_items'            => __( 'View Sessions', 'event-management-system' ),
			'search_items'          => __( 'Search Session', 'event-management-system' ),
			'not_found'             => __( 'Not found', 'event-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'event-management-system' ),
			'featured_image'        => __( 'Featured Image', 'event-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'event-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'event-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'event-management-system' ),
			'insert_into_item'      => __( 'Insert into session', 'event-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this session', 'event-management-system' ),
			'items_list'            => __( 'Sessions list', 'event-management-system' ),
			'items_list_navigation' => __( 'Sessions list navigation', 'event-management-system' ),
			'filter_items_list'     => __( 'Filter sessions list', 'event-management-system' ),
		);

		$args = array(
			'label'               => __( 'Session', 'event-management-system' ),
			'description'         => __( 'Event sessions and schedule items', 'event-management-system' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // Shown under Events menu
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-calendar',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => array( 'ems_session', 'ems_sessions' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		);

		register_post_type( self::POST_TYPE, $args );
		$this->logger->debug( 'Session post type registered', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 1.2.0
	 */
	public function add_meta_boxes() {
		// Session Details
		add_meta_box(
			'ems_session_details',
			__( 'Session Details', 'event-management-system' ),
			array( $this, 'render_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// Session Timing
		add_meta_box(
			'ems_session_timing',
			__( 'Date & Time', 'event-management-system' ),
			array( $this, 'render_timing_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);

		// Registration Info
		add_meta_box(
			'ems_session_registration',
			__( 'Registration Info', 'event-management-system' ),
			array( $this, 'render_registration_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render session details meta box
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Post object.
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( 'ems_session_meta', 'ems_session_nonce' );

		$event_id        = get_post_meta( $post->ID, 'event_id', true );
		$session_type    = get_post_meta( $post->ID, 'session_type', true );
		$location        = get_post_meta( $post->ID, 'location', true );
		$track           = get_post_meta( $post->ID, 'track', true );
		$abstract_id     = get_post_meta( $post->ID, 'linked_abstract_id', true );
		$presenters      = get_post_meta( $post->ID, 'presenters', true );

		// Get events for dropdown
		$events = get_posts( array(
			'post_type'      => 'ems_event',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		// Session types
		$schedule_builder = new EMS_Schedule_Builder();
		$session_types    = $schedule_builder->get_session_types();
		?>
		<table class="form-table">
			<tr>
				<th><label for="event_id"><?php esc_html_e( 'Event', 'event-management-system' ); ?> <span class="required">*</span></label></th>
				<td>
					<select name="event_id" id="event_id" class="widefat" required>
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
				<th><label for="session_type"><?php esc_html_e( 'Session Type', 'event-management-system' ); ?> <span class="required">*</span></label></th>
				<td>
					<select name="session_type" id="session_type" class="widefat" required>
						<option value=""><?php esc_html_e( 'Select Type', 'event-management-system' ); ?></option>
						<?php foreach ( $session_types as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $session_type, $type ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="location"><?php esc_html_e( 'Location/Room', 'event-management-system' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" name="location" id="location" value="<?php echo esc_attr( $location ); ?>" class="widefat" required>
					<p class="description"><?php esc_html_e( 'Room name or location identifier', 'event-management-system' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="track"><?php esc_html_e( 'Track/Stream', 'event-management-system' ); ?></label></th>
				<td>
					<input type="text" name="track" id="track" value="<?php echo esc_attr( $track ); ?>" class="widefat">
					<p class="description"><?php esc_html_e( 'Optional track or stream name', 'event-management-system' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="linked_abstract_id"><?php esc_html_e( 'Linked Abstract', 'event-management-system' ); ?></label></th>
				<td>
					<input type="number" name="linked_abstract_id" id="linked_abstract_id" value="<?php echo esc_attr( $abstract_id ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'Link to an approved abstract (optional)', 'event-management-system' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render timing meta box
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Post object.
	 */
	public function render_timing_meta_box( $post ) {
		$start_datetime = get_post_meta( $post->ID, 'start_datetime', true );
		$end_datetime   = get_post_meta( $post->ID, 'end_datetime', true );
		?>
		<p>
			<label for="start_datetime"><strong><?php esc_html_e( 'Start Date/Time', 'event-management-system' ); ?> <span class="required">*</span></strong></label><br>
			<input type="datetime-local" name="start_datetime" id="start_datetime" value="<?php echo esc_attr( $start_datetime ? date( 'Y-m-d\TH:i', strtotime( $start_datetime ) ) : '' ); ?>" class="widefat" required>
		</p>
		<p>
			<label for="end_datetime"><strong><?php esc_html_e( 'End Date/Time', 'event-management-system' ); ?> <span class="required">*</span></strong></label><br>
			<input type="datetime-local" name="end_datetime" id="end_datetime" value="<?php echo esc_attr( $end_datetime ? date( 'Y-m-d\TH:i', strtotime( $end_datetime ) ) : '' ); ?>" class="widefat" required>
		</p>
		<style>
			.required { color: red; }
		</style>
		<?php
	}

	/**
	 * Render registration meta box
	 *
	 * @since 1.2.0
	 * @param WP_Post $post Post object.
	 */
	public function render_registration_meta_box( $post ) {
		$capacity              = get_post_meta( $post->ID, 'capacity', true );
		$current_registrations = get_post_meta( $post->ID, 'current_registrations', true );
		?>
		<p>
			<label for="capacity"><strong><?php esc_html_e( 'Capacity', 'event-management-system' ); ?></strong></label><br>
			<input type="number" name="capacity" id="capacity" value="<?php echo esc_attr( $capacity ); ?>" min="0" class="small-text">
			<p class="description"><?php esc_html_e( '0 = unlimited', 'event-management-system' ); ?></p>
		</p>
		<?php if ( $post->ID ) : ?>
			<p>
				<strong><?php esc_html_e( 'Current Registrations:', 'event-management-system' ); ?></strong><br>
				<span style="font-size: 18px;"><?php echo absint( $current_registrations ); ?></span>
				<?php if ( $capacity > 0 ) : ?>
					/ <?php echo absint( $capacity ); ?>
				<?php endif; ?>
			</p>
			<?php if ( $capacity > 0 ) : ?>
				<p>
					<strong><?php esc_html_e( 'Remaining:', 'event-management-system' ); ?></strong><br>
					<span style="font-size: 18px;"><?php echo max( 0, absint( $capacity ) - absint( $current_registrations ) ); ?></span>
				</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @since 1.2.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['ems_session_nonce'] ) || ! wp_verify_nonce( $_POST['ems_session_nonce'], 'ems_session_meta' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_ems_session', $post_id ) ) {
			return;
		}

		// Save meta fields
		$meta_fields = array( 'event_id', 'session_type', 'location', 'track', 'linked_abstract_id', 'capacity' );
		
		foreach ( $meta_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}

		// Save datetime fields
		if ( isset( $_POST['start_datetime'] ) ) {
			$start = sanitize_text_field( $_POST['start_datetime'] );
			update_post_meta( $post_id, 'start_datetime', date( 'Y-m-d H:i:s', strtotime( $start ) ) );
		}

		if ( isset( $_POST['end_datetime'] ) ) {
			$end = sanitize_text_field( $_POST['end_datetime'] );
			update_post_meta( $post_id, 'end_datetime', date( 'Y-m-d H:i:s', strtotime( $end ) ) );
		}

		// Initialize registration count if new
		if ( get_post_status( $post_id ) === 'publish' && ! get_post_meta( $post_id, 'current_registrations', true ) ) {
			update_post_meta( $post_id, 'current_registrations', 0 );
		}
	}

	/**
	 * Set admin columns
	 *
	 * @since 1.2.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function admin_columns( $columns ) {
		$new_columns = array();
		
		$new_columns['cb']            = $columns['cb'];
		$new_columns['title']         = __( 'Session Title', 'event-management-system' );
		$new_columns['event']         = __( 'Event', 'event-management-system' );
		$new_columns['session_type']  = __( 'Type', 'event-management-system' );
		$new_columns['datetime']      = __( 'Date & Time', 'event-management-system' );
		$new_columns['location']      = __( 'Location', 'event-management-system' );
		$new_columns['registrations'] = __( 'Registrations', 'event-management-system' );
		$new_columns['date']          = $columns['date'];

		return $new_columns;
	}

	/**
	 * Populate admin column content
	 *
	 * @since 1.2.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'event':
				$event_id = get_post_meta( $post_id, 'event_id', true );
				if ( $event_id ) {
					$event = get_post( $event_id );
					if ( $event ) {
						echo '<a href="' . esc_url( get_edit_post_link( $event_id ) ) . '">' . esc_html( $event->post_title ) . '</a>';
					}
				}
				break;

			case 'session_type':
				$type = get_post_meta( $post_id, 'session_type', true );
				if ( $type ) {
					$schedule_builder = new EMS_Schedule_Builder();
					$types            = $schedule_builder->get_session_types();
					echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : ucfirst( $type ) );
				}
				break;

			case 'datetime':
				$start = get_post_meta( $post_id, 'start_datetime', true );
				$end   = get_post_meta( $post_id, 'end_datetime', true );
				if ( $start && $end ) {
					echo esc_html( EMS_Date_Helper::format( $start, 'M j, Y' ) ) . '<br>';
					echo '<small>' . esc_html( EMS_Date_Helper::format( $start, 'g:i A' ) ) . ' - ' . esc_html( EMS_Date_Helper::format( $end, 'g:i A' ) ) . '</small>';
				}
				break;

			case 'location':
				$location = get_post_meta( $post_id, 'location', true );
				echo esc_html( $location );
				break;

			case 'registrations':
				$capacity   = get_post_meta( $post_id, 'capacity', true );
				$registered = get_post_meta( $post_id, 'current_registrations', true );
				
				if ( $capacity > 0 ) {
					$percentage = ( $registered / $capacity ) * 100;
					$color      = $percentage >= 90 ? '#d63638' : ( $percentage >= 75 ? '#dba617' : '#00a32a' );
					
					echo '<strong style="color: ' . esc_attr( $color ) . ';">' . absint( $registered ) . ' / ' . absint( $capacity ) . '</strong>';
				} else {
					echo '<strong>' . absint( $registered ) . '</strong> <small>(unlimited)</small>';
				}
				break;
		}
	}

	/**
	 * Set sortable columns
	 *
	 * @since 1.2.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['datetime'] = 'start_datetime';
		return $columns;
	}
}
