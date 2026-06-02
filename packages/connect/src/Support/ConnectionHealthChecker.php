<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Illuminate\Support\Facades\Http;
use Moox\Connect\Models\ApiConnection;

class ConnectionHealthChecker
{
    public function check(ApiConnection $connection): void
    {
        // If the health endpoint isn't configured, use the conventional default.
        // (The Filament form placeholder indicates `/health`.)
        $healthPath = $connection->health_path ?: '/health';
        $url = rtrim($connection->base_url, '/').'/'.ltrim($healthPath, '/');

        $headers = array_merge(
            $connection->headers ?? [],
            $this->buildAuthHeaders($connection)
        );

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $connection->status = 'Active';
                $connection->saveQuietly();

                $connection->logs()->create([
                    'trigger' => 'SYSTEM',
                    'status_code' => (string) $response->status(),
                    'request_data' => [
                        'type' => 'health_check',
                        'url' => $url,
                        'headers' => $headers,
                    ],
                    'response_data' => [
                        'body' => $response->json() ?? $response->body(),
                    ],
                    'error_message' => null,
                ]);

                return;
            }

            $this->markError($connection, $url, $response->status(), $response->body());
        } catch (\Throwable $e) {
            $this->markError($connection, $url, null, $e->getMessage());
        }
    }

    protected function buildAuthHeaders(ApiConnection $connection): array
    {
        $creds = $connection->auth_credentials ?? [];
        $type = strtolower((string) $connection->auth_type);

        if ($type === 'bearer') {
            // Bearer-Token-Key optional pro Connection überschreibbar, Default: "token"
            $tokenKey = $creds['token_key'] ?? 'token';

            return isset($creds[$tokenKey])
                ? ['Authorization' => 'Bearer '.$creds[$tokenKey]]
                : [];
        }

        if ($type === 'basic') {
            // Für Basic-Auth wird alles aus der Connection gelesen, Fallback: username/password
            $usernameKey = $creds['basic_username_key'] ?? 'username';
            $passwordKey = $creds['basic_password_key'] ?? 'password';

            if (! isset($creds[$usernameKey], $creds[$passwordKey])) {
                return [];
            }

            return [
                'Authorization' => 'Basic '.base64_encode(
                    $creds[$usernameKey].':'.$creds[$passwordKey]
                ),
            ];
        }

        if ($type === 'jwt') {
            // JWT-Access-Token-Key pro Connection oder Fallback: "access_token"
            $accessTokenKey = $creds['access_token_key'] ?? 'access_token';

            $loginMethod = $connection->login_method ?? 'none';
            // Backwards-Compatibility: wenn kein login_method gesetzt ist, vom Setup her ableiten
            if (! $loginMethod || $loginMethod === '') {
                $graphqlQuery = $creds['graphql_query'] ?? null;
                $loginPath = $creds['login_path'] ?? null;
                if (($graphqlQuery !== null && $graphqlQuery !== '') && $loginPath !== null) {
                    $loginMethod = 'graphql_login';
                } elseif ($loginPath !== null) {
                    $loginMethod = 'rest_login';
                } elseif (isset($creds[$accessTokenKey])) {
                    $loginMethod = 'direct_token';
                } else {
                    $loginMethod = 'none';
                }
            }

            // Kein Login konfiguriert, aber Token vorhanden → direkt verwenden
            if (isset($creds[$accessTokenKey])) {
                return ['Authorization' => 'Bearer '.$creds[$accessTokenKey]];
            }

            // Wenn explizit "kein Login" gewählt wurde und kein Token vorhanden ist → keine Auth
            if ($loginMethod === 'none' || $loginMethod === 'direct_token') {
                return [];
            }

            $loginPath = $creds['login_path'] ?? null;
            if ($loginPath === null) {
                return [];
            }

            // Alle Login-Feldnamen kommen aus der Connection, Fallback: username/password
            $usernameKey = $creds['basic_username_key'] ?? 'username';
            $passwordKey = $creds['basic_password_key'] ?? 'password';

            // Für das Payload werden – sofern nicht anders benötigt – dieselben Keys verwendet
            $payloadUsernameKey = $creds['basic_username_key'] ?? $usernameKey;
            $payloadPasswordKey = $creds['basic_password_key'] ?? $passwordKey;

            $loginUrl = rtrim($connection->base_url, '/').'/'.ltrim($loginPath, '/');

            // REST-Login-Payload oder GraphQL-Login-Payload aufbauen – je nach login_method
            $payload = [];
            $graphqlQuery = $creds['graphql_query'] ?? null;

            if ($loginMethod === 'graphql_login') {
                if ($graphqlQuery === null || $graphqlQuery === '') {
                    return [];
                }

                $variables = [];
                if (isset($creds[$usernameKey])) {
                    $variables[$payloadUsernameKey] = $creds[$usernameKey];
                }
                if (isset($creds[$passwordKey])) {
                    $variables[$payloadPasswordKey] = $creds[$passwordKey];
                }

                $payload = [
                    'query' => $graphqlQuery,
                ];

                if ($variables !== []) {
                    $payload['variables'] = $variables;
                }
            } elseif ($loginMethod === 'rest_login') {
                if (isset($creds[$usernameKey])) {
                    $payload[$payloadUsernameKey] = $creds[$usernameKey];
                }
                if (isset($creds[$passwordKey])) {
                    $payload[$payloadPasswordKey] = $creds[$passwordKey];
                }

                if ($payload === []) {
                    return [];
                }
            }

            $response = Http::post($loginUrl, $payload);
            if (! $response->successful()) {
                $connection->logs()->create([
                    'trigger' => 'SYSTEM',
                    'status_code' => (string) $response->status(),
                    'request_data' => [
                        'type' => 'jwt_login',
                        'url' => $loginUrl,
                        'payload_keys' => array_keys($payload),
                    ],
                    'response_data' => [
                        'body' => $response->json() ?? $response->body(),
                    ],
                    'error_message' => 'JWT login failed',
                ]);

                return [];
            }

            $data = $response->json();
            $tokenPath = $creds['token_path'] ?? $accessTokenKey;
            $token = data_get($data, $tokenPath);

            if (! is_string($token) || $token === '') {
                // Fallback: Versuche, irgendwo im Response einen JWT-ähnlichen Token zu finden
                $token = $this->findJwtTokenInArray($data);
            }

            if (! is_string($token) || $token === '') {
                $connection->logs()->create([
                    'trigger' => 'SYSTEM',
                    'status_code' => (string) $response->status(),
                    'request_data' => [
                        'type' => 'jwt_login',
                        'url' => $loginUrl,
                        'payload_keys' => array_keys($payload),
                        'token_path' => $tokenPath,
                    ],
                    'response_data' => $data,
                    'error_message' => 'JWT token not found in login response',
                ]);

                return [];
            }

            $creds[$accessTokenKey] = $token;
            $connection->auth_credentials = $creds;
            $connection->saveQuietly();
            $connection->logs()->create([
                'trigger' => 'SYSTEM',
                'status_code' => (string) $response->status(),
                'request_data' => [
                    'type' => 'jwt_login',
                    'url' => $loginUrl,
                    'payload_keys' => array_keys($payload),
                    'token_path' => $tokenPath,
                ],
                'response_data' => $data,
                'error_message' => null,
            ]);

            return ['Authorization' => 'Bearer '.$token];
        }

        return [];
    }

    /**
     * Durchsucht ein verschachteltes Array nach einem JWT-ähnlichen Token (xxx.yyy.zzz).
     */
    protected function findJwtTokenInArray(mixed $data): ?string
    {
        if (is_string($data)) {
            // sehr einfache JWT-Erkennung: drei durch Punkte getrennte Base64-ähnliche Segmente
            if (preg_match('/^[A-Za-z0-9\-\_=]+\.[A-Za-z0-9\-\_=]+\.[A-Za-z0-9\-\_=]+$/', $data) === 1) {
                return $data;
            }

            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        foreach ($data as $value) {
            $found = $this->findJwtTokenInArray($value);
            if (is_string($found) && $found !== '') {
                return $found;
            }
        }

        return null;
    }

    protected function markError(
        ApiConnection $connection,
        string $url,
        ?int $status,
        ?string $message
    ): void {
        $connection->status = 'Error';
        $connection->saveQuietly();

        $statusCode = $status !== null ? (string) $status : '0';

        $shortMessage = $message;
        if ($shortMessage !== null && mb_strlen($shortMessage) > 240) {
            $shortMessage = mb_substr($shortMessage, 0, 240).'…';
        }

        $connection->logs()->create([
            'trigger' => 'SYSTEM',
            'status_code' => $statusCode,
            'request_data' => [
                'type' => 'health_check',
                'url' => $url,
            ],
            'response_data' => $message !== null ? ['error_raw' => $message] : [],
            'error_message' => $shortMessage,
        ]);
    }
}
