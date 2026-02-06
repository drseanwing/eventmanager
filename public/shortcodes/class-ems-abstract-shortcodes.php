<?php
/**
 * Abstract Shortcodes
 *
 * Provides shortcodes for abstract submission form, presenter dashboard,
 * and abstract listings.
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/public/shortcodes
 * @since      1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EMS_Abstract_Shortcodes
 *
 * Registers and renders shortcodes for:
 * - [ems_abstract_submission] - Abstract submission form
 * - [ems_presenter_dashboard] - Presenter dashboard with submitted abstracts
 * - [ems_abstract_list] - Public abstract listing (approved abstracts)
 *
 * @since 1.0.0
 */
class EMS_Abstract_Shortcodes {

    /**
     * Logger instance
     *
     * @since 1.0.0
     * @var EMS_Logger
     */
    private $logger;

    /**
     * Abstract submission handler
     *
     * @since 1.0.0
     * @var EMS_Abstract_Submission
     */
    private $submission_handler;

    /**
     * Abstract review handler
     *
     * @since 1.0.0
     * @var EMS_Abstract_Review
     */
    private $review_handler;

    /**
     * Constructor
     *
     * Initialize dependencies and register shortcodes.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = EMS_Logger::instance();
        $this->submission_handler = new EMS_Abstract_Submission();
        $this->review_handler = new EMS_Abstract_Review();

        // Register shortcodes
        add_shortcode( 'ems_abstract_submission', array( $this, 'abstract_submission_shortcode' ) );
        add_shortcode( 'ems_presenter_dashboard', array( $this, 'presenter_dashboard_shortcode' ) );
        add_shortcode( 'ems_abstract_list', array( $this, 'abstract_list_shortcode' ) );

        // Handle form submissions
        add_action( 'init', array( $this, 'handle_abstract_submission' ) );
    }

    /**
     * Abstract submission form shortcode
     *
     * Renders a form for submitting abstracts to an event.
     * Event ID can be provided via shortcode attribute or URL parameter.
     *
     * Usage: [ems_abstract_submission event_id="123"]
     * Or via URL: /submit-abstract/?event_id=123
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Form HTML.
     */
    public function abstract_submission_shortcode( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( add_query_arg( array() ) ); // Return to current page after login
            return '<div class="ems-notice ems-notice-warning">' . 
                   sprintf(
                       __( 'Please <a href="%s">log in</a> to submit an abstract.', 'event-management-system' ),
                       esc_url( $login_url )
                   ) . 
                   '</div>';
        }

        // Parse attributes
        $atts = shortcode_atts( array(
            'event_id' => 0,
        ), $atts );

        $event_id = intval( $atts['event_id'] );

        // If no event_id in shortcode, check URL parameter
        if ( empty( $event_id ) && isset( $_GET['event_id'] ) ) {
            $event_id = intval( $_GET['event_id'] );
        }

        // Validate event
        if ( empty( $event_id ) ) {
            // Show event selection or message
            return $this->render_event_selection_for_abstract();
        }

        $event = get_post( $event_id );
        if ( ! $event || 'ems_event' !== $event->post_type ) {
            return '<div class="ems-notice ems-notice-error">' . 
                   __( 'Invalid event.', 'event-management-system' ) . 
                   '</div>';
        }

        // Check submission dates
        $date_check = $this->submission_handler->validate_submission_dates( $event_id );
        if ( ! $date_check['open'] ) {
            return '<div class="ems-notice ems-notice-info">' . 
                   esc_html( $date_check['message'] ) . 
                   '</div>';
        }

        // Check if user has already submitted
        $user_id = get_current_user_id();
        $user_abstracts = $this->submission_handler->get_user_abstracts( $user_id );
        
        $already_submitted = false;
        if ( $user_abstracts['success'] && ! empty( $user_abstracts['abstracts'] ) ) {
            foreach ( $user_abstracts['abstracts'] as $abstract ) {
                if ( $abstract['event_id'] == $event_id ) {
                    $already_submitted = true;
                    break;
                }
            }
        }

        ob_start();
        ?>
        <div class="ems-abstract-submission-form">
            <h2><?php printf( __( 'Submit Abstract: %s', 'event-management-system' ), esc_html( $event->post_title ) ); ?></h2>
            
            <?php if ( $already_submitted ) : ?>
                <div class="ems-notice ems-notice-info">
                    <?php _e( 'You have already submitted an abstract for this event. You can edit it from your presenter dashboard.', 'event-management-system' ); ?>
                </div>
            <?php else : ?>
                
                <form method="post" enctype="multipart/form-data" class="ems-form">
                    <?php wp_nonce_field( 'ems_submit_abstract', 'ems_abstract_nonce' ); ?>
                    <input type="hidden" name="action" value="ems_submit_abstract">
                    <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
                    
                    <div class="ems-form-group">
                        <label for="abstract_title"><?php _e( 'Title *', 'event-management-system' ); ?></label>
                        <input type="text" id="abstract_title" name="title" required maxlength="200" class="ems-form-control">
                    </div>

                    <div class="ems-form-group">
                        <label for="abstract_type"><?php _e( 'Presentation Type *', 'event-management-system' ); ?></label>
                        <select id="abstract_type" name="abstract_type" required class="ems-form-control">
                            <option value=""><?php _e( 'Select type...', 'event-management-system' ); ?></option>
                            <option value="oral"><?php _e( 'Oral Presentation', 'event-management-system' ); ?></option>
                            <option value="poster"><?php _e( 'Poster Presentation', 'event-management-system' ); ?></option>
                            <option value="workshop"><?php _e( 'Workshop', 'event-management-system' ); ?></option>
                        </select>
                    </div>

                    <div class="ems-form-group">
                        <label for="abstract_content"><?php _e( 'Abstract *', 'event-management-system' ); ?></label>
                        <textarea id="abstract_content" name="content" required rows="8" maxlength="2000" class="ems-form-control"></textarea>
                        <small class="ems-form-help"><?php _e( 'Maximum 2000 characters', 'event-management-system' ); ?></small>
                    </div>

                    <div class="ems-form-group">
                        <label for="keywords"><?php _e( 'Keywords', 'event-management-system' ); ?></label>
                        <input type="text" id="keywords" name="keywords" placeholder="<?php esc_attr_e( 'Separate with commas', 'event-management-system' ); ?>" class="ems-form-control">
                    </div>

                    <div class="ems-form-group">
                        <label for="learning_objectives"><?php _e( 'Learning Objectives', 'event-management-system' ); ?></label>
                        <textarea id="learning_objectives" name="learning_objectives" rows="4" maxlength="500" class="ems-form-control"></textarea>
                    </div>

                    <div class="ems-form-group">
                        <label for="presenter_bio"><?php _e( 'Presenter Bio', 'event-management-system' ); ?></label>
                        <textarea id="presenter_bio" name="presenter_bio" rows="4" maxlength="500" class="ems-form-control"></textarea>
                    </div>

                    <div class="ems-form-group">
                        <label for="supplementary_files"><?php _e( 'Supplementary Files', 'event-management-system' ); ?></label>
                        <input type="file" id="supplementary_files" name="files[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx" class="ems-form-control">
                        <small class="ems-form-help"><?php _e( 'PDF, DOC, DOCX, PPT, PPTX - Max 10MB per file', 'event-management-system' ); ?></small>
                    </div>

                    <div class="ems-form-actions">
                        <button type="submit" class="ems-btn ems-btn-primary"><?php _e( 'Submit Abstract', 'event-management-system' ); ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Presenter dashboard shortcode
     *
     * Displays a dashboard with the user's submitted abstracts and their status.
     *
     * Usage: [ems_presenter_dashboard]
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Dashboard HTML.
     */
    public function presenter_dashboard_shortcode( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="ems-notice ems-notice-warning">' . 
                   __( 'Please log in to view your presenter dashboard.', 'event-management-system' ) . 
                   '</div>';
        }

        $user_id = get_current_user_id();
        $abstracts_result = $this->submission_handler->get_user_abstracts( $user_id );

        ob_start();
        ?>
        <div class="ems-presenter-dashboard">
            <h2><?php _e( 'My Submitted Abstracts', 'event-management-system' ); ?></h2>

            <?php if ( $abstracts_result['success'] && ! empty( $abstracts_result['abstracts'] ) ) : ?>
                <div class="ems-abstracts-grid">
                    <?php foreach ( $abstracts_result['abstracts'] as $abstract ) : ?>
                        <div class="ems-abstract-card">
                            <div class="ems-abstract-header">
                                <h3><?php echo esc_html( $abstract['title'] ); ?></h3>
                                <span class="ems-badge ems-badge-<?php echo esc_attr( $abstract['review_status'] ); ?>">
                                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $abstract['review_status'] ) ) ); ?>
                                </span>
                            </div>
                            
                            <div class="ems-abstract-meta">
                                <p><strong><?php _e( 'Event:', 'event-management-system' ); ?></strong> <?php echo esc_html( $abstract['event_title'] ); ?></p>
                                <p><strong><?php _e( 'Type:', 'event-management-system' ); ?></strong> <?php echo esc_html( ucfirst( $abstract['abstract_type'] ) ); ?></p>
                                <p><strong><?php _e( 'Submitted:', 'event-management-system' ); ?></strong> <?php echo esc_html( EMS_Date_Helper::format_date( $abstract['submission_date'] ) ); ?></p>
                            </div>

                            <div class="ems-abstract-actions">
                                <a href="<?php echo esc_url( add_query_arg( 'abstract_id', $abstract['id'] ) ); ?>" class="ems-btn ems-btn-small">
                                    <?php _e( 'View Details', 'event-management-system' ); ?>
                                </a>
                                
                                <?php if ( in_array( $abstract['review_status'], array( 'pending', 'revision_requested' ) ) ) : ?>
                                    <a href="<?php echo esc_url( add_query_arg( array( 'abstract_id' => $abstract['id'], 'action' => 'edit' ) ) ); ?>" class="ems-btn ems-btn-small ems-btn-secondary">
                                        <?php _e( 'Edit', 'event-management-system' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="ems-notice ems-notice-info">
                    <?php _e( 'You have not submitted any abstracts yet.', 'event-management-system' ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Abstract list shortcode
     *
     * Displays a public list of approved abstracts for an event.
     *
     * Usage: [ems_abstract_list event_id="123" status="approved"]
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string List HTML.
     */
    public function abstract_list_shortcode( $atts ) {
        // Parse attributes
        $atts = shortcode_atts( array(
            'event_id' => 0,
            'status'   => 'approved',
            'type'     => '',
        ), $atts );

        $event_id = intval( $atts['event_id'] );

        if ( empty( $event_id ) ) {
            return '<div class="ems-notice ems-notice-error">' . 
                   __( 'Event ID is required.', 'event-management-system' ) . 
                   '</div>';
        }

        // Get abstracts
        $abstracts_result = $this->submission_handler->get_event_abstracts( $event_id, array(
            'status' => sanitize_text_field( $atts['status'] ),
            'type'   => sanitize_text_field( $atts['type'] ),
        ) );

        ob_start();
        ?>
        <div class="ems-abstract-list">
            <?php if ( $abstracts_result['success'] && ! empty( $abstracts_result['abstracts'] ) ) : ?>
                <?php foreach ( $abstracts_result['abstracts'] as $abstract ) : ?>
                    <div class="ems-abstract-item">
                        <h3><?php echo esc_html( $abstract['title'] ); ?></h3>
                        <div class="ems-abstract-meta">
                            <span class="ems-author"><?php echo esc_html( $abstract['author_name'] ); ?></span>
                            <span class="ems-separator">•</span>
                            <span class="ems-type"><?php echo esc_html( ucfirst( $abstract['abstract_type'] ) ); ?></span>
                        </div>
                        <div class="ems-abstract-excerpt">
                            <?php 
                            $content = get_post_field( 'post_content', $abstract['id'] );
                            echo wp_trim_words( $content, 50, '...' );
                            ?>
                        </div>
                        <a href="<?php echo esc_url( get_permalink( $abstract['id'] ) ); ?>" class="ems-read-more">
                            <?php _e( 'Read More', 'event-management-system' ); ?> →
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="ems-notice ems-notice-info">
                    <?php _e( 'No abstracts found.', 'event-management-system' ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle abstract submission form
     *
     * Processes the abstract submission form POST data.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_abstract_submission() {
        // Check if this is an abstract submission
        if ( ! isset( $_POST['action'] ) || 'ems_submit_abstract' !== $_POST['action'] ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['ems_abstract_nonce'] ) || 
             ! wp_verify_nonce( $_POST['ems_abstract_nonce'], 'ems_submit_abstract' ) ) {
            wp_die( __( 'Security check failed', 'event-management-system' ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_die( __( 'You must be logged in to submit an abstract', 'event-management-system' ) );
        }

        $user_id = get_current_user_id();

        // Prepare abstract data
        $abstract_data = array(
            'event_id'            => intval( $_POST['event_id'] ),
            'user_id'             => $user_id,
            'title'               => sanitize_text_field( $_POST['title'] ),
            'content'             => wp_kses_post( $_POST['content'] ),
            'abstract_type'       => sanitize_text_field( $_POST['abstract_type'] ),
            'keywords'            => isset( $_POST['keywords'] ) ? sanitize_text_field( $_POST['keywords'] ) : '',
            'learning_objectives' => isset( $_POST['learning_objectives'] ) ? sanitize_textarea_field( $_POST['learning_objectives'] ) : '',
            'presenter_bio'       => isset( $_POST['presenter_bio'] ) ? sanitize_textarea_field( $_POST['presenter_bio'] ) : '',
        );

        // Handle file uploads
        if ( ! empty( $_FILES['files'] ) && ! empty( $_FILES['files']['name'][0] ) ) {
            $abstract_data['files'] = $_FILES['files'];
        }

        // Submit abstract
        $result = $this->submission_handler->submit_abstract( $abstract_data );

        if ( $result['success'] ) {
            // Redirect with success message
            $redirect_url = add_query_arg( array(
                'ems_message' => 'abstract_submitted',
                'abstract_id' => $result['abstract_id'],
            ), get_permalink() );
            
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            // Redirect with error message
            $redirect_url = add_query_arg( array(
                'ems_error' => urlencode( $result['message'] ),
            ), get_permalink() );
            
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }

    /**
     * Render event selection for abstract submission
     *
     * Shows a list of events with open abstract submissions when no event_id is provided.
     *
     * @since 1.3.0
     * @return string HTML output.
     */
    private function render_event_selection_for_abstract() {
        // Get events with open abstract submissions
        $events = get_posts( array(
            'post_type'      => 'ems_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'abstract_submission_open',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => 'abstract_submission_close',
                    'compare' => 'EXISTS',
                ),
            ),
        ) );

        // Filter to only events with currently open submissions
        $open_events = array();
        foreach ( $events as $event ) {
            $date_check = $this->submission_handler->validate_submission_dates( $event->ID );
            if ( $date_check['open'] ) {
                $open_events[] = $event;
            }
        }

        ob_start();
        ?>
        <div class="ems-abstract-event-selection">
            <?php if ( empty( $open_events ) ) : ?>
                <div class="ems-notice ems-notice-info">
                    <h3><?php esc_html_e( 'No Events Accepting Abstracts', 'event-management-system' ); ?></h3>
                    <p><?php esc_html_e( 'There are currently no events accepting abstract submissions. Please check back later or browse upcoming events.', 'event-management-system' ); ?></p>
                    <?php 
                    $events_page = get_option( 'ems_page_events' );
                    if ( $events_page ) : 
                    ?>
                        <p><a href="<?php echo esc_url( get_permalink( $events_page ) ); ?>" class="ems-btn ems-btn-secondary">
                            <?php esc_html_e( 'Browse Events', 'event-management-system' ); ?>
                        </a></p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <h3><?php esc_html_e( 'Select an Event', 'event-management-system' ); ?></h3>
                <p><?php esc_html_e( 'Choose an event to submit your abstract:', 'event-management-system' ); ?></p>
                
                <div class="ems-event-list" style="display: grid; gap: 15px; margin-top: 20px;">
                    <?php foreach ( $open_events as $event ) : 
                        $start_date = get_post_meta( $event->ID, 'event_start_date', true );
                        $abstract_close = get_post_meta( $event->ID, 'abstract_submission_close', true );
                        $submit_url = add_query_arg( 'event_id', $event->ID, get_permalink() );
                    ?>
                        <div class="ems-event-option" style="padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                            <h4 style="margin: 0 0 10px;">
                                <a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>">
                                    <?php echo esc_html( $event->post_title ); ?>
                                </a>
                            </h4>
                            <?php if ( $start_date ) : ?>
                                <p style="color: #666; margin: 0 0 10px; font-size: 14px;">
                                    <strong><?php esc_html_e( 'Event Date:', 'event-management-system' ); ?></strong>
                                    <?php echo esc_html( EMS_Date_Helper::format_date( $start_date ) ); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ( $abstract_close ) : ?>
                                <p style="color: #856404; margin: 0 0 15px; font-size: 14px;">
                                    <strong><?php esc_html_e( 'Submission Deadline:', 'event-management-system' ); ?></strong>
                                    <?php echo esc_html( EMS_Date_Helper::format( $abstract_close ) ); ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?php echo esc_url( $submit_url ); ?>" class="ems-btn ems-btn-primary">
                                <?php esc_html_e( 'Submit Abstract', 'event-management-system' ); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .ems-abstract-event-selection .ems-btn { 
            display: inline-block; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: 500;
        }
        .ems-abstract-event-selection .ems-btn-primary { background: #2271b1; color: #fff; }
        .ems-abstract-event-selection .ems-btn-primary:hover { background: #135e96; }
        .ems-abstract-event-selection .ems-btn-secondary { background: #f0f0f0; color: #1d2327; }
        .ems-abstract-event-selection .ems-event-option:hover { border-color: #2271b1; }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcodes
new EMS_Abstract_Shortcodes();
