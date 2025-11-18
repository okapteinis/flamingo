# Flamingo Plugin Test Suite

This directory contains comprehensive test suites for the Flamingo WordPress plugin.

## Test Structure

```
tests/
├── security/                    # Security-focused tests
│   └── test-input-sanitization.php
├── integration/                 # WordPress integration tests
│   └── test-wordpress-67-compatibility.php
├── compatibility/               # Platform compatibility tests
│   └── test-classicpress.php
└── README.md                   # This file
```

## Test Categories

### Security Tests (`tests/security/`)

Tests for security vulnerabilities and proper data sanitization:

- **Input Sanitization**: Tests for XSS prevention, SQL injection protection
- **CSV Injection**: Tests for formula injection prevention in exports
- **CSRF Protection**: Validates nonce verification
- **Data Validation**: Tests proper input validation and sanitization

**Run security tests:**
```bash
phpunit tests/security/
```

### Integration Tests (`tests/integration/`)

Tests for WordPress 6.7+ compatibility and integration:

- **WordPress API Usage**: Validates use of current WordPress APIs
- **Deprecated Functions**: Checks for deprecated function usage
- **Database Compatibility**: Tests database operations
- **Cron Jobs**: Validates scheduled task functionality
- **GDPR/Privacy**: Tests privacy eraser functionality
- **Post Types & Taxonomies**: Validates custom post type registration

**Run integration tests:**
```bash
phpunit tests/integration/
```

### Compatibility Tests (`tests/compatibility/`)

Tests for ClassicPress and platform compatibility:

- **ClassicPress Compatibility**: Tests plugin works on ClassicPress fork
- **No Gutenberg Dependencies**: Validates classic editor compatibility
- **REST API Independence**: Ensures plugin doesn't require REST API
- **Hook System**: Tests WordPress/ClassicPress hook compatibility
- **Database Schema**: Validates database compatibility

**Run compatibility tests:**
```bash
phpunit tests/compatibility/
```

## Running Tests

### Prerequisites

1. **Install WordPress Test Suite:**
   ```bash
   bash tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

2. **Install PHPUnit:**
   ```bash
   composer require --dev phpunit/phpunit
   ```

### Run All Tests

```bash
phpunit
```

### Run Specific Test Suite

```bash
# Security tests only
phpunit tests/security/

# Integration tests only
phpunit tests/integration/

# Compatibility tests only
phpunit tests/compatibility/
```

### Run Individual Test File

```bash
phpunit tests/security/test-input-sanitization.php
```

### Run Specific Test Method

```bash
phpunit --filter test_xss_prevention tests/security/test-input-sanitization.php
```

## Test Coverage

Generate code coverage report (requires Xdebug):

```bash
phpunit --coverage-html coverage/
```

View the report by opening `coverage/index.html` in your browser.

## Writing New Tests

### Security Test Template

```php
<?php
class Flamingo_Test_New_Security extends WP_UnitTestCase {
    public function test_security_feature() {
        // Arrange: Set up test data
        $malicious_input = '<script>alert("xss")</script>';

        // Act: Perform the action
        $result = your_function( $malicious_input );

        // Assert: Verify the result
        $this->assertStringNotContainsString( '<script>', $result );
    }
}
```

### Integration Test Template

```php
<?php
class Flamingo_Test_New_Integration extends WP_UnitTestCase {
    public function test_wordpress_feature() {
        // Test WordPress compatibility
        $this->assertTrue( function_exists( 'some_wp_function' ) );
    }
}
```

## Continuous Integration

These tests are designed to run in CI/CD pipelines. Example GitHub Actions workflow:

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: phpunit
```

## Test Best Practices

1. **Isolation**: Each test should be independent and not rely on others
2. **Cleanup**: Use `tearDown()` to clean up after each test
3. **Assertions**: Use specific assertions (`assertStringContains` vs `assertTrue`)
4. **Documentation**: Add PHPDoc comments explaining what each test validates
5. **Naming**: Use descriptive test method names starting with `test_`

## Troubleshooting

### Tests fail with "Class 'WP_UnitTestCase' not found"

Install the WordPress test suite:
```bash
bash tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Database errors

Ensure your test database is properly configured in `wp-tests-config.php`.

### Permission errors

Ensure the test user has proper permissions to create/delete posts and metadata.

## Contributing

When contributing new features, please include tests that cover:

1. **Happy path**: Feature works as expected
2. **Error handling**: Feature handles errors gracefully
3. **Security**: Feature properly sanitizes/validates input
4. **Edge cases**: Feature handles unusual but valid inputs

## Resources

- [WordPress Plugin Handbook - Testing](https://developer.wordpress.org/plugins/testing/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Core Test Suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)

## Co-Authors

This test suite was developed by:
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)

## License

GPLv2 or later, same as the Flamingo plugin.
