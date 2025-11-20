<?php

declare(strict_types=1);

namespace SimpleLicense\Plugin\Http;

use SimpleLicense\Plugin\Constants;
use SimpleLicense\Plugin\Exceptions\NetworkException;

/**
 * WordPress HTTP Client Implementation
 * Uses WordPress wp_remote_* functions for HTTP requests
 */
class WordPressHttpClient implements HttpClientInterface
{
    private string $baseUrl;
    private int $timeout;

    public function __construct(
        string $baseUrl,
        int $timeout = Constants::DEFAULT_TIMEOUT_SECONDS
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, ['headers' => $headers]);
    }

    public function post(string $url, array $data = [], array $headers = []): array
    {
        $options = [
            'headers' => array_merge(
                [Constants::HEADER_CONTENT_TYPE => Constants::CONTENT_TYPE_JSON],
                $headers
            ),
            'body' => wp_json_encode($data),
        ];

        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $data = [], array $headers = []): array
    {
        $options = [
            'headers' => array_merge(
                [Constants::HEADER_CONTENT_TYPE => Constants::CONTENT_TYPE_JSON],
                $headers
            ),
            'body' => wp_json_encode($data),
            'method' => 'PUT',
        ];

        return $this->request('PUT', $url, $options);
    }

    public function delete(string $url, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
            'method' => 'DELETE',
        ];

        return $this->request('DELETE', $url, $options);
    }

    /**
     * Execute HTTP request using WordPress functions
     *
     * @param string $method HTTP method
     * @param string $url URL
     * @param array<string, mixed> $options Request options
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws NetworkException
     */
    private function request(string $method, string $url, array $options = []): array
    {
        $fullUrl = $this->baseUrl . $url;
        $defaultOptions = [
            'timeout' => $this->timeout,
            'sslverify' => true,
        ];

        $requestOptions = array_merge($defaultOptions, $options);

        $response = wp_remote_request($fullUrl, $requestOptions);

        if (is_wp_error($response)) {
            throw new NetworkException(
                sprintf('Network error: %s', $response->get_error_message()),
                Constants::ERROR_CODE_VALIDATION_ERROR,
                null,
                0,
                $response->get_error_data()
            );
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);

        $headersArray = [];
        if ($headers instanceof \WP_HTTP_Requests_Response) {
            foreach ($headers->getAll() as $name => $values) {
                $headersArray[$name] = is_array($values) ? implode(', ', $values) : (string) $values;
            }
        } else {
            foreach ($headers as $name => $value) {
                $headersArray[$name] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return [
            'status' => (int) $statusCode,
            'body' => (string) $body,
            'headers' => $headersArray,
        ];
    }
}

