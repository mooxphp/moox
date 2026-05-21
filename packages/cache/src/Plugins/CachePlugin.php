<?php

declare(strict_types=1);

namespace Moox\Cache\Plugins;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Cache\Contracts\CacheTarget;
use Moox\Cache\Data\CacheKey;
use Moox\Cache\Pages\CacheManagerPage;
use Moox\Cache\Support\CacheTargetRegistry;

class CachePlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool|Closure $canAccess = true;

    protected bool $showNavigation = true;

    protected bool $showDashboardWidget = true;

    protected bool $showTopbarButton = true;

    /** @var list<CacheTarget> */
    protected array $targets = [];

    /** @var list<CacheKey> */
    protected array $cacheKeys = [];

    public function getId(): string
    {
        return 'moox-cache';
    }

    public function register(Panel $panel): void
    {
        if ($this->showNavigation) {
            $panel->pages([
                CacheManagerPage::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        $registry = app(CacheTargetRegistry::class);

        $registry->registerMany($this->targets);

        if ($this->cacheKeys !== []) {
            $configuredKeys = config('moox-cache.cache_keys', []);

            foreach ($this->cacheKeys as $cacheKey) {
                $configuredKeys[] = [
                    'key' => $cacheKey->key,
                    'label' => $cacheKey->label,
                    'description' => $cacheKey->description,
                ];
            }

            config(['moox-cache.cache_keys' => $configuredKeys]);
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function canAccess(bool|Closure $condition): static
    {
        $this->canAccess = $condition;

        return $this;
    }

    public function showNavigation(bool $condition = true): static
    {
        $this->showNavigation = $condition;

        return $this;
    }

    public function showDashboardWidget(bool $condition = true): static
    {
        $this->showDashboardWidget = $condition;

        return $this;
    }

    public function showTopbarButton(bool $condition = true): static
    {
        $this->showTopbarButton = $condition;

        return $this;
    }

    /**
     * @param  list<CacheTarget>  $targets
     */
    public function targets(array $targets): static
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * @param  list<CacheKey>  $cacheKeys
     */
    public function cacheKeys(array $cacheKeys): static
    {
        $this->cacheKeys = $cacheKeys;

        return $this;
    }

    public function canUserAccess(): bool
    {
        return (bool) $this->evaluate($this->canAccess);
    }

    public function shouldShowDashboardWidget(): bool
    {
        return $this->showDashboardWidget;
    }

    public function shouldShowTopbarButton(): bool
    {
        return $this->showTopbarButton;
    }
}
