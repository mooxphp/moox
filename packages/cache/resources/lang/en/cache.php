<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Cache',
        'title' => 'Cache Manager',
        'group' => 'System',
    ],
    'categories' => [
        'laravel' => 'Laravel',
        'stores' => 'Stores',
        'keys' => 'Keys',
        'page-cache' => 'Page Cache',
        'cloudflare' => 'Cloudflare',
    ],
    'actions' => [
        'clear' => 'Clear',
        'forget_key' => 'Forget key',
    ],
    'form' => [
        'cache_key' => 'Cache key',
        'page_cache_slug' => 'Slug or path',
        'page_cache_recursive' => 'Recursive',
    ],
    'confirm' => [
        'clear' => 'Clear :target?',
        'forget_key' => 'Forget cache key :key?',
    ],
    'messages' => [
        'cleared' => ':target cleared successfully.',
        'failed' => 'Failed to clear :target.',
        'key_required' => 'A cache key is required.',
        'key_forgotten' => 'Cache key :key forgotten.',
        'key_not_found' => 'Cache key :key was not found.',
        'store_flushed' => 'Cache store :store flushed.',
        'target_not_found' => 'Cache target not found.',
    ],
    'result' => [
        'heading' => 'Result: :target',
        'status' => 'Status',
        'duration' => 'Duration',
        'message' => 'Message',
        'success' => 'Success',
        'failure' => 'Failure',
    ],
    'targets' => [
        'application_cache' => [
            'label' => 'Application cache',
            'description' => 'Runs cache:clear',
        ],
        'config_cache' => [
            'label' => 'Config cache',
            'description' => 'Runs config:clear',
        ],
        'route_cache' => [
            'label' => 'Route cache',
            'description' => 'Runs route:clear',
        ],
        'view_cache' => [
            'label' => 'View cache',
            'description' => 'Runs view:clear',
        ],
        'event_cache' => [
            'label' => 'Event cache',
            'description' => 'Runs event:clear',
        ],
        'compiled' => [
            'label' => 'Compiled files',
            'description' => 'Runs clear-compiled',
        ],
        'optimize_clear' => [
            'label' => 'Optimize clear',
            'description' => 'Runs optimize:clear',
        ],
        'custom_key' => [
            'label' => 'Custom cache key',
            'description' => 'Forget a single cache key',
        ],
        'cache_store_flush' => [
            'label' => 'Flush cache store',
            'description' => 'Flush the selected cache store',
        ],
    ],
];
