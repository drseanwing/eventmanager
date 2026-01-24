# GitHub Copilot Instructions - Event Management System

## Overview

This repository contains a comprehensive WordPress plugin and theme for event and conference management, specifically designed for events at the Royal Brisbane & Women's Hospital. The system handles registrations, abstract submissions, schedule building, ticketing, waitlists, and sponsor collaboration.

---

## Global Development Standards

### Code Quality Principles

Apply these standards across all components:

1. **Production-Ready Code**: Never use placeholders, TODOs without implementation, or incomplete logic. All code should be deployable.

2. **Comprehensive Commenting**: Include PHPDoc blocks for all classes, methods, and functions. Use inline comments for complex logic.

3. **Verbose Logging**: Use the EMS Logger class (`class-ems-logger.php`) for all operations with structured output including timestamps, log levels, and contextual data.

4. **Graceful Error Handling**: Implement try-catch blocks with specific error types, meaningful error messages, and fallback behaviors. Never allow silent failures.

5. **Modular Architecture**: Separate concerns into distinct modules/classes. Each function should do one thing well. Dependencies should be injectable.

6. **Security First**: All input must be validated and sanitized. Use WordPress functions (`sanitize_*`, `esc_*`, `wp_nonce_*`). Follow OWASP guidelines.

### WordPress Naming Conventions

| Context | Convention | Example |
|---------|------------|---------|
| PHP Classes | Prefixed PascalCase | `EMS_Registration`, `EMS_Abstract_Submission` |
| PHP Methods | snake_case | `register_participant()`, `submit_abstract()` |
| PHP Constants | Prefixed SCREAMING_SNAKE | `EMS_VERSION`, `EMS_PLUGIN_DIR` |
| Hook Names | lowercase with underscores | `ems_after_registration`, `ems_abstract_submitted` |
| CSS Classes | kebab-case with prefix | `.ems-registration-form`, `.ems-schedule-grid` |
| JavaScript Functions | camelCase | `handleRegistration()`, `validateForm()` |
| Database Tables | Prefixed snake_case (plural) | `wp_ems_registrations`, `wp_ems_abstracts` |
| Database Columns | snake_case | `created_at`, `payment_status` |
| Custom Post Types | lowercase with dashes | `event`, `session`, `abstract`, `sponsor` |
| Shortcodes | lowercase with dashes | `[ems-registration-form]`, `[ems-schedule]` |

### Logging Standards

```php
// Use EMS Logger for all logging
$logger = new EMS_Logger();

$logger->log('info', 'Registration started', array(
    'event_id' => $event_id,
    'user_id' => $user_id,
    'ticket_type' => $ticket_type
));

$logger->log('error', 'Payment failed', array(
    'registration_id' => $reg_id,
    'error' => $error_message,
    'gateway' => $gateway
));
```

### Error Handling Pattern

```php
// Always wrap database operations and external API calls
try {
    $result = $this->process_registration($data);
    
    $this->logger->log('info', 'Registration processed', array(
        'registration_id' => $result['id'],
        'status' => 'success'
    ));
    
    return $result;
    
} catch (Exception $e) {
    $this->logger->log('error', 'Registration failed', array(
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ));
    
    // Graceful fallback - show user-friendly error
    return new WP_Error('registration_failed', 
        __('Unable to process registration. Please try again.', 'event-management-system'));
}
```

---

## Project-Specific Instructions

### Technology Stack

- **Platform**: WordPress 5.8+ (PHP 7.4+)
- **Database**: MySQL/MariaDB via WordPress $wpdb
- **Frontend**: Vanilla JavaScript (ES6+), jQuery where necessary
- **CSS**: Custom properties (CSS variables) with GrandConference design tokens
- **Theme**: Conference Starter (custom theme)
- **Plugin**: Event Management System (EMS)

### Architecture Overview

**Plugin Structure** (`plugin/`):
```
plugin/
├── event-management-system.php          # Main plugin file
├── includes/                            # Core classes
│   ├── class-ems-core.php               # Core orchestrator
│   ├── class-ems-loader.php             # Hook/filter loader
│   ├── class-ems-activator.php          # Activation & DB schema
│   ├── custom-post-types/               # Event, Session, Abstract, Sponsor
│   ├── registration/                    # Registration, Ticketing, Waitlist, Payments
│   ├── abstracts/                       # Abstract submission & review
│   ├── schedule/                        # Schedule builder & display
│   ├── files/                           # File management & access control
│   ├── notifications/                   # Email system
│   ├── utilities/                       # Logger, Security, Validator, Helpers
│   └── user-roles/                      # Custom roles & capabilities
├── admin/                               # Admin interface
│   ├── class-ems-admin.php
│   ├── views/                           # Admin template files
│   └── css/js/                          # Admin assets
└── public/                              # Frontend
    ├── class-ems-public.php
    ├── shortcodes/                      # Frontend shortcodes
    └── css/js/                          # Public assets
```

**Theme Structure** (`theme/conference-starter/`):
```
theme/conference-starter/
├── style.css                            # Main stylesheet with design tokens
├── functions.php                        # Theme setup
├── inc/                                 # Theme functions
│   ├── ems-integration.php              # EMS plugin integration
│   ├── customizer.php                   # WordPress Customizer
│   └── template-*.php                   # Helper functions
├── single-event.php                     # Single event template
├── archive-event.php                    # Event archive template
└── assets/                              # Theme assets
    ├── css/ems-integration.css          # EMS-specific styling
    └── js/                              # Theme JavaScript
```

### Database Schema Conventions

All EMS tables use the `wp_ems_` prefix and include standard audit columns:

```php
// Standard columns for all tables
'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
'updated_at' => 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',

// Use these column names consistently
'user_id' => 'bigint(20) unsigned',          // WordPress user ID
'event_id' => 'bigint(20) unsigned',         // Event post ID
'status' => 'varchar(20)',                   // e.g., 'pending', 'confirmed', 'cancelled'
'payment_status' => 'varchar(20)',           // e.g., 'unpaid', 'paid', 'refunded'
```

**Key Tables**:
- `wp_ems_registrations` - Event registrations with ticketing
- `wp_ems_waitlist` - Waitlist for sold-out events/sessions
- `wp_ems_abstracts` - Abstract submissions
- `wp_ems_abstract_reviews` - Peer review system
- `wp_ems_session_registrations` - Individual session bookings
- `wp_ems_notification_log` - Email tracking

### WordPress Integration Patterns

**Custom Post Types**:
```php
// All CPTs extend EMS_CPT_Abstract class
class EMS_CPT_Event extends EMS_CPT_Abstract {
    protected $post_type = 'event';
    protected $singular = 'Event';
    protected $plural = 'Events';
    
    protected function get_post_type_args() {
        return array(
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-calendar-alt',
            // ...
        );
    }
}
```

**Custom Meta Boxes**:
```php
// Use nonce verification for all meta box saves
public function save_meta_box($post_id, $post) {
    // Verify nonce
    if (!isset($_POST['ems_event_meta_nonce']) || 
        !wp_verify_nonce($_POST['ems_event_meta_nonce'], 'ems_event_meta')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Sanitize and save
    $event_date = sanitize_text_field($_POST['ems_event_date']);
    update_post_meta($post_id, '_ems_event_date', $event_date);
}
```

**AJAX Handlers**:
```php
// Always check nonce and capabilities
public function ajax_register_participant() {
    check_ajax_referer('ems_public_nonce', 'nonce');
    
    // Validate input
    $validator = new EMS_Validator();
    $data = $validator->validate_registration_data($_POST);
    
    if (is_wp_error($data)) {
        wp_send_json_error($data->get_error_message());
    }
    
    // Process registration
    $result = $this->registration->register_participant($data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success($result);
}
```

**Shortcodes**:
```php
// Always return output, never echo
public function registration_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'event_id' => 0,
        'show_title' => 'yes',
    ), $atts, 'ems-registration-form');
    
    // Start output buffering
    ob_start();
    
    // Include template
    include EMS_PLUGIN_DIR . 'public/views/registration-form.php';
    
    return ob_get_clean();
}
```

### Security Guidelines

**Input Validation**:
```php
// Use EMS_Validator class for complex validation
$validator = new EMS_Validator();

// Email validation
if (!$validator->is_valid_email($email)) {
    return new WP_Error('invalid_email', 'Invalid email address');
}

// Required fields
if (!$validator->has_required_fields($data, array('first_name', 'last_name', 'email'))) {
    return new WP_Error('missing_fields', 'Required fields missing');
}
```

**Sanitization**:
```php
// Always sanitize input
$email = sanitize_email($_POST['email']);
$first_name = sanitize_text_field($_POST['first_name']);
$abstract_text = sanitize_textarea_field($_POST['abstract_text']);

// For arrays
$attendees = array_map('sanitize_text_field', $_POST['attendees']);
```

**Escaping Output**:
```php
// Always escape output
echo esc_html($user_name);
echo esc_url($registration_url);
echo esc_attr($data_value);
echo wp_kses_post($formatted_content);
```

**File Uploads**:
```php
// Use EMS_File_Manager for all file operations
$file_manager = new EMS_File_Manager();
$security = new EMS_Security();

// Validate file before upload
if (!$security->is_allowed_file_type($_FILES['abstract_file'])) {
    return new WP_Error('invalid_file', 'File type not allowed');
}

// Upload with proper permissions
$result = $file_manager->upload_file($_FILES['abstract_file'], array(
    'entity_type' => 'abstract',
    'entity_id' => $abstract_id,
    'uploaded_by' => get_current_user_id()
));
```

### Email Notification Standards

```php
// Use EMS_Email_Manager for all emails
$email_manager = new EMS_Email_Manager();

// Send registration confirmation
$email_manager->send_registration_confirmation(array(
    'to' => $registration->email,
    'registration_id' => $registration->id,
    'event_id' => $registration->event_id,
    'first_name' => $registration->first_name,
    'ticket_type' => $registration->ticket_type,
    'amount_paid' => $registration->amount_paid
));

// All emails are logged to wp_ems_notification_log
```

### Frontend JavaScript Patterns

**AJAX Calls**:
```javascript
// Use ems_public object (localized script)
jQuery(document).ready(function($) {
    $('#ems-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ems_public.ajax_url,
            type: 'POST',
            data: {
                action: 'ems_register_participant',
                nonce: ems_public.nonce,
                form_data: $(this).serialize()
            },
            success: function(response) {
                if (response.success) {
                    // Handle success
                    showSuccessMessage(response.data);
                } else {
                    // Handle error
                    showErrorMessage(response.data);
                }
            },
            error: function() {
                showErrorMessage('An error occurred. Please try again.');
            }
        });
    });
});
```

### CSS Design Tokens

All styling should use CSS custom properties defined in `style.css`:

```css
/* Typography */
--font-body: 'Work Sans', sans-serif;
--font-heading: 'Poppins', sans-serif;
--font-size-base: 14px;

/* Colors */
--color-primary: #222222;
--color-accent: #1EC6B6;
--color-accent-blue: #007bff;
--color-accent-pink: #FF2D55;

/* Spacing */
--spacing-xs: 5px;
--spacing-sm: 10px;
--spacing-md: 20px;
--spacing-lg: 40px;

/* Usage */
.ems-form-field {
    font-family: var(--font-body);
    color: var(--color-text);
    margin-bottom: var(--spacing-md);
}
```

### Custom User Roles & Capabilities

The plugin defines custom roles:
- **Event Organizer**: Full event management
- **Abstract Reviewer**: Review submitted abstracts
- **Session Manager**: Manage sessions and schedules
- **Participant**: Register for events (default)

```php
// Check capabilities
if (!current_user_can('manage_ems_events')) {
    wp_die(__('You do not have permission to access this page.', 'event-management-system'));
}

// Custom capabilities
'edit_ems_events'
'publish_ems_events'
'manage_ems_registrations'
'review_ems_abstracts'
'manage_ems_schedule'
```

### Testing & Development

**Testing Registration Workflow**:
1. Create event post
2. Add ticket types via meta box
3. Use shortcode `[ems-registration-form event_id="123"]`
4. Test with various scenarios (sold out, promo codes, waitlist)
5. Verify emails sent and logged

**Common Debug Locations**:
- Logs: `wp-content/uploads/ems-logs/`
- Uploads: `wp-content/uploads/ems/`
- Error logs: Check PHP error log and WordPress debug.log

**Enable WordPress Debug Mode**:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Theme Integration Best Practices

**Check for Plugin Active**:
```php
// In theme functions
if (class_exists('EMS_Core')) {
    // EMS plugin is active
    // Add theme-specific EMS customizations
}
```

**Override EMS Templates**:
```php
// Theme can override plugin templates by creating:
// theme/conference-starter/ems/registration-form.php
// theme/conference-starter/ems/schedule-grid.php
```

**Enqueue Dependencies**:
```php
// Theme should enqueue EMS integration styles
wp_enqueue_style('ems-integration', 
    get_template_directory_uri() . '/assets/css/ems-integration.css',
    array('conference-starter-style'),
    '1.0.0'
);
```

---

## Key Development Patterns

### Registration Module Pattern
1. Validate user input (EMS_Validator)
2. Check event capacity and ticket availability
3. Process payment if required (EMS_Payment_Gateway)
4. Create registration record in database
5. Send confirmation email (EMS_Email_Manager)
6. Log all steps (EMS_Logger)

### Abstract Submission Pattern
1. Verify user authentication
2. Validate abstract data and uploaded file
3. Store file securely (EMS_File_Manager)
4. Create abstract record with pending status
5. Notify event organizer
6. Send confirmation to submitter

### Schedule Builder Pattern
1. Drag-and-drop interface in admin
2. AJAX saves session assignments
3. Conflict detection (same time/room)
4. Publish to frontend via shortcode
5. Session registration with capacity limits

---

## WordPress Coding Standards

Follow WordPress PHP Coding Standards:
- Use tabs for indentation
- Brace style: opening brace on same line
- Space after control structures: `if ( condition )`
- Yoda conditions: `if ( 5 === $value )`
- No shorthand PHP tags
- Proper PHPDoc blocks

---

## Common Tasks & Solutions

### Add New Custom Post Type
1. Create class extending `EMS_CPT_Abstract` in `includes/custom-post-types/`
2. Register in `EMS_Core::load_dependencies()`
3. Add meta boxes via `add_meta_boxes` hook
4. Create frontend template in theme: `single-{post_type}.php`

### Add New Email Template
1. Add method in `EMS_Email_Manager`
2. Use WordPress `wp_mail()` function
3. Log to `wp_ems_notification_log`
4. Include unsubscribe link where appropriate

### Add New Shortcode
1. Create method in appropriate shortcode class
2. Register via `add_shortcode()`
3. Return output (never echo)
4. Support standard shortcode attributes

### Add New AJAX Endpoint
1. Add handler in admin or public class
2. Register via `wp_ajax_` and `wp_ajax_nopriv_` hooks
3. Verify nonce
4. Return JSON via `wp_send_json_success()` or `wp_send_json_error()`

---

## Known Issues & Workarounds

### Duplicate Method Fix (v1.3.0)
**Issue**: `cancel_registration()` was declared twice in class-ems-registration.php
**Resolution**: Keep the comprehensive version with full audit trail

### JavaScript Localization
**Issue**: Inline scripts used `ems_ajax` but localized script is `ems_public`
**Resolution**: All JavaScript should use `ems_public.ajax_url` and `ems_public.nonce`

### Database Auto-Upgrade
**Feature**: Database schema updates run automatically via `EMS_Core::maybe_upgrade_database()`
**Note**: No need to deactivate/reactivate plugin for schema changes

---

## Future Development (Phase 4)

These features are planned but not yet implemented:
- Sponsor portal (stub exists: `class-ems-sponsor-portal.php`)
- Enhanced waitlist display options
- Advanced reporting dashboard
- Integration with external payment gateways

When implementing these, follow existing patterns and maintain backward compatibility.

---

## Documentation References

Companion documentation files in repository root:
- `ems-plugin-core.md` - Core classes and utilities
- `ems-plugin-admin.md` - Admin interface and CPTs
- `ems-plugin-modules.md` - Registration, abstracts, schedule
- `ems-plugin-public.md` - Frontend and shortcodes
- `theme-core.md` - Theme structure and templates
- `theme-integration.md` - EMS-theme integration
- `project-manifest.md` - Complete project overview
- `BUILD-STATUS.md` - Current status and to-do list

---

## When Copilot Generates Code

1. **Check the context**: Understand if you're working in plugin or theme
2. **Apply security standards**: Validate, sanitize, escape, check nonces
3. **Use existing patterns**: Follow class structure and naming conventions
4. **Add comprehensive logging**: Use EMS_Logger for all operations
5. **Handle errors gracefully**: Return WP_Error objects with user-friendly messages
6. **Document thoroughly**: Add PHPDoc blocks for all public methods
7. **Consider integration**: How does this connect to existing modules?
8. **Test thoroughly**: Verify with various user roles and edge cases

If uncertain about implementation details, refer to existing similar functionality in the codebase.
