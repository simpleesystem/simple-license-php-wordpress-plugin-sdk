<?php

declare(strict_types=1);

/**
 * WordPress Functions Mock
 * Provides WordPress function implementations for testing
 * These functions are in the global namespace
 */

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::getOption($option, $default);
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value): bool
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::updateOption($option, $value);
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::deleteOption($option);
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $transient): mixed
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::getTransient($transient);
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $transient, mixed $value, int $expiration): bool
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::setTransient($transient, $value, $expiration);
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $transient): bool
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::deleteTransient($transient);
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::homeUrl($path);
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return \SimpleLicense\Plugin\Tests\Helpers\WordPressTestHelper::getBloginfo($show);
    }
}

