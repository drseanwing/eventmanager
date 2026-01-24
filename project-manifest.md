# EMS Project Manifest

**Last Updated:** 2026-01-24  
**Plugin Version:** 1.3.0  
**Theme Version:** 2.2.0  
**Status:** FUNCTIONAL - Plugin and Theme properly organized

---

## Current Repository Structure

This document reflects the **actual** current structure of files in the repository.

### Plugin: `plugin/`

```
plugin/
├── event-management-system.php          # Main plugin file
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
│   ├── collaboration/                   # Sponsor Collaboration (Phase 4)
│   │   └── class-ems-sponsor-portal.php # STUB - Sponsor portal
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
│   │   └── class-ems-schedule-shortcodes.php  # Schedule shortcodes
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
├── template-parts/                      # ❌ MISSING DIRECTORY
│   ├── content.php                      # ❌ Not created
│   ├── content-none.php                 # ❌ Not created
│   ├── content-search.php               # ❌ Not created
│   ├── content-page.php                 # ❌ Not created
│   └── content-card.php                 # ❌ Not created
│
├── templates/                           # ❌ MISSING DIRECTORY
│   ├── homepage.php                     # ❌ Not created (use homepage-template.php)
│   └── full-width.php                   # ❌ Not created
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
| class-ems-sponsor-portal.php | plugin/includes/collaboration/ | ⚠️ STUB |
| class-ems-admin.php | plugin/admin/ | ✅ |
| ems-admin.css | plugin/admin/css/ | ✅ |
| ems-admin.js | plugin/admin/js/ | ✅ |
| abstract-review.php | plugin/admin/views/ | ✅ |
| reviewer-dashboard.php | plugin/admin/views/ | ✅ |
| class-ems-public.php | plugin/public/ | ✅ |
| class-ems-abstract-shortcodes.php | plugin/public/shortcodes/ | ✅ |
| class-ems-schedule-shortcodes.php | plugin/public/shortcodes/ | ✅ |
| ems-public.css | plugin/public/css/ | ✅ |
| ems-schedule.css | plugin/public/css/ | ✅ |
| ems-public.js | plugin/public/js/ | ✅ |
| ems-schedule.js | plugin/public/js/ | ✅ |

### Theme Files (Mixed Status)

| File | Expected Location | Current Location | Status |
|------|-------------------|------------------|--------|
| style.css | theme/conference-starter/ | plugin/ | ❌ MOVE |
| functions.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| header.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| footer.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| index.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| page.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| single.php | theme/conference-starter/ | plugin/ | ❌ MOVE |
| archive.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| search.php | theme/conference-starter/ | plugin/ | ❌ MOVE |
| searchform.php | theme/conference-starter/ | plugin/ | ❌ MOVE |
| 404.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| sidebar.php | theme/conference-starter/ | plugin/ | ❌ MOVE |
| comments.php | theme/conference-starter/ | theme/conference-starter/ | ✅ |
| single-event.php | theme/conference-starter/ | plugin/ems/ | ❌ MOVE |
| archive-event.php | theme/conference-starter/ | plugin/ems/ | ❌ MOVE |

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
| 2.2.0 | Jan 2026 | Theme: Block patterns, homepage template |

---

## Audit Log

### 2026-01-24 - Comprehensive Audit

**Auditor:** Automated Analysis

**Findings Summary:**
- Plugin core code is complete and functional
- Theme has missing files that need to be moved or created
- File organization issues between plugin and theme directories
- 2 TODO comments remain (Phase 4 features)
- 1 stub class for future development

**Actions Required:** See BUILD-STATUS.md for consolidated to-do list
