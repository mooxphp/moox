<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | REST Countries API Key
    |--------------------------------------------------------------------------
    |
    | Every REST Countries v5 request requires an API key. Sign up for a free key
    | at https://restcountries.com/sign-up and set REST_COUNTRIES_API_KEY in
    | your .env file.
    |
    */

    'api_key' => env('REST_COUNTRIES_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */

    'base_url' => env('REST_COUNTRIES_BASE_URL', 'https://api.restcountries.com/countries/v5'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('REST_COUNTRIES_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Pagination Page Size
    |--------------------------------------------------------------------------
    |
    | Maximum records per request. Free plans allow up to 100; paid plans up to
    | 500. The import paginates until all countries are fetched.
    |
    */

    'page_limit' => (int) env('REST_COUNTRIES_PAGE_LIMIT', 100),
];
