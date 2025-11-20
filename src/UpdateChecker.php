<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin;

use SimpleLicense\Plugin\Constants;

/**
 * Update Checker
 * Handles plugin update checking with WordPress integration
 */
class UpdateChecker
{
    private Client $client;
    private string $pluginSlug;
    private string $pluginFile;
    private string $currentVersion;

    public function __construct(
        Client $client,
        string $pluginSlug,
        string $pluginFile,
        string $currentVersion
    ) {
        $this->client = $client;
        $this->pluginSlug = $pluginSlug;
        $this->pluginFile = $pluginFile;
        $this->currentVersion = $currentVersion;
    }

    /**
     * Check for updates
     *
     * @param string|null $licenseKey Optional license key (defaults to stored key)
     * @param string|null $domain Optional domain (defaults to current site domain)
     * @return array<string, mixed>|null Update data or null if no update available
     */
    public function checkForUpdates(?string $licenseKey = null, ?string $domain = null): ?array
    {
        $licenseKey = $licenseKey ?? $this->getStoredLicenseKey();
        if (empty($licenseKey)) {
            return null;
        }

        $domain = $domain ?? $this->getCurrentDomain();

        // Check cache first
        $cached = get_transient(Constants::WP_TRANSIENT_UPDATE_CHECK);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $update = $this->client->checkForUpdates($licenseKey, $domain, $this->pluginSlug, $this->currentVersion);
            set_transient(Constants::WP_TRANSIENT_UPDATE_CHECK, $update, Constants::DEFAULT_CACHE_TTL_SECONDS);

            return $update;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Register WordPress update hooks
     *
     * @return void
     */
    public function registerUpdateHooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'injectUpdateData']);
    }

    /**
     * Inject update data into WordPress update transient
     *
     * @param \stdClass|mixed $transient Update transient
     * @return \stdClass|mixed Modified transient
     */
    public function injectUpdateData($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $update = $this->checkForUpdates();
        if ($update === null) {
            return $transient;
        }

        $updateObject = (object) [
            'slug' => $this->pluginSlug,
            'plugin' => $this->pluginFile,
            'new_version' => $update['version'] ?? '',
            'package' => $update['download_url'] ?? '',
            'tested' => $update['tested_wp'] ?? '',
            'requires' => $update['min_wp'] ?? '',
            'sections' => [
                'changelog' => $update['changelog'] ?? '',
            ],
        ];

        $transient->response[$this->pluginFile] = $updateObject;

        return $transient;
    }

    /**
     * Get stored license key
     *
     * @return string|null License key
     */
    private function getStoredLicenseKey(): ?string
    {
        return get_option(Constants::WP_OPTION_LICENSE_KEY, null);
    }

    /**
     * Get current domain
     *
     * @return string Domain name
     */
    private function getCurrentDomain(): string
    {
        return (string) parse_url(home_url(), PHP_URL_HOST);
    }
}

