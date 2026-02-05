# Sponsor Functionality Review & Gap Analysis

## Current Sponsor Functionality (v1.4.0)

### Data Layer
- **`ems_sponsor` custom post type** — supports title, editor, thumbnail; public but hidden from admin menu (`show_in_menu: false`)
- **`ems_sponsor_level` taxonomy** — hierarchical, applied to sponsor CPT (global, not per-event)
- **`wp_ems_sponsor_events` table** — links sponsors to events with a freetext `sponsor_level` VARCHAR(50)
- **`wp_ems_sponsor_files` table** — file metadata with visibility, download counter, user tracking
- **User meta `_ems_sponsor_id`** — links WP user accounts to sponsor posts

### User Role
- **`ems_sponsor`** role with capabilities: `read`, `upload_files`, `access_ems_sponsor_portal`, `upload_ems_sponsor_files`, `download_ems_sponsor_files`, `view_linked_ems_events`

### Portal & Shortcodes
- **`[ems_sponsor_portal]`** — authenticated dashboard with welcome header, 4 statistics cards (events, files, downloads, storage), event list with sponsor level badges, per-event file upload/download/delete via AJAX
- **`[ems_sponsor_files sponsor_id="123" event_id="456"]`** — simple widget listing files with download links

### Backend Operations (EMS_Sponsor_Portal class)
- Link/unlink sponsors to events
- File upload with MIME/size validation (25MB max, 14 file types)
- File download with access control
- File deletion with permission checks
- Statistics aggregation
- Organizer email notification on file upload

### AJAX Endpoints
- `ems_upload_sponsor_file` — file upload with nonce verification
- `ems_delete_sponsor_file` — file deletion with nonce verification

### File Download Handler
- Via `template_redirect` hook using `?ems_download_sponsor_file={id}` URL parameter

### CSS
- ~300 lines of responsive sponsor portal styles (desktop 4-col grid → tablet 2-col → mobile 1-col)

### Hooks/Filters
- `ems_sponsor_max_file_size` — default 25MB
- `ems_sponsor_allowed_file_types` — configurable file type list

---

## What Does NOT Exist

1. No admin pages for sponsor management
2. No event-level sponsorship enable/disable toggle
3. No event meta box for sponsors
4. No sponsor onboarding form
5. No sponsor EOI (Expression of Interest) form
6. No sponsor logo carousel/gallery shortcode
7. No sponsor page template (relies on default CPT single template)
8. No sponsorship level definitions with value/slots/recognition
9. No multi-step form infrastructure
10. No sponsor organisation detail fields (ABN, address, insurance, compliance, etc.)
11. No shortcode for displaying event sponsors publicly
12. No graceful degradation when sponsorship is disabled on an event
13. No REST API endpoints for sponsors
14. No EOI submission/review/approval workflow
15. No sponsor-specific email templates beyond file upload notification

---

## Gap Analysis: Current vs. Required

### AREA 1: Sponsor as a User Type with Public Page

| Requirement | Current State | Gap |
|---|---|---|
| Sponsor is a user type | `ems_sponsor` role exists | Partial — role exists but has minimal capabilities |
| Sponsor page/post with org info | `ems_sponsor` CPT exists with title/editor/thumbnail | **GAP** — No structured fields for org details, website URL, logo. No dedicated single template. Post type hidden from admin menu |
| Links to sponsor website | Not implemented | **GAP** — No website URL meta field, no template rendering |

### AREA 2: Event-Sponsor Relationship & Display

| Requirement | Current State | Gap |
|---|---|---|
| Event can have multiple sponsors | `wp_ems_sponsor_events` table exists | Partial — link/unlink works but only via code, no admin UI |
| Sponsor levels with coloured pills (Bronze/Silver/Gold) | `sponsor_level` stored as freetext varchar | **GAP** — No predefined levels, no colour mapping, no pill styling beyond a single generic badge class |
| Carousel/gallery of sponsor logos linking to sponsor pages | Not implemented | **GAP** — No shortcode, no template, no JS carousel |
| Sponsorship enabled/disabled per event | Not implemented | **GAP** — No event meta field, no toggle in event editor |
| Levels enabled/disabled per event | Not implemented | **GAP** — No event meta field |
| Graceful shortcode handling when disabled | Not implemented | **GAP** — Shortcodes don't check event sponsorship state |

### AREA 3: Admin Backend for Sponsorship Levels

| Requirement | Current State | Gap |
|---|---|---|
| Define sponsorship levels per event | `ems_sponsor_level` taxonomy exists but is global, not event-specific | **GAP** — Need event-level sponsorship tier configuration |
| Level value (AUD amount) | Not implemented | **GAP** |
| Slots available per level | Not implemented | **GAP** |
| Proposed recognition per level | Not implemented | **GAP** |
| Default template auto-population | Not implemented | **GAP** |
| Admin meta box on event editor | Not implemented | **GAP** — No sponsor-related meta boxes on event CPT |

### AREA 4: Sponsor Onboarding Form (Multi-Page, Event-Independent)

**Entirely missing.** No multi-step form infrastructure exists. All of the following sections need to be built from scratch:

| Section | Fields Required | Current State |
|---|---|---|
| 1. Organisation Details | Legal name, trading name, ABN/ACN, address, website, country/parent | NONE |
| 2. Primary Contact & Signatory | Contact name/role/email/phone, signatory name/title/email, marketing contact | NONE |
| 3. Industry Classification | Sector multiselect, MA member, MTAA member, other codes, TGA sponsor number | NONE |
| 4. Products & Scope | Products/services, ARTG listings, scheduling, device class, off-label, TGA alerts, educational relation | NONE |
| 9. Legal/Insurance/Compliance | Public liability, product liability, PI insurance, workers comp, code compliance, adverse findings, legal proceedings, transparency | NONE |
| 10. Conflict of Interest | Qld Health awareness, personal relationships, procurement, transfers of value, preferential treatment | NONE |

This form should create: (a) a WordPress user account with `ems_sponsor` role, (b) an `ems_sponsor` post with all captured data, (c) link user to sponsor post via `_ems_sponsor_id` meta.

### AREA 5: Sponsor Registration EOI Form (Event-Specific)

**Entirely missing.** Needs to be linked to a specific event ID and capture:

| Section | Fields Required | Current State |
|---|---|---|
| 5. Sponsorship Commitment & Preferences | Tier preference, estimated value, contribution nature, conditions, speaker nomination, duration, events of interest | NONE |
| 6. Exclusivity & Category Protection | Exclusivity requested, category, shared presence, competitor objections, conditions | NONE |
| 7. Desired Recognition & Visibility | Recognition elements, trade display, representatives, product samples, promotional material, delegate data collection, social media | NONE |
| 8. Educational Independence | Content control acknowledgement, influence disclosure, therapeutic claims, review/approval | NONE |

### AREA 6: Infrastructure & Supporting Systems

| Requirement | Current State | Gap |
|---|---|---|
| Multi-page form framework | Not implemented | **GAP** — Need session/state management, step validation, progress indicator, save-and-resume |
| Sponsor post meta fields schema | Only `_ems_sponsor_id` on users | **GAP** — Need 60+ structured meta fields |
| Database tables for EOI submissions | Not implemented | **GAP** — Need EOI table with status tracking |
| Admin review/approval workflow for EOIs | Not implemented | **GAP** |
| Email notifications for onboarding/EOI | File upload notification only | **GAP** — Need confirmation, review, approval/rejection emails |
| Sponsor logo storage & retrieval | Thumbnail support exists on CPT | Partial — thumbnail exists but no structured logo management for carousel |
| REST API for sponsor operations | Not implemented | **GAP** |

---

## Comprehensive Task List

### Phase 1: Data Model & Schema Foundation

| # | Task | Priority | Complexity |
|---|---|---|---|
| 1.1 | Add sponsor organisation meta fields to `ems_sponsor` CPT (legal name, trading name, ABN/ACN, registered address, website URL, country/parent company) | High | Medium |
| 1.2 | Add sponsor contact meta fields (primary contact name/role/email/phone, signatory name/title/email, marketing contact) | High | Medium |
| 1.3 | Add industry classification meta fields (sector array, MA member, MTAA member, other codes, TGA sponsor number) | High | Medium |
| 1.4 | Add products & scope meta fields (products, ARTG listings, scheduling, device class, off-label flag, TGA alerts, educational relation) | High | Medium |
| 1.5 | Add legal/insurance/compliance meta fields (6 insurance fields, code compliance, adverse findings, legal proceedings, transparency) | High | Medium |
| 1.6 | Add conflict of interest meta fields (Qld Health, relationships, procurement, transfers of value, preferential treatment) | High | Medium |
| 1.7 | Create `wp_ems_sponsorship_levels` table (event_id, level_name, level_slug, colour, value_aud, slots_total, slots_filled, recognition_text, sort_order, enabled) | High | Medium |
| 1.8 | Create `wp_ems_sponsor_eoi` table (sponsor_id, event_id, status, submitted_at, reviewed_at, reviewed_by, all EOI form fields as JSON or individual columns) | High | Medium |
| 1.9 | Add event-level meta fields: `_ems_sponsorship_enabled` (bool), `_ems_sponsorship_levels_enabled` (bool) | High | Low |
| 1.10 | Add migration logic in activator for new tables and meta fields | High | Medium |
| 1.11 | Update `ems_sponsor` CPT registration to show in admin menu with proper labels and expanded `supports` array | Medium | Low |

### Phase 2: Admin Backend — Event Sponsorship Configuration

| # | Task | Priority | Complexity |
|---|---|---|---|
| 2.1 | Create sponsorship settings meta box on Event CPT editor with enable/disable toggle | High | Medium |
| 2.2 | Build sponsorship levels management UI within event meta box (add/edit/delete levels with name, colour picker, value, slots, recognition) | High | High |
| 2.3 | Implement default template auto-population for sponsorship levels (Bronze/Silver/Gold with preset values, colours #CD7F32/#C0C0C0/#FFD700, slot counts, and recognition text) | Medium | Medium |
| 2.4 | Create admin list view for sponsors linked to an event, showing level, status, and actions | High | Medium |
| 2.5 | Build admin interface to link/unlink sponsors to events and assign levels (using existing `link_sponsor_to_event()` backend) | High | Medium |
| 2.6 | Add slot availability tracking (prevent over-allocation per level) | Medium | Medium |
| 2.7 | Save/load sponsorship level data with proper nonce verification and sanitisation | High | Medium |

### Phase 3: Admin Backend — Sponsor Management

| # | Task | Priority | Complexity |
|---|---|---|---|
| 3.1 | Add `ems_sponsor` CPT to admin menu as a submenu item under EMS | High | Low |
| 3.2 | Create sponsor detail meta boxes in admin editor showing all organisation fields (sections 1–4, 9–10) | High | High |
| 3.3 | Build admin sponsor list table with columns: name, trading name, ABN, linked events count, status | Medium | Medium |
| 3.4 | Add admin capability to manually create sponsor accounts (user + post + link) | Medium | Medium |
| 3.5 | Build EOI submissions admin list page with filters (by event, by status: pending/approved/rejected/withdrawn) | High | High |
| 3.6 | Build EOI detail/review admin page with approve/reject actions and notes | High | High |
| 3.7 | Add admin dashboard widget showing pending EOI count and recent sponsor activity | Low | Medium |

### Phase 4: Multi-Page Form Framework

| # | Task | Priority | Complexity |
|---|---|---|---|
| 4.1 | Build reusable multi-step form engine (step registration, navigation, validation, progress bar, session state) | High | High |
| 4.2 | Implement form state persistence (WP transients or custom table) for save-and-resume | Medium | Medium |
| 4.3 | Build client-side step validation with inline error display | High | Medium |
| 4.4 | Build server-side step validation with per-field rules | High | Medium |
| 4.5 | Create form renderer supporting field types: text, textarea, email, phone, URL, select, multiselect, checkbox, radio, file upload, conditional display | High | High |
| 4.6 | Add progress indicator component (step dots/bar with labels) | Medium | Low |
| 4.7 | Add form summary/review step before final submission | Medium | Medium |
| 4.8 | Create AJAX endpoints for form step submission and validation | High | Medium |

### Phase 5: Sponsor Onboarding Form

| # | Task | Priority | Complexity |
|---|---|---|---|
| 5.1 | Create `[ems_sponsor_onboarding]` shortcode | High | Low |
| 5.2 | Build Step 1: Organisation Details (fields 1.1–1.6 from requirements) | High | Medium |
| 5.3 | Build Step 2: Primary Contact & Authorised Signatory (fields 2.1–2.7) | High | Medium |
| 5.4 | Build Step 3: Industry Classification (fields 3.1–3.5 with conditional logic) | High | Medium |
| 5.5 | Build Step 4: Products, Services & Scope (fields 4.1–4.8 with ARTG validation) | High | High |
| 5.6 | Build Step 5: Legal, Insurance & Compliance (fields 9.1–9.10) | High | Medium |
| 5.7 | Build Step 6: Conflict of Interest & Public Sector (fields 10.1–10.5) | High | Medium |
| 5.8 | Build Step 7: Review & Submit (summary of all entered data with edit links per section) | High | Medium |
| 5.9 | Implement form submission handler: create WP user account, create `ems_sponsor` post, populate all meta fields, link user to sponsor post | High | High |
| 5.10 | Send confirmation email to sponsor on successful onboarding | Medium | Low |
| 5.11 | Send notification email to admin on new sponsor onboarding | Medium | Low |
| 5.12 | Handle duplicate detection (ABN/ACN check, email check) | Medium | Medium |
| 5.13 | Add CAPTCHA/honeypot spam protection to onboarding form | Medium | Low |

### Phase 6: Sponsor EOI Form (Event-Specific)

| # | Task | Priority | Complexity |
|---|---|---|---|
| 6.1 | Create `[ems_sponsor_eoi event_id="123"]` shortcode | High | Low |
| 6.2 | Implement event validation (event exists, sponsorship enabled, slots available) | High | Medium |
| 6.3 | Build Step 1: Sponsorship Commitment & Preferences (fields 5.1–5.7, dynamically populated from event's configured levels) | High | High |
| 6.4 | Build Step 2: Exclusivity & Category Protection (fields 6.1–6.5) | High | Medium |
| 6.5 | Build Step 3: Desired Recognition & Visibility (fields 7.1–7.7) | High | Medium |
| 6.6 | Build Step 4: Educational Independence & Content Boundaries (fields 8.1–8.4) | High | Medium |
| 6.7 | Build Step 5: Review & Submit | High | Medium |
| 6.8 | Implement EOI submission handler: validate sponsor exists, check event capacity, save to `wp_ems_sponsor_eoi` table, set status to 'pending' | High | High |
| 6.9 | Send EOI confirmation email to sponsor | Medium | Low |
| 6.10 | Send EOI notification email to event organiser | Medium | Low |
| 6.11 | Implement EOI approval flow: on approval, auto-link sponsor to event at specified level, decrement available slots | High | High |
| 6.12 | Implement EOI rejection flow: notification to sponsor with optional reason | Medium | Medium |
| 6.13 | Prevent duplicate EOI submissions (same sponsor + same event) | Medium | Low |
| 6.14 | Require authentication: logged-in sponsors only, with link to onboarding form for new sponsors | High | Medium |

### Phase 7: Public-Facing Display

| # | Task | Priority | Complexity |
|---|---|---|---|
| 7.1 | Create `[ems_event_sponsors event_id="123"]` shortcode displaying sponsors grouped by level with coloured pills | High | Medium |
| 7.2 | Create `[ems_sponsor_carousel event_id="123"]` shortcode with logo carousel (auto-scroll, responsive, links to sponsor pages) | High | High |
| 7.3 | Create `[ems_sponsor_gallery event_id="123"]` shortcode with static logo grid (responsive, links to sponsor pages) | Medium | Medium |
| 7.4 | Build single sponsor page template (`single-ems_sponsor.php`) showing organisation name, logo, description, website link, linked events | High | Medium |
| 7.5 | Implement sponsor archive page template (`archive-ems_sponsor.php`) | Medium | Medium |
| 7.6 | Define and implement colour-coded level pills: Bronze (#CD7F32), Silver (#C0C0C0), Gold (#FFD700), Platinum (#E5E4E2), with custom level colours supported | High | Low |
| 7.7 | Add CSS for carousel component (navigation arrows, dots, auto-play, responsive breakpoints) | High | Medium |
| 7.8 | Add JavaScript for carousel functionality (Slick/Swiper integration or lightweight custom) | High | Medium |
| 7.9 | Implement graceful degradation: all sponsor shortcodes check `_ems_sponsorship_enabled` and return empty/message when disabled | High | Medium |
| 7.10 | Implement graceful handling: shortcodes with invalid/missing event_id return informative admin notice in preview but empty string on frontend | Medium | Low |
| 7.11 | Create `[ems_sponsor_levels event_id="123"]` shortcode showing available sponsorship packages (level name, value, slots remaining, recognition) as a public prospectus | Medium | Medium |

### Phase 8: Sponsor Portal Enhancements

| # | Task | Priority | Complexity |
|---|---|---|---|
| 8.1 | Add organisation profile view/edit section to sponsor portal | Medium | Medium |
| 8.2 | Add EOI submissions list to sponsor portal (status tracking per event) | High | Medium |
| 8.3 | Add sponsor page preview link to portal | Low | Low |
| 8.4 | Show sponsorship level details (value, recognition) in portal event cards | Medium | Low |
| 8.5 | Add notification preferences to sponsor portal | Low | Medium |

### Phase 9: Email Notifications

| # | Task | Priority | Complexity |
|---|---|---|---|
| 9.1 | Create email template: Sponsor Onboarding Confirmation (to sponsor) | Medium | Low |
| 9.2 | Create email template: New Sponsor Onboarding (to admin) | Medium | Low |
| 9.3 | Create email template: EOI Received Confirmation (to sponsor) | Medium | Low |
| 9.4 | Create email template: EOI Received Notification (to event organiser) | Medium | Low |
| 9.5 | Create email template: EOI Approved (to sponsor, with level details and next steps) | Medium | Low |
| 9.6 | Create email template: EOI Rejected (to sponsor, with optional reason) | Medium | Low |
| 9.7 | Create email template: Sponsorship Level Change (to sponsor) | Low | Low |
| 9.8 | Register all templates with existing `EMS_Email_Manager` | Medium | Medium |

### Phase 10: Security, Validation & Compliance

| # | Task | Priority | Complexity |
|---|---|---|---|
| 10.1 | ABN/ACN format validation (11-digit ABN, 9-digit ACN with check digit) | High | Low |
| 10.2 | Input sanitisation for all new form fields (XSS prevention) | High | Medium |
| 10.3 | CSRF protection (nonces) for all new forms and AJAX endpoints | High | Medium |
| 10.4 | Rate limiting on onboarding and EOI form submissions | Medium | Medium |
| 10.5 | File upload restrictions on any new upload fields (if any added to onboarding) | Medium | Low |
| 10.6 | Add capability checks to all new admin pages and AJAX handlers | High | Medium |
| 10.7 | Implement data export capability for sponsor data (privacy compliance) | Low | Medium |
| 10.8 | Add audit logging for sponsor status changes, EOI approvals/rejections | Medium | Medium |

### Phase 11: Testing & Documentation

| # | Task | Priority | Complexity |
|---|---|---|---|
| 11.1 | Unit tests for sponsorship level CRUD operations | Medium | Medium |
| 11.2 | Unit tests for onboarding form validation (ABN, required fields, duplicates) | Medium | Medium |
| 11.3 | Unit tests for EOI submission and approval flow | Medium | Medium |
| 11.4 | Unit tests for shortcode graceful degradation | Medium | Low |
| 11.5 | Integration tests for complete onboarding flow (form → user → post → meta) | Medium | High |
| 11.6 | Integration tests for EOI flow (submit → review → approve → link) | Medium | High |
| 11.7 | Update plugin documentation with new shortcodes, settings, and capabilities | Low | Low |

---

## Summary Statistics

| Phase | Tasks | New Files (est.) | New DB Tables |
|---|---|---|---|
| 1. Data Model | 11 | 0–2 | 2 |
| 2. Admin — Event Config | 7 | 1–2 | 0 |
| 3. Admin — Sponsor Mgmt | 7 | 2–3 | 0 |
| 4. Multi-Page Form Framework | 8 | 2–3 | 0–1 |
| 5. Onboarding Form | 13 | 1–2 | 0 |
| 6. EOI Form | 14 | 1–2 | 0 |
| 7. Public Display | 11 | 3–4 | 0 |
| 8. Portal Enhancements | 5 | 0–1 | 0 |
| 9. Email Notifications | 8 | 1–2 | 0 |
| 10. Security & Validation | 8 | 1–2 | 0 |
| 11. Testing | 7 | 5–7 | 0 |
| **Total** | **99** | **~17–30** | **2** |

## Critical Path

```
Phase 1 (Data Model)
    ├── Phase 2 (Admin Event Config)
    │     └── Phase 7 (Public Display)
    ├── Phase 3 (Admin Sponsor Mgmt)
    │     └── Phase 7 (Public Display)
    └── Phase 4 (Form Framework)
          ├── Phase 5 (Onboarding Form)
          └── Phase 6 (EOI Form)

Phase 8 (Portal) depends on Phases 1, 6
Phase 9 (Email) can begin alongside Phases 5, 6
Phase 10 (Security) runs in parallel throughout
Phase 11 (Testing) follows each phase completion
```

## Key Files Reference

| File | Purpose |
|---|---|
| `plugin/includes/custom-post-types/class-ems-cpt-sponsor.php` | Sponsor CPT & taxonomy definition (52 lines) |
| `plugin/includes/collaboration/class-ems-sponsor-portal.php` | Main sponsor portal functionality (918 lines) |
| `plugin/public/shortcodes/class-ems-sponsor-shortcodes.php` | Shortcodes and AJAX handlers (481 lines) |
| `plugin/includes/class-ems-activator.php` (L232–261) | Database table definitions |
| `plugin/includes/user-roles/class-ems-roles.php` (L258–273) | Sponsor role definition |
| `plugin/public/css/ems-public.css` (L822–1120) | Sponsor portal styling |
| `plugin/includes/utilities/class-ems-user-helper.php` (L519–524) | Sponsor portal redirect |
| `plugin/public/class-ems-public.php` (L55–57, L1556–1574) | Shortcode registration & download handler |
| `plugin/includes/custom-post-types/class-ems-cpt-event.php` (L173–209) | Event meta boxes (NO sponsor meta boxes currently) |
