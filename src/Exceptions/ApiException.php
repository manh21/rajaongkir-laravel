<?php

namespace Komodo\RajaOngkir\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    private ?array $response {
        get {
            return $this->response;
        }
    }

    private ?int $statusCode {
        get {
            return $this->statusCode;
        }
    }

    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $response = null,
        ?\Throwable $previous = null
    ) {
        $this->response = $response;
        $this->statusCode = $code;

        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse($response, int $statusCode = 500, ?\Throwable $previous = null): self
    {
        // Handle different response types
        if (is_string($response)) {
            // Handle string response (raw body)
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $response = $decoded;
            } else {
                return new self($response, $statusCode, ['raw' => $response], $previous);
            }
        }

        if (! is_array($response)) {
            return new self('Unknown API error', $statusCode, ['response' => $response], $previous);
        }

        // Handle different API response formats
        if (isset($response['meta'])) {
            // Handle error response with "meta" structure
            $meta = $response['meta'];
            $message = $meta['message'] ?? 'An unknown error occurred';
            $code = $meta['code'] ?? $statusCode;
        } else {
            // Fallback for unknown response structure
            $message = $response['message'] ?? $response['error'] ?? 'An unknown error occurred';
            $code = $response['code'] ?? $response['status_code'] ?? $statusCode;
        }

        return new self($message, $code, $response, $previous);
    }

    /**
     * Check if the response indicates a failed API call
     */
    public static function isErrorResponse(array $response): bool
    {
        // Check for error in meta structure
        if (isset($response['meta']['status']) && $response['meta']['status'] === 'failed') {
            return true;
        }

        return false;
    }
}
