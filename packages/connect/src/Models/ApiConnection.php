<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Moox\Connect\Support\ConnectionHealthChecker;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class ApiConnection extends Model
{
    use BaseInModel, SingleSimpleInModel, SoftDeletes;

    protected $table = 'api_connections';

    protected $fillable = [
        'name',
        'base_url',
        'health_path',
        'api_type',
        'auth_type',
        'login_method',
        'auth_credentials',
        'headers',
        'rate_limit',
        'lang_param',
        'default_locale',
        'status',
        'notify_on_failure',
        'options',
    ];

    protected $casts = [
        'auth_credentials' => 'encrypted:array',
        'headers' => 'array',
        'options' => 'array',
    ];

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options ?? [], $key, $default);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(ApiEndpoint::class);
    }

    public function client(): object
    {
        return new class($this)
        {
            public function __construct(
                private ApiConnection $connection
            ) {
            }

            /**
             * Execute an endpoint with dynamic route params and return decoded JSON.
             *
             * NOTE: `FetchImportRecordsJob` expects an `executeEndpoint()` method on the client.
             */
            public function executeEndpoint(ApiEndpoint $endpoint, array $parameters = []): array|string|null
            {
                return $this->executeEndpointWithMeta($endpoint, $parameters)['body'];
            }

            /**
             * @return array{status: int, headers: array<string, array<int, string>>, body: array|string|null}
             */
            public function executeEndpointWithMeta(ApiEndpoint $endpoint, array $parameters = []): array
            {
                $authHeaders = $this->buildAuthHeaders();
                $headers = array_merge($this->connection->headers ?? [], $authHeaders);

                $method = strtoupper((string) $endpoint->method);
                $path = (string) $endpoint->path;

                $routePlaceholders = [];
                if (preg_match_all('/\{(?<name>[^}]+)\}/', $path, $m) === 1) {
                    $routePlaceholders = $m['name'] ?? [];
                }

                $pathReplacementMap = [];
                foreach ($routePlaceholders as $name) {
                    $pathReplacementMap['{'.$name.'}'] = array_key_exists($name, $parameters)
                        ? rawurlencode((string) $parameters[$name])
                        : '';
                }

                if ($pathReplacementMap !== []) {
                    $path = strtr($path, $pathReplacementMap);
                }

                // Remaining parameters can be used as query/body inputs.
                foreach ($routePlaceholders as $name) {
                    unset($parameters[$name]);
                }

                $url = rtrim($this->connection->base_url, '/').'/'.ltrim($path, '/');
                $variables = $endpoint->variables ?? [];

                $request = Http::withHeaders($headers);
                if ($this->connection->timeout ?? null) {
                    $request = $request->timeout((int) $this->connection->timeout);
                }

                $response = $this->dispatchRequest($request, $method, $url, $variables, $parameters);

                // JWT token might be expired; do a single refresh+retry on auth errors.
                if (in_array($response->status(), [401, 403], true)) {
                    $refreshedHeaders = $this->refreshAuthHeadersForRetry();

                    if ($refreshedHeaders !== []) {
                        $retryHeaders = array_merge($this->connection->headers ?? [], $refreshedHeaders);
                        $retryRequest = Http::withHeaders($retryHeaders);

                        if ($this->connection->timeout ?? null) {
                            $retryRequest = $retryRequest->timeout((int) $this->connection->timeout);
                        }

                        $response = $this->dispatchRequest($retryRequest, $method, $url, $variables, $parameters);
                    }
                }

                return [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $this->normalizeResponse($response),
                ];
            }

            private function dispatchRequest(PendingRequest $request, string $method, string $url, array $variables, array $parameters)
            {
                return match ($method) {
                    'POST' => $request->post($url, array_merge($variables, $parameters)),
                    'PUT' => $request->put($url, array_merge($variables, $parameters)),
                    'PATCH' => $request->patch($url, array_merge($variables, $parameters)),
                    'DELETE' => $request->delete($url, array_merge($variables, $parameters)),
                    default => $request->get($url, array_merge($variables, $parameters)),
                };
            }

            private function buildAuthHeaders(): array
            {
                $checker = app(ConnectionHealthChecker::class);

                return (new \ReflectionClass($checker))
                    ->getMethod('buildAuthHeaders')
                    ->invoke($checker, $this->connection);
            }

            private function refreshAuthHeadersForRetry(): array
            {
                $authType = strtolower((string) ($this->connection->auth_type ?? ''));
                $creds = $this->connection->auth_credentials ?? [];

                if ($authType !== 'jwt') {
                    return [];
                }

                $loginMethod = (string) ($this->connection->login_method ?? '');
                if ($loginMethod === 'none' || $loginMethod === 'direct_token') {
                    return [];
                }

                $accessTokenKey = $creds['access_token_key'] ?? 'access_token';
                unset($creds[$accessTokenKey]);

                $this->connection->auth_credentials = $creds;
                $this->connection->saveQuietly();

                return $this->buildAuthHeaders();
            }

            private function normalizeResponse($response): array|string|null
            {
                $data = $response->json();
                if ($data !== null) {
                    return $data;
                }

                $body = $response->body();
                if ($body === '') {
                    return null;
                }

                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    return $decoded;
                }

                return $body;
            }
        };
    }
}
