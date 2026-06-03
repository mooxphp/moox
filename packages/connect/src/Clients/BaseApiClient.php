<?php

declare(strict_types=1);

namespace Moox\Connect\Clients;

use Moox\Connect\Auth\MultiAuthStrategy;
use Moox\Connect\Contracts\ApiClientInterface;
use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Contracts\ApiResponseInterface;
use Moox\Connect\Contracts\AuthenticationStrategyInterface;
use Moox\Connect\Exceptions\ApiException;
use Moox\Connect\Http\ApiRequest;
use Moox\Connect\Http\ApiResponse;
use Moox\Connect\Http\ResponseParser;
use Moox\Connect\Models\ApiConnection;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiLog;
use Moox\Connect\Support\RateLimiter;
use Moox\Connect\Support\RateLimiterFactory;
use Moox\Connect\Support\RetryHandler;
use RuntimeException;

abstract class BaseApiClient implements ApiClientInterface
{
    protected ?string $baseUrl = null;

    protected AuthenticationStrategyInterface $auth;

    protected ResponseParser $responseParser;

    protected RateLimiter $rateLimiter;

    protected RetryHandler $retryHandler;

    protected array $defaultHeaders = [];

    protected ApiConnection $api;

    protected ?ApiRequestInterface $request = null;

    protected ?ApiEndpoint $endpoint = null;

    public function __construct(
        private RateLimiterFactory $rateLimiterFactory,
        AuthenticationStrategyInterface $auth,
        ApiConnection $api,
        ?string $baseUrl = null,
        ?ResponseParser $responseParser = null,
        ?RateLimiter $rateLimiter = null,
        ?RetryHandler $retryHandler = null
    ) {
        $this->auth = $auth;
        $this->api = $api;
        $this->baseUrl = $baseUrl;
        $this->responseParser = $responseParser ?? new ResponseParser;
        $this->rateLimiter = $rateLimiter ?? new RateLimiter;
        $this->retryHandler = $retryHandler ?? new RetryHandler;
    }

    abstract protected function executeRequest(ApiRequestInterface $request): ApiResponseInterface;

    public function sendRequest(ApiRequestInterface $request): ApiResponseInterface
    {
        $this->request = $request;

        if (! $this->auth->isAuthenticated()) {
            $this->authenticate();
        }

        $request = $this->prepareRequest($request);

        $endpointPath = $request->getEndpoint();
        $this->endpoint = ApiEndpoint::where('path', $endpointPath)->firstOrFail();

        // Get rate limiter for this endpoint
        $rateLimiter = $this->rateLimiterFactory->forEndpoint(
            $this->endpoint->id,
            $this->endpoint->rate_limit,
            $this->endpoint->rate_window
        );

        // Check rate limit
        $result = $rateLimiter->attempt();

        if (! $result->isAllowed()) {
            $response = new ApiResponse(
                data: ['error' => 'Rate limit exceeded'],
                statusCode: 429,
                headers: []
            );

            return $response->withRateLimitHeaders(
                limit: $result->getLimit(),
                remaining: $result->getRemaining(),
                reset: $result->getReset()
            );
        }

        // Execute request
        $response = $this->retryHandler->execute(function () use ($request) {
            $this->rateLimiter->throttle();

            try {
                $response = $this->executeRequest($request);

                // Log the interaction
                ApiLog::create([
                    'api_connection_id' => $this->api->id,
                    'endpoint_id' => $this->endpoint->id,
                    'trigger' => $this->context ?? 'SYSTEM',
                    'request_data' => $request->toArray(),
                    'response_data' => $response->json(),
                    'status_code' => $response->getStatusCode(),
                    'error_message' => ! $response->isSuccessful()
                        ? ($response->json()['error'] ?? null)
                        : null,
                ]);

                if (! $response->isSuccessful()) {
                    throw ApiException::fromResponse(
                        $response,
                        $request->getEndpoint(),
                        [
                            'api_connection_id' => $this->api->id,
                            'trigger' => $this->context ?? 'SYSTEM',
                            'request' => $request->toArray(),
                        ]
                    );
                }

                return $response;
            } catch (RuntimeException $e) {
                if ($this->shouldRefreshToken($e) && $this->auth instanceof MultiAuthStrategy) {
                    $this->refreshToken();
                    $request = $this->auth->applyToRequest($request);

                    return $this->executeRequest($request);
                }

                throw new ApiException(
                    $e->getMessage(),
                    500,
                    $request->getEndpoint(),
                    null,
                    ['original_exception' => get_class($e)]
                );
            }
        });

        // Add rate limit headers to response
        return $response->withRateLimitHeaders(
            limit: $result->getLimit(),
            remaining: $result->getRemaining(),
            reset: $result->getReset()
        );
    }

    public function authenticate(): void
    {
        $this->auth->authenticate();
    }

    public function refreshToken(): void
    {
        $this->auth->refreshCredentials();
    }

    protected function prepareRequest(ApiRequestInterface $request): ApiRequestInterface
    {
        $request = $this->auth->applyToRequest($request);

        if ($this->baseUrl !== null) {
            return new ApiRequest(
                $request->getMethod(),
                $request->getEndpoint(),
                array_merge($this->defaultHeaders, $request->getHeaders()),
                $request->getQueryParams(),
                $request->getBody(),
                $this->baseUrl
            );
        }

        return $request;
    }

    protected function shouldRefreshToken(RuntimeException $e): bool
    {
        return $e->getMessage() === 'Token expired' ||
               str_contains(strtolower($e->getMessage()), 'unauthorized');
    }

    public function withDefaultHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->defaultHeaders[$name] = $value;

        return $clone;
    }

    public function withDefaultHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->defaultHeaders = array_merge($clone->defaultHeaders, $headers);

        return $clone;
    }

    protected function getEndpoint(): ApiEndpoint
    {
        return ApiEndpoint::where('path', $this->request->getEndpoint())->firstOrFail();
    }
}
