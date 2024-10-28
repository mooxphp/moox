<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Builder\Features\Author;
use Moox\Builder\Builder\Features\Publish;
use Moox\Builder\Builder\Features\SoftDelete;
use Moox\Builder\Builder\Generators\MigrationGenerator;
use Moox\Builder\Builder\Generators\ModelGenerator;
use Moox\Builder\Builder\Generators\PluginGenerator;
use Moox\Builder\Builder\Generators\ResourceGenerator;

class BuildTestEntityCommand extends Command
{
    protected $signature = 'mooxbuilder:testrun';

    protected $description = 'Builds a test entity with all features for rapid development';

    public function handle(): int
    {
        $this->info('Building test entity...');

        // Common setup
        $entityName = 'TestEntity';
        $tableName = 'test_entities';
        $features = [
            new Author,
            new Publish,
            new SoftDelete,
        ];

        // Generate Migration
        $this->info('Generating migration...');
        $migrationGenerator = new MigrationGenerator($tableName);
        foreach ($features as $feature) {
            $migrationGenerator->addFeature($feature);
        }
        $migrationGenerator->addBaseField('$table->string("title")');
        $migrationGenerator->addBaseField('$table->string("slug")->unique()');
        $migrationGenerator->addBaseField('$table->text("content")');

        // Generate Model
        $this->info('Generating model...');
        $modelGenerator = new ModelGenerator(
            namespace: 'App\Models',
            className: $entityName,
            table: $tableName
        );
        foreach ($features as $feature) {
            $modelGenerator->addFeature($feature);
        }
        $modelGenerator->addFillable('title')
            ->addFillable('slug')
            ->addFillable('content');

        // Generate Resource
        $this->info('Generating resource...');
        $resourceGenerator = new ResourceGenerator(
            namespace: 'App\Filament\Resources',
            className: $entityName,
            model: "App\Models\\$entityName"
        );
        foreach ($features as $feature) {
            $resourceGenerator->addFeature($feature);
        }

        // Generate Plugin
        $this->info('Generating plugin...');
        $pluginGenerator = new PluginGenerator(
            namespace: 'App\Filament\Plugins',
            className: $entityName,
            id: 'test-entity'
        );
        foreach ($features as $feature) {
            $pluginGenerator->addFeature($feature);
        }
        $pluginGenerator->addResource("{$entityName}Resource");

        // Write files
        $this->writeFiles([
            database_path('migrations/'.date('Y_m_d_His')."_create_{$tableName}_table.php") => $migrationGenerator->generate(),
            app_path("Models/{$entityName}.php") => $modelGenerator->generate(),
            app_path("Filament/Resources/{$entityName}Resource.php") => $resourceGenerator->generate()['resource'],
            app_path("Filament/Resources/{$entityName}Resource/Pages/Create{$entityName}.php") => $resourceGenerator->generate()['pages']['create'],
            app_path("Filament/Resources/{$entityName}Resource/Pages/Edit{$entityName}.php") => $resourceGenerator->generate()['pages']['edit'],
            app_path("Filament/Resources/{$entityName}Resource/Pages/List{$entityName}s.php") => $resourceGenerator->generate()['pages']['list'],
            app_path("Filament/Resources/{$entityName}Resource/Pages/View{$entityName}.php") => $resourceGenerator->generate()['pages']['view'],
            app_path("Filament/Plugins/{$entityName}Plugin.php") => $pluginGenerator->generate(),
        ]);

        // Register Panel Provider
        $this->registerPanelProvider($entityName);

        $this->info('Test entity built successfully!');

        return self::SUCCESS;
    }

    protected function writeFiles(array $files): void
    {
        foreach ($files as $path => $content) {
            $directory = dirname($path);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($path, $content);
            $this->line("Created: $path");
        }
    }

    protected function registerPanelProvider(string $entityName): void
    {
        $providerPath = app_path('Providers/TestEntityPanelProvider.php');
        $content = <<<PHP
<?php

namespace App\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Plugins\\{$entityName}Plugin;

class TestEntityPanelProvider extends PanelProvider
{
    public function panel(Panel \$panel): Panel
    {
        return \$panel
            ->id('test-entity')
            ->path('test-entity')
            ->plugin(new {$entityName}Plugin())
            ->login();
    }
}
PHP;

        file_put_contents($providerPath, $content);
        $this->line("Created: $providerPath");

        // Add provider to config/app.php
        $this->addProviderToConfig();
    }

    protected function addProviderToConfig(): void
    {
        $configPath = config_path('app.php');
        $config = file_get_contents($configPath);

        if (! str_contains($config, 'TestEntityPanelProvider::class')) {
            $config = str_replace(
                'providers\' => [',
                'providers\' => ['.PHP_EOL.'        App\Providers\TestEntityPanelProvider::class,',
                $config
            );
            file_put_contents($configPath, $config);
            $this->line('Added provider to config/app.php');
        }
    }
}
