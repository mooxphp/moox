<?php

declare(strict_types=1);

return [
    'targets' => [
        'purge_all' => [
            'label' => 'Purge everything',
            'description' => 'Purge the entire Cloudflare zone cache',
        ],
        'purge_files' => [
            'label' => 'Purge by URL',
            'description' => 'Purge specific files or URLs',
        ],
        'purge_tags' => [
            'label' => 'Purge by tag',
            'description' => 'Purge cache by Cache-Tag',
        ],
        'purge_hosts' => [
            'label' => 'Purge by host',
            'description' => 'Purge cache for specific hostnames',
        ],
    ],
    'messages' => [
        'not_configured' => 'Cloudflare API token and zone ID are required.',
        'purge_success' => 'Cloudflare cache purged successfully.',
        'purge_failed' => 'Cloudflare cache purge failed.',
        'invalid_domains' => 'These URLs are not on an allowed domain: :urls',
        'urls_required' => 'At least one URL is required.',
        'tags_required' => 'At least one cache tag is required.',
        'hosts_required' => 'At least one host is required.',
    ],
];
