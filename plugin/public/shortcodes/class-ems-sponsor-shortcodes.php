<?php
/**
 * Sponsor Portal Shortcodes
 *
 * Provides shortcodes for sponsor portal dashboard and file management.
 *
 * @package    Event_Management_System
 * @subpackage Event_Management_System/public/shortcodes
 * @since      1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EMS_Sponsor_Shortcodes
 *
 * Registers and renders shortcodes for:
 * - [ems_sponsor_portal] - Sponsor portal dashboard
 * - [ems_sponsor_files] - Sponsor file listing
 *
 * @since 1.4.0
 */
class EMS_Sponsor_Shortcodes {

    /**
     * Logger instance
     *
     * @since 1.4.0
     * @var EMS_Logger
     */
    private $logger;

    /**
     * Sponsor portal handler
     *
     * @since 1.4.0
     * @var EMS_Sponsor_Portal
     */
    private $sponsor_portal;

    /**
     * Constructor
     *
     * Initialize dependencies and register shortcodes.
     *
     * @since 1.4.0
     */
    public function __construct() {
        $this->logger = EMS_Logger::instance();
        $this->sponsor_portal = new EMS_Sponsor_Portal();

        // Register shortcodes
        add_shortcode( 'ems_sponsor_portal', array( $this, 'sponsor_portal_shortcode' ) );
        add_shortcode( 'ems_sponsor_files', array( $this, 'sponsor_files_shortcode' ) );

        // Handle form submissions via AJAX
        add_action( 'wp_ajax_ems_upload_sponsor_file', array( $this, 'ajax_upload_sponsor_file' ) );
        add_action( 'wp_ajax_ems_delete_sponsor_file', array( $this, 'ajax_delete_sponsor_file' ) );
    }

    /**
     * Sponsor portal dashboard shortcode
     *
     * Renders the main sponsor portal with dashboard, events, and file management.
     *
     * Usage: [ems_sponsor_portal]
     *
     * @since 1.4.0
     * @param array $atts Shortcode attributes.
     * @return string Portal HTML.
     */
    public function sponsor_portal_shortcode( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( add_query_arg( array() ) );
            return '<div class="ems-notice ems-notice-warning">' . 
                   sprintf(
                       __( 'Please <a href="%s">log in</a> to access the sponsor portal.', 'event-management-system' ),
                       esc_url( $login_url )
                   ) . 
                   '</div>';
        }

        // Check if user has sponsor capability
        if ( ! current_user_can( 'access_ems_sponsor_portal' ) ) {
            return '<div class="ems-notice ems-notice-error">' . 
                   __( 'You do not have access to the sponsor portal.', 'event-management-system' ) . 
                   '</div>';
        }

        // Get sponsor ID for current user
        $sponsor_id = $this->sponsor_portal->get_user_sponsor_id( get_current_user_id() );
        if ( ! $sponsor_id ) {
            return '<div class="ems-notice ems-notice-error">' . 
                   __( 'No sponsor profile associated with your account. Please contact the administrator.', 'event-management-system' ) . 
                   '</div>';
        }

        // Get sponsor data
        $sponsor = get_post( $sponsor_id );
        $events = $this->sponsor_portal->get_sponsor_events( $sponsor_id );
        $statistics = $this->sponsor_portal->get_sponsor_statistics( $sponsor_id );

        ob_start();
        ?>
        
        <div class="ems-sponsor-portal">
            <!-- Portal Header -->
            <div class="ems-sponsor-header">
                <h1><?php echo esc_html( sprintf( __( 'Welcome, %s', 'event-management-system' ), $sponsor->post_title ) ); ?></h1>
                <p class="ems-sponsor-description"><?php esc_html_e( 'Manage your event sponsorships and share files with event organizers.', 'event-management-system' ); ?></p>
            </div>

            <!-- Statistics Dashboard -->
            <div class="ems-sponsor-statistics">
                <div class="ems-stat-card">
                    <div class="ems-stat-value"><?php echo esc_html( $statistics['event_count'] ); ?></div>
                    <div class="ems-stat-label"><?php esc_html_e( 'Linked Events', 'event-management-system' ); ?></div>
                </div>
                <div class="ems-stat-card">
                    <div class="ems-stat-value"><?php echo esc_html( $statistics['file_count'] ); ?></div>
                    <div class="ems-stat-label"><?php esc_html_e( 'Files Uploaded', 'event-management-system' ); ?></div>
                </div>
                <div class="ems-stat-card">
                    <div class="ems-stat-value"><?php echo esc_html( $statistics['total_downloads'] ); ?></div>
                    <div class="ems-stat-label"><?php esc_html_e( 'Total Downloads', 'event-management-system' ); ?></div>
                </div>
                <div class="ems-stat-card">
                    <div class="ems-stat-value"><?php echo esc_html( size_format( $statistics['total_size'] ) ); ?></div>
                    <div class="ems-stat-label"><?php esc_html_e( 'Storage Used', 'event-management-system' ); ?></div>
                </div>
            </div>

            <!-- Events List -->
            <div class="ems-sponsor-events-section">
                <h2><?php esc_html_e( 'Your Sponsored Events', 'event-management-system' ); ?></h2>
                
                <?php if ( empty( $events ) ) : ?>
                    <p class="ems-no-results"><?php esc_html_e( 'You are not currently linked to any events.', 'event-management-system' ); ?></p>
                <?php else : ?>
                    <div class="ems-sponsor-events-list">
                        <?php foreach ( $events as $event ) : ?>
                            <div class="ems-sponsor-event-card" data-event-id="<?php echo esc_attr( $event['event_id'] ); ?>">
                                <div class="ems-event-header">
                                    <h3><?php echo esc_html( $event['event_title'] ); ?></h3>
                                    <?php if ( $event['sponsor_level'] ) : ?>
                                        <span class="ems-sponsor-level-badge"><?php echo esc_html( $event['sponsor_level'] ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ( $event['event_date'] ) : ?>
                                    <p class="ems-event-date">
                                        <strong><?php esc_html_e( 'Date:', 'event-management-system' ); ?></strong>
                                        <?php echo esc_html( EMS_Date_Helper::format( $event['event_date'] ) ); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="ems-event-actions">
                                    <button type="button" class="ems-button ems-toggle-files" data-event-id="<?php echo esc_attr( $event['event_id'] ); ?>">
                                        <?php esc_html_e( 'Manage Files', 'event-management-system' ); ?>
                                    </button>
                                </div>
                                
                                <!-- Files Section (Initially Hidden) -->
                                <div class="ems-event-files" id="ems-files-<?php echo esc_attr( $event['event_id'] ); ?>" style="display: none;">
                                    <h4><?php esc_html_e( 'Files for this Event', 'event-management-system' ); ?></h4>
                                    
                                    <!-- Upload Form -->
                                    <div class="ems-file-upload-section">
                                        <h5><?php esc_html_e( 'Upload New File', 'event-management-system' ); ?></h5>
                                        <form class="ems-sponsor-file-upload-form" data-event-id="<?php echo esc_attr( $event['event_id'] ); ?>" data-sponsor-id="<?php echo esc_attr( $sponsor_id ); ?>">
                                            <div class="ems-form-group">
                                                <label for="sponsor-file-<?php echo esc_attr( $event['event_id'] ); ?>"><?php esc_html_e( 'Choose File:', 'event-management-system' ); ?></label>
                                                <input type="file" id="sponsor-file-<?php echo esc_attr( $event['event_id'] ); ?>" name="sponsor_file" required />
                                                <p class="ems-field-help"><?php esc_html_e( 'Allowed types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, PNG, GIF, ZIP, MP4, MOV (Max: 25MB)', 'event-management-system' ); ?></p>
                                            </div>
                                            <div class="ems-form-group">
                                                <label for="file-description-<?php echo esc_attr( $event['event_id'] ); ?>"><?php esc_html_e( 'Description:', 'event-management-system' ); ?></label>
                                                <textarea id="file-description-<?php echo esc_attr( $event['event_id'] ); ?>" name="file_description" rows="3"></textarea>
                                            </div>
                                            <div class="ems-form-group">
                                                <button type="submit" class="ems-button ems-button-primary"><?php esc_html_e( 'Upload File', 'event-management-system' ); ?></button>
                                            </div>
                                            <div class="ems-upload-status"></div>
                                        </form>
                                    </div>
                                    
                                    <!-- Files List -->
                                    <div class="ems-files-list">
                                        <?php
                                        $files = $this->sponsor_portal->get_sponsor_files( $sponsor_id, $event['event_id'] );
                                        if ( empty( $files ) ) :
                                        ?>
                                            <p class="ems-no-files"><?php esc_html_e( 'No files uploaded yet.', 'event-management-system' ); ?></p>
                                        <?php else : ?>
                                            <table class="ems-files-table">
                                                <thead>
                                                    <tr>
                                                        <th><?php esc_html_e( 'File Name', 'event-management-system' ); ?></th>
                                                        <th><?php esc_html_e( 'Size', 'event-management-system' ); ?></th>
                                                        <th><?php esc_html_e( 'Uploaded', 'event-management-system' ); ?></th>
                                                        <th><?php esc_html_e( 'Downloads', 'event-management-system' ); ?></th>
                                                        <th><?php esc_html_e( 'Actions', 'event-management-system' ); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ( $files as $file ) : ?>
                                                        <tr data-file-id="<?php echo esc_attr( $file->id ); ?>">
                                                            <td>
                                                                <strong><?php echo esc_html( $file->file_name ); ?></strong>
                                                                <?php if ( $file->description ) : ?>
                                                                    <br /><small><?php echo esc_html( $file->description ); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo esc_html( size_format( $file->file_size ) ); ?></td>
                                                            <td><?php echo esc_html( EMS_Date_Helper::format( $file->upload_date ) ); ?></td>
                                                            <td><?php echo esc_html( $file->downloads ); ?></td>
                                                            <td>
                                                                <a href="<?php echo esc_url( add_query_arg( array( 'ems_download_sponsor_file' => $file->id ), home_url() ) ); ?>" class="ems-button ems-button-small" target="_blank">
                                                                    <?php esc_html_e( 'Download', 'event-management-system' ); ?>
                                                                </a>
                                                                <button type="button" class="ems-button ems-button-small ems-button-danger ems-delete-file" data-file-id="<?php echo esc_attr( $file->id ); ?>">
                                                                    <?php esc_html_e( 'Delete', 'event-management-system' ); ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle files section
            $('.ems-toggle-files').on('click', function() {
                var eventId = $(this).data('event-id');
                $('#ems-files-' + eventId).slideToggle();
            });

            // Handle file upload via AJAX
            $('.ems-sponsor-file-upload-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var eventId = $form.data('event-id');
                var sponsorId = $form.data('sponsor-id');
                var $status = $form.find('.ems-upload-status');
                var formData = new FormData();
                
                var fileInput = $form.find('input[type="file"]')[0];
                if (!fileInput.files[0]) {
                    $status.html('<div class="ems-notice ems-notice-error">Please select a file.</div>');
                    return;
                }
                
                formData.append('action', 'ems_upload_sponsor_file');
                formData.append('nonce', ems_public.nonce);
                formData.append('sponsor_id', sponsorId);
                formData.append('event_id', eventId);
                formData.append('sponsor_file', fileInput.files[0]);
                formData.append('file_description', $form.find('textarea[name="file_description"]').val());
                
                $status.html('<div class="ems-notice ems-notice-info">Uploading file...</div>');
                $form.find('button[type="submit"]').prop('disabled', true);
                
                $.ajax({
                    url: ems_public.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $status.html('<div class="ems-notice ems-notice-success">' + response.data.message + '</div>');
                            // Reload page after 2 seconds to show new file
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.html('<div class="ems-notice ems-notice-error">' + response.data + '</div>');
                            $form.find('button[type="submit"]').prop('disabled', false);
                        }
                    },
                    error: function() {
                        $status.html('<div class="ems-notice ems-notice-error">An error occurred. Please try again.</div>');
                        $form.find('button[type="submit"]').prop('disabled', false);
                    }
                });
            });

            // Handle file deletion
            $('.ems-delete-file').on('click', function() {
                if (!confirm('Are you sure you want to delete this file?')) {
                    return;
                }
                
                var fileId = $(this).data('file-id');
                var $row = $(this).closest('tr');
                
                $.ajax({
                    url: ems_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ems_delete_sponsor_file',
                        nonce: ems_public.nonce,
                        file_id: fileId
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.fadeOut(function() {
                                $row.remove();
                            });
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
        </script>
        
        <?php
        return ob_get_clean();
    }

    /**
     * Sponsor files list shortcode
     *
     * Renders a list of files for a specific sponsor/event.
     *
     * Usage: [ems_sponsor_files sponsor_id="123" event_id="456"]
     *
     * @since 1.4.0
     * @param array $atts Shortcode attributes.
     * @return string Files list HTML.
     */
    public function sponsor_files_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'sponsor_id' => 0,
            'event_id'   => 0,
        ), $atts, 'ems_sponsor_files' );

        $sponsor_id = absint( $atts['sponsor_id'] );
        $event_id = absint( $atts['event_id'] );

        if ( ! $sponsor_id ) {
            return '<p>' . esc_html__( 'Invalid sponsor ID', 'event-management-system' ) . '</p>';
        }

        $files = $this->sponsor_portal->get_sponsor_files( $sponsor_id, $event_id );

        if ( empty( $files ) ) {
            return '<p>' . esc_html__( 'No files available.', 'event-management-system' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="ems-sponsor-files-widget">
            <h3><?php esc_html_e( 'Sponsor Files', 'event-management-system' ); ?></h3>
            <ul class="ems-files-list">
                <?php foreach ( $files as $file ) : ?>
                    <li>
                        <a href="<?php echo esc_url( add_query_arg( array( 'ems_download_sponsor_file' => $file->id ), home_url() ) ); ?>" target="_blank">
                            <?php echo esc_html( $file->file_name ); ?>
                        </a>
                        <span class="ems-file-size">(<?php echo esc_html( size_format( $file->file_size ) ); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for sponsor file upload
     *
     * @since 1.4.0
     * @return void
     */
    public function ajax_upload_sponsor_file() {
        check_ajax_referer( 'ems_public_nonce', 'nonce' );

        try {
            // Sanitize POST data
            $sponsor_id = isset( $_POST['sponsor_id'] ) ? absint( $_POST['sponsor_id'] ) : 0;
            $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
            $description = isset( $_POST['file_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['file_description'] ) ) : '';

            if ( ! $sponsor_id || ! $event_id ) {
                wp_send_json_error( __( 'Invalid parameters', 'event-management-system' ) );
            }

            // Check if file was uploaded
            if ( ! isset( $_FILES['sponsor_file'] ) || empty( $_FILES['sponsor_file']['name'] ) ) {
                wp_send_json_error( __( 'No file uploaded', 'event-management-system' ) );
            }

            // Sanitize file data
            $file_data = array(
                'name'     => sanitize_file_name( $_FILES['sponsor_file']['name'] ),
                'type'     => sanitize_mime_type( $_FILES['sponsor_file']['type'] ),
                'tmp_name' => $_FILES['sponsor_file']['tmp_name'],
                'error'    => $_FILES['sponsor_file']['error'],
                'size'     => absint( $_FILES['sponsor_file']['size'] ),
            );

            // Upload file
            $result = $this->sponsor_portal->upload_sponsor_file(
                $file_data,
                $sponsor_id,
                $event_id,
                array(
                    'description' => $description,
                    'visibility'  => 'private',
                )
            );

            if ( $result['success'] ) {
                wp_send_json_success( $result );
            } else {
                wp_send_json_error( $result['message'] );
            }

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in ajax_upload_sponsor_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );

            wp_send_json_error( __( 'An error occurred during file upload', 'event-management-system' ) );
        }
    }

    /**
     * AJAX handler for sponsor file deletion
     *
     * @since 1.4.0
     * @return void
     */
    public function ajax_delete_sponsor_file() {
        check_ajax_referer( 'ems_public_nonce', 'nonce' );

        try {
            $file_id = isset( $_POST['file_id'] ) ? absint( $_POST['file_id'] ) : 0;

            if ( ! $file_id ) {
                wp_send_json_error( __( 'Invalid file ID', 'event-management-system' ) );
            }

            $result = $this->sponsor_portal->delete_sponsor_file( $file_id );

            if ( $result['success'] ) {
                wp_send_json_success( $result['message'] );
            } else {
                wp_send_json_error( $result['message'] );
            }

        } catch ( Exception $e ) {
            $this->logger->error(
                'Exception in ajax_delete_sponsor_file: ' . $e->getMessage(),
                EMS_Logger::CONTEXT_GENERAL
            );

            wp_send_json_error( __( 'An error occurred during file deletion', 'event-management-system' ) );
        }
    }
}
