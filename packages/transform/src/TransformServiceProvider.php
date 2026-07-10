<?php

declare(strict_types=1);

namespace Moox\Transform;

use Moox\Core\MooxServiceProvider;
use Moox\Transform\Filament\Providers\TransformPanelProvider;
use Moox\Transform\Support\ConfiguredImportRecordPayloadReader;
use Moox\Transform\Support\ConfiguredLocaleVariantResolver;
use Moox\Transform\Support\Execution\BatchDestinationWriterRegistry;
use Moox\Transform\Support\Execution\BulkTransformExecutor;
use Moox\Transform\Support\Execution\EloquentUpsertBatchDestinationWriter;
use Moox\Transform\Support\Execution\ResolvedTransformDataFactory;
use Moox\Transform\Support\Execution\TranslatableBatchDestinationWriter;
use Moox\Transform\Support\Expansion\ExpandTransformExecutor;
use Moox\Transform\Support\Expansion\TransformProjectionExpander;
use Moox\Transform\Support\Operations\InlineOperationRegistry;
use Moox\Transform\Support\SourceContextResolver;
use Moox\Transform\Support\SourcePayloadResolver;
use Moox\Transform\Support\TemplateValueResolver;
use Moox\Transform\Support\TransformRunner;
use Spatie\LaravelPackageTools\Package;

class TransformServiceProvider extends MooxServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/transform.php', 'transform');
        $this->mergeConfigFrom(__DIR__.'/../config/transform-definition.php', 'transform-definition');
        $this->mergeConfigFrom(__DIR__.'/../config/transform-record.php', 'transform-record');

        $this->app->singleton(SourceContextResolver::class);
        $this->app->singleton(SourcePayloadResolver::class);
        $this->app->singleton(TemplateValueResolver::class);
        $this->app->singleton(ConfiguredImportRecordPayloadReader::class);
        $this->app->singleton(ConfiguredLocaleVariantResolver::class);
        $this->app->singleton(TransformProjectionExpander::class);
        $this->app->singleton(ExpandTransformExecutor::class);
        $this->app->singleton(ResolvedTransformDataFactory::class, function ($app): ResolvedTransformDataFactory {
            return new ResolvedTransformDataFactory(
                $app->make(InlineOperationRegistry::class),
                $app->make(SourceContextResolver::class),
            );
        });
        $this->app->singleton(EloquentUpsertBatchDestinationWriter::class);
        $this->app->singleton(TranslatableBatchDestinationWriter::class);
        $this->app->singleton(BatchDestinationWriterRegistry::class, function ($app): BatchDestinationWriterRegistry {
            return new BatchDestinationWriterRegistry([
                $app->make(TranslatableBatchDestinationWriter::class),
                $app->make(EloquentUpsertBatchDestinationWriter::class),
            ]);
        });
        $this->app->singleton(BulkTransformExecutor::class);
        $this->app->singleton(TransformRunner::class);

        if (config('transform.enable-panel')) {
            $this->app->register(TransformPanelProvider::class);
        }
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('transform')
            ->hasConfigFile(['transform', 'transform-definition', 'transform-record'])
            ->hasMigrations([
                'create_transform_definitions_table',
                'create_transform_records_table',
                'upgrade_transform_bulk_capabilities_table',
            ])
            ->hasTranslations();
    }
}
