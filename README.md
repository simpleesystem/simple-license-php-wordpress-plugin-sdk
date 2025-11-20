# Simple License System - Plugin SDK

PHP SDK for WordPress plugins that are licensed software via Simple License System.

## Installation

```bash
composer require simple-license/plugin-sdk
```

## Quick Start

```php
use SimpleLicense\Plugin\Client;
use SimpleLicense\Plugin\LicenseManager;
use SimpleLicense\Plugin\UpdateChecker;
use SimpleLicense\Plugin\AdminSettingsPage;

// Initialize client
$client = new Client('https://your-license-server.com');

// License Manager
$licenseManager = new LicenseManager($client);

// Activate license
$licenseManager->activate('your-license-key');

// Check if license is valid
if ($licenseManager->isValid()) {
    // License is valid
}

// Get feature value
$maxSites = $licenseManager->getFeature('max_sites', 1);

// Update Checker
$updateChecker = new UpdateChecker(
    $client,
    'my-plugin',
    'my-plugin/my-plugin.php',
    '1.0.0'
);
$updateChecker->registerUpdateHooks();

// Admin Settings Page
$settingsPage = new AdminSettingsPage(
    $licenseManager,
    'my-plugin',
    'License Settings',
    'License'
);
$settingsPage->register();
```

## API Coverage

### License Operations
- `activateLicense()` - Activate license on domain
- `validateLicense()` - Validate license
- `deactivateLicense()` - Deactivate license
- `getLicenseData()` - Get license information
- `getLicenseFeatures()` - Get license features/entitlements
- `reportUsage()` - Report usage statistics

### Update Management
- `checkForUpdates()` - Check for plugin updates

## WordPress Integration

### License Manager

The `LicenseManager` class handles license lifecycle with WordPress integration:

```php
$licenseManager = new LicenseManager($client);

// Activate and store in WordPress options
$licenseManager->activate('license-key');

// Validate (with caching)
$isValid = $licenseManager->validate();

// Check if valid (uses cache)
$isValid = $licenseManager->isValid();

// Get feature value
$value = $licenseManager->getFeature('feature_key', 'default');

// Deactivate and clear WordPress options
$licenseManager->deactivate();
```

### Update Checker

Automatic plugin update checking:

```php
$updateChecker = new UpdateChecker(
    $client,
    'my-plugin',              // Plugin slug
    'my-plugin/my-plugin.php', // Plugin file
    '1.0.0'                    // Current version
);

// Register WordPress update hooks
$updateChecker->registerUpdateHooks();

// Manual update check
$update = $updateChecker->checkForUpdates();
```

### Admin Settings Page

Pre-built admin settings page with proper templating:

```php
$settingsPage = new AdminSettingsPage(
    $licenseManager,
    'my-plugin',        // Plugin slug
    'License Settings', // Page title
    'License'           // Menu title
);

$settingsPage->register();
```

## Error Handling

All methods throw typed exceptions:

- `ApiException` - Base exception for all API errors
- `LicenseExpiredException` - License has expired
- `ActivationLimitExceededException` - Activation limit exceeded
- `LicenseNotFoundException` - License not found
- `ValidationException` - Request validation errors
- `NetworkException` - Network/HTTP errors

```php
try {
    $licenseManager->activate('license-key');
} catch (LicenseExpiredException $e) {
    // Handle expired license
} catch (ActivationLimitExceededException $e) {
    // Handle activation limit
} catch (ApiException $e) {
    // Handle other errors
}
```

## Configuration

```php
$client = new Client(
    baseUrl: 'https://your-license-server.com',
    httpClient: null, // Optional custom HTTP client
    timeout: 15 // Optional timeout in seconds
);
```

## Caching

The SDK uses WordPress transients for caching:

- License validation: 1 hour (valid), 1 hour (invalid)
- Update checks: 24 hours

Cache is automatically cleared on activation/deactivation.

## Testing

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage

# Mutation testing
composer test:mutation
```

## Requirements

- PHP 8.1+
- WordPress 5.0+

## License

MIT

