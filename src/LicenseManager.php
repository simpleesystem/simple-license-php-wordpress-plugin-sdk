<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin;

use SimpleLicense\Plugin\Constants;

/**
 * License Manager
 * Handles license lifecycle management with WordPress integration
 */
class LicenseManager
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Activate license and store in WordPress options
     *
     * @param string $licenseKey License key
     * @param string|null $domain Optional domain (defaults to current site domain)
     * @param string|null $siteName Optional site name
     * @return array<string, mixed> License data
     */
    public function activate(string $licenseKey, ?string $domain = null, ?string $siteName = null): array
    {
        $domain = $domain ?? $this->getCurrentDomain();
        $siteName = $siteName ?? $this->getCurrentSiteName();

        $data = $this->client->activateLicense($licenseKey, $domain, $siteName);
        $this->storeLicenseState($licenseKey, $data);

        return $data;
    }

    /**
     * Validate license and update WordPress options
     *
     * @param string|null $licenseKey Optional license key (defaults to stored key)
     * @param string|null $domain Optional domain (defaults to current site domain)
     * @return bool True if valid
     */
    public function validate(?string $licenseKey = null, ?string $domain = null): bool
    {
        $licenseKey = $licenseKey ?? $this->getStoredLicenseKey();
        if (empty($licenseKey)) {
            return false;
        }

        $domain = $domain ?? $this->getCurrentDomain();

        // Check cache first
        $cached = \get_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION);
        if ($cached !== false) {
            return $cached === Constants::WP_TRANSIENT_VALUE_VALID;
        }

        try {
            $data = $this->client->validateLicense($licenseKey, $domain);
            $this->storeLicenseState($licenseKey, $data);
            \set_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION, Constants::WP_TRANSIENT_VALUE_VALID, Constants::DEFAULT_VALIDATION_CACHE_TTL_SECONDS);

            return true;
        } catch (\Exception $e) {
            \set_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION, Constants::WP_TRANSIENT_VALUE_INVALID, Constants::DEFAULT_FAILED_VALIDATION_CACHE_TTL_SECONDS);
            \update_option(Constants::WP_OPTION_LICENSE_STATUS, Constants::LICENSE_STATUS_INACTIVE);

            return false;
        }
    }

    /**
     * Deactivate license and clear WordPress options
     *
     * @param string|null $licenseKey Optional license key (defaults to stored key)
     * @param string|null $domain Optional domain (defaults to current site domain)
     * @return void
     */
    public function deactivate(?string $licenseKey = null, ?string $domain = null): void
    {
        $licenseKey = $licenseKey ?? $this->getStoredLicenseKey();
        if (empty($licenseKey)) {
            return;
        }

        $domain = $domain ?? $this->getCurrentDomain();

        try {
            $this->client->deactivateLicense($licenseKey, $domain);
        } catch (\Exception $e) {
            // Continue with cleanup even if API call fails
        }

        $this->clearLicenseState();
    }

    /**
     * Check if license is valid
     *
     * @return bool True if license is valid
     */
    public function isValid(): bool
    {
        $licenseKey = $this->getStoredLicenseKey();
        $status = \get_option(Constants::WP_OPTION_LICENSE_STATUS);

        if (empty($licenseKey) || $status !== Constants::LICENSE_STATUS_ACTIVE) {
            return false;
        }

        return $this->validate($licenseKey);
    }

    /**
     * Get license feature value
     *
     * @param string $key Feature key
     * @param mixed $default Default value
     * @return mixed Feature value
     */
    public function getFeature(string $key, mixed $default = null): mixed
    {
        $features = \get_option(Constants::WP_OPTION_LICENSE_FEATURES, []);
        return $features[$key] ?? $default;
    }

    /**
     * Get stored license key
     *
     * @return string|null License key
     */
    public function getStoredLicenseKey(): ?string
    {
        return \get_option(Constants::WP_OPTION_LICENSE_KEY, null);
    }

    /**
     * Store license state in WordPress options
     *
     * @param string $licenseKey License key
     * @param array<string, mixed> $data License data
     * @return void
     */
    private function storeLicenseState(string $licenseKey, array $data): void
    {
        \update_option(Constants::WP_OPTION_LICENSE_KEY, $licenseKey);
        \update_option(Constants::WP_OPTION_LICENSE_STATUS, $data['status'] ?? Constants::LICENSE_STATUS_INACTIVE);
        \update_option(Constants::WP_OPTION_LICENSE_EXPIRES_AT, $data['expires_at'] ?? '');
        \update_option(Constants::WP_OPTION_LICENSE_FEATURES, $data['features'] ?? []);
        \update_option(Constants::WP_OPTION_LICENSE_TIER_CODE, $data['tier_code'] ?? '');

        // Clear validation cache
        \delete_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION);
    }

    /**
     * Clear license state from WordPress options
     *
     * @return void
     */
    private function clearLicenseState(): void
    {
        \delete_option(Constants::WP_OPTION_LICENSE_KEY);
        \delete_option(Constants::WP_OPTION_LICENSE_STATUS);
        \delete_option(Constants::WP_OPTION_LICENSE_EXPIRES_AT);
        \delete_option(Constants::WP_OPTION_LICENSE_FEATURES);
        \delete_option(Constants::WP_OPTION_LICENSE_TIER_CODE);
        \delete_transient(Constants::WP_TRANSIENT_LICENSE_VALIDATION);
    }

    /**
     * Get current domain
     *
     * @return string Domain name
     */
    private function getCurrentDomain(): string
    {
        return (string) parse_url(\home_url(), PHP_URL_HOST);
    }

    /**
     * Get current site name
     *
     * @return string Site name
     */
    private function getCurrentSiteName(): string
    {
        return (string) \get_bloginfo('name');
    }
}

