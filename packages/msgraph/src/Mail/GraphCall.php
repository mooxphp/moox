<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail;

use Closure;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\ApiException;
use Moox\MsGraph\Exceptions\ExceptionMapper;
use Moox\MsGraph\Exceptions\GraphConnectionException;
use Moox\MsGraph\Exceptions\GraphException;
use Moox\MsGraph\Exceptions\GraphRateLimitException;
use Throwable;

/**
 * Runs Graph SDK calls with typed error mapping and Retry-After-aware 429 retries.
 */
final class GraphCall
{
    /**
     * @param  Closure(int): void  $sleeper
     */
    public function __construct(
        private Closure $sleeper,
        private int $maxRetries = 3,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $call
     * @return T
     */
    public function run(callable $call, string $operation): mixed
    {
        Log::debug('[Msgraph] Graph API: '.$operation);

        $attempts = 0;

        while (true) {
            try {
                return $call();
            } catch (InvalidArgumentException $e) {
                throw $e;
            } catch (GraphException $e) {
                throw $e;
            } catch (Throwable $e) {
                $mapped = $this->mapOrConnection($e);

                if ($mapped instanceof GraphRateLimitException && $attempts < $this->maxRetries) {
                    $attempts++;
                    ($this->sleeper)($this->delaySeconds($mapped, $attempts));

                    continue;
                }

                Log::error('[Msgraph] Graph API error: '.$operation, [
                    'exception' => $mapped,
                    'status' => $mapped->getCode(),
                ]);

                throw $mapped;
            }
        }
    }

    private function delaySeconds(GraphRateLimitException $exception, int $attempt): int
    {
        if ($exception->retryAfterSeconds !== null) {
            return $exception->retryAfterSeconds;
        }

        return min(2 ** ($attempt - 1), 8);
    }

    private function mapOrConnection(Throwable $exception): GraphException
    {
        $mapped = ExceptionMapper::map($exception);

        if ($exception instanceof ApiException
            || $exception instanceof RequestException
            || $exception instanceof ConnectException) {
            return $mapped;
        }

        return new GraphConnectionException(
            'Graph API connection failed: '.$exception->getMessage(),
            0,
            $exception,
        );
    }
}
