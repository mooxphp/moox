<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as AppTestCase;

class TestCase extends AppTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var array<string, mixed> $packageConfig */
        $packageConfig = require dirname(__DIR__).'/config/mail-outbox.php';

        config([
            'mail-outbox' => array_replace_recursive(
                is_array(config('mail-outbox')) ? config('mail-outbox') : [],
                $packageConfig,
            ),
            'mail.default' => 'array',
            'mail.mailers.array' => [
                'transport' => 'array',
            ],
            'queue.default' => 'sync',
        ]);

        $this->runMailOutboxMigrations();
    }

    private function runMailOutboxMigrations(): void
    {
        if (Schema::hasTable('mail_send_logs')) {
            return;
        }

        $migration = include dirname(__DIR__).'/database/migrations/create_mail_send_logs_table.php.stub';
        $migration->up();
    }
}
