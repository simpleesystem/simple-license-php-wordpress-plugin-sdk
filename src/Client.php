<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin;

use SimpleLicense\Plugin\Exceptions\ActivationLimitExceededException;
use SimpleLicense\Plugin\Exceptions\ApiException;
use SimpleLicense\Plugin\Exceptions\LicenseExpiredException;
use SimpleLicense\Plugin\Exceptions\LicenseNotFoundException;
use SimpleLicense\Plugin\Exceptions\NetworkException;
use SimpleLicense\Plugin\Exceptions\ValidationException;
use SimpleLicense\Plugin\Http\HttpClientInterface;
use SimpleLicense\Plugin\Http\WordPressHttpClient;

/**
 * Main Client for Plugin SDK
 * Handles all public API endpoints for license management
 */
class Client
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;

    public function __construct(
        string $baseUrl,
        ?HttpClientInterface $httpClient = null,
        int $timeout = Constants::DEFAULT_TIMEOUT_SECONDS
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = $httpClient ?? new WordPressHttpClient($this->baseUrl, $timeout);
    }

    /**
     * Activate a license on a domain
     *
     * @param string $licenseKey License key
     * @param string $domain Domain name
     * @param string|null $siteName Optional site name
     * @return array<string, mixed> License data
     * @throws ApiException
     */
    public function activateLicense(string $licenseKey, string $domain, ?string $siteName = null): array
    {
        $data = [
            'license_key' => $licenseKey,
            'domain' => $domain,
        ];

        if ($siteName !== null) {
            $data['site_name'] = $siteName;
        }

        $response = $this->httpClient->post(Constants::API_ENDPOINT_LICENSES_ACTIVATE, $data);
        $parsed = $this->parseResponse($response);

        if (!isset($parsed[Constants::RESPONSE_KEY_SUCCESS]) || !$parsed[Constants::RESPONSE_KEY_SUCCESS]) {
            $this->handleErrorResponse($parsed, $response['status']);
        }

        return $parsed[Constants::RESPONSE_KEY_DATA] ?? [];
    }

    /**
     * Validate a license on a domain
     *
     * @param string $licenseKey License key
     * @param string $domain Domain name
     * @return array<string, mixed> License data
     * @throws ApiException
     */
    public function validateLicense(string $licenseKey, string $domain): array
    {
        $data = [
            'license_key' => $licenseKey,
            'domain' => $domain,
        ];

        $response = $this->httpClient->post(Constants::API_ENDPOINT_LICENSES_VALIDATE, $data);
        $parsed = $this->parseResponse($response);

        if (!isset($parsed[Constants::RESPONSE_KEY_SUCCESS]) || !$parsed[Constants::RESPONSE_KEY_SUCCESS]) {
            $this->handleErrorResponse($parsed, $response['status']);
        }

        return $parsed[Constants::RESPONSE_KEY_DATA] ?? [];
    }

    /**
     * Deactivate a license on a domain
     *
     * @param string $licenseKey License key
     * @param string $domain Domain name
     * @return array<string, mixed> Response
     * @throws ApiException
     */
    public function deactivateLicense(string $licenseKey, string $domain): array
    {
        $data = [
            'license_key' => $licenseKey,
            'domain' => $domain,
        ];

        $response = $this->httpClient->post(Constants::API_ENDPOINT_LICENSES_DEACTIVATE, $data);
        return $this->parseResponse($response);
    }

    /**
     * Get license data by key
     *
     * @param string $licenseKey License key
     * @return array<string, mixed> License data
     * @throws LicenseNotFoundException
     * @throws ApiException
     */
    public function getLicenseData(string $licenseKey): array
    {
        $url = sprintf(Constants::API_ENDPOINT_LICENSES_GET, $licenseKey);
        $response = $this->httpClient->get($url);
        $parsed = $this->parseResponse($response);

        if (!isset($parsed[Constants::RESPONSE_KEY_SUCCESS]) || !$parsed[Constants::RESPONSE_KEY_SUCCESS]) {
            $errorCode = $parsed[Constants::RESPONSE_KEY_ERROR][Constants::RESPONSE_KEY_CODE] ?? '';
            if ($errorCode === Constants::ERROR_CODE_LICENSE_NOT_FOUND) {
                throw new LicenseNotFoundException(
                    $parsed[Constants::RESPONSE_KEY_ERROR][Constants::RESPONSE_KEY_MESSAGE] ?? 'License not found',
                    $errorCode
                );
            }
            $this->handleErrorResponse($parsed, $response['status']);
        }

        return $parsed[Constants::RESPONSE_KEY_DATA] ?? [];
    }

    /**
     * Get license features
     *
     * @param string $licenseKey License key
     * @return array<string, mixed> Features data
     * @throws ApiException
     */
    public function getLicenseFeatures(string $licenseKey): array
    {
        $url = sprintf(Constants::API_ENDPOINT_LICENSES_FEATURES, $licenseKey);
        $response = $this->httpClient->get($url);
        $parsed = $this->parseResponse($response);

        if (!isset($parsed[Constants::RESPONSE_KEY_SUCCESS]) || !$parsed[Constants::RESPONSE_KEY_SUCCESS]) {
            $this->handleErrorResponse($parsed, $response['status']);
        }

        return $parsed[Constants::RESPONSE_KEY_DATA] ?? [];
    }

    /**
     * Report license usage
     *
     * @param string $licenseKey License key
     * @param string $domain Domain name
     * @param array<string, mixed> $usageData Usage data
     * @return array<string, mixed> Response
     * @throws ApiException
     */
    public function reportUsage(string $licenseKey, string $domain, array $usageData): array
    {
        $data = array_merge(
            [
                'license_key' => $licenseKey,
                'domain' => $domain,
            ],
            $usageData
        );

        $response = $this->httpClient->post(Constants::API_ENDPOINT_LICENSES_USAGE, $data);
        return $this->parseResponse($response);
    }

    /**
     * Check for plugin updates
     *
     * @param string $licenseKey License key
     * @param string $domain Domain name
     * @param string $slug Plugin slug
     * @param string $currentVersion Current plugin version
     * @return array<string, mixed>|null Update data or null if no update available
     * @throws ApiException
     */
    public function checkForUpdates(string $licenseKey, string $domain, string $slug, string $currentVersion): ?array
    {
        $data = [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'slug' => $slug,
            'current_version' => $currentVersion,
        ];

        $response = $this->httpClient->post(Constants::API_ENDPOINT_UPDATES_CHECK, $data);
        $parsed = $this->parseResponse($response);

        if (!isset($parsed[Constants::RESPONSE_KEY_SUCCESS]) || !$parsed[Constants::RESPONSE_KEY_SUCCESS]) {
            $this->handleErrorResponse($parsed, $response['status']);
        }

        return $parsed[Constants::RESPONSE_KEY_UPDATE] ?? null;
    }

    /**
     * Handle error responses and throw appropriate exceptions
     *
     * @param array<string, mixed> $parsed Parsed response data
     * @param int $statusCode HTTP status code
     * @throws ApiException
     */
    private function handleErrorResponse(array $parsed, int $statusCode): void
    {
        $errorCode = $parsed[Constants::RESPONSE_KEY_ERROR][Constants::RESPONSE_KEY_CODE] ?? Constants::ERROR_CODE_VALIDATION_ERROR;
        $errorMessage = $parsed[Constants::RESPONSE_KEY_ERROR][Constants::RESPONSE_KEY_MESSAGE] ?? 'API error';

        if ($errorCode === Constants::ERROR_CODE_LICENSE_EXPIRED) {
            throw new LicenseExpiredException($errorMessage, $errorCode);
        }

        if ($errorCode === Constants::ERROR_CODE_ACTIVATION_LIMIT_EXCEEDED) {
            throw new ActivationLimitExceededException($errorMessage, $errorCode);
        }

        if ($errorCode === Constants::ERROR_CODE_LICENSE_NOT_FOUND) {
            throw new LicenseNotFoundException($errorMessage, $errorCode);
        }

        if ($statusCode === Constants::HTTP_BAD_REQUEST) {
            throw new ValidationException($errorMessage, $errorCode);
        }

        throw new ApiException($errorMessage, $errorCode, $parsed[Constants::RESPONSE_KEY_ERROR] ?? null);
    }

    /**
     * Parse API response
     *
     * @param array{status: int, body: string, headers: array<string, string>} $response HTTP response
     * @return array<string, mixed> Parsed response data
     * @throws ApiException
     */
    private function parseResponse(array $response): array
    {
        $body = $response['body'];

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                'Invalid JSON response from server',
                Constants::ERROR_CODE_VALIDATION_ERROR,
                ['body' => $body]
            );
        }

        return $data;
    }
}

