<?php

declare(strict_types=1);

namespace Moox\Cache\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Moox\Cache\Contracts\CacheTarget;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Plugins\CachePlugin;
use Moox\Cache\Support\CacheTargetRegistry;

class CacheManagerPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected string $view = 'moox-cache::filament.pages.cache-manager';

    protected static ?int $navigationSort = 90;

    public ?string $lastResultTarget = null;

    public ?CacheClearResult $lastResult = null;

    public ?string $customKey = null;

    public ?string $cacheStore = null;

    public ?string $pageCacheSlug = null;

    public bool $pageCacheRecursive = false;

    public static function getNavigationLabel(): string
    {
        return __('moox-cache::cache.navigation.label');
    }

    public function getTitle(): string
    {
        return __('moox-cache::cache.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('moox-cache::cache.navigation.group');
    }

    public static function canAccess(): bool
    {
        return CachePlugin::make()->canUserAccess();
    }

    /**
     * @return array<string, list<CacheTarget>>
     */
    public function getGroupedTargets(): array
    {
        return app(CacheTargetRegistry::class)->groupedByCategory();
    }

    public function clearTarget(string $targetKey): void
    {
        $registry = app(CacheTargetRegistry::class);
        $target = $registry->get($targetKey);

        if ($target === null) {
            Notification::make()
                ->title(__('moox-cache::cache.messages.target_not_found'))
                ->danger()
                ->send();

            return;
        }

        $request = new CacheClearRequest(
            key: $this->customKey,
            store: $this->cacheStore ?? config('cache.default'),
            slug: $this->pageCacheSlug,
            recursive: $this->pageCacheRecursive,
        );

        $result = $target->clear($request);

        $this->lastResultTarget = $target->label();
        $this->lastResult = $result;

        $notification = Notification::make()->title($result->message);

        if ($result->success) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->send();
    }

    /**
     * @return list<array{key: string, label: string, description: string|null}>
     */
    public function getConfiguredCacheKeys(): array
    {
        return config('moox-cache.cache_keys', []);
    }
}
