<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin\Http;

/**
 * HTTP Client Interface
 * Abstraction for HTTP requests to allow for different implementations
 */
interface HttpClientInterface
{
    /**
     * Send a GET request
     *
     * @param string $url The URL to request
     * @param array<string, string> $headers Optional headers
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws \SimpleLicense\Plugin\Exceptions\NetworkException
     */
    public function get(string $url, array $headers = []): array;

    /**
     * Send a POST request
     *
     * @param string $url The URL to request
     * @param array<string, mixed> $data The request body data
     * @param array<string, string> $headers Optional headers
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws \SimpleLicense\Plugin\Exceptions\NetworkException
     */
    public function post(string $url, array $data = [], array $headers = []): array;

    /**
     * Send a PUT request
     *
     * @param string $url The URL to request
     * @param array<string, mixed> $data The request body data
     * @param array<string, string> $headers Optional headers
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws \SimpleLicense\Plugin\Exceptions\NetworkException
     */
    public function put(string $url, array $data = [], array $headers = []): array;

    /**
     * Send a DELETE request
     *
     * @param string $url The URL to request
     * @param array<string, string> $headers Optional headers
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws \SimpleLicense\Plugin\Exceptions\NetworkException
     */
    public function delete(string $url, array $headers = []): array;
}

