# EMS Sponsorship System Documentation

**Plugin**: Event Management System (EMS)
**Version**: 1.5.0
**Last Updated**: 2026-02-05
**Status**: Phase 11 Complete - Testing & Documentation

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Database Schema](#database-schema)
3. [Shortcodes Reference](#shortcodes-reference)
4. [Email Templates](#email-templates)
5. [Admin Workflows](#admin-workflows)
6. [Sponsor Workflows](#sponsor-workflows)
7. [Hooks and Filters](#hooks-and-filters)
8. [API Reference](#api-reference)

---

## System Overview

The EMS Sponsorship System provides a comprehensive platform for managing event sponsors, sponsorship levels, expressions of interest (EOI), and sponsor onboarding.

### Key Features

- **Event-Level Sponsorship Configuration**: Enable/disable sponsorship per event
- **Flexible Sponsorship Levels**: Custom levels (Bronze/Silver/Gold or any custom names) with:
  - Custom colours for visual branding
  - Monetary value tracking
  - Slot management (limited or unlimited)
  - Recognition text per level
- **Sponsor Onboarding**: 6-step registration form covering:
  - Organisation details (ABN/ACN validation)
  - Contacts and signatories
  - Industry classification
  - Products and ARTG compliance
  - Legal, insurance, and compliance declarations
  - Conflict of interest disclosures
- **Expression of Interest (EOI)**: 4-step form for sponsors to apply for events
- **Sponsor Portal**: Dedicated portal for sponsors to manage their profile and event participation
- **Public Display**: Shortcodes for displaying sponsors on event pages

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     PUBLIC INTERFACE                         │
├─────────────────────────────────────────────────────────────┤
│  Shortcodes:                                                │
│  - [ems_sponsor_onboarding]   - Registration form          │
│  - [ems_sponsor_eoi]          - EOI submission             │
│  - [ems_sponsor_portal]       - Sponsor dashboard          │
│  - [ems_event_sponsors]       - Sponsor list by level      │
│  - [ems_sponsor_carousel]     - Logo carousel              │
│  - [ems_sponsor_gallery]      - Logo grid                  │
│  - [ems_sponsor_levels]       - Available packages         │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                     BUSINESS LOGIC                           │
├─────────────────────────────────────────────────────────────┤
│  Classes:                                                   │
│  - EMS_Sponsorship_Levels     - CRUD for levels            │
│  - EMS_Sponsor_Meta           - Meta field management       │
│  - EMS_Sponsor_Portal         - Portal backend             │
│  - EMS_Validator              - Validation utilities        │
│  - EMS_Email_Manager          - Email templates            │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                     DATA LAYER                               │
├─────────────────────────────────────────────────────────────┤
│  Custom Post Types:                                         │
│  - ems_sponsor                - Sponsor organisations       │
│  - event                      - Events (with sponsor meta)  │
│                                                             │
│  Custom Tables:                                             │
│  - wp_ems_sponsorship_levels  - Level definitions          │
│  - wp_ems_sponsor_events      - Sponsor-event links        │
│  - wp_ems_sponsor_eoi         - EOI submissions            │
│  - wp_ems_sponsor_files       - Uploaded documents         │
│                                                             │
│  User Roles:                                                │
│  - ems_sponsor                - Sponsor role (6 caps)       │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Table: `wp_ems_sponsorship_levels`

Event-specific sponsorship level definitions.

```sql
CREATE TABLE wp_ems_sponsorship_levels (
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

**Key Fields**:
- `level_slug`: Auto-generated from `level_name`, unique per event
- `colour`: Hex colour code for visual display
- `slots_total`: NULL = unlimited, integer = limited slots
- `slots_filled`: Automatically incremented/decremented on sponsor link/unlink

### Table: `wp_ems_sponsor_eoi`

Expression of Interest submissions.

```sql
CREATE TABLE wp_ems_sponsor_eoi (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    sponsor_id BIGINT(20) UNSIGNED NOT NULL,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    preferred_level VARCHAR(100) DEFAULT NULL,
    estimated_value DECIMAL(10,2) DEFAULT NULL,
    contribution_nature TEXT DEFAULT NULL,
    -- [40+ additional fields for EOI data]
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

**Status Values**: `pending` | `approved` | `rejected` | `withdrawn`

**Unique Constraint**: One EOI per sponsor per event

### Table: `wp_ems_sponsor_events`

Many-to-many relationship linking sponsors to events with levels.

```sql
CREATE TABLE wp_ems_sponsor_events (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    sponsor_id BIGINT(20) UNSIGNED NOT NULL,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    level_id BIGINT(20) UNSIGNED DEFAULT NULL,
    linked_at DATETIME NOT NULL,
    unlinked_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY sponsor_id (sponsor_id),
    KEY event_id (event_id),
    KEY level_id (level_id)
);
```

### Table: `wp_ems_sponsor_files`

Uploaded sponsor documents.

```sql
CREATE TABLE wp_ems_sponsor_files (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    sponsor_id BIGINT(20) UNSIGNED NOT NULL,
    event_id BIGINT(20) UNSIGNED NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    uploaded_at DATETIME NOT NULL,
    uploaded_by BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY sponsor_event (sponsor_id, event_id)
);
```

### Post Meta: `ems_sponsor` CPT

All sponsor metadata fields use `_ems_sponsor_` prefix:

| Meta Key | Type | Description |
|----------|------|-------------|
| `_ems_sponsor_legal_name` | string | Legal entity name |
| `_ems_sponsor_trading_name` | string | Trading/brand name |
| `_ems_sponsor_abn` | string | Australian Business Number (11 digits) |
| `_ems_sponsor_acn` | string | Australian Company Number (9 digits) |
| `_ems_sponsor_registered_address` | text | Registered business address |
| `_ems_sponsor_website_url` | url | Company website |
| `_ems_sponsor_contact_email` | email | Primary contact email |
| `_ems_sponsor_signatory_name` | string | Authorised signatory |
| `_ems_sponsor_industry_sectors` | array | Industry classifications |
| `_ems_sponsor_products` | text | Products/services description |
| `_ems_sponsor_code_compliance_agreed` | boolean | MA/MTAA code compliance |
| ... | ... | 40+ additional fields |

See `plugin/includes/sponsor/class-ems-sponsor-meta.php` for complete list.

### Post Meta: `event` CPT

| Meta Key | Type | Description |
|----------|------|-------------|
| `_ems_sponsorship_enabled` | boolean | Enable sponsorship for this event |
| `_ems_sponsorship_levels_enabled` | boolean | Use custom sponsorship levels |

---

## Shortcodes Reference

### `[ems_sponsor_onboarding]`

Renders the 6-step sponsor onboarding form.

**Usage**:
```
[ems_sponsor_onboarding]
```

**Attributes**: None

**Behavior**:
- Redirects logged-in sponsors to portal
- Shows login prompt for logged-in non-sponsors
- Renders multi-step form for guests

**Form Steps**:
1. Organisation Details (ABN/ACN validated)
2. Contact & Signatory
3. Industry Classification
4. Products & Scope
5. Legal, Insurance & Compliance
6. Conflict of Interest
7. Review & Submit

**On Submission**:
- Creates WP user with `ems_sponsor` role
- Creates `ems_sponsor` post
- Populates all meta fields
- Links user to sponsor post
- Sends confirmation email to sponsor
- Sends notification email to admin
- Redirects to sponsor portal

---

### `[ems_sponsor_eoi event_id="123"]`

Renders the EOI submission form for a specific event.

**Attributes**:
| Attribute | Required | Description |
|-----------|----------|-------------|
| `event_id` | Yes | Event post ID |

**Example**:
```
[ems_sponsor_eoi event_id="456"]
```

**Requirements**:
- User must be logged in
- User must have `ems_sponsor` role
- Event must exist and have sponsorship enabled
- Sponsor must not have existing EOI for this event

**Form Steps**:
1. Sponsorship Commitment & Preferences (shows available levels with slots)
2. Exclusivity & Category Protection
3. Desired Recognition & Visibility
4. Educational Independence & Content Boundaries
5. Review & Submit

**On Submission**:
- Creates record in `wp_ems_sponsor_eoi` with status `pending`
- Sends confirmation email to sponsor
- Sends notification email to event organiser

---

### `[ems_sponsor_portal]`

Renders the sponsor dashboard (existing from Phase 4).

**Usage**:
```
[ems_sponsor_portal]
```

**Features**:
- View/edit organisation profile
- View linked events with level badges
- View EOI submissions and status
- Upload/download files per event
- View contract details

**Authentication**: Requires logged-in user with `ems_sponsor` role

---

### `[ems_event_sponsors event_id="123"]`

Displays sponsors for an event, grouped by level with coloured pills.

**Attributes**:
| Attribute | Required | Default | Description |
|-----------|----------|---------|-------------|
| `event_id` | Yes | - | Event post ID |
| `group_by_level` | No | `true` | Group sponsors by level |
| `show_logos` | No | `true` | Display sponsor logos |
| `show_names` | No | `true` | Display sponsor names |

**Example**:
```
[ems_event_sponsors event_id="456" group_by_level="true" show_logos="true"]
```

**Output**:
```html
<div class="ems-event-sponsors">
  <div class="ems-sponsor-level-group">
    <h3><span class="ems-sponsor-level-pill" style="background:#FFD700">Gold</span></h3>
    <div class="ems-sponsor-logos">
      <a href="/sponsor/acme-medical">
        <img src="/uploads/acme-logo.png" alt="Acme Medical">
      </a>
    </div>
  </div>
  <!-- Silver, Bronze... -->
</div>
```

**Graceful Degradation**:
- Returns empty string if sponsorship disabled
- Returns empty string if event_id invalid
- Returns empty string if no sponsors linked

---

### `[ems_sponsor_carousel event_id="123"]`

Auto-scrolling responsive sponsor logo carousel.

**Attributes**:
| Attribute | Required | Default | Description |
|-----------|----------|---------|-------------|
| `event_id` | Yes | - | Event post ID |
| `autoplay` | No | `true` | Auto-scroll enabled |
| `speed` | No | `3000` | Scroll speed (ms) |
| `pause_on_hover` | No | `true` | Pause when hovering |

**Example**:
```
[ems_sponsor_carousel event_id="456" autoplay="true" speed="5000"]
```

**Features**:
- Responsive (6 logos desktop, 4 tablet, 2 mobile)
- Touch-friendly swipe
- Keyboard navigation
- ARIA labels for accessibility

---

### `[ems_sponsor_gallery event_id="123"]`

Static responsive grid of sponsor logos.

**Attributes**:
| Attribute | Required | Default | Description |
|-----------|----------|---------|-------------|
| `event_id` | Yes | - | Event post ID |
| `columns` | No | `4` | Number of columns |
| `group_by_level` | No | `true` | Group by level |
| `show_names` | No | `false` | Show names below logos |

**Example**:
```
[ems_sponsor_gallery event_id="456" columns="6" show_names="true"]
```

---

### `[ems_sponsor_levels event_id="123"]`

Public prospectus of available sponsorship packages.

**Attributes**:
| Attribute | Required | Description |
|-----------|----------|-------------|
| `event_id` | Yes | Event post ID |

**Example**:
```
[ems_sponsor_levels event_id="456"]
```

**Output**:
```html
<div class="ems-sponsor-levels">
  <div class="ems-level-card">
    <h3 style="background:#FFD700">Gold Sponsorship</h3>
    <p class="level-value">$5,000 AUD</p>
    <p class="level-slots">2 of 3 slots remaining</p>
    <div class="level-recognition">
      Premium recognition and exhibition space
    </div>
    <a href="/eoi-form" class="button">Express Interest</a>
  </div>
  <!-- Silver, Bronze... -->
</div>
```

---

## Email Templates

All email templates managed via `EMS_Email_Manager` class.

### Template: `sponsor-onboarding-confirmation`

**Trigger**: Sponsor completes onboarding form
**To**: New sponsor
**Subject**: Welcome to [Site Name] - Your Sponsor Account is Ready

**Variables**:
- `{sponsor_name}` - Sponsor organisation name
- `{login_url}` - Link to set password
- `{portal_url}` - Link to sponsor portal
- `{contact_name}` - Admin contact name
- `{contact_email}` - Admin email

**Template Location**: `plugin/includes/notifications/templates/sponsor-onboarding-confirmation.php`

---

### Template: `sponsor-onboarding-admin`

**Trigger**: New sponsor completes onboarding
**To**: Site admin / event organiser
**Subject**: New Sponsor Registration: {sponsor_name}

**Variables**:
- `{sponsor_name}` - Organisation name
- `{abn}` - ABN
- `{contact_email}` - Sponsor contact email
- `{admin_edit_url}` - Link to edit sponsor in admin
- `{registration_date}` - Timestamp

---

### Template: `sponsor-eoi-confirmation`

**Trigger**: Sponsor submits EOI
**To**: Sponsor
**Subject**: EOI Received for {event_name}

**Variables**:
- `{sponsor_name}`
- `{event_name}`
- `{preferred_level}`
- `{submitted_date}`
- `{portal_url}` - Link to track EOI status

---

### Template: `sponsor-eoi-admin`

**Trigger**: Sponsor submits EOI
**To**: Event organiser
**Subject**: New Sponsorship EOI for {event_name}

**Variables**:
- `{sponsor_name}`
- `{event_name}`
- `{preferred_level}`
- `{estimated_value}`
- `{admin_eoi_url}` - Link to review EOI

---

### Template: `sponsor-eoi-approved`

**Trigger**: Admin approves EOI
**To**: Sponsor
**Subject**: Your Sponsorship for {event_name} is Confirmed!

**Variables**:
- `{sponsor_name}`
- `{event_name}`
- `{assigned_level}` - Final level assigned (may differ from preferred)
- `{level_value}`
- `{recognition_text}` - Benefits description
- `{next_steps_url}` - Link to portal for next actions

---

### Template: `sponsor-eoi-rejected`

**Trigger**: Admin rejects EOI
**To**: Sponsor
**Subject**: Update on Your {event_name} Sponsorship Application

**Variables**:
- `{sponsor_name}`
- `{event_name}`
- `{rejection_reason}` - Optional reason provided by admin
- `{contact_email}` - Admin contact for questions

---

### Template: `sponsor-level-changed`

**Trigger**: Admin changes sponsor's level
**To**: Sponsor
**Subject**: Your {event_name} Sponsorship Level has been Updated

**Variables**:
- `{sponsor_name}`
- `{event_name}`
- `{old_level}`
- `{new_level}`
- `{new_recognition_text}`

---

## Admin Workflows

### Workflow 1: Configure Event for Sponsorship

1. **Edit Event** (wp-admin/post.php?post=123&action=edit)
2. Find **"Sponsorship Settings"** meta box
3. Check **"Enable Sponsorship for this Event"**
4. Check **"Enable Sponsorship Levels"**
5. Click **"Populate Default Levels"** button (creates Bronze/Silver/Gold)
6. **Customize levels**:
   - Edit level names, colours, values, slots
   - Drag to reorder
   - Add custom levels
   - Delete unused levels
7. **Publish** event

---

### Workflow 2: Review and Approve EOI

1. **Navigate** to EMS > EOI Submissions
2. **Filter** by event or status (Pending/Approved/Rejected)
3. **Click** on EOI to view details
4. **Review** submission:
   - Preferred level
   - Estimated value
   - Contribution nature
   - Exclusivity requests
   - Recognition requirements
5. **Action**:
   - **Approve**: Select level from dropdown, click "Approve"
     - Creates sponsor-event link
     - Decrements available slots
     - Sends approval email
   - **Reject**: Enter reason (optional), click "Reject"
     - Updates EOI status
     - Sends rejection email
   - **Request More Info**: (future feature)

---

### Workflow 3: Manually Link Sponsor to Event

1. **Edit Event**
2. Find **"Linked Sponsors"** section in Sponsorship meta box
3. **Search** for sponsor (AJAX autocomplete by name)
4. **Select level** from dropdown
5. **Click** "Link Sponsor"
6. **Verify** slot availability (error if level full)
7. Sponsor appears in list with level pill and "Unlink" action

---

### Workflow 4: Manage Sponsorship Levels

**Add Level**:
1. Edit event
2. Sponsorship meta box > "Add Level" button
3. Enter name, colour, value, slots, recognition text
4. Click "Save Level"

**Edit Level**:
1. Click "Edit" on existing level
2. Modify fields
3. Click "Update Level"

**Delete Level**:
1. Click "Delete" on level
2. **Only allowed** if `slots_filled = 0`
3. Confirm deletion

**Reorder Levels**:
1. Drag level row by handle
2. Order auto-saves on drop

---

## Sponsor Workflows

### Workflow 1: Register as New Sponsor

1. **Navigate** to sponsor onboarding page
2. **Complete 6-step form**:
   - Organisation details (ABN/ACN validated)
   - Contact information
   - Industry classification
   - Products and scope
   - Legal and compliance
   - Conflict of interest declarations
3. **Review** all entered data
4. **Submit**
5. **Check email** for confirmation and password setup link
6. **Set password** and login
7. **Redirected** to sponsor portal

---

### Workflow 2: Submit EOI for Event

1. **Login** to sponsor portal
2. **Browse** available events
3. **Click** "Express Interest" on desired event
4. **Complete 4-step EOI form**:
   - Commitment & preferences (select level)
   - Exclusivity requirements
   - Recognition & visibility needs
   - Educational independence acknowledgements
5. **Review** and submit
6. **Track status** in portal under "My EOIs"
7. **Receive email** when EOI is approved/rejected

---

### Workflow 3: Manage Profile

1. **Login** to sponsor portal
2. **Click** "Edit Profile"
3. **Update** organisation details, contacts, products
4. **Save** changes
5. Changes reflected immediately in portal and admin

---

## Hooks and Filters

### Actions

**`ems_sponsor_onboarding_complete`**
```php
do_action( 'ems_sponsor_onboarding_complete', $sponsor_id, $user_id, $form_data );
```
Fired after sponsor registration is complete.

**`ems_eoi_submitted`**
```php
do_action( 'ems_eoi_submitted', $eoi_id, $sponsor_id, $event_id );
```
Fired after EOI is submitted.

**`ems_eoi_approved`**
```php
do_action( 'ems_eoi_approved', $eoi_id, $sponsor_id, $event_id, $level_id );
```
Fired after EOI is approved.

**`ems_eoi_rejected`**
```php
do_action( 'ems_eoi_rejected', $eoi_id, $sponsor_id, $event_id, $reason );
```
Fired after EOI is rejected.

**`ems_sponsor_linked_to_event`**
```php
do_action( 'ems_sponsor_linked_to_event', $sponsor_id, $event_id, $level_id );
```
Fired when sponsor is linked to event.

**`ems_sponsor_unlinked_from_event`**
```php
do_action( 'ems_sponsor_unlinked_from_event', $sponsor_id, $event_id );
```
Fired when sponsor is unlinked.

---

### Filters

**`ems_sponsor_onboarding_fields`**
```php
$fields = apply_filters( 'ems_sponsor_onboarding_fields', $fields );
```
Modify onboarding form fields.

**`ems_eoi_form_fields`**
```php
$fields = apply_filters( 'ems_eoi_form_fields', $fields, $event_id );
```
Modify EOI form fields.

**`ems_sponsor_meta_whitelist`**
```php
$allowed_keys = apply_filters( 'ems_sponsor_meta_whitelist', $allowed_keys );
```
Modify allowed meta keys for sponsor updates.

**`ems_default_sponsorship_levels`**
```php
$defaults = apply_filters( 'ems_default_sponsorship_levels', $defaults, $event_id );
```
Customize default levels (Bronze/Silver/Gold).

---

## API Reference

### Class: `EMS_Sponsorship_Levels`

**Location**: `plugin/includes/sponsor/class-ems-sponsorship-levels.php`

#### Methods

**`add_level( $event_id, $data )`**
```php
$level_id = $levels->add_level( 123, array(
    'level_name'       => 'Platinum',
    'colour'           => '#E5E4E2',
    'value_aud'        => 10000,
    'slots_total'      => 2,
    'recognition_text' => 'Premium benefits',
    'sort_order'       => 1,
) );
```
Returns: `int|false` Level ID or false on failure

**`get_event_levels( $event_id )`**
```php
$levels = $levels->get_event_levels( 123 );
foreach ( $levels as $level ) {
    echo $level->level_name;
}
```
Returns: `array` Array of level objects ordered by `sort_order`

**`get_level( $level_id )`**
```php
$level = $levels->get_level( 456 );
echo $level->level_name . ': ' . $level->value_aud;
```
Returns: `object|null` Level object or null

**`update_level( $level_id, $data )`**
```php
$success = $levels->update_level( 456, array(
    'value_aud'   => 7500,
    'slots_total' => 5,
) );
```
Returns: `bool` True on success

**`delete_level( $level_id )`**
```php
$result = $levels->delete_level( 456 );
if ( is_wp_error( $result ) ) {
    echo $result->get_error_message();
}
```
Returns: `bool|WP_Error` True or error if slots filled

**`populate_defaults( $event_id )`**
```php
$created_ids = $levels->populate_defaults( 123 );
// Returns: [bronze_id, silver_id, gold_id]
```
Returns: `array` Array of created level IDs

**`increment_slots_filled( $level_id )`**
```php
$success = $levels->increment_slots_filled( 456 );
```
Returns: `bool` True if incremented, false if slots full

**`decrement_slots_filled( $level_id )`**
```php
$levels->decrement_slots_filled( 456 );
```
Returns: `bool`

**`get_available_slots( $level_id )`**
```php
$available = $levels->get_available_slots( 456 );
if ( $available === -1 ) {
    echo 'Unlimited slots';
} else {
    echo $available . ' slots remaining';
}
```
Returns: `int` Available slots, or `-1` for unlimited

---

### Class: `EMS_Sponsor_Meta`

**Location**: `plugin/includes/sponsor/class-ems-sponsor-meta.php`

#### Methods

**`get_sponsor_meta( $post_id )`**
```php
$meta = EMS_Sponsor_Meta::get_sponsor_meta( 789 );
echo $meta['legal_name'];
echo $meta['abn'];
echo $meta['contact_email'];
```
Returns: `array` Associative array of all sponsor meta

**`update_sponsor_meta( $post_id, $data )`**
```php
$result = EMS_Sponsor_Meta::update_sponsor_meta( 789, array(
    'contact_email' => 'new@email.com',
    'website_url'   => 'https://newsite.com',
) );
```
Returns: `bool|WP_Error` True on success

---

### Class: `EMS_Validator`

**Location**: `plugin/includes/utilities/class-ems-validator.php`

#### Methods

**`validate_abn( $abn )`**
```php
$validator = new EMS_Validator();
$result = $validator->validate_abn( '53 004 085 616' );
if ( is_wp_error( $result ) ) {
    echo $result->get_error_message();
}
```
Returns: `bool|WP_Error`

**`validate_acn( $acn )`**
```php
$result = $validator->validate_acn( '004 085 616' );
```
Returns: `bool|WP_Error`

**`validate_email( $email, $check_dns = true )`**
```php
$result = $validator->validate_email( 'test@example.com', false );
```
Returns: `bool|WP_Error`

---

## Customization Examples

### Example 1: Add Custom Onboarding Field

```php
add_filter( 'ems_sponsor_onboarding_fields', function( $fields ) {
    $fields['step_1']['custom_field'] = array(
        'type'     => 'text',
        'label'    => 'Custom Field',
        'required' => false,
    );
    return $fields;
} );
```

### Example 2: Send Custom Email on EOI Approval

```php
add_action( 'ems_eoi_approved', function( $eoi_id, $sponsor_id, $event_id, $level_id ) {
    $sponsor = get_post( $sponsor_id );
    $event = get_post( $event_id );

    // Send custom notification to internal team
    wp_mail(
        'team@example.com',
        'EOI Approved: ' . $sponsor->post_title,
        'Sponsor ' . $sponsor->post_title . ' approved for ' . $event->post_title
    );
}, 10, 4 );
```

### Example 3: Modify Default Sponsorship Levels

```php
add_filter( 'ems_default_sponsorship_levels', function( $defaults, $event_id ) {
    // Add Platinum level
    $defaults[] = array(
        'level_name'       => 'Platinum',
        'colour'           => '#E5E4E2',
        'value_aud'        => 10000,
        'slots_total'      => 1,
        'recognition_text' => 'Exclusive naming rights',
        'sort_order'       => 0, // First position
    );
    return $defaults;
}, 10, 2 );
```

---

**End of Sponsorship System Documentation**

For testing procedures, see [SPONSORSHIP-TESTING-GUIDE.md](SPONSORSHIP-TESTING-GUIDE.md)
For implementation plan, see [SPONSORSHIP-IMPLEMENTATION-PLAN.md](SPONSORSHIP-IMPLEMENTATION-PLAN.md)
