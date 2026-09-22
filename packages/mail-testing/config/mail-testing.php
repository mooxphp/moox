<?php

declare(strict_types=1);

return [
    'default_count' => (int) env('MAIL_TESTING_DEFAULT_COUNT', 500),
    'min_count' => 1,
    'template_slug' => 'test',
    'layout_slug' => 'mail-testing',
    'disk' => env('MAIL_TESTING_DISK', 'local'),
    'timeout' => (int) env('MAIL_TESTING_JOB_TIMEOUT', 0),
    'progress_every' => 25,
    'queues' => [
        'connection' => env('MAIL_TESTING_QUEUE_CONNECTION'),
        'name' => env('MAIL_TESTING_QUEUE', 'mail-testing'),
    ],
];
