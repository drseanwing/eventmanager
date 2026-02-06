=== Event Management System ===
Contributors: rbwh, drseanwing
Tags: events, conference, registration, abstracts, sponsors, schedule
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive event management system for conferences, courses, and seminars with registration, abstract submission, schedule building, and sponsor collaboration.

== Description ==

The Event Management System (EMS) is a WordPress plugin designed for medical conferences, professional development courses, and academic seminars. It provides:

* **Event Management** - Create and manage conferences, courses, and seminars with full scheduling support
* **Registration System** - Online registration with ticketing, waitlists, and payment gateway integration
* **Abstract Submission** - Multi-step abstract submission with peer review workflow
* **Schedule Builder** - Drag-and-drop schedule builder with multi-track support
* **Sponsor Portal** - Full sponsor lifecycle management including onboarding, EOI submissions, file sharing, and self-service dashboard
* **Public Display Shortcodes** - Sponsor grids, carousels, event listings, and more

= Sponsor Features =

* Sponsor onboarding with 7-step compliance-aware registration
* Expression of Interest (EOI) submission for events
* Sponsor dashboard with profile editing, page management, and application tracking
* Public sponsor pages with logo, description, and event listings
* Sponsor grid and carousel shortcodes for event pages

= Shortcodes =

* `[ems_upcoming_events]` - Display upcoming events
* `[ems_event_details]` - Single event details
* `[ems_registration_form]` - Event registration
* `[ems_schedule]` - Event schedule display
* `[ems_sponsor_grid]` - Sponsor logo grid for an event
* `[ems_sponsor_carousel]` - Sponsor logo carousel (single or multi mode)
* `[ems_sponsor_portal]` - Sponsor dashboard
* `[ems_sponsor_eoi]` - Sponsorship expression of interest form
* `[ems_sponsor_onboarding]` - Sponsor registration form

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/event-management-system/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create required database tables and default pages

== Changelog ==

= 1.6.0 =
* Added sponsor grid shortcode [ems_sponsor_grid] for responsive logo display
* Added single-item mode to sponsor carousel (mode="single")
* Added "My Sponsor Page" tab in sponsor portal with WYSIWYG editor and logo management
* Expanded sponsor profile editing to all 6 onboarding sections
* Added EOI creation directly from sponsor dashboard
* Added early authentication redirects on EOI page
* Auto-creates sponsor portal, onboarding, and EOI pages on activation

= 1.5.0 =
* Added sponsor display shortcodes (event sponsors, carousel, gallery, levels)
* Added sponsor EOI submission form
* Added sponsor portal with dashboard, profile editing, and application tracking
* Added single sponsor page template
* Added sponsorship level management

= 1.4.0 =
* Added sponsor portal with file sharing and collaboration
* Added sponsor onboarding form
* Added sponsor custom post type with full meta management

= 1.3.0 =
* Added schedule builder with multi-track support
* Added session registration and waitlist

= 1.2.0 =
* Added abstract submission and peer review workflow

= 1.1.0 =
* Added registration system with ticketing and payments

= 1.0.0 =
* Initial release with event management and custom post types
