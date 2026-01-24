<?php
/**
 * Event Custom Post Type
 *
 * Registers and manages the Event custom post type.
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
 * Event CPT class.
 *
 * @since 1.0.0
 */
class EMS_CPT_Event {

	/**
	 * Post type slug
	 *
	 * @var string
	 */
	const POST_TYPE = 'ems_event';

	/**
	 * Category taxonomy slug
	 *
	 * @var string
	 */
	const TAXONOMY_CATEGORY = 'ems_event_category';

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
	}

	/**
	 * Register the Event post type
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Events', 'Post Type General Name', 'event-management-system' ),
			'singular_name'         => _x( 'Event', 'Post Type Singular Name', 'event-management-system' ),
			'menu_name'             => __( 'Events', 'event-management-system' ),
			'name_admin_bar'        => __( 'Event', 'event-management-system' ),
			'archives'              => __( 'Event Archives', 'event-management-system' ),
			'attributes'            => __( 'Event Attributes', 'event-management-system' ),
			'parent_item_colon'     => __( 'Parent Event:', 'event-management-system' ),
			'all_items'             => __( 'All Events', 'event-management-system' ),
			'add_new_item'          => __( 'Add New Event', 'event-management-system' ),
			'add_new'               => __( 'Add New', 'event-management-system' ),
			'new_item'              => __( 'New Event', 'event-management-system' ),
			'edit_item'             => __( 'Edit Event', 'event-management-system' ),
			'update_item'           => __( 'Update Event', 'event-management-system' ),
			'view_item'             => __( 'View Event', 'event-management-system' ),
			'view_items'            => __( 'View Events', 'event-management-system' ),
			'search_items'          => __( 'Search Event', 'event-management-system' ),
			'not_found'             => __( 'Not found', 'event-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'event-management-system' ),
			'featured_image'        => __( 'Event Image', 'event-management-system' ),
			'set_featured_image'    => __( 'Set event image', 'event-management-system' ),
			'remove_featured_image' => __( 'Remove event image', 'event-management-system' ),
			'use_featured_image'    => __( 'Use as event image', 'event-management-system' ),
			'insert_into_item'      => __( 'Insert into event', 'event-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this event', 'event-management-system' ),
			'items_list'            => __( 'Events list', 'event-management-system' ),
			'items_list_navigation' => __( 'Events list navigation', 'event-management-system' ),
			'filter_items_list'     => __( 'Filter events list', 'event-management-system' ),
		);

		$args = array(
			'label'               => __( 'Event', 'event-management-system' ),
			'description'         => __( 'Education events including conferences, courses, and seminars', 'event-management-system' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			'taxonomies'          => array( self::TAXONOMY_CATEGORY ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // We'll add under custom menu
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-calendar-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'rewrite'             => array( 'slug' => 'events' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => array( 'ems_event', 'ems_events' ),
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
		);

		register_post_type( self::POST_TYPE, $args );

		// Register meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		$this->logger->debug( 'Event post type registered', EMS_Logger::CONTEXT_GENERAL );
	}

	/**
	 * Register taxonomies
	 *
	 * @since 1.0.0
	 */
	public function register_taxonomies() {
		// Event Category taxonomy
		$labels = array(
			'name'                       => _x( 'Event Categories', 'Taxonomy General Name', 'event-management-system' ),
			'singular_name'              => _x( 'Event Category', 'Taxonomy Singular Name', 'event-management-system' ),
			'menu_name'                  => __( 'Categories', 'event-management-system' ),
			'all_items'                  => __( 'All Categories', 'event-management-system' ),
			'parent_item'                => __( 'Parent Category', 'event-management-system' ),
			'parent_item_colon'          => __( 'Parent Category:', 'event-management-system' ),
			'new_item_name'              => __( 'New Category Name', 'event-management-system' ),
			'add_new_item'               => __( 'Add New Category', 'event-management-system' ),
			'edit_item'                  => __( 'Edit Category', 'event-management-system' ),
			'update_item'                => __( 'Update Category', 'event-management-system' ),
			'view_item'                  => __( 'View Category', 'event-management-system' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'event-management-system' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'event-management-system' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'event-management-system' ),
			'popular_items'              => __( 'Popular Categories', 'event-management-system' ),
			'search_items'               => __( 'Search Categories', 'event-management-system' ),
			'not_found'                  => __( 'Not Found', 'event-management-system' ),
			'no_terms'                   => __( 'No categories', 'event-management-system' ),
			'items_list'                 => __( 'Categories list', 'event-management-system' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'event-management-system' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'rewrite'           => array( 'slug' => 'event-category' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( self::TAXONOMY_CATEGORY, array( self::POST_TYPE ), $args );
	}

	/**
	 * Add meta boxes for event details
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ems_event_details',
			__( 'Event Details', 'event-management-system' ),
			array( $this, 'render_event_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ems_event_registration',
			__( 'Registration Settings', 'event-management-system' ),
			array( $this, 'render_registration_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'ems_event_abstracts',
			__( 'Abstract Submission Settings', 'event-management-system' ),
			array( $this, 'render_abstracts_meta_box' ),
			self::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'ems_event_capacity',
			__( 'Capacity & Schedule', 'event-management-system' ),
			array( $this, 'render_capacity_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render event details meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object
	 */
	public function render_event_details_meta_box( $post ) {
		wp_nonce_field( 'ems_event_details_nonce', 'ems_event_details_nonce' );

		$event_type       = get_post_meta( $post->ID, 'event_type', true );
		$start_date       = get_post_meta( $post->ID, 'event_start_date', true );
		$end_date         = get_post_meta( $post->ID, 'event_end_date', true );
		$location         = get_post_meta( $post->ID, 'event_location', true );
		$venue_details    = get_post_meta( $post->ID, 'event_venue_details', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="event_type"><?php esc_html_e( 'Event Type', 'event-management-system' ); ?></label></th>
				<td>
					<select name="event_type" id="event_type" class="regular-text">
						<option value="conference" <?php selected( $event_type, 'conference' ); ?>><?php esc_html_e( 'Conference', 'event-management-system' ); ?></option>
						<option value="course" <?php selected( $event_type, 'course' ); ?>><?php esc_html_e( 'Course', 'event-management-system' ); ?></option>
						<option value="seminar" <?php selected( $event_type, 'seminar' ); ?>><?php esc_html_e( 'Seminar', 'event-management-system' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="event_start_date"><?php esc_html_e( 'Start Date & Time', 'event-management-system' ); ?></label></th>
				<td>
					<input type="datetime-local" name="event_start_date" id="event_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="event_end_date"><?php esc_html_e( 'End Date & Time', 'event-management-system' ); ?></label></th>
				<td>
					<input type="datetime-local" name="event_end_date" id="event_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="event_location"><?php esc_html_e( 'Location', 'event-management-system' ); ?></label></th>
				<td>
					<input type="text" name="event_location" id="event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Brisbane Convention Centre', 'event-management-system' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="event_venue_details"><?php esc_html_e( 'Venue Details', 'event-management-system' ); ?></label></th>
				<td>
					<textarea name="event_venue_details" id="event_venue_details" rows="5" class="large-text"><?php echo esc_textarea( $venue_details ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Address, parking information, accessibility details, etc.', 'event-management-system' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render registration settings meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object
	 */
	public function render_registration_meta_box( $post ) {
		$reg_open_date  = get_post_meta( $post->ID, 'registration_open_date', true );
		$reg_close_date = get_post_meta( $post->ID, 'registration_close_date', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="registration_open_date"><?php esc_html_e( 'Registration Opens', 'event-management-system' ); ?></label></th>
				<td>
					<input type="datetime-local" name="registration_open_date" id="registration_open_date" value="<?php echo esc_attr( $reg_open_date ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="registration_close_date"><?php esc_html_e( 'Registration Closes', 'event-management-system' ); ?></label></th>
				<td>
					<input type="datetime-local" name="registration_close_date" id="registration_close_date" value="<?php echo esc_attr( $reg_close_date ); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render abstract submission settings meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object
	 */
	public function render_abstracts_meta_box( $post ) {
		$abstract_open    = get_post_meta( $post->ID, 'abstract_submission_open', true );
		$abstract_close   = get_post_meta( $post->ID, 'abstract_submission_close', true );
		$allowed_types    = get_post_meta( $post->ID, 'abstract_types_allowed', true );
		if ( ! is_array( $allowed_types ) ) {
			$allowed_types = array( 'oral', 'poster', 'workshop' );
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="abstract_submission_open"><?php esc_html_e( 'Submissions Open', 'event-management-system' ); ?></label></th>
				<td>
					<input type="datetime-local" name="abstract_submission_open" id="abstract_submission_open" value="<?php echo esc_attr( $abstract_open ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="abstract_submission_close"><?php esc_html_e( 'Submissions Close', 'event-management-system' ); ?></label></th>
				<td>
					<input type="datetime-local" name="abstract_submission_close" id="abstract_submission_close" value="<?php echo esc_attr( $abstract_close ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Allowed Types', 'event-management-system' ); ?></th>
				<td>
					<label><input type="checkbox" name="abstract_types_allowed[]" value="oral" <?php checked( in_array( 'oral', $allowed_types ) ); ?> /> <?php esc_html_e( 'Oral Presentations', 'event-management-system' ); ?></label><br />
					<label><input type="checkbox" name="abstract_types_allowed[]" value="poster" <?php checked( in_array( 'poster', $allowed_types ) ); ?> /> <?php esc_html_e( 'Poster Presentations', 'event-management-system' ); ?></label><br />
					<label><input type="checkbox" name="abstract_types_allowed[]" value="workshop" <?php checked( in_array( 'workshop', $allowed_types ) ); ?> /> <?php esc_html_e( 'Workshops', 'event-management-system' ); ?></label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render capacity and schedule meta box
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object
	 */
	public function render_capacity_meta_box( $post ) {
		$max_capacity          = get_post_meta( $post->ID, 'max_capacity', true );
		$current_registrations = get_post_meta( $post->ID, 'current_registrations', true );
		$schedule_published    = get_post_meta( $post->ID, 'schedule_published', true );
		$concurrent_sessions   = get_post_meta( $post->ID, 'has_concurrent_sessions', true );
		$track_count           = get_post_meta( $post->ID, 'track_count', true );
		?>
		<p>
			<label for="max_capacity"><strong><?php esc_html_e( 'Max Capacity', 'event-management-system' ); ?></strong></label><br />
			<input type="number" name="max_capacity" id="max_capacity" value="<?php echo esc_attr( $max_capacity ); ?>" class="widefat" min="0" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Current Registrations', 'event-management-system' ); ?></strong><br />
			<?php echo esc_html( $current_registrations ? $current_registrations : '0' ); ?>
		</p>
		<p>
			<label><input type="checkbox" name="has_concurrent_sessions" value="1" <?php checked( $concurrent_sessions, 1 ); ?> /> <?php esc_html_e( 'Concurrent Sessions', 'event-management-system' ); ?></label>
		</p>
		<p id="track_count_field" style="<?php echo $concurrent_sessions ? '' : 'display:none;'; ?>">
			<label for="track_count"><strong><?php esc_html_e( 'Number of Tracks', 'event-management-system' ); ?></strong></label><br />
			<input type="number" name="track_count" id="track_count" value="<?php echo esc_attr( $track_count ? $track_count : 2 ); ?>" class="widefat" min="1" max="10" />
		</p>
		<p>
			<label><input type="checkbox" name="schedule_published" value="1" <?php checked( $schedule_published, 1 ); ?> /> <?php esc_html_e( 'Schedule Published', 'event-management-system' ); ?></label>
		</p>
		<script>
		jQuery(document).ready(function($) {
			$('input[name="has_concurrent_sessions"]').on('change', function() {
				if ($(this).is(':checked')) {
					$('#track_count_field').show();
				} else {
					$('#track_count_field').hide();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID
	 * @param WP_Post $post Post object
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Check nonce
		if ( ! isset( $_POST['ems_event_details_nonce'] ) || ! wp_verify_nonce( $_POST['ems_event_details_nonce'], 'ems_event_details_nonce' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$validator = new EMS_Validator();

		// Save event type
		if ( isset( $_POST['event_type'] ) ) {
			$event_type = sanitize_text_field( $_POST['event_type'] );
			update_post_meta( $post_id, 'event_type', $event_type );
		}

		// Save dates
		$date_fields = array(
			'event_start_date',
			'event_end_date',
			'registration_open_date',
			'registration_close_date',
			'abstract_submission_open',
			'abstract_submission_close',
		);

		foreach ( $date_fields as $field ) {
			if ( isset( $_POST[ $field ] ) && ! empty( $_POST[ $field ] ) ) {
				$date = sanitize_text_field( $_POST[ $field ] );
				// Convert to MySQL datetime format
				$mysql_date = str_replace( 'T', ' ', $date ) . ':00';
				update_post_meta( $post_id, $field, $mysql_date );
			}
		}

		// Save text fields
		if ( isset( $_POST['event_location'] ) ) {
			update_post_meta( $post_id, 'event_location', sanitize_text_field( $_POST['event_location'] ) );
		}

		if ( isset( $_POST['event_venue_details'] ) ) {
			update_post_meta( $post_id, 'event_venue_details', sanitize_textarea_field( $_POST['event_venue_details'] ) );
		}

		// Save capacity
		if ( isset( $_POST['max_capacity'] ) ) {
			update_post_meta( $post_id, 'max_capacity', absint( $_POST['max_capacity'] ) );
		}

		// Save abstract types
		if ( isset( $_POST['abstract_types_allowed'] ) && is_array( $_POST['abstract_types_allowed'] ) ) {
			$allowed_types = array_map( 'sanitize_text_field', $_POST['abstract_types_allowed'] );
			update_post_meta( $post_id, 'abstract_types_allowed', $allowed_types );
		} else {
			update_post_meta( $post_id, 'abstract_types_allowed', array() );
		}

		// Save concurrent sessions
		$concurrent_sessions = isset( $_POST['has_concurrent_sessions'] ) ? 1 : 0;
		update_post_meta( $post_id, 'has_concurrent_sessions', $concurrent_sessions );

		if ( $concurrent_sessions && isset( $_POST['track_count'] ) ) {
			update_post_meta( $post_id, 'track_count', absint( $_POST['track_count'] ) );
		}

		// Save schedule published status
		$schedule_published = isset( $_POST['schedule_published'] ) ? 1 : 0;
		update_post_meta( $post_id, 'schedule_published', $schedule_published );

		// Log the save
		$this->logger->info(
			"Event meta data saved for event ID: {$post_id}",
			EMS_Logger::CONTEXT_GENERAL,
			array( 'post_id' => $post_id )
		);
	}
}
