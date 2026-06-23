<?php

declare(strict_types=1);

namespace Moox\Data\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RestCountriesClient
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->request()->get($this->url($path), $query);
    }

    /**
     * @param  list<string>  $responseFields
     * @return list<array<string, mixed>>
     */
    public function listAllCountries(array $responseFields = []): array
    {
        $countries = [];
        $offset = 0;
        $limit = max(1, (int) config('rest-countries.page_limit', 100));

        do {
            $query = [
                'limit' => $limit,
                'offset' => $offset,
            ];

            if ($responseFields !== []) {
                $query['response_fields'] = implode(',', $responseFields);
            }

            $response = $this->get('', $query);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Failed to fetch countries from REST Countries API. Status: '.$response->status().'. Body: '.$response->body()
                );
            }

            $objects = $response->json('data.objects');

            if (! is_array($objects)) {
                throw new RuntimeException('Unexpected REST Countries API response shape.');
            }

            foreach ($objects as $country) {
                if (is_array($country)) {
                    $countries[] = $country;
                }
            }

            $more = (bool) $response->json('data.meta.more', false);
            $offset += $limit;
        } while ($more);

        return $countries;
    }

    protected function request(): PendingRequest
    {
        $apiKey = config('rest-countries.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException(
                'REST Countries API key is not configured. Set REST_COUNTRIES_API_KEY in your .env file.'
            );
        }

        return Http::timeout((int) config('rest-countries.timeout', 60))
            ->withToken($apiKey)
            ->acceptJson();
    }

    protected function url(string $path): string
    {
        $baseUrl = rtrim((string) config('rest-countries.base_url', 'https://api.restcountries.com/countries/v5'), '/');

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
