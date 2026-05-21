<?php

declare(strict_types=1);

return [
    'enabled' => env('CLOUDFLARE_CACHE_ENABLED', false),

    'api_token' => env('CLOUDFLARE_API_TOKEN'),

    'zone_id' => env('CLOUDFLARE_ZONE_ID'),

    'allowed_domains' => array_filter(array_map(
        trim(...),
        explode(',', (string) env('CLOUDFLARE_ALLOWED_DOMAINS', '')),
    )),

    'base_url' => 'https://api.cloudflare.com/client/v4',
];
