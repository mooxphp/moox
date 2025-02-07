<?php

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\On;

trait SingleSoftDeleteInListPage
{
    public ?string $activeTab = null;

    #[On('tab-changed')]
    public function updateTab(): void
    {
        $this->activeTab = request()->query('activeTab', '');
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        $resource = static::getResource();

        if ($resource::enableCreate()) {
            $actions[] = CreateAction::make('create')
                ->visible(fn (): bool => $this->activeTab !== 'deleted');
        }

        if ($resource::enableEmptyTrash()) {
            $actions[] = Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalHeading(__('core::core.empty_trash_confirmation'))
                ->modalDescription(__('core::core.empty_trash_description'))
                ->disabled(function (): bool {
                    $model = $this->getModel();

                    if (! in_array(SoftDeletes::class, class_uses_recursive($model))) {
                        return true;
                    }

                    $modelInstance = new $model;
                    if (! method_exists($modelInstance, 'getQualifiedDeletedAtColumn')) {
                        return true;
                    }

                    $deletedAtColumn = $modelInstance->getQualifiedDeletedAtColumn();

                    return $model::withoutGlobalScope(SoftDeletingScope::class)
                        ->whereNotNull($deletedAtColumn)
                        ->count() === 0;
                })
                ->action(function (): void {
                    $model = $this->getModel();

                    if (! in_array(SoftDeletes::class, class_uses_recursive($model))) {
                        return;
                    }

                    $modelInstance = new $model;
                    if (! method_exists($modelInstance, 'getQualifiedDeletedAtColumn')) {
                        return;
                    }

                    $deletedAtColumn = $modelInstance->getQualifiedDeletedAtColumn();

                    $model::withoutGlobalScope(SoftDeletingScope::class)
                        ->whereNotNull($deletedAtColumn)
                        ->forceDelete();

                    $this->dispatch('refresh');
                })
                ->visible(fn (): bool => $this->activeTab === 'deleted');
        }

        return $actions;
    }

    protected function applyStatusFilter(Builder $query, string $status): Builder
    {
        $model = $this->getModel();

        if (! in_array(SoftDeletes::class, class_uses_recursive($model))) {
            return $query;
        }

        $modelInstance = new $model;
        if (! method_exists($modelInstance, 'getQualifiedDeletedAtColumn')) {
            return $query;
        }

        $deletedAtColumn = $modelInstance->getQualifiedDeletedAtColumn();

        return match ($status) {
            'deleted' => $query->whereNotNull($deletedAtColumn),
            default => $query->whereNull($deletedAtColumn),
        };
    }
}
