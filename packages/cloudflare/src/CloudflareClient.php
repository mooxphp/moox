<?php

declare(strict_types=1);

namespace Moox\Cloudflare;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

class CloudflareClient
{
    public function __construct(
        protected ?Client $httpClient = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return filled(config('cloudflare.api_token'))
            && filled(config('cloudflare.zone_id'));
    }

    /**
     * @return array{success: bool, message: string, result: mixed}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => __('moox-cloudflare::cloudflare.messages.not_configured'),
                'result' => null,
            ];
        }

        return $this->request('GET', 'zones/'.config('cloudflare.zone_id'));
    }

    /**
     * @return array{success: bool, message: string, result: mixed}
     */
    public function purgeEverything(): array
    {
        return $this->request('POST', 'zones/'.config('cloudflare.zone_id').'/purge_cache', [
            'purge_everything' => true,
        ]);
    }

    /**
     * @param  list<string>  $files
     * @return array{success: bool, message: string, result: mixed}
     */
    public function purgeFiles(array $files): array
    {
        $validated = $this->validateUrls($files);

        if ($validated['invalid'] !== []) {
            return [
                'success' => false,
                'message' => __('moox-cloudflare::cloudflare.messages.invalid_domains', [
                    'urls' => implode(', ', $validated['invalid']),
                ]),
                'result' => null,
            ];
        }

        return $this->request('POST', 'zones/'.config('cloudflare.zone_id').'/purge_cache', [
            'files' => $validated['valid'],
        ]);
    }

    /**
     * @param  list<string>  $tags
     * @return array{success: bool, message: string, result: mixed}
     */
    public function purgeTags(array $tags): array
    {
        return $this->request('POST', 'zones/'.config('cloudflare.zone_id').'/purge_cache', [
            'tags' => array_values($tags),
        ]);
    }

    /**
     * @param  list<string>  $hosts
     * @return array{success: bool, message: string, result: mixed}
     */
    public function purgeHosts(array $hosts): array
    {
        return $this->request('POST', 'zones/'.config('cloudflare.zone_id').'/purge_cache', [
            'hosts' => array_values($hosts),
        ]);
    }

    /**
     * @param  list<string>  $urls
     * @return array{valid: list<string>, invalid: list<string>}
     */
    public function validateUrls(array $urls): array
    {
        $allowedDomains = config('cloudflare.allowed_domains', []);

        if ($allowedDomains === []) {
            return [
                'valid' => array_values($urls),
                'invalid' => [],
            ];
        }

        $valid = [];
        $invalid = [];

        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);

            if ($host === null || ! $this->hostIsAllowed((string) $host, $allowedDomains)) {
                $invalid[] = $url;

                continue;
            }

            $valid[] = $url;
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{success: bool, message: string, result: mixed}
     */
    protected function request(string $method, string $uri, array $body = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => __('moox-cloudflare::cloudflare.messages.not_configured'),
                'result' => null,
            ];
        }

        try {
            $options = [];

            if ($body !== []) {
                $options['json'] = $body;
            }

            $response = $this->client()->request($method, $uri, $options);

            $payload = json_decode((string) $response->getBody(), true);
            $success = (bool) Arr::get($payload, 'success', false);

            return [
                'success' => $success,
                'message' => $success
                    ? __('moox-cloudflare::cloudflare.messages.purge_success')
                    : (string) (Arr::get($payload, 'errors.0.message') ?? __('moox-cloudflare::cloudflare.messages.purge_failed')),
                'result' => $payload,
            ];
        } catch (GuzzleException $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'result' => null,
            ];
        }
    }

    protected function client(): Client
    {
        if ($this->httpClient instanceof Client) {
            return $this->httpClient;
        }

        $this->httpClient = new Client([
            'base_uri' => rtrim((string) config('cloudflare.base_url'), '/').'/',
            'headers' => [
                'Authorization' => 'Bearer '.config('cloudflare.api_token'),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        return $this->httpClient;
    }

    /**
     * @param  list<string>  $allowedDomains
     */
    protected function hostIsAllowed(string $host, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
