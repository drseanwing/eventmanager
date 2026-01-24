# EMS Project Manifest

**Last Updated:** January 2026  
**Plugin Version:** 1.3.0  
**Theme Version:** 1.1.0  
**Total Files:** 47  
**Total Code:** ~16,000 lines PHP, ~3,000 lines CSS, ~2,000 lines JS

---

## Production Directory Structure

### Plugin: `event-management-system/`

```
event-management-system/
├── event-management-system.php          # Main plugin file
├── README.md                            # Plugin documentation
│
├── includes/
│   ├── class-ems-core.php               # Core orchestrator
│   ├── class-ems-loader.php             # Hook/filter loader
│   ├── class-ems-activator.php          # Activation routines + DB schema
│   ├── class-ems-deactivator.php        # Deactivation cleanup
│   │
│   ├── cpt/                             # Custom Post Types
│   │   ├── class-ems-cpt-event.php      # Event CPT
│   │   ├── class-ems-cpt-session.php    # Session CPT
│   │   ├── class-ems-cpt-abstract.php   # Abstract CPT
│   │   └── class-ems-cpt-sponsor.php    # Sponsor CPT
│   │
│   ├── registration/                    # Registration Module
│   │   ├── class-ems-registration.php   # Core registration logic
│   │   ├── class-ems-ticketing.php      # Ticket types, pricing, promos
│   │   ├── class-ems-waitlist.php       # Waitlist management
│   │   └── class-ems-payment-gateway.php # Payment abstraction + invoice
│   │
│   ├── abstracts/                       # Abstract Submission Module
│   │   ├── class-ems-abstract-submission.php  # Submission handling
│   │   └── class-ems-abstract-review.php      # Review workflow
│   │
│   ├── schedule/                        # Schedule Module
│   │   ├── class-ems-schedule-builder.php     # Admin schedule builder
│   │   ├── class-ems-schedule-display.php     # Frontend rendering
│   │   └── class-ems-session-registration.php # Session registration
│   │
│   ├── files/                           # File Management
│   │   ├── class-ems-file-manager.php         # Upload handling
│   │   └── class-ems-file-access-control.php  # Security/permissions
│   │
│   ├── notifications/                   # Email System
│   │   └── class-ems-email-manager.php  # All email templates/sending
│   │
│   └── utilities/                       # Helper Classes
│       ├── class-ems-logger.php         # Logging system
│       ├── class-ems-security.php       # Security helpers
│       ├── class-ems-validator.php      # Input validation
│       ├── class-ems-user-helper.php    # User/role utilities
│       ├── class-ems-date-helper.php    # Date formatting
│       └── class-ems-roles.php          # Custom roles/capabilities
│
├── admin/
│   ├── class-ems-admin.php              # Admin interface
│   ├── css/
│   │   └── ems-admin.css                # Admin styles
│   ├── js/
│   │   └── ems-admin.js                 # Admin JavaScript
│   └── views/
│       ├── abstract-review.php          # Reviewer interface
│       └── reviewer-dashboard.php       # Reviewer dashboard
│
├── public/
│   ├── class-ems-public.php             # Frontend controller
│   ├── shortcodes/
│   │   ├── class-ems-abstract-shortcodes.php  # Abstract shortcodes
│   │   └── class-ems-schedule-shortcodes.php  # Schedule shortcodes
│   ├── css/
│   │   ├── ems-public.css               # Frontend styles
│   │   └── ems-schedule.css             # Schedule-specific styles
│   └── js/
│       ├── ems-public.js                # Frontend JavaScript
│       └── ems-schedule.js              # Schedule interactions
│
├── templates/                           # (Future: template overrides)
│
└── languages/                           # Translations
```

### Theme: `conference-starter/`

```
conference-starter/
├── style.css                            # Main stylesheet + design tokens
├── functions.php                        # Theme functions + EMS integration
├── README.md                            # Theme documentation
│
├── header.php                           # Site header
├── footer.php                           # Site footer
├── index.php                            # Default template
├── page.php                             # Page template
├── single.php                           # Single post template
├── archive.php                          # Archive template
├── search.php                           # Search results
├── searchform.php                       # Search form
├── 404.php                              # Not found
├── sidebar.php                          # Sidebar
├── comments.php                         # Comments template
│
├── single-event.php                     # EMS: Single event template
├── archive-event.php                    # EMS: Event archive template
│
├── inc/
│   └── customizer.php                   # Customizer settings
│
├── css/
│   └── ems-integration.css              # EMS styling enhancements
│
└── js/
    ├── main.js                          # Theme JavaScript
    ├── customizer.js                    # Customizer preview
    └── ems-blocks.js                    # Gutenberg block definitions
```

---

## File-to-Folder Mapping

This maps the flat files in `/mnt/project/` to their production locations.

### Plugin Files

| Flat File | Production Path |
|-----------|-----------------|
| `event-management-system.php` | `event-management-system/event-management-system.php` |
| `class-ems-core.php` | `event-management-system/includes/class-ems-core.php` |
| `class-ems-loader.php` | `event-management-system/includes/class-ems-loader.php` |
| `class-ems-activator.php` | `event-management-system/includes/class-ems-activator.php` |
| `class-ems-deactivator.php` | `event-management-system/includes/class-ems-deactivator.php` |
| `class-ems-cpt-event.php` | `event-management-system/includes/cpt/class-ems-cpt-event.php` |
| `class-ems-cpt-session.php` | `event-management-system/includes/cpt/class-ems-cpt-session.php` |
| `class-ems-cpt-abstract.php` | `event-management-system/includes/cpt/class-ems-cpt-abstract.php` |
| `class-ems-cpt-sponsor.php` | `event-management-system/includes/cpt/class-ems-cpt-sponsor.php` |
| `class-ems-registration.php` | `event-management-system/includes/registration/class-ems-registration.php` |
| `class-ems-ticketing.php` | `event-management-system/includes/registration/class-ems-ticketing.php` |
| `class-ems-waitlist.php` | `event-management-system/includes/registration/class-ems-waitlist.php` |
| `class-ems-payment-gateway.php` | `event-management-system/includes/registration/class-ems-payment-gateway.php` |
| `class-ems-abstract-submission.php` | `event-management-system/includes/abstracts/class-ems-abstract-submission.php` |
| `class-ems-abstract-review.php` | `event-management-system/includes/abstracts/class-ems-abstract-review.php` |
| `class-ems-schedule-builder.php` | `event-management-system/includes/schedule/class-ems-schedule-builder.php` |
| `class-ems-schedule-display.php` | `event-management-system/includes/schedule/class-ems-schedule-display.php` |
| `class-ems-session-registration.php` | `event-management-system/includes/schedule/class-ems-session-registration.php` |
| `class-ems-file-manager.php` | `event-management-system/includes/files/class-ems-file-manager.php` |
| `class-ems-file-access-control.php` | `event-management-system/includes/files/class-ems-file-access-control.php` |
| `class-ems-email-manager.php` | `event-management-system/includes/notifications/class-ems-email-manager.php` |
| `class-ems-logger.php` | `event-management-system/includes/utilities/class-ems-logger.php` |
| `class-ems-security.php` | `event-management-system/includes/utilities/class-ems-security.php` |
| `class-ems-validator.php` | `event-management-system/includes/utilities/class-ems-validator.php` |
| `class-ems-user-helper.php` | `event-management-system/includes/utilities/class-ems-user-helper.php` |
| `class-ems-date-helper.php` | `event-management-system/includes/utilities/class-ems-date-helper.php` |
| `class-ems-roles.php` | `event-management-system/includes/utilities/class-ems-roles.php` |
| `class-ems-admin.php` | `event-management-system/admin/class-ems-admin.php` |
| `ems-admin.css` | `event-management-system/admin/css/ems-admin.css` |
| `ems-admin.js` | `event-management-system/admin/js/ems-admin.js` |
| `abstract-review.php` | `event-management-system/admin/views/abstract-review.php` |
| `reviewer-dashboard.php` | `event-management-system/admin/views/reviewer-dashboard.php` |
| `class-ems-public.php` | `event-management-system/public/class-ems-public.php` |
| `class-ems-abstract-shortcodes.php` | `event-management-system/public/shortcodes/class-ems-abstract-shortcodes.php` |
| `class-ems-schedule-shortcodes.php` | `event-management-system/public/shortcodes/class-ems-schedule-shortcodes.php` |
| `ems-public.css` | `event-management-system/public/css/ems-public.css` |
| `ems-schedule.css` | `event-management-system/public/css/ems-schedule.css` |
| `ems-public.js` | `event-management-system/public/js/ems-public.js` |
| `ems-schedule.js` | `event-management-system/public/js/ems-schedule.js` |

### Theme Files

| Flat File | Production Path |
|-----------|-----------------|
| `style.css` | `conference-starter/style.css` |
| `functions.php` | `conference-starter/functions.php` |
| `header.php` | `conference-starter/header.php` |
| `footer.php` | `conference-starter/footer.php` |
| `index.php` | `conference-starter/index.php` |
| `page.php` | `conference-starter/page.php` |
| `single.php` | `conference-starter/single.php` |
| `archive.php` | `conference-starter/archive.php` |
| `search.php` | `conference-starter/search.php` |
| `searchform.php` | `conference-starter/searchform.php` |
| `404.php` | `conference-starter/404.php` |
| `sidebar.php` | `conference-starter/sidebar.php` |
| `comments.php` | `conference-starter/comments.php` |
| `single-event.php` | `conference-starter/single-event.php` |
| `archive-event.php` | `conference-starter/archive-event.php` |
| `customizer.php` | `conference-starter/inc/customizer.php` |
| `ems-integration.css` | `conference-starter/css/ems-integration.css` |
| `main.js` | `conference-starter/js/main.js` |
| `customizer.js` | `conference-starter/js/customizer.js` |
| `ems-blocks.js` | `conference-starter/js/ems-blocks.js` |
| `README.md` | `conference-starter/README.md` |

---

## Database Schema

### Tables Created by Plugin

```sql
-- Registrations
wp_ems_registrations (
    id, event_id, user_id, email, first_name, last_name,
    discipline, specialty, seniority, organization,
    dietary_requirements, accessibility_requirements,
    ticket_type, ticket_quantity, promo_code,
    payment_status, payment_method, amount_paid, transaction_id,
    status, cancelled_at, cancelled_by, cancellation_reason,
    registered_at, confirmed_at
)

-- Waitlist
wp_ems_waitlist (
    id, event_id, session_id, user_id, email, first_name, last_name,
    discipline, specialty, seniority,
    position, status, notified_at, created_at, converted_at
)

-- Abstract Submissions
wp_ems_abstracts (
    id, event_id, user_id, title, authors, abstract_text,
    keywords, presentation_type, file_path,
    status, submitted_at, updated_at
)

-- Abstract Reviews
wp_ems_abstract_reviews (
    id, abstract_id, reviewer_id, score, recommendation,
    comments, reviewed_at
)

-- Session Registrations
wp_ems_session_registrations (
    id, event_id, session_id, user_id, status, registered_at
)

-- Notification Log
wp_ems_notification_log (
    id, entity_type, entity_id, notification_type,
    recipient_email, sent_at, status, message_id
)
```

---

## Known Fixes & Updates

These fixes were applied during development and should be verified in production code:

### 1. Duplicate Method Fix (class-ems-registration.php)
**Issue:** `cancel_registration()` method declared twice (lines ~460 and ~833)
**Fix:** Remove the older, simpler version; keep the comprehensive v1.3.0 version

### 2. JavaScript Variable Name (class-ems-public.php)
**Issue:** Inline JavaScript used `ems_ajax.ajax_url` but localized script uses `ems_public`
**Fix:** Change all `ems_ajax` references to `ems_public` in inline JavaScript

### 3. Database Auto-Upgrade (class-ems-core.php)
**Addition:** Added `maybe_upgrade_database()` method to handle schema updates without requiring deactivation/reactivation

### 4. Registration Cancellation System
**Addition:** Full cancellation with `cancelled_at`, `cancelled_by`, `cancellation_reason` columns

---

## Installation Steps

### Fresh Installation

1. Upload `event-management-system/` to `/wp-content/plugins/`
2. Upload `conference-starter/` to `/wp-content/themes/`
3. Activate plugin via **Plugins > Installed Plugins**
4. Activate theme via **Appearance > Themes**
5. Plugin creates database tables on activation
6. Go to **Events > Settings** to configure

### Update from Previous Version

1. Backup database
2. Replace plugin folder contents
3. Database migrations run automatically on next page load
4. Clear any caching plugins

---

## Companion Markdown Documents

| Document | Contents | Use When |
|----------|----------|----------|
| `ems-plugin-core.md` | Main file, loader, activator, deactivator, core, roles, logger, security, validator, user helper, date helper | Working on bootstrap, utilities |
| `ems-plugin-admin.md` | Admin class, admin CSS/JS, all 4 CPT classes | Working on admin interface, post types |
| `ems-plugin-modules.md` | Registration, ticketing, waitlist, payment, abstract submission/review, schedule builder/display, session registration, email manager, file manager | Working on features |
| `ems-plugin-public.md` | Public class, shortcode classes, public CSS/JS, admin views | Working on frontend |
| `theme-core.md` | style.css, functions.php, standard templates | Working on theme basics |
| `theme-integration.md` | EMS integration CSS, customizer, JS, event templates | Working on plugin-theme integration |

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | Jan 2026 | Phase 1: Registration system |
| 1.1.0 | Jan 2026 | Phase 2: Abstract submission |
| 1.2.0 | Jan 2026 | Phase 3: Schedule builder |
| 1.3.0 | Jan 2026 | Cancellation system, bug fixes |
