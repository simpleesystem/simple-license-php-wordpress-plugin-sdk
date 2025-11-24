<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SimpleLicense\Plugin\Client;
use SimpleLicense\Plugin\Constants;
use SimpleLicense\Plugin\Exceptions\ActivationLimitExceededException;
use SimpleLicense\Plugin\Exceptions\LicenseExpiredException;
use SimpleLicense\Plugin\Exceptions\LicenseNotFoundException;
use SimpleLicense\Plugin\Http\WordPressHttpClient;
use SimpleLicense\Plugin\Tests\TestingConstants;

/**
 * Client Integration Tests
 * Tests actual API workflows with mock HTTP server
 */
class ClientIntegrationTest extends TestCase
{
    private Client $client;
    private string $mockServerUrl;

    protected function setUp(): void
    {
        $this->mockServerUrl = TestingConstants::TEST_API_BASE_URL;
        $this->client = new Client($this->mockServerUrl);
    }

    public function testCompleteLicenseActivationWorkflow(): void
    {
        // Test the complete workflow: activate -> validate -> deactivate
        // This validates the SDK correctly handles the full license lifecycle

        $mockHttpClient = $this->createMockHttpClient([
            // Activate
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_ACTIVATE,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                    'site_name' => TestingConstants::TEST_SITE_NAME,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'activation' => [
                                'id' => TestingConstants::TEST_ACTIVATION_ID,
                                'license_id' => TestingConstants::TEST_LICENSE_ID,
                                'license_key' => TestingConstants::TEST_LICENSE_KEY,
                                'domain' => TestingConstants::TEST_DOMAIN,
                                'site_name' => TestingConstants::TEST_SITE_NAME,
                                'status' => Constants::LICENSE_STATUS_ACTIVE,
                                'activated_at' => TestingConstants::TEST_ACTIVATED_AT,
                            ],
                            'license' => [
                                'license_key' => TestingConstants::TEST_LICENSE_KEY,
                                'status' => Constants::LICENSE_STATUS_ACTIVE,
                                'tier_code' => TestingConstants::TEST_TIER_CODE,
                                'features' => ['max_sites' => TestingConstants::TEST_ACTIVATION_LIMIT],
                            ],
                        ],
                    ]),
                ],
            ],
            // Validate
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_VALIDATE,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'license_key' => TestingConstants::TEST_LICENSE_KEY,
                            'status' => Constants::LICENSE_STATUS_ACTIVE,
                            'features' => ['max_sites' => TestingConstants::TEST_ACTIVATION_LIMIT],
                        ],
                    ]),
                ],
            ],
            // Deactivate
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_DEACTIVATE,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act: Execute complete workflow
        $activateResult = $client->activateLicense(
            TestingConstants::TEST_LICENSE_KEY,
            TestingConstants::TEST_DOMAIN,
            TestingConstants::TEST_SITE_NAME
        );
        $this->assertArrayHasKey('activation', $activateResult, 'Activation response should include activation object');
        $this->assertArrayHasKey('license', $activateResult, 'Activation response should include license object');
        $this->assertEquals(Constants::LICENSE_STATUS_ACTIVE, $activateResult['license']['status'], 'License should be activated');
        $this->assertArrayHasKey('features', $activateResult['license'], 'Activated license should have features');

        $validateResult = $client->validateLicense(TestingConstants::TEST_LICENSE_KEY, TestingConstants::TEST_DOMAIN);
        $this->assertEquals(Constants::LICENSE_STATUS_ACTIVE, $validateResult['status'], 'License should validate as active');

        $deactivateResult = $client->deactivateLicense(TestingConstants::TEST_LICENSE_KEY, TestingConstants::TEST_DOMAIN);
        $this->assertTrue($deactivateResult['success'], 'License deactivation should succeed');
    }

    public function testLicenseExpiredErrorHandling(): void
    {
        // Test that expired licenses are correctly identified
        // This validates the SDK properly handles license expiration

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_VALIDATE,
                'response' => [
                    'status' => Constants::HTTP_BAD_REQUEST,
                    'body' => json_encode([
                        'success' => false,
                        'error' => [
                            'code' => Constants::ERROR_CODE_LICENSE_EXPIRED,
                            'message' => 'License has expired',
                        ],
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act & Assert
        $this->expectException(LicenseExpiredException::class);
        $this->expectExceptionMessage('License has expired');
        $client->validateLicense(TestingConstants::TEST_LICENSE_KEY, TestingConstants::TEST_DOMAIN);
    }

    public function testActivationLimitExceededErrorHandling(): void
    {
        // Test that activation limit exceeded is correctly identified
        // This validates the SDK properly handles activation limits

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_ACTIVATE,
                'response' => [
                    'status' => Constants::HTTP_CONFLICT,
                    'body' => json_encode([
                        'success' => false,
                        'error' => [
                            'code' => Constants::ERROR_CODE_ACTIVATION_LIMIT_EXCEEDED,
                            'message' => 'Activation limit exceeded',
                        ],
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act & Assert
        $this->expectException(ActivationLimitExceededException::class);
        $this->expectExceptionMessage('Activation limit exceeded');
        $client->activateLicense(TestingConstants::TEST_LICENSE_KEY, TestingConstants::TEST_DOMAIN);
    }

    public function testCheckForUpdatesReturnsUpdateWhenAvailable(): void
    {
        // Test that update checking correctly identifies available updates
        // This validates the SDK properly handles update checking

        $updateData = [
            'version' => TestingConstants::TEST_PLUGIN_NEW_VERSION,
            'download_url' => 'https://example.com/update.zip',
            'changelog' => 'Bug fixes',
            'tested_wp' => '6.6',
            'min_wp' => '6.1',
        ];

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_UPDATES_CHECK,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                    'slug' => TestingConstants::TEST_PLUGIN_SLUG,
                    'current_version' => TestingConstants::TEST_PLUGIN_VERSION,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                        'update' => $updateData,
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act
        $update = $client->checkForUpdates(
            TestingConstants::TEST_LICENSE_KEY,
            TestingConstants::TEST_DOMAIN,
            TestingConstants::TEST_PLUGIN_SLUG,
            TestingConstants::TEST_PLUGIN_VERSION
        );

        // Assert
        $this->assertNotNull($update, 'Should return update when available');
        $this->assertEquals(TestingConstants::TEST_PLUGIN_NEW_VERSION, $update['version'], 'Should return correct version');
        $this->assertArrayHasKey('download_url', $update, 'Should include download URL');
    }

    public function testCheckForUpdatesReturnsNullWhenNoUpdate(): void
    {
        // Test that update checking returns null when no update is available
        // This validates the SDK correctly handles the no-update scenario

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_UPDATES_CHECK,
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                        'update' => null,
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act
        $update = $client->checkForUpdates(
            TestingConstants::TEST_LICENSE_KEY,
            TestingConstants::TEST_DOMAIN,
            TestingConstants::TEST_PLUGIN_SLUG,
            TestingConstants::TEST_PLUGIN_VERSION
        );

        // Assert
        $this->assertNull($update, 'Should return null when no update available');
    }

    public function testGetLicenseFeaturesReturnsCorrectFeatures(): void
    {
        // Test that getLicenseFeatures correctly retrieves feature data
        // This validates the SDK properly handles feature retrieval

        $features = [
            'max_sites' => TestingConstants::TEST_ACTIVATION_LIMIT,
            'support_level' => 'priority',
            'analytics_enabled' => true,
        ];

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'GET',
                'url' => sprintf(Constants::API_ENDPOINT_LICENSES_FEATURES, TestingConstants::TEST_LICENSE_KEY),
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                        'data' => $features,
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act
        $result = $client->getLicenseFeatures(TestingConstants::TEST_LICENSE_KEY);

        // Assert
        $this->assertEquals($features, $result, 'Should return correct features');
        $this->assertEquals(TestingConstants::TEST_ACTIVATION_LIMIT, $result['max_sites'], 'Should have correct max_sites value');
    }

    public function testActivateLicenseWithOptionalParameters(): void
    {
        // Test that activateLicense correctly sends optional parameters
        // This validates the SDK correctly handles os, region, client_version, device_hash

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_ACTIVATE,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                    'site_name' => TestingConstants::TEST_SITE_NAME,
                    'os' => TestingConstants::TEST_OS,
                    'region' => TestingConstants::TEST_REGION,
                    'client_version' => TestingConstants::TEST_CLIENT_VERSION,
                    'device_hash' => TestingConstants::TEST_DEVICE_HASH,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                        'data' => [
                            'activation' => [
                                'id' => TestingConstants::TEST_ACTIVATION_ID,
                                'license_key' => TestingConstants::TEST_LICENSE_KEY,
                                'domain' => TestingConstants::TEST_DOMAIN,
                                'os' => TestingConstants::TEST_OS,
                                'region' => TestingConstants::TEST_REGION,
                                'client_version' => TestingConstants::TEST_CLIENT_VERSION,
                                'device_hash' => TestingConstants::TEST_DEVICE_HASH,
                                'status' => Constants::LICENSE_STATUS_ACTIVE,
                            ],
                            'license' => [
                                'license_key' => TestingConstants::TEST_LICENSE_KEY,
                                'status' => Constants::LICENSE_STATUS_ACTIVE,
                            ],
                        ],
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act
        $result = $client->activateLicense(
            TestingConstants::TEST_LICENSE_KEY,
            TestingConstants::TEST_DOMAIN,
            TestingConstants::TEST_SITE_NAME,
            TestingConstants::TEST_OS,
            TestingConstants::TEST_REGION,
            TestingConstants::TEST_CLIENT_VERSION,
            TestingConstants::TEST_DEVICE_HASH
        );

        // Assert
        $this->assertArrayHasKey('activation', $result, 'Should include activation object');
        $this->assertEquals(TestingConstants::TEST_OS, $result['activation']['os'], 'Should include OS in activation');
        $this->assertEquals(TestingConstants::TEST_REGION, $result['activation']['region'], 'Should include region in activation');
        $this->assertEquals(TestingConstants::TEST_CLIENT_VERSION, $result['activation']['client_version'], 'Should include client_version in activation');
        $this->assertEquals(TestingConstants::TEST_DEVICE_HASH, $result['activation']['device_hash'], 'Should include device_hash in activation');
    }

    public function testReportUsageWithRequiredMonth(): void
    {
        // Test that reportUsage correctly sends usage data with required month
        // This validates the SDK correctly handles the new reportUsage signature

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_USAGE,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                    'month' => TestingConstants::TEST_MONTH,
                    'conversations_count' => TestingConstants::TEST_CONVERSATIONS_COUNT,
                    'voice_count' => TestingConstants::TEST_VOICE_COUNT,
                    'text_count' => TestingConstants::TEST_TEXT_COUNT,
                    'consents_captured' => TestingConstants::TEST_CONSENTS_CAPTURED,
                    'compliance_violations' => TestingConstants::TEST_COMPLIANCE_VIOLATIONS,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act
        $result = $client->reportUsage(
            TestingConstants::TEST_LICENSE_KEY,
            TestingConstants::TEST_DOMAIN,
            TestingConstants::TEST_MONTH,
            TestingConstants::TEST_CONVERSATIONS_COUNT,
            TestingConstants::TEST_VOICE_COUNT,
            TestingConstants::TEST_TEXT_COUNT,
            TestingConstants::TEST_CONSENTS_CAPTURED,
            TestingConstants::TEST_COMPLIANCE_VIOLATIONS
        );

        // Assert
        $this->assertTrue($result['success'], 'Usage reporting should succeed');
    }

    public function testReportUsageWithDefaultValues(): void
    {
        // Test that reportUsage correctly uses default values for optional parameters
        // This validates the SDK correctly handles default parameter values

        $mockHttpClient = $this->createMockHttpClient([
            [
                'method' => 'POST',
                'url' => Constants::API_ENDPOINT_LICENSES_USAGE,
                'request_data' => [
                    'license_key' => TestingConstants::TEST_LICENSE_KEY,
                    'domain' => TestingConstants::TEST_DOMAIN,
                    'month' => TestingConstants::TEST_MONTH,
                    'conversations_count' => 0,
                    'voice_count' => 0,
                    'text_count' => 0,
                    'consents_captured' => 0,
                    'compliance_violations' => 0,
                ],
                'response' => [
                    'status' => Constants::HTTP_OK,
                    'body' => json_encode([
                        'success' => true,
                    ]),
                ],
            ],
        ]);

        $client = new Client($this->mockServerUrl, $mockHttpClient);

        // Act - Only provide required parameters, defaults should be used
        $result = $client->reportUsage(
            TestingConstants::TEST_LICENSE_KEY,
            TestingConstants::TEST_DOMAIN,
            TestingConstants::TEST_MONTH
        );

        // Assert
        $this->assertTrue($result['success'], 'Usage reporting with defaults should succeed');
    }

    /**
     * Create mock HTTP client that responds to specific requests
     *
     * @param array<int, array<string, mixed>> $responses Array of expected requests and responses
     * @return \SimpleLicense\Plugin\Http\HttpClientInterface
     */
    private function createMockHttpClient(array $responses): \SimpleLicense\Plugin\Http\HttpClientInterface
    {
        $mockClient = $this->createMock(\SimpleLicense\Plugin\Http\HttpClientInterface::class);
        // Group by method type for proper sequencing
        $methodGroups = [];
        foreach ($responses as $responseConfig) {
            $method = strtolower($responseConfig['method']);
            if (!isset($methodGroups[$method])) {
                $methodGroups[$method] = [];
            }
            $methodGroups[$method][] = $responseConfig;
        }

        // Set up expectations for each method group
        foreach ($methodGroups as $method => $configs) {
            $responsesForMethod = array_column($configs, 'response');
            $urlsForMethod = array_column($configs, 'url');
            $requestDataForMethod = array_column($configs, 'request_data', null);

            $invocation = $mockClient
                ->expects($this->exactly(count($configs)))
                ->method($method);

            // Use array-based tracking instead of static variables
            $callTracker = ['index' => 0];

            $invocation->with(
                $this->callback(function ($url) use ($urlsForMethod, &$callTracker) {
                    $expectedUrl = $urlsForMethod[$callTracker['index']] ?? '';
                    $callTracker['index']++;
                    return str_contains($url, $expectedUrl);
                }),
                $this->callback(function ($data) use ($requestDataForMethod, &$callTracker) {
                    $dataIndex = $callTracker['index'] - 1; // Already incremented for URL
                    $expectedData = $requestDataForMethod[$dataIndex] ?? [];
                    if (empty($expectedData)) {
                        return true;
                    }
                    foreach ($expectedData as $key => $value) {
                        if (!isset($data[$key]) || $data[$key] !== $value) {
                            return false;
                        }
                    }
                    return true;
                })
            );

            $invocation->willReturnOnConsecutiveCalls(...$responsesForMethod);
        }

        return $mockClient;
    }
}

