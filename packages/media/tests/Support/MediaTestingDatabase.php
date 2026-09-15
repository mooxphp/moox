<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

if (! function_exists('configureMediaTestingDatabase')) {
    /**
     * Isolate media unit tests on an in-memory SQLite connection so they never
     * touch the application MySQL schema. Audit stays off: observers would
     * otherwise write to activity_log, which this DB does not provide.
     */
    function configureMediaTestingDatabase(): void
    {
        config([
            'database.default' => 'media_testing',
            'database.connections.media_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'audit.enabled' => false,
            'media.audit.enabled' => false,
        ]);

        DB::purge('media_testing');
        DB::setDefaultConnection('media_testing');
        DB::reconnect('media_testing');
    }
}
