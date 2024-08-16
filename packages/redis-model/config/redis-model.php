<?php

use Illuminate\Support\Str;

return [
    'navigation_sort' => 2001,

    'redis_model_options' => [
        'database_default' => 'redis_model_default',
        'prefix' => env('REDIS_MODEL_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_redis_model_'),
    ],

    'commands' => [
        'generate_path' => app_path('RedisModels'),
        'rootNamespace' => 'App\\RedisModels',
    ],

    'database' => [
        'redis_model_default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],
];
