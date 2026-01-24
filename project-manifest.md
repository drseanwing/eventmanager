# EMS Project Manifest

**Last Updated:** 2026-01-24  
**Plugin Version:** 1.4.0 (UAT Release)  
**Theme Version:** 2.3.0  
**Status:** UAT READY - All Phase 4 features complete (Sponsor Portal & Waitlist)

---

## Overview

The Event Management System (EMS) is a comprehensive WordPress solution for managing conferences, courses, and seminars. It provides:

- **Event Registration** with ticketing, promo codes, and capacity management
- **Abstract Submission** with peer review workflow
- **Schedule Builder** with drag-and-drop interface
- **Sponsor Portal** for file sharing and collaboration (Phase 4)
- **Waitlist Management** with automatic promotion

---

## Current Repository Structure

This document reflects the **actual** current structure of files in the repository.

### Plugin: `plugin/`

```
plugin/
├── event-management-system.php          # Main plugin file (v1.4.0)
├── README.md                            # Plugin documentation
│
├── includes/
│   ├── class-ems-core.php               # Core orchestrator
│   ├── class-ems-loader.php             # Hook/filter loader
│   ├── class-ems-activator.php          # Activation routines + DB schema
│   ├── class-ems-deactivator.php        # Deactivation cleanup
│   │
│   ├── custom-post-types/               # Custom Post Types
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
│   ├── collaboration/                   # Sponsor Collaboration (Phase 4 - COMPLETE)
│   │   └── class-ems-sponsor-portal.php # Full sponsor portal with file management
│   │
│   ├── user-roles/                      # User Roles
│   │   └── class-ems-roles.php          # Custom roles/capabilities
│   │
│   └── utilities/                       # Helper Classes
│       ├── class-ems-logger.php         # Logging system
│       ├── class-ems-security.php       # Security helpers
│       ├── class-ems-validator.php      # Input validation
│       ├── class-ems-user-helper.php    # User/role utilities
│       └── class-ems-date-helper.php    # Date formatting
│
├── admin/
│   ├── class-ems-admin.php              # Admin interface
│   ├── index.php                        # Security placeholder
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
│   ├── index.php                        # Security placeholder
│   ├── shortcodes/
│   │   ├── class-ems-abstract-shortcodes.php  # Abstract shortcodes
│   │   ├── class-ems-schedule-shortcodes.php  # Schedule shortcodes
│   │   └── class-ems-sponsor-shortcodes.php   # Sponsor portal shortcodes (Phase 4)
│   ├── css/
│   │   ├── ems-public.css               # Frontend styles
│   │   └── ems-schedule.css             # Schedule-specific styles
│   └── js/
│       ├── ems-public.js                # Frontend JavaScript
│       └── ems-schedule.js              # Schedule interactions
│
├── logs/                                # Log files directory
│   └── index.php                        # Security placeholder
│
└── index.php                            # Security placeholder
```

### Theme: `theme/conference-starter/`

```
theme/conference-starter/
├── style.css                            # ✅ Main stylesheet + design tokens
├── functions.php                        # ✅ Theme functions
├── README.md                            # ✅ Theme documentation
├── BUILD-STATUS.md                      # ✅ Theme build status
├── HOMEPAGE-README.md                   # ✅ Homepage documentation
├── screenshot.png                       # ✅ Theme preview
│
├── header.php                           # ✅ Site header
├── footer.php                           # ✅ Site footer
├── index.php                            # ✅ Default template
├── page.php                             # ✅ Page template
├── single.php                           # ✅ Single post template
├── archive.php                          # ✅ Archive template
├── search.php                           # ✅ Search results
├── searchform.php                       # ✅ Custom search form
├── 404.php                              # ✅ Not found
├── sidebar.php                          # ✅ Sidebar widget area
├── comments.php                         # ✅ Comments template
│
├── homepage-template.php                # ✅ Homepage template
├── single-event.php                     # ✅ EMS single event template
├── archive-event.php                    # ✅ EMS event archive template
│
├── inc/
│   ├── customizer.php                   # ✅ Customizer settings
│   ├── template-tags.php                # ✅ Template helpers
│   ├── template-functions.php           # ✅ Theme functions
│   ├── ems-integration.php              # ✅ EMS plugin integration
│   ├── functions-homepage.php           # ✅ Homepage functions
│   ├── block-patterns.php               # ✅ Gutenberg block patterns
│   └── page-options.php                 # ✅ Page options
│
├── template-parts/                      # ✅ Content template parts
│   ├── content.php                      # ✅ Default post content
│   ├── content-none.php                 # ✅ No results message
│   ├── content-search.php               # ✅ Search results content
│   ├── content-page.php                 # ✅ Page content
│   └── content-card.php                 # ✅ Card layout content
│
├── templates/                           # ✅ Page templates
│   ├── homepage.php                     # ✅ Conference homepage template
│   └── full-width.php                   # ✅ Full-width page template
│
└── assets/
    ├── css/
    │   ├── ems-integration.css          # ✅ EMS styling
    │   ├── editor-style.css             # ✅ Gutenberg styles
    │   ├── homepage.css                 # ✅ Homepage styling
    │   └── block-patterns.css           # ✅ Block pattern styling
    │
    └── js/
        ├── main.js                      # ✅ Theme JavaScript
        ├── customizer.js                # ✅ Customizer preview
        └── homepage.js                  # ✅ Homepage JavaScript
```

---

## Current File Mapping (Actual vs Expected)

> **NOTE:** This section documents the actual current locations vs where files should be.

### Plugin Files (✅ All Present in Correct Locations)

| File | Current Location | Status |
|------|------------------|--------|
| event-management-system.php | plugin/ | ✅ |
| class-ems-core.php | plugin/includes/ | ✅ |
| class-ems-loader.php | plugin/includes/ | ✅ |
| class-ems-activator.php | plugin/includes/ | ✅ |
| class-ems-deactivator.php | plugin/includes/ | ✅ |
| class-ems-cpt-*.php (4 files) | plugin/includes/custom-post-types/ | ✅ |
| class-ems-registration.php | plugin/includes/registration/ | ✅ |
| class-ems-ticketing.php | plugin/includes/registration/ | ✅ |
| class-ems-waitlist.php | plugin/includes/registration/ | ✅ |
| class-ems-payment-gateway.php | plugin/includes/registration/ | ✅ |
| class-ems-abstract-submission.php | plugin/includes/abstracts/ | ✅ |
| class-ems-abstract-review.php | plugin/includes/abstracts/ | ✅ |
| class-ems-schedule-builder.php | plugin/includes/schedule/ | ✅ |
| class-ems-schedule-display.php | plugin/includes/schedule/ | ✅ |
| class-ems-session-registration.php | plugin/includes/schedule/ | ✅ |
| class-ems-file-manager.php | plugin/includes/files/ | ✅ |
| class-ems-file-access-control.php | plugin/includes/files/ | ✅ |
| class-ems-email-manager.php | plugin/includes/notifications/ | ✅ |
| class-ems-logger.php | plugin/includes/utilities/ | ✅ |
| class-ems-security.php | plugin/includes/utilities/ | ✅ |
| class-ems-validator.php | plugin/includes/utilities/ | ✅ |
| class-ems-user-helper.php | plugin/includes/utilities/ | ✅ |
| class-ems-date-helper.php | plugin/includes/utilities/ | ✅ |
| class-ems-roles.php | plugin/includes/user-roles/ | ✅ |
| class-ems-sponsor-portal.php | plugin/includes/collaboration/ | ✅ (Phase 4 Complete) |
| class-ems-admin.php | plugin/admin/ | ✅ |
| ems-admin.css | plugin/admin/css/ | ✅ |
| ems-admin.js | plugin/admin/js/ | ✅ |
| abstract-review.php | plugin/admin/views/ | ✅ |
| reviewer-dashboard.php | plugin/admin/views/ | ✅ |
| class-ems-public.php | plugin/public/ | ✅ |
| class-ems-abstract-shortcodes.php | plugin/public/shortcodes/ | ✅ |
| class-ems-schedule-shortcodes.php | plugin/public/shortcodes/ | ✅ |
| class-ems-sponsor-shortcodes.php | plugin/public/shortcodes/ | ✅ (Phase 4 Complete) |
| ems-public.css | plugin/public/css/ | ✅ |
| ems-schedule.css | plugin/public/css/ | ✅ |
| ems-public.js | plugin/public/js/ | ✅ |
| ems-schedule.js | plugin/public/js/ | ✅ |

### Theme Files (✅ All Present in Correct Locations)

| File | Location | Status |
|------|----------|--------|
| style.css | theme/conference-starter/ | ✅ |
| functions.php | theme/conference-starter/ | ✅ |
| header.php | theme/conference-starter/ | ✅ |
| footer.php | theme/conference-starter/ | ✅ |
| index.php | theme/conference-starter/ | ✅ |
| page.php | theme/conference-starter/ | ✅ |
| single.php | theme/conference-starter/ | ✅ |
| archive.php | theme/conference-starter/ | ✅ |
| search.php | theme/conference-starter/ | ✅ |
| searchform.php | theme/conference-starter/ | ✅ |
| 404.php | theme/conference-starter/ | ✅ |
| sidebar.php | theme/conference-starter/ | ✅ |
| comments.php | theme/conference-starter/ | ✅ |
| single-event.php | theme/conference-starter/ | ✅ |
| archive-event.php | theme/conference-starter/ | ✅ |

---

## Database Schema

### Tables Created by Plugin

```sql
-- Registrations
wp_ems_registrations (
    id, event_id, user_id, email, first_name, last_name,
    discipline, specialty, seniority,
    ticket_type, ticket_quantity, promo_code, amount_paid,
    payment_status, payment_reference,
    status, special_requirements,
    cancelled_at, cancelled_by, cancellation_reason,
    registration_date
)

-- Waitlist
wp_ems_waitlist (
    id, event_id, session_id, email, first_name, last_name,
    discipline, specialty, seniority,
    position, notified, added_date
)

-- Session Registrations
wp_ems_session_registrations (
    id, session_id, registration_id,
    registered_date, attendance_status
)

-- Abstract Reviews
wp_ems_abstract_reviews (
    id, abstract_id, reviewer_id,
    assigned_date, review_date,
    score, comments, recommendation, status
)

-- Notification Log
wp_ems_notification_log (
    id, notification_type, recipient_email, subject,
    related_id, related_type,
    sent_date, status, error_message
)

-- Sponsor Events (Phase 4)
wp_ems_sponsor_events (
    id, sponsor_id, event_id, sponsor_level, created_at
)

-- Sponsor Files (Phase 4)
wp_ems_sponsor_files (
    id, sponsor_id, event_id, user_id,
    file_name, file_path, file_type, file_size,
    description, upload_date, visibility, downloads
)
```

---

## Known Fixes Applied

These fixes have been applied and verified:

### 1. Duplicate Method Fix (v1.3.0)
**Issue:** `cancel_registration()` method was declared twice  
**Resolution:** Kept the comprehensive version with full audit trail

### 2. JavaScript Variable Name (v1.3.0)
**Issue:** Inline JavaScript used `ems_ajax` instead of `ems_public`  
**Resolution:** All references updated to use `ems_public`

### 3. Database Auto-Upgrade (v1.3.0)
**Addition:** Added `maybe_upgrade_database()` method for seamless schema updates

### 4. Registration Cancellation System (v1.3.0)
**Addition:** Full cancellation with `cancelled_at`, `cancelled_by`, `cancellation_reason` columns

### 5. Sponsor Portal Implementation (v1.4.0)
**Completion:** Full sponsor portal with file uploads, event associations, statistics dashboard

### 6. Waitlist Display Options (v1.4.0)
**Addition:** Visual indicators when event is at capacity, waitlist checkbox in registration form

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
| `ems-plugin-modules.md` | Registration, ticketing, waitlist, payment, abstract submission/review, schedule builder/display, session registration, email manager, file manager, sponsor portal | Working on features |
| `ems-plugin-public.md` | Public class, shortcode classes (abstract, schedule, sponsor), public CSS/JS | Working on frontend |
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
| **1.4.0** | **Jan 2026** | **Phase 4: Sponsor Portal & Waitlist (UAT Release)** |

### Theme Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.0.0 | Jan 2026 | Initial release with EMS integration |
| 2.2.0 | Jan 2026 | Block patterns, homepage template |
| **2.3.0** | **Jan 2026** | **UAT Release** |

---

## Audit Log

### 2026-01-24 - UAT 1.4.0 Final Review

**Auditor:** Automated Analysis

**Findings Summary:**
- ✅ Plugin code is complete and functional (v1.4.0)
- ✅ Theme is complete and properly organized (v2.3.0)
- ✅ All files are in correct locations
- ✅ No TODO/FIXME/STUB comments remain
- ✅ Phase 4 features (Sponsor Portal & Waitlist) fully implemented
- ✅ All front-end components have corresponding back-end functions
- ✅ Documentation updated to reflect UAT release

**Status:** Ready for User Acceptance Testing

### 2026-01-24 - Comprehensive Audit

**Auditor:** Automated Analysis

**Findings Summary:**
- Plugin core code is complete and functional
- Theme files properly organized
- File organization issues resolved
- Phase 4 features implemented

**Actions Completed:** All items in BUILD-STATUS.md resolved
