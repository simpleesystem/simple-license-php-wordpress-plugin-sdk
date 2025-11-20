<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SimpleLicense\Plugin\Client;
use SimpleLicense\Plugin\Constants;
use SimpleLicense\Plugin\LicenseManager;
use SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper;
use SimpleLicense\Plugin\Tests\TestingConstants;

// Load WordPress function mocks (must be in global namespace)
if (!function_exists('get_option')) {
    require_once __DIR__ . '/../Helpers/WordPressFunctions.php';
}

/**
 * License Manager Integration Tests
 * Tests WordPress integration workflows
 */
class LicenseManagerIntegrationTest extends TestCase
{
    private Client $client;
    private LicenseManager $licenseManager;

    protected function setUp(): void
    {
        WordPressTestHelper::setUp();

        // Ensure WordPress functions are available in global namespace
        if (!function_exists('get_option')) {
            require_once __DIR__ . '/../Helpers/WordPressFunctions.php';
        }

        $this->client = $this->createMock(Client::class);
        $this->licenseManager = new LicenseManager($this->client);
    }

    protected function tearDown(): void
    {
        WordPressTestHelper::tearDown();
    }

    public function testActivateStoresLicenseInWordPressOptions(): void
    {
        // Test that activate correctly stores license data in WordPress options
        // This validates the manager correctly integrates with WordPress storage

        // Arrange
        $licenseData = [
            'license_key' => TestingConstants::TEST_LICENSE_KEY,
            'status' => Constants::LICENSE_STATUS_ACTIVE,
            'expires_at' => '2025-12-31T23:59:59Z',
            'features' => ['max_sites' => TestingConstants::TEST_ACTIVATION_LIMIT],
            'tier_code' => TestingConstants::TEST_TIER_CODE,
        ];

        $this->client
            ->expects($this->once())
            ->method('activateLicense')
            ->with(
                TestingConstants::TEST_LICENSE_KEY,
                $this->isString(), // domain
                $this->isString()  // site name
            )
            ->willReturn($licenseData);

        // Act
        $this->licenseManager->activate(TestingConstants::TEST_LICENSE_KEY);

        // Assert: Verify WordPress options were set
        $this->assertEquals(
            TestingConstants::TEST_LICENSE_KEY,
            \get_option(Constants::WP_OPTION_LICENSE_KEY),
            'WordPress option should store license key'
        );
        $this->assertEquals(
            Constants::LICENSE_STATUS_ACTIVE,
            \get_option(Constants::WP_OPTION_LICENSE_STATUS),
            'WordPress option should store license status'
        );
        $this->assertEquals(
            $licenseData['expires_at'],
            \get_option(Constants::WP_OPTION_LICENSE_EXPIRES_AT),
            'WordPress option should store expiration'
        );
        $this->assertEquals(
            $licenseData['features'],
            \get_option(Constants::WP_OPTION_LICENSE_FEATURES),
            'WordPress option should store features'
        );
    }

    public function testValidateUsesCacheWhenAvailable(): void
    {
        // Test that validate uses WordPress transients for caching
        // This validates the manager correctly implements caching strategy

        // Arrange: Set cached validation result
        \set_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION, Constants::WP_TRANSIENT_VALUE_VALID, TestingConstants::TEST_CACHE_TTL_SECONDS);
        \update_option(Constants::WP_OPTION_LICENSE_KEY, TestingConstants::TEST_LICENSE_KEY);
        \update_option(Constants::WP_OPTION_LICENSE_STATUS, Constants::LICENSE_STATUS_ACTIVE);

        $this->client
            ->expects($this->never())
            ->method('validateLicense');

        // Act
        $result = $this->licenseManager->validate();

        // Assert: Should return cached result without API call
        $this->assertTrue($result, 'Should return cached valid result');
    }

    public function testValidateRefreshesCacheOnFailure(): void
    {
        // Test that validate correctly updates cache on validation failure
        // This validates the manager properly handles validation failures

        // Arrange
        \update_option(Constants::WP_OPTION_LICENSE_KEY, TestingConstants::TEST_LICENSE_KEY);
        \update_option(Constants::WP_OPTION_LICENSE_STATUS, Constants::LICENSE_STATUS_ACTIVE);
        \delete_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION);

        $this->client
            ->expects($this->once())
            ->method('validateLicense')
            ->willThrowException(new \Exception('Validation failed'));

        // Act
        $result = $this->licenseManager->validate();

        // Assert
        $this->assertFalse($result, 'Should return false on validation failure');
        $this->assertEquals(
            Constants::WP_TRANSIENT_VALUE_INVALID,
            \get_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION),
            'Should cache invalid result'
        );
        $this->assertEquals(
            Constants::LICENSE_STATUS_INACTIVE,
            \get_option(Constants::WP_OPTION_LICENSE_STATUS),
            'Should update status to inactive'
        );
    }

    public function testDeactivateClearsWordPressOptions(): void
    {
        // Test that deactivate correctly clears all WordPress options
        // This validates the manager properly cleans up on deactivation

        // Arrange: Set up license data
        \update_option(Constants::WP_OPTION_LICENSE_KEY, TestingConstants::TEST_LICENSE_KEY);
        \update_option(Constants::WP_OPTION_LICENSE_STATUS, Constants::LICENSE_STATUS_ACTIVE);
        \set_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION, Constants::WP_TRANSIENT_VALUE_VALID, TestingConstants::TEST_CACHE_TTL_SECONDS);

        $this->client
            ->expects($this->once())
            ->method('deactivateLicense')
            ->with(TestingConstants::TEST_LICENSE_KEY, $this->isString());

        // Act
        $this->licenseManager->deactivate();

        // Assert: Verify all options and transients were cleared
        $this->assertFalse(
            \get_option(Constants::WP_OPTION_LICENSE_KEY),
            'License key option should be deleted'
        );
        $this->assertFalse(
            \get_option(Constants::WP_OPTION_LICENSE_STATUS),
            'License status option should be deleted'
        );
        $this->assertFalse(
            \get_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION),
            'Validation transient should be deleted'
        );
    }

    public function testGetFeatureReturnsCorrectValue(): void
    {
        // Test that getFeature correctly retrieves feature values
        // This validates the manager properly handles feature access

        // Arrange
        $features = [
            'max_sites' => TestingConstants::TEST_ACTIVATION_LIMIT,
            'support_level' => 'priority',
        ];
        \update_option(Constants::WP_OPTION_LICENSE_FEATURES, $features);

        // Act
        $maxSites = $this->licenseManager->getFeature('max_sites', 1);
        $supportLevel = $this->licenseManager->getFeature('support_level', 'standard');
        $nonexistent = $this->licenseManager->getFeature('nonexistent', 'default');

        // Assert
        $this->assertEquals(TestingConstants::TEST_ACTIVATION_LIMIT, $maxSites, 'Should return correct feature value');
        $this->assertEquals('priority', $supportLevel, 'Should return correct feature value');
        $this->assertEquals('default', $nonexistent, 'Should return default for nonexistent feature');
    }

    public function testIsValidReturnsFalseWhenNoLicenseKey(): void
    {
        // Test that isValid correctly handles missing license key
        // This validates the manager properly handles edge cases

        // Arrange: No license key stored
        \delete_option(Constants::WP_OPTION_LICENSE_KEY);
        \delete_option(Constants::WP_OPTION_LICENSE_STATUS);

        // Act
        $result = $this->licenseManager->isValid();

        // Assert
        $this->assertFalse($result, 'Should return false when no license key');
    }

    public function testIsValidReturnsFalseWhenStatusNotActive(): void
    {
        // Test that isValid correctly checks license status
        // This validates the manager properly validates license state

        // Arrange
        \update_option(Constants::WP_OPTION_LICENSE_KEY, TestingConstants::TEST_LICENSE_KEY);
        \update_option(Constants::WP_OPTION_LICENSE_STATUS, Constants::LICENSE_STATUS_EXPIRED);

        // Act
        $result = $this->licenseManager->isValid();

        // Assert
        $this->assertFalse($result, 'Should return false when status is not active');
    }
}

