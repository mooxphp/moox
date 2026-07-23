<?php

declare(strict_types=1);

namespace Moox\Builder\Tests;

use Astrotomic\Translatable\TranslatableServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Moox\Builder\BuilderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        $this->applyTranslatableConfig(config());
    }

    protected function getPackageProviders($app): array
    {
        return [
            TranslatableServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            BuilderServiceProvider::class,
        ];
    }

    protected function applyTranslatableConfig(Repository $config): void
    {
        $config->set('app.locale', 'en_US');
        $config->set('builder.default_locale', 'en_US');
        $config->set('translatable.locales', ['en_US', 'de_CH']);
        $config->set('translatable.use_fallback', true);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $this->applyTranslatableConfig($app['config']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations();
    }

    protected function loadPackageMigrations(): void
    {
        foreach ([
            'create_builder_field_groups_table',
            'create_builder_fields_table',
            'create_builder_field_options_table',
            'create_builder_field_values_table',
            'create_builder_field_group_translations_table',
            'create_builder_field_translations_table',
            'create_builder_field_option_translations_table',
        ] as $migration) {
            $path = dirname(__DIR__)."/database/migrations/{$migration}.php.stub";

            if (is_file($path)) {
                $instance = include $path;
                $instance->up();
            }
        }
    }

    protected function createItemsTable(): void
    {
        Schema::dropIfExists('items');

        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }
}
