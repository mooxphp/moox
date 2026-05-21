<?php

declare(strict_types=1);

return [
    'targets' => [
        'clear_all' => [
            'label' => 'Clear page cache',
            'description' => 'Runs page-cache:clear for all cached pages',
        ],
        'clear_slug' => [
            'label' => 'Clear page by slug',
            'description' => 'Runs page-cache:clear with slug and optional --recursive',
        ],
    ],
    'messages' => [
        'command_missing' => 'Command :command is not available. Install josephsilber/page-cache.',
        'cleared_all' => 'Page cache cleared.',
        'cleared_slug' => 'Page cache cleared for :slug.',
        'failed' => 'Page cache clear failed.',
        'slug_required' => 'A slug or path is required.',
    ],
];
