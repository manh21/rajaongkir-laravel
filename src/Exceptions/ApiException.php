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

    public static function fromResponse(array $response, ?\Throwable $previous = null): self
    {
        // Handle different API response formats
        if (isset($response['meta'])) {
            // Handle error response with "meta" structure
            $meta = $response['meta'];
            $message = $meta['message'] ?? 'An unknown error occurred';
            $code = $meta['code'] ?? 500;
        } else {
            // Fallback for unknown response structure
            $message = $response['message'] ?? $response['error'] ?? 'An unknown error occurred';
            $code = $response['code'] ?? $response['status_code'] ?? 500;
        }

        return new self($message, $code, $response, $previous);
    }

    /**
     * Check if the response indicates a failed API call
     *
     * @param array $response
     * @return bool
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
