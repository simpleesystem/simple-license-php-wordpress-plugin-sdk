# Testing Guide

## Running Tests

### Prerequisites

- PHP 8.1 or higher
- Composer installed
- WordPress test framework (optional, for full integration tests)

### Installation

```bash
composer install
```

### Run All Tests

```bash
# Unit tests
vendor/bin/phpunit tests/Unit/

# Integration tests
vendor/bin/phpunit tests/Integration/

# All tests
vendor/bin/phpunit
```

### Run with Coverage

```bash
vendor/bin/phpunit --coverage-html coverage/html
```

### Run Mutation Testing

```bash
vendor/bin/infection
```

## Test Structure

### Unit Tests (`tests/Unit/`)
- Test individual classes in isolation
- Use mocks for dependencies
- Fast execution
- High coverage target: 95%+

### Integration Tests (`tests/Integration/`)
- Test complete workflows
- Test expected behavior, not just code paths
- Validate WordPress integration
- Test error handling scenarios

### Test Helpers (`tests/Helpers/`)
- WordPress function mocks for testing
- WordPressTestHelper for managing test state
- Allows tests to run without full WordPress installation

## Test Principles

1. **Test Expected Behavior**: Tests validate business logic and intended functionality
2. **No Hardcoded Values**: All test data comes from constants or factories
3. **Arrange-Act-Assert**: Clear test structure
4. **Complete Workflows**: Integration tests cover end-to-end scenarios
5. **WordPress Integration**: Tests verify proper WordPress option/transient handling

## WordPress Testing

The SDK includes WordPress function mocks in `tests/Helpers/` that allow tests to run without a full WordPress installation. These mocks:

- Simulate WordPress options storage
- Simulate WordPress transients
- Provide WordPress utility functions

For full WordPress integration testing, use the WordPress test framework.

## Example Test Run

```bash
$ vendor/bin/phpunit --testdox

SimpleLicense\Plugin\Tests\Integration\ClientIntegrationTest
 ✔ Complete license activation workflow
 ✔ License expired error handling
 ✔ Activation limit exceeded error handling
 ✔ Check for updates returns update when available
 ✔ Check for updates returns null when no update
 ✔ Get license features returns correct features

SimpleLicense\Plugin\Tests\Integration\LicenseManagerIntegrationTest
 ✔ Activate stores license in WordPress options
 ✔ Validate uses cache when available
 ✔ Validate refreshes cache on failure
 ✔ Deactivate clears WordPress options
 ✔ Get feature returns correct value
 ✔ Is valid returns false when no license key
 ✔ Is valid returns false when status not active
```

