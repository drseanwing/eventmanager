# EMS Plugin Admin & CPT Classes

**Document Purpose:** Admin interface and Custom Post Type definitions  
**Production Paths:** 
- `event-management-system/admin/`
- `event-management-system/includes/cpt/`

---

## Table of Contents

1. [Admin Class](#admin-class) - `class-ems-admin.php` (76KB)
2. [Event CPT](#event-cpt) - `class-ems-cpt-event.php` (19KB)
3. [Session CPT](#session-cpt) - `class-ems-cpt-session.php` (16KB)
4. [Abstract CPT](#abstract-cpt) - `class-ems-cpt-abstract.php` (17KB)
5. [Sponsor CPT](#sponsor-cpt) - `class-ems-cpt-sponsor.php` (1.5KB)
6. [Admin Assets](#admin-assets) - CSS/JS files

---

## Admin Class

**File:** `class-ems-admin.php`  
**Production Path:** `event-management-system/admin/class-ems-admin.php`  
**Size:** ~76KB (~2,400 lines)

### Class Structure

```php
class EMS_Admin {
    private $plugin_name;
    private $version;
    private $logger;
    
    // Menu & Pages
    public function add_admin_menu()
    public function render_dashboard_page()
    public function render_registrations_page()
    public function render_abstracts_page()
    public function render_schedule_page()
    public function render_settings_page()
    public function render_logs_page()
    
    // Registration Management
    private function display_registrations_list()
    private function handle_registration_actions()
    private function export_registrations_csv()
    public function ajax_get_registrations()
    
    // Abstract Management
    private function display_abstracts_list()
    private function display_abstract_review_interface()
    public function ajax_assign_reviewer()
    public function handle_abstract_status_update()
    
    // Schedule Builder
    private function display_schedule_builder()
    public function ajax_save_schedule()
    
    // Settings
    private function display_settings_form()
    private function save_settings()
    
    // Meta Boxes (for Event/Session/Abstract edit screens)
    public function add_event_meta_boxes()
    public function add_session_meta_boxes()
    public function add_abstract_meta_boxes()
    public function save_event_meta()
    public function save_session_meta()
    public function save_abstract_meta()
    
    // Assets
    public function enqueue_styles()
    public function enqueue_scripts()
}
```

### Key Admin Menu Items

```php
// Main menu
add_menu_page( 'Events', 'Events', 'manage_options', 'ems-dashboard', ... );

// Submenus
add_submenu_page( 'ems-dashboard', 'Registrations', ... );
add_submenu_page( 'ems-dashboard', 'Abstracts', ... );
add_submenu_page( 'ems-dashboard', 'Schedule Builder', ... );
add_submenu_page( 'ems-dashboard', 'Settings', ... );
add_submenu_page( 'ems-dashboard', 'Logs', ... );
```

### Registration Actions Handler (FIXED in v1.3.0)

```php
private function handle_registration_actions() {
    global $wpdb;
    
    $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
    
    if ( 'cancel' === $action && isset( $_GET['id'] ) ) {
        $id = absint( $_GET['id'] );
        check_admin_referer( 'cancel_registration_' . $id );
        
        // Get reason from URL if provided
        $reason = isset( $_GET['reason'] ) ? sanitize_textarea_field( $_GET['reason'] ) : '';
        
        // Use the Registration class to properly handle cancellation
        $registration_handler = new EMS_Registration();
        $result = $registration_handler->cancel_registration( $id, 'admin', $reason );
        
        if ( $result['success'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 esc_html__( 'Registration cancelled successfully.', 'event-management-system' ) . 
                 '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 esc_html( $result['message'] ) . '</p></div>';
        }
    }
    
    if ( 'export' === $action ) {
        $this->export_registrations_csv();
    }
}
```

---

## Event CPT

**File:** `class-ems-cpt-event.php`  
**Production Path:** `event-management-system/includes/cpt/class-ems-cpt-event.php`

### Post Type Registration

```php
class EMS_CPT_Event {
    
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Events', 'event-management-system' ),
            'singular_name'      => __( 'Event', 'event-management-system' ),
            'add_new'            => __( 'Add New Event', 'event-management-system' ),
            'add_new_item'       => __( 'Add New Event', 'event-management-system' ),
            'edit_item'          => __( 'Edit Event', 'event-management-system' ),
            'view_item'          => __( 'View Event', 'event-management-system' ),
            'all_items'          => __( 'All Events', 'event-management-system' ),
            // ...
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'events', 'with_front' => false ),
            'capability_type'     => array( 'ems_event', 'ems_events' ),
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
            'show_in_rest'        => true,
        );

        register_post_type( 'ems_event', $args );
    }
    
    public function register_taxonomies() {
        // Event Type taxonomy
        register_taxonomy( 'ems_event_type', 'ems_event', array(
            'labels'            => array( ... ),
            'hierarchical'      => true,
            'public'            => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'event-type' ),
        ));
        
        // Event Track taxonomy
        register_taxonomy( 'ems_event_track', 'ems_event', array( ... ));
    }
}
```

### Event Meta Fields

| Meta Key | Type | Description |
|----------|------|-------------|
| `_ems_event_start_date` | datetime | Event start date/time |
| `_ems_event_end_date` | datetime | Event end date/time |
| `_ems_event_venue` | string | Venue name |
| `_ems_event_address` | text | Full address |
| `_ems_event_capacity` | int | Maximum registrations |
| `_ems_event_registration_open` | datetime | Registration opens |
| `_ems_event_registration_close` | datetime | Registration closes |
| `_ems_event_abstract_open` | datetime | Abstract submission opens |
| `_ems_event_abstract_close` | datetime | Abstract submission closes |
| `_ems_event_ticket_types` | array | Ticket type configuration |
| `_ems_event_promo_codes` | array | Promo codes configuration |

---

## Session CPT

**File:** `class-ems-cpt-session.php`  
**Production Path:** `event-management-system/includes/cpt/class-ems-cpt-session.php`

### Post Type Registration

```php
class EMS_CPT_Session {
    
    public function register_post_type() {
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'ems-dashboard',
            'capability_type'     => array( 'ems_session', 'ems_sessions' ),
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'editor', 'custom-fields' ),
            'show_in_rest'        => true,
        );

        register_post_type( 'ems_session', $args );
    }
}
```

### Session Meta Fields

| Meta Key | Type | Description |
|----------|------|-------------|
| `_ems_session_event_id` | int | Parent event ID |
| `_ems_session_date` | date | Session date |
| `_ems_session_start_time` | time | Start time |
| `_ems_session_end_time` | time | End time |
| `_ems_session_room` | string | Room/location |
| `_ems_session_type` | string | lecture/workshop/keynote/etc |
| `_ems_session_capacity` | int | Maximum attendees |
| `_ems_session_speakers` | array | Speaker user IDs |
| `_ems_session_abstract_id` | int | Linked abstract ID |
| `_ems_session_track` | string | Track/stream |

---

## Abstract CPT

**File:** `class-ems-cpt-abstract.php`  
**Production Path:** `event-management-system/includes/cpt/class-ems-cpt-abstract.php`

### Post Type Registration

```php
class EMS_CPT_Abstract {
    
    public function register_post_type() {
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'ems-dashboard',
            'capability_type'     => array( 'ems_abstract', 'ems_abstracts' ),
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'editor', 'author', 'custom-fields' ),
            'show_in_rest'        => true,
        );

        register_post_type( 'ems_abstract', $args );
    }
}
```

### Abstract Meta Fields

| Meta Key | Type | Description |
|----------|------|-------------|
| `_ems_abstract_event_id` | int | Parent event ID |
| `_ems_abstract_authors` | array | Co-author details |
| `_ems_abstract_keywords` | array | Keywords |
| `_ems_abstract_presentation_type` | string | oral/poster/workshop |
| `_ems_abstract_track` | string | Submission track |
| `_ems_abstract_file` | string | Uploaded file path |
| `_ems_abstract_status` | string | submitted/under_review/approved/rejected/revision_requested |
| `_ems_abstract_reviewers` | array | Assigned reviewer IDs |
| `_ems_abstract_decision` | string | Final decision |
| `_ems_abstract_decision_notes` | text | Decision notes |

---

## Sponsor CPT

**File:** `class-ems-cpt-sponsor.php`  
**Production Path:** `event-management-system/includes/cpt/class-ems-cpt-sponsor.php`

```php
class EMS_CPT_Sponsor {
    
    public function register_post_type() {
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'ems-dashboard',
            'rewrite'             => array( 'slug' => 'sponsors' ),
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'        => true,
        );

        register_post_type( 'ems_sponsor', $args );
    }
    
    public function register_taxonomies() {
        // Sponsor Level taxonomy (Gold, Silver, Bronze, etc.)
        register_taxonomy( 'ems_sponsor_level', 'ems_sponsor', array(
            'labels'            => array( ... ),
            'hierarchical'      => true,
            'public'            => true,
            'show_in_rest'      => true,
        ));
    }
}
```

---

## Admin Assets

### CSS: `ems-admin.css`
**Production Path:** `event-management-system/admin/css/ems-admin.css`

Key styles for:
- Dashboard cards and statistics
- Registration list tables
- Abstract review interface
- Schedule builder drag-and-drop
- Settings forms

### JS: `ems-admin.js`
**Production Path:** `event-management-system/admin/js/ems-admin.js`

Key functionality:
- AJAX registration loading/filtering
- Abstract reviewer assignment
- Schedule builder interactions
- Meta box dynamic fields
- Settings validation

---

## View Files

### `abstract-review.php`
**Production Path:** `event-management-system/admin/views/abstract-review.php`

Single abstract review interface with:
- Abstract details display
- Scoring form (1-10)
- Recommendation dropdown
- Comments textarea
- File viewer/download

### `reviewer-dashboard.php`
**Production Path:** `event-management-system/admin/views/reviewer-dashboard.php`

Reviewer's queue showing:
- Assigned abstracts list
- Review status (pending/completed)
- Quick access links
- Statistics/progress
