<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin;

use SimpleLicense\Plugin\Constants;

/**
 * Admin Settings Page
 * Handles WordPress admin settings page with proper templating
 */
class AdminSettingsPage
{
    private LicenseManager $licenseManager;
    private string $pluginSlug;
    private string $pageTitle;
    private string $menuTitle;
    private string $optionGroup;
    private string $nonceAction;
    private string $nonceField;

    public function __construct(
        LicenseManager $licenseManager,
        string $pluginSlug,
        string $pageTitle = 'License Settings',
        string $menuTitle = 'License'
    ) {
        $this->licenseManager = $licenseManager;
        $this->pluginSlug = $pluginSlug;
        $this->pageTitle = $pageTitle;
        $this->menuTitle = $menuTitle;
        $this->optionGroup = $pluginSlug . '_license';
        $this->nonceAction = $pluginSlug . '_license_nonce';
        $this->nonceField = $this->nonceAction . '_field';
    }

    /**
     * Register admin menu and settings page
     *
     * @return void
     */
    public function register(): void
    {
        \add_action('admin_menu', [$this, 'addMenuPage']);
        \add_action('admin_init', [$this, 'handleFormSubmission']);
    }

    /**
     * Add admin menu page
     *
     * @return void
     */
    public function addMenuPage(): void
    {
        \add_options_page(
            $this->pageTitle,
            $this->menuTitle,
            'manage_options',
            $this->optionGroup,
            [$this, 'renderPage']
        );
    }

    /**
     * Handle form submission
     *
     * @return void
     */
    public function handleFormSubmission(): void
    {
        if (!isset($_POST[$this->nonceField])) {
            return;
        }

        if (!\check_admin_referer($this->nonceAction, $this->nonceField)) {
            return;
        }

        if (!\current_user_can('manage_options')) {
            return;
        }

        $action = $_POST['action'] ?? '';
        $licenseKey = \sanitize_text_field($_POST['license_key'] ?? '');

        if ($action === 'activate' && !empty($licenseKey)) {
            $this->handleActivation($licenseKey);
        } elseif ($action === 'deactivate') {
            $this->handleDeactivation();
        }
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function renderPage(): void
    {
        $data = $this->preparePageData();
        $this->renderTemplate($data);
    }

    /**
     * Prepare data for template
     *
     * @return array<string, mixed>
     */
    private function preparePageData(): array
    {
        $licenseKey = $this->licenseManager->getStoredLicenseKey();
        $status = \get_option(Constants::WP_OPTION_LICENSE_STATUS, Constants::LICENSE_STATUS_INACTIVE);
        $expiresAt = \get_option(Constants::WP_OPTION_LICENSE_EXPIRES_AT, '');
        $features = \get_option(Constants::WP_OPTION_LICENSE_FEATURES, []);
        $tierCode = \get_option(Constants::WP_OPTION_LICENSE_TIER_CODE, '');

        $isActive = $status === Constants::LICENSE_STATUS_ACTIVE;
        $isExpired = $status === Constants::LICENSE_STATUS_EXPIRED;
        $isRevoked = $status === Constants::LICENSE_STATUS_REVOKED;

        $messages = $this->getAdminMessages();

        return [
            'page_title' => $this->pageTitle,
            'plugin_slug' => $this->pluginSlug,
            'option_group' => $this->optionGroup,
            'nonce_field' => $this->nonceField,
            'nonce_action' => $this->nonceAction,
            'license_key' => $licenseKey,
            'status' => $status,
            'expires_at' => $expiresAt,
            'features' => $features,
            'tier_code' => $tierCode,
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'is_revoked' => $isRevoked,
            'messages' => $messages,
        ];
    }

    /**
     * Render template
     *
     * @param array<string, mixed> $data Template data
     * @return void
     */
    private function renderTemplate(array $data): void
    {
        // Use WordPress admin template structure
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($data['page_title']); ?></h1>

            <?php if (!empty($data['messages'])): ?>
                <?php foreach ($data['messages'] as $message): ?>
                    <div class="notice notice-<?php echo esc_attr($message['type']); ?> is-dismissible">
                        <p><?php echo esc_html($message['text']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" action="">
                <?php \wp_nonce_field($data['nonce_action'], $data['nonce_field']); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="license_key"><?php esc_html_e('License Key', $data['plugin_slug']); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="license_key"
                                name="license_key"
                                class="regular-text"
                                value="<?php echo esc_attr($data['license_key'] ?? ''); ?>"
                                <?php echo $data['is_active'] ? 'readonly' : ''; ?>
                            />
                        </td>
                    </tr>

                    <?php if ($data['is_active']): ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Status', $data['plugin_slug']); ?></th>
                            <td>
                                <span class="license-status license-status-active">
                                    <?php esc_html_e('Active', $data['plugin_slug']); ?>
                                </span>
                            </td>
                        </tr>

                        <?php if (!empty($data['tier_code'])): ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Tier', $data['plugin_slug']); ?></th>
                                <td><?php echo esc_html($data['tier_code']); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($data['expires_at'])): ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Expires', $data['plugin_slug']); ?></th>
                                <td><?php echo esc_html($data['expires_at']); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($data['features'])): ?>
                            <tr>
                                <th scope="row"><?php esc_html_e('Features', $data['plugin_slug']); ?></th>
                                <td>
                                    <ul>
                                        <?php foreach ($data['features'] as $key => $value): ?>
                                            <li>
                                                <strong><?php echo esc_html($key); ?>:</strong>
                                                <?php echo esc_html(is_array($value) ? wp_json_encode($value) : (string) $value); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php elseif ($data['is_expired']): ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Status', $data['plugin_slug']); ?></th>
                            <td>
                                <span class="license-status license-status-expired">
                                    <?php esc_html_e('Expired', $data['plugin_slug']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php elseif ($data['is_revoked']): ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Status', $data['plugin_slug']); ?></th>
                            <td>
                                <span class="license-status license-status-revoked">
                                    <?php esc_html_e('Revoked', $data['plugin_slug']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>

                <p class="submit">
                    <?php if ($data['is_active']): ?>
                        <input
                            type="submit"
                            name="action"
                            value="deactivate"
                            class="button button-secondary"
                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to deactivate this license?', $data['plugin_slug']); ?>');"
                        />
                    <?php else: ?>
                        <input
                            type="submit"
                            name="action"
                            value="activate"
                            class="button button-primary"
                        />
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle license activation
     *
     * @param string $licenseKey License key
     * @return void
     */
    private function handleActivation(string $licenseKey): void
    {
        try {
            $this->licenseManager->activate($licenseKey);
            add_settings_error(
                $this->optionGroup,
                'license_activated',
                __('License activated successfully!', $this->pluginSlug),
                'success'
            );
        } catch (\Exception $e) {
            add_settings_error(
                $this->optionGroup,
                'license_activation_failed',
                sprintf(__('License activation failed: %s', $this->pluginSlug), $e->getMessage()),
                'error'
            );
        }
    }

    /**
     * Handle license deactivation
     *
     * @return void
     */
    private function handleDeactivation(): void
    {
        try {
            $this->licenseManager->deactivate();
            add_settings_error(
                $this->optionGroup,
                'license_deactivated',
                __('License deactivated successfully.', $this->pluginSlug),
                'success'
            );
        } catch (\Exception $e) {
            add_settings_error(
                $this->optionGroup,
                'license_deactivation_failed',
                sprintf(__('License deactivation failed: %s', $this->pluginSlug), $e->getMessage()),
                'error'
            );
        }
    }

    /**
     * Get admin messages
     *
     * @return array<int, array<string, string>>
     */
    private function getAdminMessages(): array
    {
        $messages = [];
        $settingsErrors = get_settings_errors($this->optionGroup);

        foreach ($settingsErrors as $error) {
            $messages[] = [
                'type' => $error['type'] ?? 'info',
                'text' => $error['message'] ?? '',
            ];
        }

        return $messages;
    }
}

