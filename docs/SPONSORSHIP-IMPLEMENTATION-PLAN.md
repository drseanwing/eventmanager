# Sponsorship System Implementation Plan

## Document Purpose

Comprehensive, granular task list for implementing the full sponsorship subsystem of the Event Management System (EMS) WordPress plugin. Each task has a tightly defined scope, explicit acceptance criteria, file targets, and parallelization eligibility.

**Generated**: 2026-02-05
**Baseline**: EMS Plugin v1.4.0, Phase 4 Complete
**Source Documents**: SPONSOR_REVIEW.md, PHASE4-IMPLEMENTATION-SUMMARY.md, project-manifest.md, user specification

---

## Current State Summary

### What Exists (Phase 4 Deliverables)

| Component | Location | Status |
|-----------|----------|--------|
| `ems_sponsor` CPT | `plugin/includes/custom-post-types/class-ems-cpt-sponsor.php` | Complete - now shows in admin menu |
| `ems_sponsor_level` taxonomy | Same file | Complete (global, not per-event) |
| `wp_ems_sponsor_events` table | Created in `class-ems-activator.php` | Complete |
| `wp_ems_sponsor_files` table | Same | Complete |
| `EMS_Sponsor_Portal` class | `plugin/includes/collaboration/class-ems-sponsor-portal.php` (918 lines) | Complete |
| `[ems_sponsor_portal]` shortcode | `plugin/public/shortcodes/class-ems-sponsor-shortcodes.php` (481 lines) | Complete |
| `ems_sponsor` user role | 6 capabilities | Complete |
| Sponsor portal CSS | `plugin/public/css/ems-public.css` (lines 826-1135) | Complete |
| File upload/download/delete | AJAX + template_redirect handler | Complete |
| Organizer email on file upload | via `EMS_Email_Manager` | Complete |

### What Does NOT Exist (Gaps)

1. No event-level sponsorship enable/disable toggle
2. No event-level sponsorship level configuration (value, slots, recognition)
3. No admin meta boxes for sponsor management on events
4. No sponsor onboarding form (60+ fields across 6 sections)
5. No sponsor EOI form (event-specific, 4 sections)
6. No multi-step form framework
7. No sponsor logo carousel/gallery shortcodes
8. No colour-coded level pills (Bronze/Silver/Gold)
9. No graceful shortcode degradation when sponsorship disabled
10. No EOI submission/review/approval workflow
11. No sponsor-specific email templates (beyond file upload)
12. No REST API for sponsor operations
13. No structured meta fields for sponsor organisation data

---

## Implementation Phases

### Critical Path

```
Phase 1 (Data Model) ─┬─ Phase 2 (Admin Event Config) ──┐
                       │                                   ├─ Phase 7 (Public Display)
                       ├─ Phase 3 (Admin Sponsor Mgmt) ───┘
                       │
                       └─ Phase 4 (Form Framework) ─┬─ Phase 5 (Onboarding Form)
                                                     └─ Phase 6 (EOI Form)

Phase 8 (Portal) depends on: Phases 1, 6
Phase 9 (Email) can run alongside: Phases 5, 6
Phase 10 (Security) runs in parallel throughout
Phase 11 (Testing) follows each phase
```

### Parallelization Map

| Can Run In Parallel | Notes |
|---------------------|-------|
| Phase 2 + Phase 3 | Both depend only on Phase 1 |
| Phase 2 + Phase 4 | No shared files |
| Phase 3 + Phase 4 | No shared files |
| Phase 5 + Phase 6 | Both depend on Phase 4 but use separate shortcode classes |
| Phase 5 + Phase 9 (partial) | Email templates can be built alongside form steps |
| Phase 7 + Phase 8 | Different shortcode classes, different file targets |
| Phase 10 tasks | Security/validation tasks can be woven into any phase |

---

## Phase 1: Data Model & Schema Foundation

**Dependency**: None
**Parallelizable**: All tasks within this phase can be split across 3 workers (meta fields, tables, event meta)

### Task 1.1: Sponsor Organisation Meta Fields

**Scope**: Register post meta fields on `ems_sponsor` CPT for organisation details.

**Fields to register** (all stored as `_ems_sponsor_*` post meta):

| Meta Key | Type | Validation | Source Spec |
|----------|------|------------|-------------|
| `_ems_sponsor_legal_name` | string | Required, max 255 | 1.1 |
| `_ems_sponsor_trading_name` | string | Optional, max 255 | 1.2 |
| `_ems_sponsor_abn` | string | 11 digits, check digit validated | 1.3 |
| `_ems_sponsor_acn` | string | 9 digits, check digit validated | 1.3 |
| `_ems_sponsor_registered_address` | text | Optional | 1.4 |
| `_ems_sponsor_website_url` | url | URL format validated | 1.5 |
| `_ems_sponsor_country` | string | Optional, max 100 | 1.6 |
| `_ems_sponsor_parent_company` | string | Optional, max 255 | 1.6 |

**Files affected**: New file `plugin/includes/sponsor/class-ems-sponsor-meta.php`
**Acceptance criteria**: All meta keys registered via `register_post_meta()` with correct types, sanitisation callbacks, and `show_in_rest` support.
**Parallel eligible**: YES (with 1.2, 1.3, 1.4)

### Task 1.2: Sponsor Contact Meta Fields

**Scope**: Register contact and signatory meta fields on `ems_sponsor` CPT.

| Meta Key | Type | Validation | Source Spec |
|----------|------|------------|-------------|
| `_ems_sponsor_contact_name` | string | Required | 2.1 |
| `_ems_sponsor_contact_role` | string | Optional | 2.2 |
| `_ems_sponsor_contact_email` | string | Email format | 2.3 |
| `_ems_sponsor_contact_phone` | string | Phone format | 2.4 |
| `_ems_sponsor_signatory_name` | string | Required | 2.5 |
| `_ems_sponsor_signatory_title` | string | Optional | 2.5 |
| `_ems_sponsor_signatory_email` | string | Email format | 2.6 |
| `_ems_sponsor_marketing_contact_name` | string | Optional | 2.7 |
| `_ems_sponsor_marketing_contact_email` | string | Email format | 2.7 |

**Files affected**: Same file as 1.1
**Parallel eligible**: YES (with 1.1, 1.3, 1.4)

### Task 1.3: Industry Classification Meta Fields

**Scope**: Register industry classification meta fields.

| Meta Key | Type | Validation | Source Spec |
|----------|------|------------|-------------|
| `_ems_sponsor_industry_sectors` | array | Multiselect from defined list | 3.1 |
| `_ems_sponsor_ma_member` | boolean | Y/N | 3.2 |
| `_ems_sponsor_mtaa_member` | boolean | Y/N | 3.3 |
| `_ems_sponsor_other_codes` | text | Freetext | 3.4 |
| `_ems_sponsor_tga_number` | string | Optional | 3.5 |

**Industry sector options**: Pharmaceutical (prescription) | Pharmaceutical (OTC/complementary) | Medical devices/technology | Diagnostic/pathology equipment | Simulation/manikin equipment | Health IT/digital health | Education/publishing | Professional body/college | Other

**Files affected**: Same file as 1.1
**Parallel eligible**: YES (with 1.1, 1.2, 1.4)

### Task 1.4: Products, Scope, Legal, Insurance & Compliance Meta Fields

**Scope**: Register all remaining sponsor meta fields covering products, legal, insurance, compliance, and conflict of interest sections.

**Products & Scope fields** (spec sections 4.1-4.8):

| Meta Key | Type | Source |
|----------|------|--------|
| `_ems_sponsor_products` | text | 4.1 |
| `_ems_sponsor_artg_listings` | text | 4.2 |
| `_ems_sponsor_scheduling_class` | string | 4.3 (select: Unscheduled/S2/S3/S4/S8/Not a medicine) |
| `_ems_sponsor_device_class` | string | 4.4 (select: Class I/IIa/IIb/III/AIMD/N/A) |
| `_ems_sponsor_non_artg_product` | boolean | 4.5 |
| `_ems_sponsor_off_label` | boolean | 4.6 |
| `_ems_sponsor_tga_safety_alert` | boolean | 4.7 |
| `_ems_sponsor_educational_relation` | text | 4.8 |

**Legal, Insurance & Compliance fields** (spec sections 9.1-9.10):

| Meta Key | Type | Source |
|----------|------|--------|
| `_ems_sponsor_public_liability_confirmed` | boolean | 9.1 |
| `_ems_sponsor_public_liability_insurer` | string | 9.2 |
| `_ems_sponsor_public_liability_policy` | string | 9.2 |
| `_ems_sponsor_product_liability_confirmed` | boolean | 9.3 |
| `_ems_sponsor_professional_indemnity` | boolean | 9.4 |
| `_ems_sponsor_workers_comp_confirmed` | boolean | 9.5 |
| `_ems_sponsor_code_compliance_agreed` | boolean | 9.6 |
| `_ems_sponsor_compliance_process` | boolean | 9.7 |
| `_ems_sponsor_adverse_findings` | text | 9.8 |
| `_ems_sponsor_legal_proceedings` | text | 9.9 |
| `_ems_sponsor_transparency_obligations` | text | 9.10 |

**Conflict of Interest fields** (spec sections 10.1-10.5):

| Meta Key | Type | Source |
|----------|------|--------|
| `_ems_sponsor_qld_health_aware` | boolean | 10.1 |
| `_ems_sponsor_personal_relationships` | text | 10.2 |
| `_ems_sponsor_procurement_conflicts` | text | 10.3 |
| `_ems_sponsor_transfers_of_value` | text | 10.4 |
| `_ems_sponsor_preferential_treatment_agreed` | boolean | 10.5 |

**Files affected**: Same file as 1.1
**Parallel eligible**: YES (with 1.1, 1.2, 1.3)

### Task 1.5: Create `wp_ems_sponsorship_levels` Table

**Scope**: Create database table for event-specific sponsorship level definitions.

**Schema**:

```sql
CREATE TABLE {$prefix}ems_sponsorship_levels (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    level_name VARCHAR(100) NOT NULL,
    level_slug VARCHAR(100) NOT NULL,
    colour VARCHAR(7) NOT NULL DEFAULT '#CD7F32',
    value_aud DECIMAL(10,2) DEFAULT NULL,
    slots_total INT(11) DEFAULT NULL,
    slots_filled INT(11) NOT NULL DEFAULT 0,
    recognition_text TEXT DEFAULT NULL,
    sort_order INT(11) NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY event_level (event_id, level_slug)
);
```

**Files affected**: `plugin/includes/class-ems-activator.php` (add table creation), `plugin/includes/class-ems-core.php` (add migration check)
**Acceptance criteria**: Table created on activation and via auto-upgrade. Indexes on event_id.
**Parallel eligible**: YES (with 1.6, 1.7)

### Task 1.6: Create `wp_ems_sponsor_eoi` Table

**Scope**: Create database table for sponsor Expression of Interest submissions.

**Schema**:

```sql
CREATE TABLE {$prefix}ems_sponsor_eoi (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    sponsor_id BIGINT(20) UNSIGNED NOT NULL,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    preferred_level VARCHAR(100) DEFAULT NULL,
    estimated_value DECIMAL(10,2) DEFAULT NULL,
    contribution_nature TEXT DEFAULT NULL,
    conditional_outcome TEXT DEFAULT NULL,
    speaker_nomination TEXT DEFAULT NULL,
    duration_interest VARCHAR(50) DEFAULT NULL,
    exclusivity_requested TINYINT(1) NOT NULL DEFAULT 0,
    exclusivity_category VARCHAR(255) DEFAULT NULL,
    shared_presence_accepted TINYINT(1) NOT NULL DEFAULT 0,
    competitor_objections TEXT DEFAULT NULL,
    exclusivity_conditions TEXT DEFAULT NULL,
    recognition_elements TEXT DEFAULT NULL,
    trade_display_requested TINYINT(1) NOT NULL DEFAULT 0,
    trade_display_requirements TEXT DEFAULT NULL,
    representatives_count INT(11) DEFAULT NULL,
    product_samples TINYINT(1) NOT NULL DEFAULT 0,
    promotional_material TINYINT(1) NOT NULL DEFAULT 0,
    delegate_data_collection TINYINT(1) NOT NULL DEFAULT 0,
    social_media_expectations TEXT DEFAULT NULL,
    content_independence_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    content_influence TEXT DEFAULT NULL,
    therapeutic_claims TINYINT(1) NOT NULL DEFAULT 0,
    material_review_required TINYINT(1) NOT NULL DEFAULT 0,
    submitted_at DATETIME NOT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    reviewed_by BIGINT(20) UNSIGNED DEFAULT NULL,
    review_notes TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY sponsor_id (sponsor_id),
    KEY event_id (event_id),
    KEY status (status),
    UNIQUE KEY sponsor_event (sponsor_id, event_id)
);
```

**Files affected**: `plugin/includes/class-ems-activator.php`
**Acceptance criteria**: Table created on activation. Unique constraint prevents duplicate EOIs per sponsor-event pair.
**Parallel eligible**: YES (with 1.5, 1.7)

### Task 1.7: Add Event-Level Sponsorship Meta Fields

**Scope**: Register event post meta for sponsorship configuration.

| Meta Key | Type | Default |
|----------|------|---------|
| `_ems_sponsorship_enabled` | boolean | false |
| `_ems_sponsorship_levels_enabled` | boolean | false |

**Files affected**: `plugin/includes/custom-post-types/class-ems-cpt-event.php` (register meta)
**Acceptance criteria**: Meta fields registered, accessible via `get_post_meta()`, available in REST API.
**Parallel eligible**: YES (with 1.5, 1.6)

### Task 1.8: Database Migration Logic

**Scope**: Add migration code to create new tables on plugin upgrade.

**Files affected**: `plugin/includes/class-ems-activator.php` (add `create_sponsorship_levels_table()` and `create_sponsor_eoi_table()`), `plugin/includes/class-ems-core.php` (update `run_database_upgrades()`)
**Dependency**: Tasks 1.5, 1.6 must be complete
**Acceptance criteria**: Tables auto-created on activation and on version upgrade from 1.4.0 to next version.
**Parallel eligible**: NO (depends on 1.5 + 1.6)

---

## Phase 2: Admin Backend - Event Sponsorship Configuration

**Dependency**: Phase 1 complete
**Parallelizable with**: Phase 3, Phase 4

### Task 2.1: Sponsorship Settings Meta Box on Event Editor

**Scope**: Add a new meta box to the Event CPT editor with a toggle to enable/disable sponsorship, and a toggle for levels.

**UI elements**:
- Checkbox: "Enable Sponsorship for this Event"
- Checkbox: "Enable Sponsorship Levels" (only visible when sponsorship enabled)

**Files affected**: `plugin/includes/custom-post-types/class-ems-cpt-event.php` (add meta box in `add_meta_boxes()`, add render method, update `save_meta_boxes()`)
**Acceptance criteria**: Meta box renders on event editor. Toggle saves/loads correctly. Levels toggle conditionally displayed via JS.
**Parallel eligible**: NO (gate for 2.2-2.7)

### Task 2.2: Sponsorship Levels Management UI

**Scope**: Build an inline UI within the event meta box to add/edit/delete sponsorship levels for the event.

**UI elements per level row**:
- Text input: Level name (e.g., "Gold")
- Colour picker: Level colour
- Number input: Value (AUD)
- Number input: Total slots
- Read-only: Filled slots
- Textarea: Recognition text
- Drag handle: Sort order
- Delete button

**Files affected**: New file `plugin/admin/views/meta-box-sponsorship-levels.php`, `plugin/admin/js/ems-admin.js` (add level management JS), `plugin/admin/css/ems-admin.css` (add level management styles)
**Acceptance criteria**: CRUD operations work via AJAX. Levels persist to `wp_ems_sponsorship_levels` table. Colour picker functional. Drag-to-reorder works.
**Parallel eligible**: YES (with 2.4, 2.5 after 2.1 complete)

### Task 2.3: Default Template Auto-Population

**Scope**: When sponsorship levels are enabled for the first time on an event, auto-populate with default Bronze/Silver/Gold levels.

**Default levels**:

| Level | Colour | Value (AUD) | Slots | Recognition |
|-------|--------|-------------|-------|-------------|
| Bronze | #CD7F32 | 1000 | 10 | Logo on event website, acknowledgement in program |
| Silver | #C0C0C0 | 2500 | 5 | All Bronze benefits + logo on signage, trade display space |
| Gold | #FFD700 | 5000 | 3 | All Silver benefits + named session sponsorship, prime booth location |

**Files affected**: AJAX handler in `plugin/admin/class-ems-admin.php` or `plugin/includes/sponsor/class-ems-sponsorship-levels.php`
**Acceptance criteria**: One-click button populates default levels. Only works when level table is empty for the event. User can modify after population.
**Parallel eligible**: YES (with 2.4, 2.5)

### Task 2.4: Admin Sponsor-Event List View

**Scope**: Within the event sponsorship meta box, display a list of sponsors linked to this event with their level and actions.

**Columns**: Sponsor Name | Level (coloured pill) | Status | Linked Date | Actions (Unlink)

**Files affected**: Extension of meta box view from 2.1
**Acceptance criteria**: Shows linked sponsors. Level pills render with correct colours. Unlink action works via AJAX using existing `unlink_sponsor_from_event()`.
**Parallel eligible**: YES (with 2.2, 2.3)

### Task 2.5: Admin Link/Unlink Sponsors to Events

**Scope**: Build an admin UI to search and link existing sponsors to an event, assigning them a level.

**UI elements**:
- Sponsor search (AJAX autocomplete by name)
- Level dropdown (populated from event's configured levels)
- "Link Sponsor" button

**Files affected**: Extension of meta box view, new AJAX handler in admin class
**Acceptance criteria**: Search returns matching sponsors. Link creates record in `wp_ems_sponsor_events`. Level assigned correctly. Prevents duplicate links.
**Parallel eligible**: YES (with 2.2, 2.3)

### Task 2.6: Slot Availability Tracking

**Scope**: When linking a sponsor to a level, check slot availability and prevent over-allocation.

**Files affected**: `plugin/includes/collaboration/class-ems-sponsor-portal.php` (update `link_sponsor_to_event()`), new `plugin/includes/sponsor/class-ems-sponsorship-levels.php`
**Acceptance criteria**: `slots_filled` increments on link, decrements on unlink. Error returned if slots full. Admin UI shows remaining slots.
**Parallel eligible**: YES (with 2.3)

### Task 2.7: Sponsorship Level CRUD Class

**Scope**: Create a dedicated class for sponsorship level database operations.

**Methods**: `get_event_levels($event_id)`, `add_level($event_id, $data)`, `update_level($level_id, $data)`, `delete_level($level_id)`, `get_level($level_id)`, `populate_defaults($event_id)`, `get_available_slots($level_id)`

**Files affected**: New file `plugin/includes/sponsor/class-ems-sponsorship-levels.php`
**Acceptance criteria**: All CRUD operations work with proper sanitisation. Class loaded in `EMS_Core::load_dependencies()`.
**Parallel eligible**: Should be done FIRST in Phase 2 (other tasks depend on it)

---

## Phase 3: Admin Backend - Sponsor Management

**Dependency**: Phase 1 complete
**Parallelizable with**: Phase 2, Phase 4

### Task 3.1: Sponsor Admin Meta Boxes

**Scope**: Add meta boxes to the Sponsor CPT editor displaying all organisation fields (sections 1-4, 9-10) in grouped fieldsets.

**Meta box groups**:
1. "Organisation Details" - fields from 1.1-1.6
2. "Primary Contact & Signatory" - fields from 2.1-2.7
3. "Industry Classification" - fields from 3.1-3.5
4. "Products & Scope" - fields from 4.1-4.8
5. "Legal, Insurance & Compliance" - fields from 9.1-9.10
6. "Conflict of Interest" - fields from 10.1-10.5

**Files affected**: `plugin/includes/custom-post-types/class-ems-cpt-sponsor.php` (add meta boxes, render methods, save handler)
**Acceptance criteria**: All fields render with correct input types. All fields save/load correctly. Validation for ABN/ACN format. Nonce verification on save.
**Parallel eligible**: YES (with 3.2, 3.3)

### Task 3.2: Sponsor Admin List Table Columns

**Scope**: Customise the WP admin list table for sponsors with additional columns.

**Columns**: Title | Trading Name | ABN | Linked Events | Status

**Files affected**: `plugin/includes/custom-post-types/class-ems-cpt-sponsor.php` (add `manage_posts_columns` and `manage_posts_custom_column` filters)
**Acceptance criteria**: All columns display. Events column shows count of linked events. Sortable by name.
**Parallel eligible**: YES (with 3.1, 3.3)

### Task 3.3: Manual Sponsor Account Creation

**Scope**: Add an admin action to manually create a sponsor (creates WP user account + `ems_sponsor` post + links them).

**Files affected**: New admin page/handler in `plugin/admin/class-ems-admin.php`
**Acceptance criteria**: Creates user with `ems_sponsor` role. Creates post. Sets `_ems_sponsor_id` user meta. Sends welcome email. Validates for duplicate email.
**Parallel eligible**: YES (with 3.1, 3.2)

### Task 3.4: EOI Submissions Admin List Page

**Scope**: Build an admin page listing all EOI submissions with filters.

**Filters**: By Event | By Status (Pending/Approved/Rejected/Withdrawn)
**Columns**: Sponsor Name | Event | Preferred Level | Estimated Value | Status | Submitted Date | Actions (View/Approve/Reject)

**Files affected**: New file `plugin/admin/views/admin-page-eoi-list.php`, handler in `plugin/admin/class-ems-admin.php`
**Acceptance criteria**: List page renders under EMS admin menu. Filters work. Pagination. Bulk actions for approve/reject.
**Parallel eligible**: YES (with 3.5)

### Task 3.5: EOI Detail/Review Admin Page

**Scope**: Build an admin page showing full EOI submission details with approve/reject actions.

**Sections**: Sponsor details (linked) | EOI form responses | Review actions (approve with level assignment / reject with reason / request more info)

**Files affected**: New file `plugin/admin/views/admin-page-eoi-detail.php`, handler in `plugin/admin/class-ems-admin.php`
**Acceptance criteria**: All EOI fields displayed. Approve action: links sponsor to event at level, decrements slots, sends approval email, updates EOI status. Reject action: sends rejection email with optional reason, updates status.
**Parallel eligible**: YES (with 3.4)

---

## Phase 4: Multi-Page Form Framework

**Dependency**: Phase 1 complete
**Parallelizable with**: Phase 2, Phase 3

### Task 4.1: Multi-Step Form Engine Core

**Scope**: Build a reusable multi-step form class that handles step registration, navigation, state management, and rendering.

**Class**: `EMS_Multi_Step_Form`

**Public API**:
- `register_step($slug, $title, $fields, $validation_rules)`
- `render()` - outputs current step HTML with navigation
- `validate_step($step_slug, $data)` - server-side validation
- `get_current_step()` / `set_current_step($slug)`
- `get_form_data()` / `set_form_data($step_slug, $data)`
- `is_complete()` - all steps validated
- `get_progress()` - returns step completion state

**Files affected**: New file `plugin/includes/forms/class-ems-multi-step-form.php`
**Acceptance criteria**: Steps render in order. Navigation (Next/Previous/Jump) works. Current step highlighted. Form data persists across steps.
**Parallel eligible**: NO (gate for 4.2-4.8)

### Task 4.2: Form State Persistence

**Scope**: Implement save-and-resume for multi-step forms using WP transients.

**Storage**: `ems_form_{form_id}_{user_id_or_session}` transient with 24-hour expiry.

**Files affected**: Extension of `class-ems-multi-step-form.php`
**Acceptance criteria**: Form state saved on each step submission. User can close browser and resume. Transient cleared on final submission. Guest users use session-based key.
**Parallel eligible**: YES (with 4.3)

### Task 4.3: Client-Side Step Validation

**Scope**: Implement JavaScript validation that runs before step submission.

**Validation types**: required, email, phone, url, min/max length, pattern (regex), conditional (field X required if field Y = value)

**Files affected**: New file `plugin/public/js/ems-forms.js`
**Acceptance criteria**: Inline error messages display under invalid fields. Form cannot advance to next step with validation errors. Errors clear on correction.
**Parallel eligible**: YES (with 4.2)

### Task 4.4: Server-Side Step Validation

**Scope**: Implement per-field server-side validation matching client-side rules.

**Files affected**: Extension of `class-ems-multi-step-form.php`
**Acceptance criteria**: All client-side rules enforced server-side. Validation errors returned as structured array. Sanitisation applied to all inputs.
**Parallel eligible**: YES (with 4.2, 4.3)

### Task 4.5: Form Field Renderer

**Scope**: Build a field renderer supporting all required field types.

**Field types**: text | textarea | email | phone | url | select | multiselect | checkbox | radio | file | date | number | hidden | conditional_group

**Each field type outputs**: label, input, help text, error container, required indicator, conditional display attributes.

**Files affected**: New file `plugin/includes/forms/class-ems-form-renderer.php`
**Acceptance criteria**: All field types render correctly. Conditional fields show/hide based on trigger field values. Accessible (labels, aria attributes).
**Parallel eligible**: YES (with 4.2, 4.3, 4.4)

### Task 4.6: Progress Indicator Component

**Scope**: Build a reusable step progress bar component.

**Renders**: Step number | Step title | Active/Complete/Pending states | Clickable completed steps

**Files affected**: Included in `class-ems-multi-step-form.php` render method, CSS in `plugin/public/css/ems-public.css`
**Acceptance criteria**: Shows all steps with visual states. Responsive (horizontal on desktop, vertical on mobile). Completed steps show checkmark. Active step highlighted.
**Parallel eligible**: YES (with 4.2-4.5)

### Task 4.7: Review Step Component

**Scope**: Build a summary/review step that displays all entered data before final submission.

**Renders**: Grouped by step title | Field label + entered value | Edit link per section that returns to that step

**Files affected**: Extension of form engine
**Acceptance criteria**: All non-empty fields displayed. Edit links navigate to correct step with data preserved. Confirmation checkbox before submit.
**Parallel eligible**: YES (with 4.6)

### Task 4.8: Form AJAX Endpoints

**Scope**: Create AJAX handlers for step navigation and form submission.

**Endpoints**:
- `ems_form_validate_step` - validate current step data, return errors or advance
- `ems_form_save_progress` - save current form state
- `ems_form_submit` - final form submission

**Files affected**: Handler in public class or new form handler class
**Acceptance criteria**: All endpoints nonce-verified. Step data validated server-side. Progress saved. Final submission triggers form-specific handler.
**Parallel eligible**: YES (with 4.6, 4.7)

---

## Phase 5: Sponsor Onboarding Form

**Dependency**: Phase 4 complete
**Parallelizable with**: Phase 6 (different shortcode class, different form definition)

### Task 5.1: Onboarding Shortcode Registration

**Scope**: Create `[ems_sponsor_onboarding]` shortcode that renders the multi-step onboarding form.

**Files affected**: New file `plugin/public/shortcodes/class-ems-sponsor-onboarding-shortcode.php`, register in `EMS_Public::register_shortcodes()`
**Acceptance criteria**: Shortcode renders form. Redirects logged-in sponsors to portal. Shows login prompt if form requires auth (configurable).
**Parallel eligible**: NO (gate for 5.2-5.8)

### Task 5.2: Step 1 - Organisation Details

**Scope**: Define form step for fields 1.1-1.6.

**Fields**: Legal entity name (required) | Trading name | ABN (validated) | ACN (validated) | Registered business address | Website URL (validated) | Country of incorporation | Parent company

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Acceptance criteria**: All fields render with correct types. ABN/ACN format validation. URL validation. Required fields enforced.
**Parallel eligible**: YES (with 5.3-5.7 once 5.1 done)

### Task 5.3: Step 2 - Primary Contact & Authorised Signatory

**Scope**: Define form step for fields 2.1-2.7.

**Fields**: Contact name (required) | Contact role | Contact email (required, validated) | Contact phone | Signatory name (required) | Signatory title | Signatory email (validated) | Marketing contact name | Marketing contact email

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Parallel eligible**: YES (with 5.2, 5.4-5.7)

### Task 5.4: Step 3 - Industry Classification

**Scope**: Define form step for fields 3.1-3.5 with conditional logic.

**Fields**: Industry sector (multiselect, required) | MA member (Y/N) | MTAA member (Y/N) | Other industry code (conditional: shown if other selected) | TGA sponsor number (conditional: shown if pharmaceutical/device selected)

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Acceptance criteria**: Conditional fields show/hide correctly. Multiselect works. At least one sector required.
**Parallel eligible**: YES (with 5.2, 5.3, 5.5-5.7)

### Task 5.5: Step 4 - Products, Services & Scope

**Scope**: Define form step for fields 4.1-4.8 with ARTG-related conditional logic.

**Fields**: Products/services relevant (required, textarea) | ARTG listing numbers (conditional: if therapeutic good) | Scheduling classification (select) | Device classification (conditional: if medical device) | Non-ARTG product flag (boolean, triggers warning) | Off-label indication flag (boolean, triggers warning) | TGA safety alert flag (boolean, triggers warning) | Educational relation description (textarea)

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Acceptance criteria**: Conditional fields based on product type. Warning messages display for flagged items (off-label, non-ARTG, safety alerts).
**Parallel eligible**: YES (with 5.2-5.4, 5.6-5.7)

### Task 5.6: Step 5 - Legal, Insurance & Compliance

**Scope**: Define form step for fields 9.1-9.10.

**Fields**: Public liability confirmed (Y/N, required) | Insurer & policy number (conditional: if yes) | Product liability confirmed (Y/N) | Professional indemnity (Y/N) | Workers comp confirmed (Y/N, required) | Code compliance agreed (checkbox, required) | Internal compliance process (Y/N) | Adverse findings (textarea, conditional: if yes) | Legal proceedings (textarea, conditional: if yes) | Transparency reporting obligations (textarea)

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Parallel eligible**: YES (with 5.2-5.5, 5.7)

### Task 5.7: Step 6 - Conflict of Interest & Public Sector

**Scope**: Define form step for fields 10.1-10.5.

**Fields**: Qld Health Directive awareness (checkbox acknowledgement, required) | Personal/financial/familial relationships (textarea) | Procurement conflicts (textarea) | Transfers of value to HCPs (textarea) | Preferential treatment acknowledgement (checkbox, required)

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Parallel eligible**: YES (with 5.2-5.6)

### Task 5.8: Step 7 - Review & Submit

**Scope**: Render summary of all entered data with section edit links and final submission.

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Acceptance criteria**: All non-empty fields displayed grouped by section. Edit links navigate back. Submit button triggers account creation.
**Parallel eligible**: NO (depends on 5.2-5.7)

### Task 5.9: Onboarding Submission Handler

**Scope**: Process final form submission: create WP user, create sponsor post, populate meta, link user to post.

**Logic**:
1. Create WP user with `ems_sponsor` role (email as username)
2. Create `ems_sponsor` post (legal name as title)
3. Set all `_ems_sponsor_*` meta fields on the post
4. Set `_ems_sponsor_id` user meta linking user to post
5. Set post thumbnail if logo uploaded
6. Send confirmation email (Task 5.10)
7. Send admin notification (Task 5.11)
8. Clear form transient
9. Redirect to sponsor portal or success page

**Files affected**: `class-ems-sponsor-onboarding-shortcode.php`
**Acceptance criteria**: User created with correct role. Post created with all meta. Link established. Emails sent. Form state cleared.
**Parallel eligible**: NO (depends on 5.8)

### Task 5.10: Onboarding Confirmation Email

**Scope**: Send confirmation email to sponsor on successful onboarding.

**Template variables**: sponsor name, login credentials (link to set password), portal URL, event list URL

**Files affected**: Email template, register with `EMS_Email_Manager`
**Parallel eligible**: YES (with Phase 9 email work)

### Task 5.11: Admin Notification on New Sponsor

**Scope**: Send notification to admin when a new sponsor completes onboarding.

**Template variables**: sponsor name, organisation, ABN, contact email, admin sponsor edit link

**Files affected**: Email template, register with `EMS_Email_Manager`
**Parallel eligible**: YES (with 5.10)

### Task 5.12: Duplicate Detection

**Scope**: Check for existing sponsors by ABN/ACN and email before creating new account.

**Logic**: Before submission, query for existing `ems_sponsor` posts with matching ABN or existing users with matching email. If found: show message with options (link to login, contact admin).

**Files affected**: Extension of submission handler
**Parallel eligible**: YES (with 5.9)

### Task 5.13: Spam Protection

**Scope**: Add honeypot field and optional CAPTCHA to onboarding form.

**Implementation**: Hidden honeypot field (reject if filled). Rate limiting (max 3 submissions per IP per hour).

**Files affected**: Extension of form engine or onboarding shortcode
**Parallel eligible**: YES (independent)

---

## Phase 6: Sponsor EOI Form (Event-Specific)

**Dependency**: Phase 4 complete, Phase 2 (for level data) recommended
**Parallelizable with**: Phase 5

### Task 6.1: EOI Shortcode Registration

**Scope**: Create `[ems_sponsor_eoi event_id="123"]` shortcode.

**Files affected**: New file `plugin/public/shortcodes/class-ems-sponsor-eoi-shortcode.php`, register in `EMS_Public`
**Acceptance criteria**: Shortcode accepts `event_id` attribute. Requires logged-in sponsor. Redirects non-sponsors to onboarding form.
**Parallel eligible**: NO (gate for 6.2-6.7)

### Task 6.2: Event Validation

**Scope**: Before rendering EOI form, validate: event exists, sponsorship enabled, levels configured, slots available, sponsor hasn't already submitted EOI.

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Acceptance criteria**: Graceful error messages for each failure case. If sponsorship disabled: "Sponsorship is not available for this event." If already submitted: show status of existing EOI.
**Parallel eligible**: NO (prerequisite for form steps)

### Task 6.3: Step 1 - Sponsorship Commitment & Preferences

**Scope**: Define form step for fields 5.1-5.7, dynamically populated from event's configured levels.

**Fields**: Preferred tier (select, populated from `wp_ems_sponsorship_levels` for this event, showing name + value + available slots) | Estimated total value (number) | Nature of contribution (multiselect: Financial unrestricted / Financial earmarked / In-kind equipment / In-kind consumables / In-kind catering / In-kind educational / In-kind speaker support / In-kind AV / Other) | Conditional outcome (textarea, with warning note about MA Code) | Speaker nomination (textarea) | Duration (select: Single event / Annual / Multi-year) | Specific events of interest (textarea)

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Acceptance criteria**: Tier select dynamically shows available levels with remaining slots. Sold-out tiers disabled.
**Parallel eligible**: YES (with 6.4-6.6 once 6.1+6.2 done)

### Task 6.4: Step 2 - Exclusivity & Category Protection

**Scope**: Define form step for fields 6.1-6.5.

**Fields**: Exclusivity requested (Y/N) | Exclusivity category (text, conditional) | Shared category acceptance (Y/N, conditional) | Competitor objections (textarea, conditional) | Exclusivity conditions (textarea, conditional)

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Parallel eligible**: YES (with 6.3, 6.5, 6.6)

### Task 6.5: Step 3 - Desired Recognition & Visibility

**Scope**: Define form step for fields 7.1-7.7.

**Fields**: Recognition elements (multiselect: Logo on materials / Logo on certificates / Verbal acknowledgement / Trade display / Product demo / Branded items / Signage / Digital presence / Named session / Sponsored break / Other) | Trade display requirements (textarea, conditional) | Representatives attending (number) | Product samples (Y/N, with compliance note about S3/S4/S8) | Promotional material (Y/N, with AHPRA/TGA note) | Delegate data collection (Y/N, with Privacy Act note) | Social media expectations (textarea)

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Parallel eligible**: YES (with 6.3, 6.4, 6.6)

### Task 6.6: Step 4 - Educational Independence & Content Boundaries

**Scope**: Define form step for fields 8.1-8.4.

**Fields**: Content independence acknowledgement (required checkbox: "The event organiser retains sole control...") | Content influence disclosure (textarea, conditional: "Does the sponsor seek to influence educational content?") | Therapeutic claims (Y/N, with TGA/MA code note) | Material review/approval requirement (Y/N, with distinction note between branding review vs editorial control)

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Parallel eligible**: YES (with 6.3, 6.4, 6.5)

### Task 6.7: Step 5 - Review & Submit

**Scope**: Summary of all EOI data with edit links and submission.

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Parallel eligible**: NO (depends on 6.3-6.6)

### Task 6.8: EOI Submission Handler

**Scope**: Process EOI: validate sponsor, check event capacity, save to `wp_ems_sponsor_eoi`, set status to 'pending'.

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Acceptance criteria**: Record created in EOI table. Status set to 'pending'. Confirmation email sent. Admin notification sent.
**Parallel eligible**: NO (depends on 6.7)

### Task 6.9: EOI Approval Flow

**Scope**: When admin approves an EOI: auto-link sponsor to event at specified level, decrement available slots, update EOI status, send approval email.

**Files affected**: Admin handler (from Phase 3 Task 3.5), `EMS_Sponsor_Portal::link_sponsor_to_event()`, sponsorship levels class
**Acceptance criteria**: Sponsor linked at correct level. Slots decremented. EOI status = 'approved'. Email sent with level details and next steps.
**Parallel eligible**: YES (with 6.10)

### Task 6.10: EOI Rejection Flow

**Scope**: When admin rejects an EOI: update status, send rejection email with optional reason.

**Files affected**: Admin handler (from Phase 3 Task 3.5)
**Acceptance criteria**: EOI status = 'rejected'. Email sent with reason (if provided).
**Parallel eligible**: YES (with 6.9)

### Task 6.11: Authentication Gate

**Scope**: EOI form requires logged-in sponsor. If not logged in, show login form with link to onboarding.

**Files affected**: `class-ems-sponsor-eoi-shortcode.php`
**Acceptance criteria**: Non-logged-in users see login prompt. Non-sponsors see message directing to onboarding form. Logged-in sponsors see EOI form.
**Parallel eligible**: YES (independent)

---

## Phase 7: Public-Facing Display

**Dependency**: Phases 1 and 2 complete (need levels data and sponsor-event links)
**Parallelizable with**: Phase 8

### Task 7.1: Event Sponsors Shortcode with Level Pills

**Scope**: Create `[ems_event_sponsors event_id="123"]` shortcode displaying sponsors grouped by level with colour-coded pills.

**Output**: For each level (sorted by sort_order), render a section with level name pill, then sponsor logos/names linking to sponsor pages.

**Level pill colours**: Bronze #CD7F32 | Silver #C0C0C0 | Gold #FFD700 | Platinum #E5E4E2 | Custom colours from level config

**Files affected**: New file or extension of `class-ems-sponsor-shortcodes.php`
**Acceptance criteria**: Sponsors grouped by level. Pills colour-coded. Logos link to sponsor posts. Empty levels hidden. Graceful output when no sponsors.
**Parallel eligible**: YES (with 7.2, 7.3, 7.4)

### Task 7.2: Sponsor Logo Carousel Shortcode

**Scope**: Create `[ems_sponsor_carousel event_id="123"]` shortcode with auto-scrolling responsive logo carousel.

**Features**: Auto-scroll | Pause on hover | Navigation arrows | Dot indicators | Responsive breakpoints (6 logos desktop, 4 tablet, 2 mobile) | Links to sponsor pages

**Files affected**: New shortcode class or extension, new JS file `plugin/public/js/ems-carousel.js`, CSS additions
**Acceptance criteria**: Carousel auto-scrolls. Responsive. Touch-friendly. Accessible (keyboard navigation, aria labels). Logos link to sponsor pages.
**Parallel eligible**: YES (with 7.1, 7.3, 7.4)

### Task 7.3: Sponsor Logo Gallery Shortcode

**Scope**: Create `[ems_sponsor_gallery event_id="123"]` shortcode with a static responsive grid of sponsor logos.

**Output**: CSS Grid of sponsor logos (featured images) linking to sponsor pages. Optional grouping by level.

**Attributes**: `event_id` (required) | `columns` (default: 4) | `group_by_level` (default: true) | `show_names` (default: false)

**Files affected**: Extension of sponsor shortcodes
**Parallel eligible**: YES (with 7.1, 7.2, 7.4)

### Task 7.4: Single Sponsor Page Template

**Scope**: Create a template for displaying individual sponsor posts with structured information.

**Sections**: Logo (featured image) | Organisation name | Description (post content) | Website link (button) | Events sponsored (list with level pills) | Contact info (if public)

**Files affected**: New template override `plugin/templates/single-ems_sponsor.php` (loaded via `single_template` filter in `EMS_Public`)
**Acceptance criteria**: Template renders sponsor data. Website link opens in new tab. Events list shows linked events with coloured level pills.
**Parallel eligible**: YES (with 7.1, 7.2, 7.3)

### Task 7.5: Level Pills CSS

**Scope**: Define CSS for sponsorship level pills/badges.

**CSS classes**: `.ems-sponsor-level-pill` with `.ems-sponsor-level-pill--{slug}` modifiers. Inline `style` attribute for custom colour. Pill shape with rounded corners.

**Default colour classes**:
- `.ems-sponsor-level-pill--bronze` { background: #CD7F32; color: #fff; }
- `.ems-sponsor-level-pill--silver` { background: #C0C0C0; color: #333; }
- `.ems-sponsor-level-pill--gold` { background: #FFD700; color: #333; }
- `.ems-sponsor-level-pill--platinum` { background: #E5E4E2; color: #333; }

**Files affected**: `plugin/public/css/ems-public.css`
**Parallel eligible**: YES (independent, can be done early)

### Task 7.6: Graceful Shortcode Degradation

**Scope**: All sponsor shortcodes check `_ems_sponsorship_enabled` event meta and return empty/message when disabled.

**Behaviour**:
- If `_ems_sponsorship_enabled` is false: return empty string (frontend), show admin notice in preview
- If event_id invalid/missing: return empty string (frontend), show admin notice in preview
- If no sponsors linked: return empty string or "No sponsors yet" message

**Files affected**: All sponsor shortcode classes
**Acceptance criteria**: No PHP errors or broken HTML when sponsorship disabled. Clean degradation.
**Parallel eligible**: YES (with all Phase 7 tasks)

### Task 7.7: Sponsorship Levels Public Display

**Scope**: Create `[ems_sponsor_levels event_id="123"]` shortcode showing available sponsorship packages as a public prospectus.

**Output**: Card for each level showing: level name (with coloured header) | value | slots remaining | recognition text | "Express Interest" button linking to EOI form

**Files affected**: Extension of sponsor shortcodes
**Parallel eligible**: YES (with 7.1-7.4)

---

## Phase 8: Sponsor Portal Enhancements

**Dependency**: Phases 1 and 6 complete
**Parallelizable with**: Phase 7

### Task 8.1: Organisation Profile in Portal

**Scope**: Add a profile view/edit section to the sponsor portal showing organisation details with edit capability.

**Files affected**: `plugin/public/shortcodes/class-ems-sponsor-shortcodes.php`, `plugin/includes/collaboration/class-ems-sponsor-portal.php`
**Acceptance criteria**: Profile section renders all organisation fields. Edit form saves changes. Only the linked sponsor's data is editable.
**Parallel eligible**: YES (with 8.2, 8.3)

### Task 8.2: EOI Status Tracking in Portal

**Scope**: Add EOI submissions list to sponsor portal showing status per event.

**Output**: Table with: Event Name | Submitted Date | Status (pill) | Preferred Level | Actions (View Details)

**Files affected**: `class-ems-sponsor-shortcodes.php`
**Parallel eligible**: YES (with 8.1, 8.3)

### Task 8.3: Level Details in Portal Event Cards

**Scope**: Show sponsorship level name, value, and recognition text in the portal's event cards.

**Files affected**: `class-ems-sponsor-shortcodes.php`
**Parallel eligible**: YES (with 8.1, 8.2)

---

## Phase 9: Email Notifications

**Dependency**: Phases 5, 6 in progress/complete
**Parallelizable with**: Phases 5, 6, 7, 8

### Task 9.1: Sponsor Onboarding Confirmation Email

**Template name**: `sponsor-onboarding-confirmation`
**To**: New sponsor | **Trigger**: Onboarding form submission
**Variables**: sponsor_name, login_url, portal_url

### Task 9.2: New Sponsor Admin Notification Email

**Template name**: `sponsor-onboarding-admin`
**To**: Admin | **Trigger**: Onboarding form submission
**Variables**: sponsor_name, organisation, abn, contact_email, admin_edit_url

### Task 9.3: EOI Received Confirmation Email

**Template name**: `sponsor-eoi-confirmation`
**To**: Sponsor | **Trigger**: EOI submission
**Variables**: sponsor_name, event_name, preferred_level, submitted_date

### Task 9.4: EOI Admin Notification Email

**Template name**: `sponsor-eoi-admin`
**To**: Event organiser | **Trigger**: EOI submission
**Variables**: sponsor_name, event_name, preferred_level, estimated_value, admin_eoi_url

### Task 9.5: EOI Approved Email

**Template name**: `sponsor-eoi-approved`
**To**: Sponsor | **Trigger**: Admin approves EOI
**Variables**: sponsor_name, event_name, assigned_level, recognition_text, next_steps_url

### Task 9.6: EOI Rejected Email

**Template name**: `sponsor-eoi-rejected`
**To**: Sponsor | **Trigger**: Admin rejects EOI
**Variables**: sponsor_name, event_name, rejection_reason (optional)

### Task 9.7: Sponsorship Level Change Email

**Template name**: `sponsor-level-changed`
**To**: Sponsor | **Trigger**: Admin changes sponsor's level
**Variables**: sponsor_name, event_name, old_level, new_level

### Task 9.8: Register All Templates with Email Manager

**Scope**: Register all 7 new templates with `EMS_Email_Manager` class following existing patterns.

**Files affected**: `plugin/includes/notifications/class-ems-email-manager.php`
**Acceptance criteria**: All templates registered. Placeholder variables resolved. HTML email format. Subject lines internationalised.

**All Phase 9 tasks are parallel eligible** with each other and with Phases 5-8.

---

## Phase 10: Security, Validation & Compliance

**Can run in parallel with all phases**

### Task 10.1: ABN/ACN Validation Utility

**Scope**: Create validation functions for Australian Business Number (11-digit, weighted check digit) and Australian Company Number (9-digit, check digit).

**Files affected**: `plugin/includes/utilities/class-ems-validator.php`
**Acceptance criteria**: ABN validation passes for valid ABNs, fails for invalid. ACN validation likewise. Functions: `validate_abn($abn)`, `validate_acn($acn)`.

### Task 10.2: Input Sanitisation for All New Fields

**Scope**: Ensure all new form fields have sanitisation callbacks registered.

**Files affected**: Meta registration (Phase 1), form handlers (Phases 5, 6)
**Acceptance criteria**: Every text field: `sanitize_text_field()`. Every textarea: `sanitize_textarea_field()`. Every URL: `esc_url_raw()`. Every email: `sanitize_email()`. Every number: `absint()` or `floatval()`. Every array: `array_map('sanitize_text_field')`.

### Task 10.3: CSRF Protection for New Endpoints

**Scope**: Add nonce verification to all new AJAX endpoints and form handlers.

**Files affected**: All new AJAX handlers, form submission handlers
**Acceptance criteria**: Every POST request verified with `wp_verify_nonce()`. Every form outputs `wp_nonce_field()`.

### Task 10.4: Rate Limiting

**Scope**: Implement rate limiting on onboarding and EOI form submissions.

**Implementation**: Store submission timestamps in transients keyed by IP. Limit: 3 onboarding attempts per hour, 5 EOI submissions per hour.

**Files affected**: Form submission handlers
**Acceptance criteria**: Over-limit submissions rejected with user-friendly message.

### Task 10.5: Capability Checks for Admin Pages

**Scope**: Add `current_user_can()` checks to all new admin pages and AJAX handlers.

**Files affected**: All admin handlers, AJAX endpoints
**Acceptance criteria**: Unauthorised users get `wp_die()` with appropriate message.

### Task 10.6: Audit Logging

**Scope**: Log sponsor status changes, EOI approvals/rejections, and sponsorship level changes.

**Files affected**: Relevant handlers, using existing `EMS_Logger`
**Acceptance criteria**: All actions logged with context (who, what, when, which sponsor/event).

---

## Phase 11: Testing & Documentation

**Status**: ✅ COMPLETE
**Deliverables**: PHPUnit test suite + comprehensive documentation

### Task 11.1: Sponsorship Level CRUD Tests ✅
**File**: `tests/sponsorship/test-sponsorship-levels.php`
**Coverage**: All CRUD operations, slot management, slug uniqueness, default population

### Task 11.2: Onboarding Form Validation Tests ✅
**File**: `tests/sponsorship/test-onboarding-validation.php`
**Coverage**: ABN/ACN validation, email format, required fields, duplicate detection, rate limiting

### Task 11.3: EOI Submission and Approval Flow Tests ✅
**File**: `tests/sponsorship/test-eoi-flow.php`
**Coverage**: EOI insertion, duplicate prevention, approval workflow, slot increments, status transitions

### Task 11.4: Shortcode Graceful Degradation Tests ✅
**File**: `tests/sponsorship/test-shortcode-degradation.php`
**Coverage**: Invalid event IDs, sponsorship disabled, missing attributes, deleted events, no PHP errors

### Task 11.5: Integration Test - Complete Onboarding Flow ✅
**File**: `tests/sponsorship/test-integration-onboarding.php`
**Coverage**: End-to-end onboarding from form render to user/post creation, duplicate prevention

### Task 11.6: Integration Test - Complete EOI Flow ✅
**File**: `tests/sponsorship/test-integration-eoi.php`
**Coverage**: End-to-end EOI submission, admin approval/rejection, slot management, email notifications

### Task 11.7: Update Plugin Documentation ✅
**Files Created**:
- `tests/phpunit.xml.dist` - PHPUnit configuration
- `tests/bootstrap.php` - Test environment bootstrap
- `SPONSORSHIP-TESTING-GUIDE.md` - Comprehensive testing guide with manual procedures
- `SPONSORSHIP-DOCS.md` - Complete system documentation (schema, shortcodes, API, workflows)

---

## Summary Statistics

| Phase | Tasks | New Files (est.) | Parallel Workers |
|-------|-------|------------------|------------------|
| 1. Data Model | 8 | 1-2 | 3 |
| 2. Admin Event Config | 7 | 2-3 | 3 (after 2.1+2.7) |
| 3. Admin Sponsor Mgmt | 5 | 2-3 | 3 |
| 4. Form Framework | 8 | 3-4 | 4 (after 4.1) |
| 5. Onboarding Form | 13 | 1-2 | 6 (steps parallel) |
| 6. EOI Form | 11 | 1-2 | 4 (steps parallel) |
| 7. Public Display | 7 | 2-3 | All parallel |
| 8. Portal Enhancements | 3 | 0 | All parallel |
| 9. Email Notifications | 8 | 1-2 | All parallel |
| 10. Security | 6 | 0-1 | Woven into phases |
| 11. Testing | 7 | 5-7 | Follows phases |
| **Total** | **83** | **~18-29** | - |

## Recommended Execution Order with Maximum Parallelism

### Wave 1 (Phase 1 only - foundation)
- **Worker A**: Tasks 1.1, 1.2, 1.3, 1.4 (meta fields)
- **Worker B**: Tasks 1.5, 1.6 (new tables)
- **Worker C**: Task 1.7 (event meta)
- **Sync point**: Task 1.8 (migration logic, needs 1.5+1.6)

### Wave 2 (Phases 2+3+4 in parallel)
- **Worker A**: Phase 2 (admin event config) - start with 2.7, then 2.1, then 2.2-2.6
- **Worker B**: Phase 3 (admin sponsor management) - tasks 3.1-3.5
- **Worker C**: Phase 4 (form framework) - start with 4.1, then 4.2-4.8
- **Worker D**: Phase 10 tasks 10.1, 10.2 (security utilities)

### Wave 3 (Phases 5+6+7 in parallel)
- **Worker A**: Phase 5 (onboarding form) - 5.1 then 5.2-5.7 parallel, then 5.8-5.9
- **Worker B**: Phase 6 (EOI form) - 6.1-6.2 then 6.3-6.6 parallel, then 6.7-6.8
- **Worker C**: Phase 7 (public display) - all tasks parallel
- **Worker D**: Phase 9 (email templates) - all tasks parallel

### Wave 4 (Phases 8+11)
- **Worker A**: Phase 8 (portal enhancements)
- **Worker B**: Phase 11 (testing)

## Key Files Reference

| File | Purpose |
|------|---------|
| `plugin/includes/custom-post-types/class-ems-cpt-sponsor.php` | Sponsor CPT & taxonomy (52 lines, now with admin menu) |
| `plugin/includes/collaboration/class-ems-sponsor-portal.php` | Main sponsor portal backend (918 lines) |
| `plugin/public/shortcodes/class-ems-sponsor-shortcodes.php` | Shortcodes and AJAX handlers (481 lines) |
| `plugin/includes/class-ems-activator.php` | Database table creation |
| `plugin/includes/class-ems-core.php` | Dependency loading, hook registration |
| `plugin/includes/user-roles/class-ems-roles.php` | Sponsor role definition |
| `plugin/public/css/ems-public.css` | Sponsor portal styling |
| `plugin/includes/utilities/class-ems-validator.php` | Validation utilities |
| `plugin/includes/notifications/class-ems-email-manager.php` | Email template system |
| `plugin/admin/class-ems-admin.php` | Admin menu and AJAX handlers |
| `plugin/public/class-ems-public.php` | Public shortcode registration |
| `plugin/includes/custom-post-types/class-ems-cpt-event.php` | Event CPT with meta boxes |

## New Files to Create

| File | Phase | Purpose |
|------|-------|---------|
| `plugin/includes/sponsor/class-ems-sponsor-meta.php` | 1 | Meta field registration and helpers |
| `plugin/includes/sponsor/class-ems-sponsorship-levels.php` | 2 | Level CRUD operations |
| `plugin/includes/forms/class-ems-multi-step-form.php` | 4 | Multi-step form engine |
| `plugin/includes/forms/class-ems-form-renderer.php` | 4 | Field type renderer |
| `plugin/public/shortcodes/class-ems-sponsor-onboarding-shortcode.php` | 5 | Onboarding form |
| `plugin/public/shortcodes/class-ems-sponsor-eoi-shortcode.php` | 6 | EOI form |
| `plugin/public/js/ems-forms.js` | 4 | Client-side form validation |
| `plugin/public/js/ems-carousel.js` | 7 | Sponsor logo carousel |
| `plugin/templates/single-ems_sponsor.php` | 7 | Single sponsor template |
| `plugin/admin/views/meta-box-sponsorship-levels.php` | 2 | Level management UI |
| `plugin/admin/views/admin-page-eoi-list.php` | 3 | EOI list admin page |
| `plugin/admin/views/admin-page-eoi-detail.php` | 3 | EOI detail admin page |
