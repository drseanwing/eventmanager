# EMS Plugin Feature Modules

**Document Purpose:** Core feature module classes  
**Production Paths:** 
- `event-management-system/includes/registration/`
- `event-management-system/includes/abstracts/`
- `event-management-system/includes/schedule/`
- `event-management-system/includes/files/`
- `event-management-system/includes/notifications/`

---

## Table of Contents

1. [Registration Module](#registration-module)
   - `class-ems-registration.php` (26KB)
   - `class-ems-ticketing.php` (11KB)
   - `class-ems-waitlist.php` (13KB)
   - `class-ems-payment-gateway.php` (16KB)
2. [Abstract Module](#abstract-module)
   - `class-ems-abstract-submission.php` (41KB)
   - `class-ems-abstract-review.php` (32KB)
3. [Schedule Module](#schedule-module)
   - `class-ems-schedule-builder.php` (29KB)
   - `class-ems-schedule-display.php` (24KB)
   - `class-ems-session-registration.php` (20KB)
4. [File Module](#file-module)
   - `class-ems-file-manager.php` (20KB)
   - `class-ems-file-access-control.php` (15KB)
5. [Email Module](#email-module)
   - `class-ems-email-manager.php` (52KB)

---

## Registration Module

### EMS_Registration

**File:** `class-ems-registration.php`  
**Production Path:** `event-management-system/includes/registration/class-ems-registration.php`

```php
class EMS_Registration {
    private $logger;
    private $validator;
    private $ticketing;
    private $waitlist;
    private $email_manager;
    
    // Core Registration
    public function process_registration( $data )
    public function get_registration( $registration_id )
    public function get_event_registrations( $event_id, $args = array() )
    public function get_user_registrations( $user_id )
    
    // Status Management
    public function confirm_registration( $registration_id )
    public function cancel_registration( $registration_id, $cancelled_by = 'user', $reason = '' )
    public function can_cancel_registration( $registration_id )
    
    // Payment
    public function confirm_payment( $registration_id, $payment_data )
    public function get_registration_by_payment_reference( $reference )
    
    // Capacity & Validation
    public function check_event_capacity( $event_id )
    public function is_duplicate_registration( $event_id, $email )
    private function validate_registration_data( $data )
    
    // Utilities
    public function generate_registration_number( $event_id )
    public function update_event_registration_count( $event_id )
    public function export_registrations_csv( $event_id )
    public function get_registration_statistics( $event_id )
}
```

#### Key Method: cancel_registration (v1.3.0)

```php
/**
 * Cancel a registration
 *
 * @since 1.3.0
 * @param int    $registration_id Registration ID to cancel.
 * @param string $cancelled_by    Who cancelled: 'user', 'admin', or 'system'.
 * @param string $reason          Optional cancellation reason.
 * @return array Result with success status and message
 */
public function cancel_registration( $registration_id, $cancelled_by = 'user', $reason = '' ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ems_registrations';

    try {
        $registration = $this->get_registration( $registration_id );

        if ( ! $registration ) {
            return array(
                'success' => false,
                'message' => __( 'Registration not found', 'event-management-system' ),
            );
        }

        // Check if can be cancelled
        if ( 'cancelled' === $registration->status ) {
            return array(
                'success' => false,
                'message' => __( 'Registration is already cancelled', 'event-management-system' ),
            );
        }

        // Update status with cancellation details
        $updated = $wpdb->update(
            $table_name,
            array(
                'status'              => 'cancelled',
                'cancelled_at'        => current_time( 'mysql' ),
                'cancelled_by'        => sanitize_text_field( $cancelled_by ),
                'cancellation_reason' => sanitize_textarea_field( $reason ),
            ),
            array( 'id' => $registration_id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            $this->logger->error(
                "Failed to cancel registration {$registration_id}: " . $wpdb->last_error,
                EMS_Logger::CONTEXT_REGISTRATION
            );
            return array(
                'success' => false,
                'message' => __( 'Database error', 'event-management-system' ),
            );
        }

        // Update event registration count
        $this->update_event_registration_count( $registration->event_id );

        // Log the cancellation
        $this->logger->info(
            "Registration {$registration_id} cancelled by {$cancelled_by}",
            EMS_Logger::CONTEXT_REGISTRATION,
            array( 'reason' => $reason )
        );

        // Send cancellation email
        $this->email_manager->send_registration_cancelled_email( $registration_id, $cancelled_by, $reason );

        // Process waitlist if enabled
        $this->waitlist->process_waitlist( $registration->event_id );

        // Hook for extensions
        do_action( 'ems_registration_cancelled', $registration_id, $cancelled_by, $reason );

        return array(
            'success' => true,
            'message' => __( 'Registration cancelled successfully', 'event-management-system' ),
        );

    } catch ( Exception $e ) {
        $this->logger->error(
            "Exception cancelling registration {$registration_id}: " . $e->getMessage(),
            EMS_Logger::CONTEXT_REGISTRATION
        );
        return array(
            'success' => false,
            'message' => __( 'An error occurred', 'event-management-system' ),
        );
    }
}
```

### EMS_Ticketing

**File:** `class-ems-ticketing.php`

```php
class EMS_Ticketing {
    // Ticket Configuration
    public function get_event_ticket_config( $event_id )
    public function save_event_ticket_config( $event_id, $config )
    
    // Pricing
    public function calculate_ticket_price( $event_id, $ticket_type, $quantity = 1 )
    public function apply_promo_code( $event_id, $promo_code, $original_price )
    public function validate_promo_code( $event_id, $promo_code )
    
    // Promo Code Management
    public function get_event_promo_codes( $event_id )
    public function add_promo_code( $event_id, $code_data )
    public function update_promo_code_usage( $event_id, $promo_code )
    
    // Helpers
    public function get_available_ticket_types( $event_id )
    public function is_ticket_type_available( $event_id, $ticket_type )
}
```

### EMS_Waitlist

**File:** `class-ems-waitlist.php`

```php
class EMS_Waitlist {
    // Queue Management
    public function add_to_waitlist( $waitlist_data )
    public function remove_from_waitlist( $waitlist_id )
    public function get_waitlist_position( $event_id, $email )
    
    // Processing
    public function process_waitlist( $event_id )
    public function promote_from_waitlist( $waitlist_id )
    
    // Queries
    public function get_event_waitlist( $event_id, $args = array() )
    public function get_waitlist_count( $event_id )
    public function is_on_waitlist( $event_id, $email )
}
```

### EMS_Payment_Gateway

**File:** `class-ems-payment-gateway.php`

```php
// Abstract base class
abstract class EMS_Payment_Gateway {
    abstract public function process_payment( $registration_id, $amount );
    abstract public function verify_payment( $transaction_id );
    abstract public function refund_payment( $transaction_id, $amount );
    abstract public function get_payment_url( $registration_id );
}

// Invoice gateway implementation
class EMS_Invoice_Gateway extends EMS_Payment_Gateway {
    public function process_payment( $registration_id, $amount )
    public function verify_payment( $transaction_id )
    public function refund_payment( $transaction_id, $amount )
    public function get_payment_url( $registration_id )
    public function generate_invoice( $registration_id )
    public function confirm_invoice_payment( $registration_id, $reference )
}

// Gateway manager
class EMS_Payment_Gateway_Manager {
    public function register_gateway( $gateway_id, $gateway_class )
    public function get_gateway( $gateway_id )
    public function get_all_gateways()
    public function get_active_gateway()
}
```

---

## Abstract Module

### EMS_Abstract_Submission

**File:** `class-ems-abstract-submission.php`

```php
class EMS_Abstract_Submission {
    // Submission
    public function submit_abstract( $data )
    public function update_abstract( $abstract_id, $data )
    public function delete_abstract( $abstract_id )
    
    // Validation
    private function validate_submission_data( $data )
    public function can_submit_abstract( $event_id, $user_id = null )
    public function is_submission_open( $event_id )
    
    // Queries
    public function get_abstract( $abstract_id )
    public function get_user_abstracts( $user_id, $event_id = null )
    public function get_event_abstracts( $event_id, $args = array() )
    
    // Status
    public function update_abstract_status( $abstract_id, $status, $notes = '' )
    public function get_abstract_status_label( $status )
    
    // File Handling
    public function handle_abstract_file_upload( $abstract_id, $file )
    public function get_abstract_file_url( $abstract_id )
}
```

### EMS_Abstract_Review

**File:** `class-ems-abstract-review.php`

```php
class EMS_Abstract_Review {
    // Reviewer Assignment
    public function assign_reviewer( $abstract_id, $reviewer_id )
    public function remove_reviewer( $abstract_id, $reviewer_id )
    public function get_abstract_reviewers( $abstract_id )
    public function get_reviewer_assignments( $reviewer_id, $event_id = null )
    
    // Review Submission
    public function submit_review( $abstract_id, $reviewer_id, $review_data )
    public function update_review( $review_id, $review_data )
    public function get_review( $abstract_id, $reviewer_id )
    public function get_abstract_reviews( $abstract_id )
    
    // Decision Making
    public function calculate_average_score( $abstract_id )
    public function get_recommendation_counts( $abstract_id )
    public function make_final_decision( $abstract_id, $decision, $notes = '' )
    
    // Queries
    public function get_pending_reviews( $event_id )
    public function get_completed_reviews( $event_id )
    public function get_reviewer_statistics( $reviewer_id )
}
```

---

## Schedule Module

### EMS_Schedule_Builder

**File:** `class-ems-schedule-builder.php`

```php
class EMS_Schedule_Builder {
    // Session Management
    public function create_session( $data )
    public function update_session( $session_id, $data )
    public function delete_session( $session_id )
    public function duplicate_session( $session_id )
    
    // Schedule Operations
    public function get_event_schedule( $event_id )
    public function get_day_schedule( $event_id, $date )
    public function get_room_schedule( $event_id, $room )
    
    // Validation
    public function validate_session_timing( $data )
    public function check_room_conflicts( $event_id, $date, $start_time, $end_time, $room, $exclude_session = null )
    public function check_speaker_conflicts( $event_id, $date, $start_time, $end_time, $speakers, $exclude_session = null )
    
    // Import/Export
    public function export_schedule_csv( $event_id )
    public function export_schedule_ical( $event_id )
    public function import_schedule_csv( $event_id, $file )
    
    // Publishing
    public function publish_schedule( $event_id )
    public function unpublish_schedule( $event_id )
    public function is_schedule_published( $event_id )
}
```

### EMS_Schedule_Display

**File:** `class-ems-schedule-display.php`

```php
class EMS_Schedule_Display {
    // Rendering
    public function render_schedule( $event_id, $view = 'list', $options = array() )
    public function render_schedule_list( $event_id, $options )
    public function render_schedule_grid( $event_id, $options )
    public function render_schedule_timeline( $event_id, $options )
    
    // Session Cards
    public function render_session_card( $session, $options )
    public function render_session_details( $session_id )
    
    // Filtering
    public function get_schedule_filters( $event_id )
    public function filter_schedule( $event_id, $filters )
    
    // User Schedule
    public function render_my_schedule( $event_id, $user_id )
    public function get_user_registered_sessions( $event_id, $user_id )
}
```

### EMS_Session_Registration

**File:** `class-ems-session-registration.php`

```php
class EMS_Session_Registration {
    // Registration
    public function register_for_session( $session_id, $registration_id )
    public function cancel_session_registration( $session_id, $registration_id )
    
    // Capacity
    public function check_session_capacity( $session_id )
    public function get_session_registration_count( $session_id )
    
    // Queries
    public function get_session_registrations( $session_id )
    public function get_user_session_registrations( $event_id, $user_id )
    public function is_registered_for_session( $session_id, $registration_id )
    
    // Conflicts
    public function check_session_conflicts( $event_id, $registration_id, $session_id )
    public function get_conflicting_sessions( $event_id, $registration_id, $session_id )
}
```

---

## File Module

### EMS_File_Manager

**File:** `class-ems-file-manager.php`

```php
class EMS_File_Manager {
    // Allowed file types
    private $allowed_types = array(
        'pdf', 'doc', 'docx', 'ppt', 'pptx', 
        'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'
    );
    
    // Upload
    public function upload_file( $file, $context, $entity_id )
    public function validate_file( $file )
    public function move_to_secure_directory( $file, $context, $entity_id )
    
    // Retrieval
    public function get_file_path( $file_id )
    public function get_file_url( $file_id, $user_id = null )
    public function get_secure_download_url( $file_id, $user_id )
    
    // Management
    public function delete_file( $file_id )
    public function cleanup_orphaned_files()
    public function cleanup_temp_files()
    
    // Metadata
    public function get_file_metadata( $file_id )
    public function update_file_metadata( $file_id, $metadata )
}
```

### EMS_File_Access_Control

**File:** `class-ems-file-access-control.php`

```php
class EMS_File_Access_Control {
    // Access Checks
    public function can_access_file( $file_id, $user_id )
    public function can_access_abstract_file( $abstract_id, $user_id )
    public function can_access_sponsor_file( $file_id, $user_id )
    
    // Token Generation
    public function generate_download_token( $file_id, $user_id )
    public function validate_download_token( $token )
    
    // Download Handler
    public function handle_secure_download()
    public function serve_file( $file_path, $filename )
    
    // Logging
    public function log_file_access( $file_id, $user_id, $action )
}
```

---

## Email Module

### EMS_Email_Manager

**File:** `class-ems-email-manager.php`  
**Size:** ~52KB (~1,000 lines)

```php
class EMS_Email_Manager {
    // Registration Emails (Phase 1)
    public function send_registration_pending_email( $registration_id )
    public function send_registration_confirmed_email( $registration_id )
    public function send_registration_cancelled_email( $registration_id, $cancelled_by, $reason )
    public function send_waitlist_confirmation_email( $registration_id )
    public function send_waitlist_spot_available_email( $waitlist_id )
    public function send_invoice_email( $registration_id )
    
    // Abstract Emails (Phase 2)
    public function send_abstract_submitted_email( $abstract_id )
    public function send_review_assigned_email( $abstract_id, $reviewer_id )
    public function send_abstract_approved_email( $abstract_id )
    public function send_abstract_rejected_email( $abstract_id )
    public function send_revision_requested_email( $abstract_id )
    
    // Schedule Emails (Phase 3)
    public function send_session_confirmation_email( $session_registration_id )
    public function send_schedule_published_email( $event_id )
    public function send_event_reminders()
    public function send_session_reminders()
    
    // Core Methods
    public function send_email( $to, $subject, $message, $type, $entity_id = null )
    private function get_email_template( $template_name )
    private function replace_placeholders( $content, $data )
    private function log_notification( $entity_type, $entity_id, $type, $recipient )
    
    // Utility
    public function get_notification_log( $entity_type, $entity_id )
    public function can_send_notification( $type, $entity_id )
}
```

#### Email Template Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{site_name}` | WordPress site name |
| `{site_url}` | WordPress site URL |
| `{user_name}` | Recipient's display name |
| `{user_email}` | Recipient's email |
| `{event_name}` | Event title |
| `{event_date}` | Formatted event date |
| `{event_location}` | Event venue |
| `{registration_number}` | Registration reference |
| `{abstract_title}` | Abstract title |
| `{session_title}` | Session title |
| `{session_time}` | Session date/time |
| `{session_room}` | Session location |
