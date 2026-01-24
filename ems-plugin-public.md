# EMS Plugin Public & Frontend

**Document Purpose:** Frontend rendering, shortcodes, and public assets  
**Production Paths:** 
- `event-management-system/public/`
- `event-management-system/public/shortcodes/`

**Last Updated:** January 2026  
**Version:** 1.4.0 (UAT Release)

---

## Table of Contents

1. [Public Class](#public-class) - `class-ems-public.php` (56KB)
2. [Abstract Shortcodes](#abstract-shortcodes) - `class-ems-abstract-shortcodes.php` (24KB)
3. [Schedule Shortcodes](#schedule-shortcodes) - `class-ems-schedule-shortcodes.php` (20KB)
4. [Sponsor Shortcodes](#sponsor-shortcodes) - `class-ems-sponsor-shortcodes.php` (Phase 4)
5. [Public CSS](#public-css) - `ems-public.css`, `ems-schedule.css`
6. [Public JS](#public-js) - `ems-public.js`, `ems-schedule.js`

---

## Public Class

**File:** `class-ems-public.php`  
**Production Path:** `event-management-system/public/class-ems-public.php`

### Class Structure

```php
class EMS_Public {
    private $plugin_name;
    private $version;
    private $logger;
    private $registration;
    private $abstract_submission;
    private $schedule_display;
    
    // Assets
    public function enqueue_styles()
    public function enqueue_scripts()
    
    // Shortcode Registration
    public function register_shortcodes()
    
    // Event Shortcodes
    public function shortcode_upcoming_events( $atts )
    public function shortcode_event_list( $atts )
    public function shortcode_event_details( $atts )
    
    // Registration Shortcodes
    public function shortcode_registration_form( $atts )
    public function shortcode_my_registrations( $atts )
    
    // Abstract Shortcodes (delegates to EMS_Abstract_Shortcodes)
    public function shortcode_abstract_submission( $atts )
    public function shortcode_presenter_dashboard( $atts )
    
    // Schedule Shortcodes (delegates to EMS_Schedule_Shortcodes)
    public function shortcode_event_schedule( $atts )
    public function shortcode_my_schedule( $atts )
    
    // AJAX Handlers
    public function ajax_submit_registration()
    public function ajax_cancel_registration()
    public function ajax_submit_abstract()
    public function ajax_register_session()
    public function ajax_cancel_session()
    
    // iCal Export
    public function handle_ical_export()
    
    // REST API
    public function register_rest_routes()
}
```

### Key Shortcodes

| Shortcode | Attributes | Description |
|-----------|------------|-------------|
| `[ems_upcoming_events]` | `limit`, `show_past` | Display event list |
| `[ems_event_list]` | `limit`, `columns`, `show_past`, `category` | Grid event display |
| `[ems_registration_form]` | `event_id` | Registration form |
| `[ems_my_registrations]` | — | User's registrations |
| `[ems_abstract_submission]` | `event_id` | Abstract submission form |
| `[ems_presenter_dashboard]` | — | Presenter's abstracts |
| `[ems_event_schedule]` | `event_id`, `view`, `group_by`, `show_filters` | Event schedule |
| `[ems_my_schedule]` | `event_id` | User's registered sessions |

### AJAX Handler: Cancel Registration (FIXED in v1.3.0)

```php
/**
 * AJAX handler for cancelling registration
 *
 * @since 1.3.0
 */
public function ajax_cancel_registration() {
    // Verify nonce
    check_ajax_referer( 'ems_public_nonce', 'nonce' );

    // Must be logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array(
            'message' => __( 'You must be logged in to cancel a registration.', 'event-management-system' ),
        ) );
    }

    $registration_id = isset( $_POST['registration_id'] ) ? absint( $_POST['registration_id'] ) : 0;
    $reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

    if ( ! $registration_id ) {
        wp_send_json_error( array(
            'message' => __( 'Invalid registration ID.', 'event-management-system' ),
        ) );
    }

    // Verify ownership
    $registration = $this->registration->get_registration( $registration_id );
    if ( ! $registration || absint( $registration->user_id ) !== get_current_user_id() ) {
        wp_send_json_error( array(
            'message' => __( 'You do not have permission to cancel this registration.', 'event-management-system' ),
        ) );
    }

    // Process cancellation
    $result = $this->registration->cancel_registration( $registration_id, 'user', $reason );

    if ( $result['success'] ) {
        wp_send_json_success( array(
            'message' => $result['message'],
        ) );
    } else {
        wp_send_json_error( array(
            'message' => $result['message'],
        ) );
    }
}
```

### Script Localization (FIXED variable name)

```php
public function enqueue_scripts() {
    wp_enqueue_script(
        $this->plugin_name,
        EMS_PLUGIN_URL . 'public/js/ems-public.js',
        array( 'jquery' ),
        $this->version,
        true
    );

    // IMPORTANT: Variable name is 'ems_public', not 'ems_ajax'
    wp_localize_script(
        $this->plugin_name,
        'ems_public', // This is the JS variable name
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ems_public_nonce' ),
            'strings'  => array(
                'confirm_cancel'   => __( 'Are you sure you want to cancel this registration?', 'event-management-system' ),
                'processing'       => __( 'Processing...', 'event-management-system' ),
                'error_occurred'   => __( 'An error occurred. Please try again.', 'event-management-system' ),
            ),
        )
    );
}
```

---

## Abstract Shortcodes

**File:** `class-ems-abstract-shortcodes.php`  
**Production Path:** `event-management-system/public/shortcodes/class-ems-abstract-shortcodes.php`

```php
class EMS_Abstract_Shortcodes {
    // Shortcodes
    public function shortcode_abstract_submission( $atts )
    public function shortcode_presenter_dashboard( $atts )
    public function shortcode_abstract_list( $atts )
    
    // Form Rendering
    private function render_submission_form( $event_id )
    private function render_event_selection()
    private function render_submission_success( $abstract_id )
    
    // Dashboard Rendering
    private function render_presenter_abstracts( $user_id )
    private function render_abstract_card( $abstract )
    private function render_abstract_status_badge( $status )
    
    // Form Processing
    public function process_abstract_submission()
    private function handle_file_upload( $abstract_id )
    
    // Helpers
    private function get_submission_events()
    private function get_presentation_types( $event_id )
    private function get_tracks( $event_id )
}
```

### Abstract Submission Flow

```
1. User visits page with [ems_abstract_submission]
2. If event_id provided → Show form for that event
3. If no event_id → Show event selection
4. User fills form with:
   - Title
   - Authors (with affiliations)
   - Abstract text
   - Keywords
   - Presentation type preference
   - Track selection
   - File upload (optional)
5. On submit:
   - Validate data
   - Create abstract post
   - Handle file upload
   - Send confirmation email
   - Show success message
```

---

## Schedule Shortcodes

**File:** `class-ems-schedule-shortcodes.php`  
**Production Path:** `event-management-system/public/shortcodes/class-ems-schedule-shortcodes.php`

```php
class EMS_Schedule_Shortcodes {
    // Shortcodes
    public function shortcode_event_schedule( $atts )
    public function shortcode_my_schedule( $atts )
    public function shortcode_session_details( $atts )
    
    // Schedule Views
    private function render_schedule_list( $event_id, $options )
    private function render_schedule_grid( $event_id, $options )
    private function render_schedule_timeline( $event_id, $options )
    
    // Components
    private function render_day_tabs( $event_id, $dates )
    private function render_filter_controls( $event_id, $options )
    private function render_session_card( $session, $options )
    
    // My Schedule
    private function render_my_schedule( $event_id, $user_id )
    private function render_empty_schedule_message()
    
    // Session Registration
    public function ajax_register_session()
    public function ajax_cancel_session()
    
    // iCal Export
    private function render_ical_download_button( $event_id )
}
```

### Schedule View Options

| View | Description | Best For |
|------|-------------|----------|
| `list` | Simple chronological list | Single-track events |
| `grid` | Day/room grid layout | Multi-room events |
| `timeline` | Visual timeline | Complex schedules |

---

## Public CSS

### ems-public.css
**Production Path:** `event-management-system/public/css/ems-public.css`

**Key Sections:**
- Registration form styling
- My Registrations dashboard
- Event cards and lists
- Form validation states
- Buttons and actions
- Modal dialogs
- Responsive breakpoints

### ems-schedule.css
**Production Path:** `event-management-system/public/css/ems-schedule.css`

**Key Sections:**
- Schedule layout (list/grid/timeline)
- Day tabs navigation
- Session cards with type colors
- Speaker info styling
- Filter controls
- My Schedule view
- Print styles for schedules

---

## Public JS

### ems-public.js
**Production Path:** `event-management-system/public/js/ems-public.js`

**Key Functions:**

```javascript
(function($) {
    'use strict';

    // Registration Form
    var EMS_Registration = {
        init: function() {
            this.bindEvents();
            this.initValidation();
        },
        
        bindEvents: function() {
            $(document).on('submit', '#ems-registration-form', this.handleSubmit);
            $(document).on('change', '#ems-ticket-type', this.updatePrice);
            $(document).on('click', '.ems-apply-promo', this.applyPromoCode);
        },
        
        handleSubmit: function(e) {
            e.preventDefault();
            // AJAX submission using ems_public.ajax_url
            $.ajax({
                url: ems_public.ajax_url, // FIXED: was ems_ajax
                type: 'POST',
                data: $(this).serialize() + '&action=ems_submit_registration&nonce=' + ems_public.nonce,
                // ...
            });
        }
    };

    // Cancel Registration Modal
    var EMS_CancelModal = {
        init: function() {
            $(document).on('click', '.ems-cancel-registration', this.openModal);
            $(document).on('click', '#ems-confirm-cancel', this.confirmCancel);
        },
        
        confirmCancel: function() {
            $.ajax({
                url: ems_public.ajax_url, // FIXED: was ems_ajax
                type: 'POST',
                data: {
                    action: 'ems_cancel_registration',
                    nonce: ems_public.nonce, // FIXED: was ems_ajax.nonce
                    registration_id: currentRegistrationId,
                    reason: $('#ems-cancel-reason').val()
                },
                // ...
            });
        }
    };

    // Initialize
    $(document).ready(function() {
        EMS_Registration.init();
        EMS_CancelModal.init();
    });

})(jQuery);
```

### ems-schedule.js
**Production Path:** `event-management-system/public/js/ems-schedule.js`

**Key Functions:**

```javascript
(function($) {
    'use strict';

    var EMS_Schedule = {
        init: function() {
            this.initTabs();
            this.initFilters();
            this.initSessionRegistration();
        },
        
        initTabs: function() {
            $(document).on('click', '.ems-schedule-tab', function(e) {
                e.preventDefault();
                var date = $(this).data('date');
                $('.ems-schedule-tab').removeClass('active');
                $(this).addClass('active');
                $('.ems-schedule-day').hide();
                $('.ems-schedule-day[data-date="' + date + '"]').show();
            });
        },
        
        initFilters: function() {
            $(document).on('change', '.ems-schedule-filter', function() {
                EMS_Schedule.filterSessions();
            });
        },
        
        filterSessions: function() {
            var track = $('#ems-filter-track').val();
            var type = $('#ems-filter-type').val();
            
            $('.ems-session-card').each(function() {
                var show = true;
                if (track && $(this).data('track') !== track) show = false;
                if (type && $(this).data('type') !== type) show = false;
                $(this).toggle(show);
            });
        },
        
        initSessionRegistration: function() {
            $(document).on('click', '.ems-register-session', this.registerSession);
            $(document).on('click', '.ems-cancel-session', this.cancelSession);
        }
    };

    $(document).ready(function() {
        EMS_Schedule.init();
    });

})(jQuery);
```

---

## REST API Endpoints

```php
// Registered in EMS_Public::register_rest_routes()

// Events
GET  /wp-json/ems/v1/events
GET  /wp-json/ems/v1/events/{id}

// Schedule
GET  /wp-json/ems/v1/events/{id}/schedule
GET  /wp-json/ems/v1/sessions/{id}

// Registration (requires authentication)
POST /wp-json/ems/v1/registrations
GET  /wp-json/ems/v1/registrations/mine

// Session Registration (requires authentication)
POST /wp-json/ems/v1/sessions/{id}/register
DELETE /wp-json/ems/v1/sessions/{id}/register
```
