<?php

declare(strict_types=1);

namespace Moox\MsGraph\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Kiota\Abstractions\ApiException;
use Throwable;

/**
 * Maps Graph API HTTP errors and Guzzle transport failures to typed exceptions.
 */
final class ExceptionMapper
{
    public static function map(Throwable $exception): GraphException
    {
        if ($exception instanceof ConnectException) {
            return new GraphConnectionException(
                'Graph API connection failed: '.$exception->getMessage(),
                0,
                $exception,
            );
        }

        if ($exception instanceof ApiException) {
            $statusCode = $exception->getResponseStatusCode() ?? $exception->getCode();
            $odataCode = self::odataCodeFromApiException($exception);
            $retryAfter = self::retryAfterFromHeaders($exception->getResponseHeaders());

            return self::mapStatus(
                statusCode: (int) $statusCode,
                odataCode: $odataCode,
                body: $exception->getMessage(),
                previous: $exception,
                retryAfterSeconds: $retryAfter,
            );
        }

        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $odataCode = self::odataCodeFromBody($body);
            $retryAfter = self::retryAfterFromHeaderLine($response->getHeaderLine('Retry-After'));

            return self::mapStatus(
                statusCode: $statusCode,
                odataCode: $odataCode,
                body: $body,
                previous: $exception,
                retryAfterSeconds: $retryAfter,
            );
        }

        return new GraphException(
            'Graph API error: '.$exception->getMessage(),
            (int) $exception->getCode(),
            $exception,
        );
    }

    private static function mapStatus(
        int $statusCode,
        ?string $odataCode,
        string $body,
        Throwable $previous,
        ?int $retryAfterSeconds,
    ): GraphException {
        return match (true) {
            $statusCode === 401, $statusCode === 403 => new GraphAuthenticationException(
                "Graph authentication failed ({$statusCode}): {$body}",
                $statusCode,
                $previous,
            ),
            $odataCode !== null
                && strcasecmp($odataCode, 'syncStateNotFound') === 0
                && ($statusCode === 410 || $statusCode === 400) => new GraphSyncStateNotFoundException(
                    'Graph delta sync state expired or invalid: '.$body,
                    $statusCode,
                    $previous,
                ),
            $statusCode === 404 && $odataCode === 'ErrorItemNotFound' => new GraphItemNotFoundException(
                "Graph item not found: {$body}",
                $statusCode,
                $previous,
            ),
            $statusCode === 404 => new GraphMailboxNotFoundException(
                "Graph mailbox not found: {$body}",
                $statusCode,
                $previous,
            ),
            $statusCode === 429 => new GraphRateLimitException(
                'Graph API rate limit exceeded: '.$body,
                $statusCode,
                $previous,
                $retryAfterSeconds,
            ),
            default => new GraphException(
                "Graph API error ({$statusCode}): {$body}",
                $statusCode,
                $previous,
            ),
        };
    }

    private static function odataCodeFromApiException(ApiException $exception): ?string
    {
        if ($exception instanceof ODataError) {
            $code = $exception->getError()?->getCode();
            if ($code !== null && $code !== '') {
                return $code;
            }
        }

        return self::odataCodeFromBody($exception->getMessage());
    }

    private static function odataCodeFromBody(string $body): ?string
    {
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            return null;
        }

        $code = $decoded['error']['code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     */
    private static function retryAfterFromHeaders(array $headers): ?int
    {
        foreach ($headers as $name => $values) {
            if (strcasecmp((string) $name, 'Retry-After') === 0 && isset($values[0])) {
                return self::retryAfterFromHeaderLine((string) $values[0]);
            }
        }

        return null;
    }

    private static function retryAfterFromHeaderLine(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }
}
