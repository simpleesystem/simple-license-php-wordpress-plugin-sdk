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
                            'license_key' => TestingConstants::TEST_LICENSE_KEY,
                            'status' => Constants::LICENSE_STATUS_ACTIVE,
                            'tier_code' => TestingConstants::TEST_TIER_CODE,
                            'features' => ['max_sites' => TestingConstants::TEST_ACTIVATION_LIMIT],
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
        $this->assertEquals(Constants::LICENSE_STATUS_ACTIVE, $activateResult['status'], 'License should be activated');
        $this->assertArrayHasKey('features', $activateResult, 'Activated license should have features');

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

