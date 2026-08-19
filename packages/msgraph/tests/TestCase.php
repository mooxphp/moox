<?php

namespace Moox\Msgraph\Tests;

use Moox\Msgraph\MsgraphServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MsgraphServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('msgraph.connections', [
            'default' => [
                'tenant_id' => 'test-tenant',
                'client_id' => 'test-client',
                'client_secret' => 'test-secret',
            ],
            'secondary' => [
                'tenant_id' => 'tenant-2',
                'client_id' => 'client-2',
                'client_secret' => 'secret-2',
            ],
        ]);
        config()->set('msgraph.default', 'default');
    }
}
