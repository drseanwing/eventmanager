# Event Management System (EMS)

A comprehensive WordPress plugin for managing events, conferences, sessions, registrations, and abstract submissions.

## Version
- **Plugin Version:** 1.2.0
- **Requires WordPress:** 5.9+
- **Tested up to:** 6.4
- **Requires PHP:** 7.4+
- **License:** GPL v2 or later

## Features

### Event Management
- Create and manage events with full metadata (dates, location, venue, timezone)
- Featured images and rich content
- Event archives with filtering
- iCal export for calendar integration

### Session Scheduling
- Multi-track session management
- Session types: Keynote, Workshop, Panel, Presentation, Break
- Location and speaker assignment
- Capacity management with registration limits
- Timeline, grid, and list views

### Registration System
- Event registration with custom fields
- Session-specific registration
- Capacity tracking and waitlists
- User dashboard for managing registrations
- Email notifications

### Abstract Submission
- Multi-step abstract submission workflow
- File upload support (PDF, DOC, DOCX)
- Review and approval workflow
- Presenter dashboard
- Status tracking (Pending, Approved, Rejected, Revision Requested)

## Shortcodes

### Event Shortcodes
| Shortcode | Description | Attributes |
|-----------|-------------|------------|
| `[ems_event_list]` | Display event grid | `limit`, `show_past`, `columns` |
| `[ems_upcoming_events]` | Compact upcoming events | `limit` |
| `[ems_event_details]` | Single event details | `id` |

### Schedule Shortcodes
| Shortcode | Description | Attributes |
|-----------|-------------|------------|
| `[ems_schedule]` | Full event schedule | `event_id`, `view`, `group_by`, `show_filters`, `show_search` |
| `[ems_my_schedule]` | User's registered sessions | `event_id` |
| `[ems_session]` | Single session display | `id` |

### Registration Shortcodes
| Shortcode | Description | Attributes |
|-----------|-------------|------------|
| `[ems_registration_form]` | Registration form | `event_id` |
| `[ems_my_registrations]` | User's registrations | - |

### Abstract Shortcodes
| Shortcode | Description | Attributes |
|-----------|-------------|------------|
| `[ems_abstract_submission]` | Abstract submission form | `event_id` |
| `[ems_presenter_dashboard]` | Presenter abstract management | - |
| `[ems_abstract_list]` | List abstracts (admin) | `event_id`, `status` |

## CSS Architecture

### Design Token Integration
The plugin CSS is built to inherit from theme design tokens, ensuring visual consistency:

```css
/* Plugin CSS uses fallback pattern */
--ems-color-primary: var(--color-primary, #222222);
--ems-spacing-lg: var(--spacing-lg, 20px);
--ems-font-body: var(--font-body, 'Work Sans', sans-serif);
```

### Theme Compatibility
When used with the Conference Starter theme, the plugin automatically inherits:
- Typography (fonts, sizes, weights)
- Color palette (primary, accent, backgrounds)
- Spacing system
- Border radius and shadows
- Button styles
- Form input styling

### Standalone Mode
When used without a compatible theme, the plugin provides complete default styling with professional appearance.

## File Structure

```
event-management-system/
├── admin/
│   ├── css/                   # Admin stylesheets
│   ├── js/                    # Admin JavaScript
│   └── class-ems-admin.php    # Admin functionality
├── includes/
│   ├── class-ems-activator.php
│   ├── class-ems-deactivator.php
│   ├── class-ems-loader.php
│   ├── class-ems-post-types.php
│   ├── class-ems-schedule.php
│   ├── class-ems-schedule-shortcodes.php
│   ├── class-ems-abstract-shortcodes.php
│   ├── class-ems-registrations.php
│   └── ...
├── public/
│   ├── css/
│   │   ├── ems-public.css     # Core public styles
│   │   └── ems-schedule.css   # Schedule-specific styles
│   ├── js/
│   │   └── ems-public.js      # Public JavaScript
│   └── class-ems-public.php   # Public functionality
├── logs/                       # Log files
├── event-management-system.php # Main plugin file
└── README.md
```

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Events | `event` | Main event entries |
| Sessions | `session` | Schedule sessions |
| Registrations | `ems_registration` | Registration records |
| Abstracts | `ems_abstract` | Abstract submissions |

## AJAX Endpoints

| Action | Description |
|--------|-------------|
| `ems_register_session` | Register user for session |
| `ems_unregister_session` | Remove session registration |
| `ems_submit_registration` | Submit event registration |
| `ems_submit_abstract` | Submit abstract |

## Hooks & Filters

### Actions
```php
// After event registration
do_action('ems_registration_complete', $registration_id, $event_id, $user_id);

// After session registration
do_action('ems_session_registered', $session_id, $user_id);

// After abstract submission
do_action('ems_abstract_submitted', $abstract_id, $event_id, $user_id);
```

### Filters
```php
// Modify registration form fields
apply_filters('ems_registration_fields', $fields, $event_id);

// Modify schedule output
apply_filters('ems_schedule_html', $html, $event_id, $args);

// Modify session card output
apply_filters('ems_session_card_html', $html, $session, $args);
```

## Installation

1. Upload the `event-management-system` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins > Installed Plugins**
3. Configure settings in **Events > Settings**
4. (Recommended) Use with Conference Starter theme for seamless styling

## Requirements

- WordPress 5.9 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Recommended Theme

For the best experience, use with the **Conference Starter** theme which provides:
- Unified design token system
- Enhanced event templates
- Gutenberg block integration
- Print-optimized schedules

## Changelog

### 1.2.0
- Unified CSS architecture with design token inheritance
- Improved theme compatibility layer
- Enhanced responsive design
- Added print styles for schedules
- Improved accessibility
- Better capacity indicator styling
- Added timeline view for schedules

### 1.1.0
- Added abstract submission system
- Added presenter dashboard
- Improved registration workflow
- Added session capacity management

### 1.0.0
- Initial release

## Support

For plugin support, please contact the developer.

## License

This plugin is licensed under the GPL v2 or later.
