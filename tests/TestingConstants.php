<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin\Tests;

/**
 * Testing Constants
 * All test data constants - zero hardcoded values in tests
 */
final class TestingConstants
{
    // Test API URLs
    public const TEST_API_BASE_URL = 'https://api.example.com';
    public const TEST_API_ENDPOINT_ACTIVATE = '/api/v1/licenses/activate';
    public const TEST_API_ENDPOINT_VALIDATE = '/api/v1/licenses/validate';

    // Test License Data
    public const TEST_LICENSE_KEY = 'eyJ2ZXJzaW9uIjoyLCJwcm9kdWN0SWQiOjEsInRpZXJDb2RlIjoiMDEiLCJkb21haW4iOiJleGFtcGxlLmNvbSIsImRlbW9Nb2RlIjpmYWxzZX0.signature';
    public const TEST_DOMAIN = 'example.com';
    public const TEST_SITE_NAME = 'Test Site';
    public const TEST_TIER_CODE = '01';

    // Test Plugin Data
    public const TEST_PLUGIN_SLUG = 'test-plugin';
    public const TEST_PLUGIN_FILE = 'test-plugin/test-plugin.php';
    public const TEST_PLUGIN_VERSION = '1.0.0';
    public const TEST_PLUGIN_NEW_VERSION = '1.1.0';

    // Test WordPress Option Keys
    public const TEST_WP_OPTION_LICENSE_KEY = 'sls_license_key';
    public const TEST_WP_OPTION_LICENSE_STATUS = 'sls_license_status';

    // Test HTTP Status Codes
    public const TEST_HTTP_STATUS_OK = 200;
    public const TEST_HTTP_STATUS_BAD_REQUEST = 400;
    public const TEST_HTTP_STATUS_NOT_FOUND = 404;

    // Test Error Codes
    public const TEST_ERROR_CODE_NOT_FOUND = 'LICENSE_NOT_FOUND';
    public const TEST_ERROR_CODE_EXPIRED = 'LICENSE_EXPIRED';
    public const TEST_ERROR_CODE_ACTIVATION_LIMIT = 'ACTIVATION_LIMIT_EXCEEDED';

    // Test Time Values
    public const TEST_TIMEOUT_SECONDS = 15;
    public const TEST_CACHE_TTL_SECONDS = 86400;

    // Private constructor to prevent instantiation
    private function __construct()
    {
    }
}

