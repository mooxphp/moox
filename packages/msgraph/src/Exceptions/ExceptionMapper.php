<?php

declare(strict_types=1);

namespace Moox\Msgraph\Exceptions;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
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

        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $statusCode = $exception->getResponse()->getStatusCode();
            $body = (string) $exception->getResponse()->getBody();

            return match (true) {
                $statusCode === 401, $statusCode === 403 => new GraphAuthenticationException(
                    "Graph authentication failed ({$statusCode}): {$body}",
                    $statusCode,
                    $exception,
                ),
                $statusCode === 404 => new GraphNotFoundException(
                    "Graph resource not found: {$body}",
                    $statusCode,
                    $exception,
                ),
                $statusCode === 429 => new GraphRateLimitException(
                    'Graph API rate limit exceeded: '.$body,
                    $statusCode,
                    $exception,
                ),
                default => new GraphException(
                    "Graph API error ({$statusCode}): {$body}",
                    $statusCode,
                    $exception,
                ),
            };
        }

        return new GraphException(
            'Graph API error: '.$exception->getMessage(),
            (int) $exception->getCode(),
            $exception,
        );
    }
}
