<?php

declare(strict_types=1);

namespace Moox\Connect\Http;

use RuntimeException;

final class ResponseParser
{
    private array $errorStatusCodes;

    private array $successStatusCodes;

    public function __construct(
        array $errorStatusCodes = [],
        array $successStatusCodes = []
    ) {
        $this->errorStatusCodes = $errorStatusCodes;
        $this->successStatusCodes = $successStatusCodes ?: range(200, 299);
    }

    public function parse(string $body, int $statusCode, array $headers = []): ApiResponse
    {
        $this->validateStatusCode($statusCode);

        $response = new ApiResponse($statusCode, $headers, $body);

        if ($response->isJson()) {
            try {
                $response->json(); // Validate JSON early
            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    "Invalid JSON response (Status: {$statusCode}): ".$e->getMessage()
                );
            }
        }

        return $response;
    }

    public function parseJson(string $body, int $statusCode, array $headers = []): array
    {
        $response = $this->parse($body, $statusCode, $headers);

        if (! $response->isJson()) {
            throw new RuntimeException(
                'Response is not JSON (Content-Type: '.
                ($response->getContentType() ?? 'none').')'
            );
        }

        return $response->json();
    }

    public function isSuccessful(int $statusCode): bool
    {
        return in_array($statusCode, $this->successStatusCodes, true);
    }

    public function isError(int $statusCode): bool
    {
        return in_array($statusCode, $this->errorStatusCodes, true);
    }

    private function validateStatusCode(int $statusCode): void
    {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new RuntimeException("Invalid HTTP status code: {$statusCode}");
        }

        if ($this->isError($statusCode)) {
            throw new RuntimeException("Error status code received: {$statusCode}");
        }

        if (! empty($this->successStatusCodes) && ! $this->isSuccessful($statusCode)) {
            throw new RuntimeException(
                "Unexpected status code {$statusCode}, expected one of: ".
                implode(', ', $this->successStatusCodes)
            );
        }
    }
}
