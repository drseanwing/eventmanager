# Event Management System (EMS)

A comprehensive WordPress plugin for managing events, conferences, sessions, registrations, abstract submissions, and sponsor collaboration.

## Version
- **Plugin Version:** 1.5.0
- **Requires WordPress:** 5.8+
- **Tested up to:** 6.4
- **Requires PHP:** 7.4+
- **License:** GPL v2 or later

## Installation

### Method 1: Download ZIP from GitHub (Recommended)
1. Go to the [GitHub releases page](https://github.com/drseanwing/eventmanager/releases) or click **Code > Download ZIP**
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and click **Install Now**
4. Activate the plugin through **Plugins > Installed Plugins**

### Method 2: Manual Upload
1. Download and extract the plugin
2. Upload the `eventmanager` folder to `/wp-content/plugins/`
3. Rename the folder to `event-management-system` (optional, for cleaner URLs)
4. Activate the plugin through **Plugins > Installed Plugins**

### Method 3: Git Clone (Development)
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/drseanwing/eventmanager.git event-management-system
```

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

### Sponsor Collaboration (v1.5.0)
- **Sponsor Onboarding**: 7-step self-service registration form (`[ems_sponsor_onboarding]`)
- **Expression of Interest (EOI)**: 5-step event sponsorship application (`[ems_sponsor_eoi event_id="123"]`)
- **Sponsor Portal**: Tabbed dashboard for sponsors to manage profile, files, and applications (`[ems_sponsor_portal]`)
- **Sponsorship Levels**: Bronze, Silver, Gold, Platinum + custom levels with slot management
- **Public Display Shortcodes**:
  - `[ems_event_sponsors]` - Display sponsors grouped by level
  - `[ems_sponsor_carousel]` - Auto-scrolling sponsor logo carousel
  - `[ems_sponsor_gallery]` - CSS Grid sponsor gallery
  - `[ems_sponsor_levels]` - Display available sponsorship levels with EOI links
- **Email Notifications**: Automated emails for onboarding, EOI submission, approval/rejection
- **Security**: ABN/ACN validation, rate limiting, audit logging, CSRF protection

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

### Sponsor Shortcodes (v1.5.0)
| Shortcode | Description | Attributes |
|-----------|-------------|------------|
| `[ems_sponsor_onboarding]` | Sponsor registration form | - |
| `[ems_sponsor_eoi]` | EOI submission form | `event_id` (required) |
| `[ems_sponsor_portal]` | Sponsor dashboard | - |
| `[ems_event_sponsors]` | Display event sponsors | `event_id`, `show_logo`, `show_description`, `columns` |
| `[ems_sponsor_carousel]` | Logo carousel | `event_id`, `speed`, `pause_on_hover` |
| `[ems_sponsor_gallery]` | Sponsor gallery grid | `event_id`, `columns` |
| `[ems_sponsor_levels]` | Available levels | `event_id`, `show_eoi_link` |

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Events | `ems_event` | Main event entries |
| Sessions | `ems_session` | Schedule sessions |
| Registrations | `ems_registration` | Registration records |
| Abstracts | `ems_abstract` | Abstract submissions |
| Sponsors | `ems_sponsor` | Sponsor organizations |

## Database Tables

The plugin creates the following custom tables:
- `{prefix}_ems_sponsorship_levels` - Sponsorship level definitions per event
- `{prefix}_ems_sponsor_eoi` - Expression of Interest submissions

## File Structure

```
event-management-system/
├── admin/
│   ├── css/                   # Admin stylesheets
│   ├── js/                    # Admin JavaScript
│   ├── views/                 # Admin page templates
│   └── class-ems-admin.php    # Admin functionality
├── includes/
│   ├── class-ems-core.php     # Core plugin class
│   ├── class-ems-activator.php
│   ├── class-ems-deactivator.php
│   ├── sponsor/               # Sponsor meta and levels
│   ├── collaboration/         # Sponsor portal
│   ├── forms/                 # Multi-step form framework
│   ├── notifications/         # Email manager
│   ├── custom-post-types/     # CPT definitions
│   └── ...
├── public/
│   ├── css/ems-public.css     # Public styles
│   ├── js/                    # Public JavaScript
│   ├── shortcodes/            # All shortcode classes
│   └── class-ems-public.php   # Public functionality
├── templates/                 # Template files
├── logs/                      # Log files (auto-created)
├── event-management-system.php # Main plugin file
└── README.md
```

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Documentation

Detailed documentation is available in the `docs/` folder:
- `SPONSORSHIP-DOCS.md` - Complete sponsorship subsystem documentation
- `SPONSORSHIP-TESTING-GUIDE.md` - Testing procedures
- `SPONSORSHIP-IMPLEMENTATION-PLAN.md` - Technical implementation details

## Changelog

### 1.5.0
- Added complete sponsor collaboration subsystem
- New sponsor onboarding workflow (7-step form)
- Expression of Interest (EOI) submission system
- Sponsor portal with profile management
- Sponsorship levels with slot tracking
- 4 new public display shortcodes
- 7 email notification templates
- ABN/ACN validation for Australian businesses
- Rate limiting and audit logging
- WCAG 2.1 AA accessibility compliance

### 1.4.0
- Added sponsor collaboration foundation
- File upload system for sponsors
- Sponsor portal shortcode

### 1.2.0
- Unified CSS architecture with design token inheritance
- Improved theme compatibility layer
- Enhanced responsive design
- Added print styles for schedules
- Improved accessibility

### 1.1.0
- Added abstract submission system
- Added presenter dashboard
- Improved registration workflow
- Added session capacity management

### 1.0.0
- Initial release

## Support

For plugin support, please [open an issue](https://github.com/drseanwing/eventmanager/issues) on GitHub.

## License

This plugin is licensed under the GPL v2 or later.
