<?php

namespace Moox\Skeleton;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class SkeletonPlugin implements Plugin
{
    use EvaluatesClosures;

    /**
     * The resource label.
     */
    protected string|Closure|null $label = null;

    /**
     * The plural resource label.
     */
    protected string|Closure|null $pluralLabel = null;

    /**
     * The resource navigation status.
     */
    protected ?bool $navigation = null;

    /**
     * The resource navigation group.
     */
    protected ?string $navigationGroup = null;

    /**
     * The resource navigation icon.
     */
    protected ?string $navigationIcon = null;

    /**
     * The resource navigation sorting order.
     */
    protected ?int $navigationSort = null;

    /**
     * The resource navigation count badge status.
     */
    protected ?bool $navigationCountBadge = null;

    /**
     * The pruning status.
     */
    protected ?bool $pruning = null;

    /**
     * The pruning retention.
     */
    protected ?int $pruningRetention = null;

    /**
     * The resource class.
     */
    protected ?string $resource = null;

    /**
     * Get the plugin identifier.
     */
    public function getId(): string
    {
        return 'skeleton';
    }

    /**
     * Register the plugin.
     */
    public function register(Panel $panel): void
    {
        $panel->resources([
            $this->getResource(),
        ]);
    }

    /**
     * Boot the plugin.
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Make a new instance of the plugin.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the plugin instance.
     */
    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    /**
     * Get the resource class.
     */
    public function getResource(): string
    {
        return $this->resource ?? config('skeleton.resources.skeleton.resource');
    }

    /**
     * Set the resource class.
     */
    public function resource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the resource label.
     */
    public function getLabel(): ?string
    {
        return $this->evaluate($this->label) ?? config('skeleton.resources.skeleton.label');
    }

    /**
     * Set the resource label.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the plural resource label.
     */
    public function getPluralLabel(): ?string
    {
        return $this->evaluate($this->pluralLabel) ?? config('skeleton.resources.skeleton.plural_label');
    }

    /**
     * Set the plural resource label.
     */
    public function pluralLabel(string $pluralLabel): static
    {
        $this->pluralLabel = $pluralLabel;

        return $this;
    }

    /**
     * Get the resource navigation group.
     */
    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? config('skeleton.resources.skeleton.navigation_group');
    }

    /**
     * Set the resource navigation group.
     */
    public function navigationGroup(string $navigationGroup): static
    {
        $this->navigationGroup = $navigationGroup;

        return $this;
    }

    /**
     * Get the resource icon.
     */
    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon ?? config('skeleton.resources.skeleton.navigation_icon');
    }

    /**
     * Set the resource icon.
     */
    public function navigationIcon(string $navigationIcon): static
    {
        $this->navigationIcon = $navigationIcon;

        return $this;
    }

    /**
     * Get the resource sort.
     */
    public function getNavigationSort(): ?int
    {
        return $this->navigationSort ?? config('skeleton.resources.skeleton.navigation_sort');
    }

    /**
     * Set the resource sort.
     */
    public function navigationSort(int $navigationSort): static
    {
        $this->navigationSort = $navigationSort;

        return $this;
    }

    /**
     * Get the resource navigation count badge status.
     */
    public function getNavigationCountBadge(): ?bool
    {
        return $this->navigationCountBadge ?? config('skeleton.resources.skeleton.navigation_count_badge');
    }

    /**
     * Set the resource navigation count badge status.
     */
    public function navigationCountBadge(bool $navigationCountBadge = true): static
    {
        $this->navigationCountBadge = $navigationCountBadge;

        return $this;
    }

    /**
     * Determine whether the resource navigation is enabled.
     */
    public function shouldRegisterNavigation(): bool
    {
        return $this->navigation ?? config('skeleton.resources.skeleton.enabled');
    }

    /**
     * Enable the resource navigation.
     */
    public function enableNavigation(bool $status = true): static
    {
        $this->navigation = $status;

        return $this;
    }

    /**
     * Get the resource breadcrumb.
     */
    public function getBreadcrumb(): string
    {
        return __('skeleton::translations.breadcrumb');
    }
}
