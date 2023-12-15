<?php

namespace Moox\Jobs;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class JobsFailedPlugin implements Plugin
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
        return 'filament-failed-jobs';
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
        $id = app(static::class)->getId();

        return app(static::class);
    }

    /**
     * Get the resource class.
     */
    public function getResource(): string
    {
        return $this->resource ?? config('jobs.resources.failed_jobs.resource');
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
        return $this->evaluate($this->label) ?? config('jobs.resources.failed_jobs.label');
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
        return $this->evaluate($this->pluralLabel) ?? config('jobs.resources.failed_jobs.plural_label');
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
        return $this->navigationGroup ?? config('jobs.resources.failed_jobs.navigation_group');
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
        return $this->navigationIcon ?? config('jobs.resources.failed_jobs.navigation_icon');
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
        return $this->navigationSort ?? config('jobs.resources.failed_jobs.navigation_sort');
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
        return $this->navigationCountBadge ?? config('jobs.resources.failed_jobs.navigation_count_badge');
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
        return $this->navigation ?? config('jobs.resources.failed_jobs.enabled');
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
     * Get the pruning status.
     */
    public function getPruning(): ?bool
    {
        return $this->pruning ?? config('jobs.pruning.enabled');
    }

    /**
     * Set the pruning status.
     */
    public function enablePruning(bool $status = true): static
    {
        $this->pruning = $status;

        return $this;
    }

    /**
     * Get the pruning retention.
     */
    public function getPruningRetention(): ?int
    {
        return $this->pruningRetention ?? config('jobs.pruning.retention_days');
    }

    /**
     * Set the pruning retention.
     */
    public function pruningRetention(int $pruningRetention): static
    {
        $this->pruningRetention = $pruningRetention;

        return $this;
    }

    /**
     * Get the resource breadcrumb.
     */
    public function getBreadcrumb(): string
    {
        return __('jobs::translations.breadcrumb');
    }
}
