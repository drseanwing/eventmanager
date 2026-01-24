# Event Management System (EMS)

A comprehensive WordPress plugin and theme for managing conferences, courses, and seminars at the Royal Brisbane & Women's Hospital.

## Overview

The Event Management System provides a complete solution for event management including:

- **Event Registration** - Ticketing, promo codes, capacity management, and waitlists
- **Abstract Submission** - Paper/poster submission with peer review workflow
- **Schedule Builder** - Drag-and-drop schedule creation and display
- **Sponsor Portal** - Sponsor file sharing and collaboration (Phase 4)
- **Email Notifications** - Automated confirmations and reminders

## Repository Structure

```
├── plugin/                     # EMS WordPress Plugin (v1.4.0)
│   ├── includes/               # Core PHP classes
│   ├── admin/                  # Admin interface
│   └── public/                 # Frontend shortcodes & assets
│
├── theme/                      # Conference Starter Theme (v2.3.0)
│   └── conference-starter/     # WordPress theme files
│
└── docs/                       # Documentation
    ├── project-manifest.md     # Complete project structure
    ├── BUILD-STATUS.md         # Build status and changelog
    ├── ems-plugin-core.md      # Core plugin documentation
    ├── ems-plugin-admin.md     # Admin module documentation
    ├── ems-plugin-modules.md   # Feature modules documentation
    ├── ems-plugin-public.md    # Frontend documentation
    ├── theme-core.md           # Theme core documentation
    └── theme-integration.md    # EMS-Theme integration docs
```

## Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher (8.0+ recommended)
- **MySQL:** 5.7 or higher / MariaDB 10.3+

## Installation

### Fresh Installation

1. Download and upload `event-management-system/` to `/wp-content/plugins/`
2. Download and upload `conference-starter/` to `/wp-content/themes/`
3. Activate the plugin via **Plugins > Installed Plugins**
4. Activate the theme via **Appearance > Themes**
5. The plugin will create necessary database tables on activation
6. Configure settings at **Events > Settings**

### Updating from Previous Version

1. Backup your database
2. Replace plugin folder contents
3. Database migrations run automatically on next page load
4. Clear any caching plugins

## Features

### Phase 1: Registration System
- Event creation with custom fields
- Ticket types with pricing
- Promo codes and discounts
- Capacity management
- Registration confirmation emails

### Phase 2: Abstract Submission
- Abstract submission form
- File upload support
- Presenter dashboard
- Peer review assignment
- Review scoring and recommendations

### Phase 3: Schedule Builder
- Drag-and-drop schedule interface
- Room/time slot management
- Session registration
- Schedule display (list/grid/timeline views)
- iCal export

### Phase 4: Sponsor Portal & Waitlist (Current)
- Sponsor file uploads and management
- Sponsor-event associations
- Download tracking and statistics
- Enhanced waitlist display options
- Automatic waitlist promotion

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[ems_event_list]` | Display events grid |
| `[ems_upcoming_events]` | List upcoming events |
| `[ems_registration_form event_id="X"]` | Event registration form |
| `[ems_my_registrations]` | User's registrations |
| `[ems_abstract_submission event_id="X"]` | Abstract submission form |
| `[ems_presenter_dashboard]` | Presenter's abstracts |
| `[ems_event_schedule event_id="X"]` | Event schedule display |
| `[ems_my_schedule]` | User's session registrations |
| `[ems_sponsor_portal]` | Sponsor portal dashboard |

## Custom Post Types

- **Events** (`ems_event`) - Main event posts
- **Sessions** (`ems_session`) - Individual sessions within events
- **Abstracts** (`ems_abstract`) - Submitted abstracts
- **Sponsors** (`ems_sponsor`) - Sponsor organizations

## User Roles

- **Event Organizer** - Full event management
- **Abstract Reviewer** - Review submitted abstracts
- **Session Manager** - Manage sessions and schedules
- **Sponsor** - Access sponsor portal
- **Participant** - Register for events (default)

## Database Tables

The plugin creates these custom tables:

- `wp_ems_registrations` - Event registrations
- `wp_ems_session_registrations` - Session registrations
- `wp_ems_waitlist` - Waitlist entries
- `wp_ems_abstract_reviews` - Abstract review records
- `wp_ems_notification_log` - Email notification history
- `wp_ems_sponsor_events` - Sponsor-event associations
- `wp_ems_sponsor_files` - Sponsor file records

## Development

### Version Information

| Component | Version | Status |
|-----------|---------|--------|
| EMS Plugin | 1.4.0 | UAT Ready |
| Conference Starter Theme | 2.3.0 | UAT Ready |

### Documentation

See the markdown files in the repository root for detailed documentation:

- **project-manifest.md** - Complete project structure and file mapping
- **BUILD-STATUS.md** - Current build status and changelog
- **ems-plugin-*.md** - Plugin component documentation
- **theme-*.md** - Theme documentation

## Support

For issues and feature requests, please use the GitHub issue tracker.

## License

- **Plugin:** GPL v2 or later
- **Theme:** GNU General Public License v2 or later

## Credits

Developed for Royal Brisbane & Women's Hospital event management needs.