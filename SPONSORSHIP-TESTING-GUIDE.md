# EMS Sponsorship System Testing Guide

**Document Purpose**: Comprehensive testing documentation for the Event Management System (EMS) sponsorship subsystem.

**Generated**: 2026-02-05
**Plugin Version**: 1.5.0
**Test Coverage**: Phase 11 - Testing & Documentation

---

## Table of Contents

1. [Overview](#overview)
2. [Test Environment Setup](#test-environment-setup)
3. [Test Suite Structure](#test-suite-structure)
4. [Running Tests](#running-tests)
5. [Test Coverage](#test-coverage)
6. [Manual Testing Procedures](#manual-testing-procedures)
7. [Known Issues and Workarounds](#known-issues-and-workarounds)

---

## Overview

The EMS sponsorship system testing suite provides comprehensive coverage for:

- **CRUD Operations**: Sponsorship levels database operations
- **Validation**: Form validation, ABN/ACN validation, duplicate detection
- **Workflows**: EOI submission and approval flows
- **Integration**: End-to-end onboarding and EOI processes
- **Graceful Degradation**: Shortcode behavior with invalid/missing data

---

## Test Environment Setup

### Prerequisites

- PHP 7.4 or higher
- WordPress 6.0 or higher
- MySQL 5.7 or MariaDB 10.2 or higher
- PHPUnit 9.x
- WordPress Test Suite (optional but recommended)

### Installing WordPress Test Suite

For full integration testing with WordPress core functions:

```bash
# From plugin root directory
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

**Parameters**:
- `wordpress_test` - Database name (will be created)
- `root` - Database user
- `''` - Database password (empty in this example)
- `localhost` - Database host
- `latest` - WordPress version

### Installing PHPUnit

```bash
# Via Composer (recommended)
composer require --dev phpunit/phpunit ^9.0

# Or globally
composer global require phpunit/phpunit ^9.0
```

### Setting Up Test Database

Create a dedicated test database:

```sql
CREATE DATABASE wordpress_test;
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

**Important**: Test database will be wiped clean between test runs. Never use production database.

---

## Test Suite Structure

```
tests/
├── phpunit.xml.dist          # PHPUnit configuration
├── bootstrap.php             # Test environment bootstrap
└── sponsorship/              # Sponsorship test suite
    ├── test-sponsorship-levels.php         # Task 11.1
    ├── test-onboarding-validation.php      # Task 11.2
    ├── test-eoi-flow.php                   # Task 11.3
    ├── test-shortcode-degradation.php      # Task 11.4
    ├── test-integration-onboarding.php     # Task 11.5
    └── test-integration-eoi.php            # Task 11.6
```

---

## Running Tests

### Run All Tests

```bash
# From plugin root directory
vendor/bin/phpunit

# Or if installed globally
phpunit
```

### Run Specific Test File

```bash
phpunit tests/sponsorship/test-sponsorship-levels.php
```

### Run Specific Test Method

```bash
phpunit --filter test_add_level_creates_record tests/sponsorship/test-sponsorship-levels.php
```

### Run with Coverage Report

```bash
phpunit --coverage-html coverage/
```

Then open `coverage/index.html` in your browser.

### Verbose Output

```bash
phpunit --verbose
```

---

## Test Coverage

### Task 11.1: Sponsorship Levels CRUD Tests

**File**: `tests/sponsorship/test-sponsorship-levels.php`

**Coverage**:
- ✅ `add_level()` creates record with all fields
- ✅ `add_level()` enforces unique slug within event
- ✅ `get_event_levels()` returns levels ordered by sort_order
- ✅ `update_level()` modifies existing level correctly
- ✅ `delete_level()` removes record when slots_filled = 0
- ✅ `delete_level()` prevents deletion when slots are filled
- ✅ `populate_defaults()` creates Bronze/Silver/Gold levels
- ✅ `increment_slots_filled()` is atomic and respects limits
- ✅ `get_available_slots()` returns correct count
- ✅ `get_available_slots()` returns -1 for unlimited (NULL slots)

**Example Test**:
```php
public function test_add_level_creates_record() {
    $level_id = $this->levels->add_level( $this->event_id, array(
        'level_name' => 'Gold',
        'colour'     => '#FFD700',
        'value_aud'  => 5000.00,
        'slots_total' => 5,
    ) );

    $this->assertNotFalse( $level_id );
    $level = $this->levels->get_level( $level_id );
    $this->assertEquals( 'Gold', $level->level_name );
}
```

---

### Task 11.2: Onboarding Validation Tests

**File**: `tests/sponsorship/test-onboarding-validation.php`

**Coverage**:
- ✅ Valid ABN passes validation (with check digit)
- ✅ Invalid ABN fails validation
- ✅ ABN with wrong length fails
- ✅ Valid ACN passes validation
- ✅ Invalid ACN fails validation
- ✅ Required field validation enforces presence
- ✅ Email format validation
- ✅ Duplicate ABN detection
- ✅ Duplicate email detection
- ✅ Rate limiting prevents spam (3 submissions/hour)
- ✅ Complete form data validation

**Valid Test ABN**: `53 004 085 616` (11 digits with weighted check)
**Valid Test ACN**: `004 085 616` (9 digits with modulo 10 check)

---

### Task 11.3: EOI Flow Tests

**File**: `tests/sponsorship/test-eoi-flow.php`

**Coverage**:
- ✅ EOI insertion creates record with all fields
- ✅ Duplicate EOI prevention (UNIQUE constraint on sponsor+event)
- ✅ EOI approval links sponsor to event at specified level
- ✅ EOI approval increments `slots_filled`
- ✅ EOI rejection updates status without linking
- ✅ Already-approved EOI cannot be re-approved
- ✅ EOI status transitions are tracked
- ✅ EOI with all optional fields populated

---

### Task 11.4: Shortcode Degradation Tests

**File**: `tests/sponsorship/test-shortcode-degradation.php`

**Coverage**:
- ✅ Shortcode returns empty when sponsorship disabled
- ✅ Shortcode returns empty with invalid event_id
- ✅ Shortcode returns empty with missing event_id
- ✅ Shortcode returns message when no sponsors linked
- ✅ Carousel shortcode degrades gracefully
- ✅ Gallery shortcode degrades gracefully
- ✅ Levels shortcode degrades gracefully
- ✅ Malformed event_id (non-numeric) handled
- ✅ event_id = 0 handled
- ✅ Negative event_id handled
- ✅ Deleted event handled
- ✅ No PHP errors in any degraded state

---

### Task 11.5: Integration Test - Onboarding

**File**: `tests/sponsorship/test-integration-onboarding.php`

**End-to-End Coverage**:
1. ✅ Validate all 6 form steps independently
2. ✅ Complete submission creates user + post + meta
3. ✅ User assigned `ems_sponsor` role
4. ✅ User linked to sponsor post via `_ems_sponsor_id` meta
5. ✅ All sponsor meta fields saved correctly
6. ✅ Duplicate email prevented
7. ✅ Duplicate ABN prevented
8. ✅ Missing required fields fail validation

**Simulates**:
- User completes all 6 onboarding steps
- Each step validated independently
- Final submission creates account and sponsor profile
- Edge cases (duplicates, missing data)

---

### Task 11.6: Integration Test - EOI

**File**: `tests/sponsorship/test-integration-eoi.php`

**End-to-End Coverage**:
1. ✅ Event eligibility validation
2. ✅ EOI submission creates pending record
3. ✅ Admin approval links sponsor + decrements slots
4. ✅ EOI status updates to 'approved'
5. ✅ Rejection workflow (no link created)
6. ✅ Multiple EOIs for different events allowed
7. ✅ Duplicate EOI for same event prevented
8. ✅ Logged-out user cannot submit
9. ✅ Non-sponsor user cannot submit

**Simulates**:
- Sponsor submits EOI for event
- Admin reviews and approves/rejects
- Slot management updates
- Email notifications (mocked)

---

## Manual Testing Procedures

### Manual Test 1: Sponsor Onboarding Flow

**Objective**: Verify complete onboarding process from user perspective.

**Prerequisites**: WordPress site with EMS plugin active, onboarding page created with `[ems_sponsor_onboarding]` shortcode.

**Steps**:

1. **Navigate** to sponsor onboarding page while logged out
2. **Complete Step 1** - Organisation Details:
   - Enter legal name: "Test Medical Corp Pty Ltd"
   - Enter ABN: `53 004 085 616`
   - Enter ACN: `004 085 616`
   - Enter website: `https://testmedical.com`
   - Click "Next"
3. **Complete Step 2** - Contact Details:
   - Enter contact name: "Jane Doe"
   - Enter contact email: `jane@testmedical.com`
   - Enter signatory name: "John Smith"
   - Click "Next"
4. **Complete Step 3** - Industry Classification:
   - Select "Pharmaceutical - Prescription"
   - Check "MA Member"
   - Click "Next"
5. **Complete Step 4** - Products:
   - Enter products: "Cardiovascular medications"
   - Select scheduling class: "S4"
   - Click "Next"
6. **Complete Step 5** - Legal & Compliance:
   - Check "Public liability confirmed"
   - Check "Workers comp confirmed"
   - Check "Code compliance agreed"
   - Click "Next"
7. **Complete Step 6** - Conflict of Interest:
   - Check "Qld Health Directive aware"
   - Check "Preferential treatment agreed"
   - Click "Next"
8. **Review** - Verify all data displayed correctly
   - Click "Submit"

**Expected Results**:
- User account created with `ems_sponsor` role
- Sponsor post created with title "Test Medical Corp Pty Ltd"
- All meta fields populated correctly
- User redirected to sponsor portal
- Confirmation email sent to `jane@testmedical.com`
- Admin notification email sent

**Verification**:
```sql
-- Check user created
SELECT * FROM wp_users WHERE user_email = 'jane@testmedical.com';

-- Check sponsor post
SELECT * FROM wp_posts WHERE post_type = 'ems_sponsor' AND post_title = 'Test Medical Corp Pty Ltd';

-- Check meta
SELECT * FROM wp_postmeta WHERE post_id = [sponsor_post_id] AND meta_key LIKE '_ems_sponsor_%';
```

---

### Manual Test 2: EOI Submission and Approval

**Objective**: Verify EOI workflow from sponsor portal.

**Prerequisites**:
- Sponsor account created (from Test 1)
- Event created with sponsorship enabled
- Sponsorship levels configured (Bronze, Silver, Gold)

**Steps**:

1. **Login** as sponsor (`jane@testmedical.com`)
2. **Navigate** to sponsor portal
3. **Find** event in "Available Events" section
4. **Click** "Express Interest" button
5. **Complete EOI Step 1** - Commitment:
   - Select preferred level: "Gold"
   - Enter estimated value: 5000
   - Select contribution nature: "Financial unrestricted"
   - Click "Next"
6. **Complete EOI Step 2** - Exclusivity:
   - Select "No exclusivity requested"
   - Check "Accept shared presence"
   - Click "Next"
7. **Complete EOI Step 3** - Recognition:
   - Check "Logo on materials"
   - Check "Trade display"
   - Enter booth requirements: "3m x 3m booth"
   - Click "Next"
8. **Complete EOI Step 4** - Educational Independence:
   - Check "Content independence acknowledged"
   - Click "Next"
9. **Review and Submit** EOI

**Admin Approval**:

10. **Login as Admin**
11. **Navigate** to EMS > EOI Submissions
12. **Find** Test Medical Corp EOI
13. **Click** "Review"
14. **Select** Gold level from dropdown
15. **Click** "Approve"

**Expected Results**:
- EOI created with status "pending"
- After approval: EOI status = "approved"
- Sponsor linked to event in `wp_ems_sponsor_events`
- Gold level `slots_filled` incremented
- Sponsor sees event in portal "My Events" section
- Approval email sent to sponsor

**Verification**:
```sql
-- Check EOI created
SELECT * FROM wp_ems_sponsor_eoi WHERE sponsor_id = [sponsor_id] AND event_id = [event_id];

-- Check sponsor-event link
SELECT * FROM wp_ems_sponsor_events WHERE sponsor_id = [sponsor_id] AND event_id = [event_id];

-- Check slots updated
SELECT slots_filled FROM wp_ems_sponsorship_levels WHERE id = [gold_level_id];
```

---

### Manual Test 3: Shortcode Graceful Degradation

**Objective**: Verify shortcodes handle edge cases without errors.

**Test Cases**:

| Scenario | Shortcode | Expected Behavior |
|----------|-----------|-------------------|
| Sponsorship disabled | `[ems_event_sponsors event_id="123"]` | Returns empty (no output) |
| Invalid event ID | `[ems_event_sponsors event_id="999999"]` | Returns empty |
| Missing event ID | `[ems_event_sponsors]` | Returns empty |
| No sponsors linked | `[ems_event_sponsors event_id="123"]` (valid event, no sponsors) | Returns empty or "No sponsors yet" |
| Deleted event | `[ems_event_sponsors event_id="123"]` (deleted post) | Returns empty |
| Non-numeric ID | `[ems_event_sponsors event_id="abc"]` | Returns empty |

**For Each Test**:
1. Create page with shortcode
2. View page in browser
3. Inspect source code for PHP errors/warnings
4. Check `debug.log` for errors

**Expected**: No PHP errors, warnings, or broken HTML in any case.

---

## Known Issues and Workarounds

### Issue 1: WordPress Test Suite Not Found

**Symptom**: Tests run in "limited mode" without full WordPress environment.

**Workaround**: Tests are designed to run in limited mode for basic validation. For full integration testing, install WordPress Test Suite (see [Setup](#installing-wordpress-test-suite)).

### Issue 2: ABN/ACN Validation Methods Not Implemented

**Symptom**: `validate_abn()` and `validate_acn()` methods do not exist in `EMS_Validator`.

**Resolution**: Implement in `plugin/includes/utilities/class-ems-validator.php`:

```php
public function validate_abn( $abn ) {
    // Remove spaces and hyphens
    $abn = preg_replace( '/[^0-9]/', '', $abn );

    // Must be 11 digits
    if ( strlen( $abn ) !== 11 ) {
        return new WP_Error( 'invalid_abn', 'ABN must be 11 digits' );
    }

    // Apply weighted check digit algorithm
    $weights = array( 10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19 );
    $digits = str_split( $abn );
    $digits[0] -= 1; // Subtract 1 from first digit

    $sum = 0;
    foreach ( $digits as $i => $digit ) {
        $sum += (int) $digit * $weights[ $i ];
    }

    if ( $sum % 89 !== 0 ) {
        return new WP_Error( 'invalid_abn', 'Invalid ABN check digit' );
    }

    return true;
}

public function validate_acn( $acn ) {
    // Remove spaces and hyphens
    $acn = preg_replace( '/[^0-9]/', '', $acn );

    // Must be 9 digits
    if ( strlen( $acn ) !== 9 ) {
        return new WP_Error( 'invalid_acn', 'ACN must be 9 digits' );
    }

    // Apply modulo 10 check digit
    $weights = array( 8, 7, 6, 5, 4, 3, 2, 1 );
    $digits = str_split( $acn );
    $check_digit = (int) array_pop( $digits );

    $sum = 0;
    foreach ( $digits as $i => $digit ) {
        $sum += (int) $digit * $weights[ $i ];
    }

    $calculated_check = ( 10 - ( $sum % 10 ) ) % 10;

    if ( $check_digit !== $calculated_check ) {
        return new WP_Error( 'invalid_acn', 'Invalid ACN check digit' );
    }

    return true;
}
```

### Issue 3: Database Tables Not Created in Test Environment

**Symptom**: Tests fail with "table does not exist" errors.

**Workaround**: Add table creation to test `setUp()` methods:

```php
public function setUp() {
    parent::setUp();

    global $wpdb;

    // Create sponsorship levels table
    $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ems_sponsorship_levels (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        level_name VARCHAR(100) NOT NULL,
        -- [rest of schema]
        PRIMARY KEY (id)
    )" );
}
```

---

## Test Execution Checklist

Before committing code, run through this checklist:

- [ ] All PHPUnit tests pass
- [ ] Code coverage > 80% for new code
- [ ] Manual test 1 (Onboarding) completed successfully
- [ ] Manual test 2 (EOI) completed successfully
- [ ] Manual test 3 (Degradation) verified
- [ ] No PHP warnings/errors in `debug.log`
- [ ] Database migrations tested on clean install
- [ ] Database migrations tested on upgrade from v1.4.0

---

## Continuous Integration

For automated testing in CI/CD pipeline:

```yaml
# .github/workflows/tests.yml (GitHub Actions example)
name: PHPUnit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: mysqli

    - name: Install dependencies
      run: composer install

    - name: Run tests
      run: vendor/bin/phpunit
```

---

## Appendix: Test Data

### Valid Test ABN: 53 004 085 616

**Validation**:
- 11 digits
- Weighted sum: (4×10 + 3×1 + 0×3 + 0×5 + 4×7 + 0×9 + 8×11 + 5×13 + 6×15 + 1×17 + 6×19) = 534
- 534 % 89 = 0 ✅

### Valid Test ACN: 004 085 616

**Validation**:
- 9 digits
- Weighted sum: (0×8 + 0×7 + 4×6 + 0×5 + 8×4 + 5×3 + 6×2 + 1×1) = 72
- Check digit: (10 - (72 % 10)) % 10 = 8 (does not match 6)
- **Note**: Use `087 743 082` for valid ACN with check digit

---

**End of Testing Guide**

For additional support, see:
- [WordPress PHPUnit Documentation](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Manual](https://phpunit.de/manual/9.6/en/index.html)
