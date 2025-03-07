<?php

declare(strict_types=1);

namespace Moox\Connect\Health;

use Illuminate\Support\Facades\Log;
use Moox\Connect\Connect\ApiRequest;
use Moox\Connect\Connect\ApiResponse;
use RuntimeException;
use Throwable;

final class ApiErrorLogger
{
    private string $channel;

    private array $context;

    public function __construct(
        string $channel = 'api',
        array $context = []
    ) {
        $this->channel = $channel;
        $this->context = $context;
    }

    public function logRequestError(
        ApiRequest $request,
        ?ApiResponse $response,
        Throwable $error
    ): void {
        $errorContext = [
            'url' => $request->getFullUrl(),
            'method' => $request->getMethod(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
            'error' => [
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'type' => get_class($error),
            ],
            'context' => $this->context,
        ];

        if ($response !== null) {
            $errorContext['response'] = [
                'status' => $response->getStatusCode(),
                'headers' => $this->sanitizeHeaders($response->getHeaders()),
            ];

            if ($response->isJson()) {
                try {
                    $errorContext['response']['body'] = $response->json();
                } catch (RuntimeException) {
                    $errorContext['response']['body'] = 'Invalid JSON response';
                }
            }
        }

        Log::channel($this->channel)->error(
            'API request failed: '.$error->getMessage(),
            $errorContext
        );
    }

    public function logRateLimit(ApiRequest $request): void
    {
        Log::channel($this->channel)->warning(
            'API rate limit reached',
            [
                'url' => $request->getFullUrl(),
                'method' => $request->getMethod(),
                'context' => $this->context,
            ]
        );
    }

    public function logAuthenticationFailure(string $message, array $details = []): void
    {
        Log::channel($this->channel)->error(
            'API authentication failed: '.$message,
            array_merge($details, ['context' => $this->context])
        );
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'api-key',
        ];

        return array_map(
            function ($name, $value) use ($sensitiveHeaders) {
                if (in_array(strtolower($name), $sensitiveHeaders, true)) {
                    return '******';
                }

                return $value;
            },
            array_keys($headers),
            $headers
        );
    }

    public function withContext(array $context): self
    {
        $clone = clone $this;
        $clone->context = array_merge($this->context, $context);

        return $clone;
    }

    public function withChannel(string $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;

        return $clone;
    }
}
