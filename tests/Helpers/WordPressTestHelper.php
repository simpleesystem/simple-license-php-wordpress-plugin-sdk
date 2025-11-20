<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin\Tests\Helpers;

/**
 * WordPress Test Helper
 * Provides mock WordPress functions for testing
 */
class WordPressTestHelper
{
    private static array $options = [];
    private static array $transients = [];

    public static function setUp(): void
    {
        self::$options = [];
        self::$transients = [];
    }

    public static function tearDown(): void
    {
        self::$options = [];
        self::$transients = [];
    }

    /**
     * Mock get_option function
     */
    public static function getOption(string $option, mixed $default = false): mixed
    {
        return self::$options[$option] ?? $default;
    }

    /**
     * Mock update_option function
     */
    public static function updateOption(string $option, mixed $value): bool
    {
        self::$options[$option] = $value;
        return true;
    }

    /**
     * Mock delete_option function
     */
    public static function deleteOption(string $option): bool
    {
        unset(self::$options[$option]);
        return true;
    }

    /**
     * Mock get_transient function
     */
    public static function getTransient(string $transient): mixed
    {
        if (!isset(self::$transients[$transient])) {
            return false;
        }

        $data = self::$transients[$transient];
        if ($data['expires'] < time()) {
            unset(self::$transients[$transient]);
            return false;
        }

        return $data['value'];
    }

    /**
     * Mock set_transient function
     */
    public static function setTransient(string $transient, mixed $value, int $expiration): bool
    {
        self::$transients[$transient] = [
            'value' => $value,
            'expires' => time() + $expiration,
        ];
        return true;
    }

    /**
     * Mock delete_transient function
     */
    public static function deleteTransient(string $transient): bool
    {
        unset(self::$transients[$transient]);
        return true;
    }

    /**
     * Mock home_url function
     */
    public static function homeUrl(string $path = ''): string
    {
        return 'https://example.com' . $path;
    }

    /**
     * Mock get_bloginfo function
     */
    public static function getBloginfo(string $show = ''): string
    {
        if ($show === 'name') {
            return 'Test Site';
        }
        return '';
    }

    /**
     * Mock parse_url function (PHP native, but we can override behavior if needed)
     */
    public static function parseUrl(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }
}

