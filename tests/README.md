# EMS Plugin Test Suite

PHPUnit test suite for the Event Management System WordPress plugin.

## Quick Start

```bash
# Install PHPUnit via Composer
composer require --dev phpunit/phpunit ^9.0

# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/sponsorship/test-sponsorship-levels.php

# Run with verbose output
vendor/bin/phpunit --verbose

# Generate coverage report
vendor/bin/phpunit --coverage-html coverage/
```

## Test Structure

```
tests/
├── phpunit.xml.dist              # PHPUnit configuration
├── bootstrap.php                 # Test environment bootstrap
└── sponsorship/                  # Sponsorship subsystem tests
    ├── test-sponsorship-levels.php
    ├── test-onboarding-validation.php
    ├── test-eoi-flow.php
    ├── test-shortcode-degradation.php
    ├── test-integration-onboarding.php
    └── test-integration-eoi.php
```

## WordPress Test Suite (Optional)

For full integration testing with WordPress core:

```bash
# Install WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

**Parameters**:
- `wordpress_test` - Database name (will be created)
- `root` - Database user
- `''` - Database password
- `localhost` - Database host
- `latest` - WordPress version

## Environment Setup

### Prerequisites

- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Composer

### Installing Dependencies

```bash
cd plugin-root/
composer install
```

### Database Setup

Create test database (will be wiped between test runs):

```sql
CREATE DATABASE wordpress_test;
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

## Test Coverage

| Test File | Coverage |
|-----------|----------|
| `test-sponsorship-levels.php` | CRUD operations, slot management, default levels |
| `test-onboarding-validation.php` | ABN/ACN validation, form validation, duplicates |
| `test-eoi-flow.php` | EOI submission, approval/rejection workflows |
| `test-shortcode-degradation.php` | Graceful error handling, invalid inputs |
| `test-integration-onboarding.php` | End-to-end onboarding process |
| `test-integration-eoi.php` | End-to-end EOI process |

## Running Specific Tests

### Run all sponsorship tests
```bash
phpunit tests/sponsorship/
```

### Run specific test method
```bash
phpunit --filter test_add_level_creates_record tests/sponsorship/test-sponsorship-levels.php
```

### Run tests matching pattern
```bash
phpunit --filter validation tests/sponsorship/
```

## Test Modes

Tests can run in two modes:

### Full Mode (WordPress Test Suite)
- Complete WordPress environment
- Database operations
- Full WP API access
- Recommended for integration tests

### Limited Mode (Fallback)
- No WordPress test suite required
- Basic class loading
- Suitable for unit tests
- Faster setup

## Writing Tests

### Test Class Template

```php
<?php
class Test_My_Feature extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        // Setup test data
    }

    public function tearDown() {
        // Clean up
        parent::tearDown();
    }

    public function test_feature_works() {
        $this->assertTrue( true );
    }
}
```

### Assertions

Common PHPUnit assertions:
- `$this->assertTrue( $condition )`
- `$this->assertEquals( $expected, $actual )`
- `$this->assertNotNull( $value )`
- `$this->assertWPError( $result )`
- `$this->assertCount( $count, $array )`

## Continuous Integration

### GitHub Actions Example

```yaml
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
    - name: Install dependencies
      run: composer install
    - name: Run tests
      run: vendor/bin/phpunit
```

## Troubleshooting

### "Table doesn't exist" errors
Create tables manually in test setUp() or ensure plugin activator runs.

### "Class not found" errors
Check bootstrap.php has correct file paths.

### Tests fail with WordPress errors
Install WordPress Test Suite (see above).

## Documentation

For detailed testing procedures, see:
- [SPONSORSHIP-TESTING-GUIDE.md](../SPONSORSHIP-TESTING-GUIDE.md) - Complete testing guide
- [SPONSORSHIP-DOCS.md](../SPONSORSHIP-DOCS.md) - System documentation

## Contributing

When adding new features:

1. Write tests FIRST (TDD)
2. Ensure tests pass locally
3. Add test documentation
4. Update this README if needed

## License

Same as plugin license.
