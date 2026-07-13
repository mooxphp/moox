<?php

declare(strict_types=1);

namespace Moox\BlockEditor;

use Illuminate\Support\Facades\Gate;
use Moox\BlockEditor\EntityQuery\DynamicFeedSourceRegistrar;
use Moox\BlockEditor\EntityQuery\EntityQueryBuilder;
use Moox\BlockEditor\EntityQuery\Mapping\DraftFeedItemResolver;
use Moox\BlockEditor\EntityQuery\Mapping\Relations\FeedItemRelationResolver;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Repositories\TemplateRepository;
use Moox\BlockEditor\Policies\TemplatePolicy;
use Moox\BlockEditor\Rendering\BlockContentRenderer;
use Moox\BlockEditor\Rendering\Blocks\DynamicFeedBlockRenderer;
use Moox\BlockEditor\Rendering\Blocks\HeadingBlockRenderer;
use Moox\BlockEditor\Rendering\Blocks\ParagraphBlockRenderer;
use Moox\BlockEditor\Support\DynamicFeedConfiguration;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BlockEditorServiceProvider extends MooxServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/dynamic-feed-sources.php', 'moox-editor.dynamic_feed.sources');
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('block-editor')
            ->hasConfigFile('moox-editor')
            ->hasViews('moox-editor')
            ->hasMigrations()
            ->hasRoutes('api');
    }

    public function bootingPackage(): void
    {
        Gate::policy(Template::class, TemplatePolicy::class);

        $this->app->singleton(EntityQueryBuilder::class);
        $this->app->singleton(FeedItemRelationResolver::class);
        $this->app->singleton(DraftFeedItemResolver::class);
        $this->app->singleton(TemplateRepository::class);

        $this->app->singleton(BlockContentRenderer::class, function ($app): BlockContentRenderer {
            $renderers = [
                $app->make(ParagraphBlockRenderer::class),
                $app->make(HeadingBlockRenderer::class),
                $app->make(DynamicFeedBlockRenderer::class),
            ];

            return new BlockContentRenderer($renderers);
        });
    }

    public function packageBooted(): void
    {
        $this->app->booted(function (): void {
            DynamicFeedConfiguration::mergePackageDefaults();
            DynamicFeedSourceRegistrar::registerFromConfig();
        });

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../resources/editor' => public_path('vendor/moox/block-editor'),
            __DIR__.'/../resources/js/browser@4.js' => public_path('vendor/moox/block-editor/browser@4.js'),
        ], 'moox-editor-assets');
    }
}
