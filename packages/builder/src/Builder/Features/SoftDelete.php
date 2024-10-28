<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Features;

class SoftDelete extends AbstractFeature
{
    protected function initializeFeature(): void
    {
        $this->useStatements = [
            'resource' => [
                'actions' => [
                    'use Filament\Tables\Actions\DeleteBulkAction;',
                    'use Filament\Tables\Actions\RestoreBulkAction;',
                    'use Filament\Actions\Action;',
                ],
                'columns' => [],
                'filters' => [],
                'forms' => [],
            ],
            'model' => [
                'use Illuminate\Database\Eloquent\SoftDeletes;',
                'use Illuminate\Database\Eloquent\Builder;',
            ],
            'migration' => [],
            'pages' => [
                'create' => [],
                'edit' => [
                    'use Filament\Actions\Action;',
                ],
                'list' => [
                    'use Illuminate\Database\Eloquent\Builder;',
                ],
                'view' => [
                    'use Filament\Actions\Action;',
                ],
            ],
        ];

        $this->traits = [
            'resource' => [],
            'model' => ['SoftDeletes'],
        ];

        $this->methods = [
            'resource' => [
                'public static function getTableQuery(?string $currentTab = null): Builder {
                    $model = static::getModel();
                    $query = $model::query()->withoutGlobalScopes();
                    if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                        $query = $model::withTrashed();
                    }
                    if ($currentTab) {
                        static::applyStatusFilter($query, $currentTab);
                    }
                    return $query;
                }',
            ],
            'model' => [
                'public function scopeOnlyTrashed(Builder $query): Builder {
                    return $query->whereNotNull("deleted_at");
                }',
            ],
            'pages' => [
                'list' => [
                    'protected function applyStatusFilter(Builder $query, string $status): Builder {
                        return match ($status) {
                            "deleted" => $query->onlyTrashed(),
                            default => $query->whereNull("deleted_at"),
                        };
                    }',
                ],
            ],
        ];
    }

    public function getFormFields(): array
    {
        return [];
    }

    public function getTableColumns(): array
    {
        return [];
    }

    public function getTableFilters(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [
            "Action::make('restore')
                ->label(__('core::core.restore'))
                ->color('success')
                ->button()
                ->extraAttributes(['class' => 'w-full'])
                ->action(fn (\$record) => \$record->restore())
                ->visible(fn (\$livewire, \$record) => \$record && \$record->trashed())",
            "Action::make('delete')
                ->label(__('core::core.delete'))
                ->color('danger')
                ->button()
                ->extraAttributes(['class' => 'w-full'])
                ->action(fn (\$record) => \$record->delete())
                ->visible(fn (\$livewire, \$record) => \$record && ! \$record->trashed())",
            "DeleteBulkAction::make()
                ->hidden(fn () => request()->routeIs('*.trash'))",
            "RestoreBulkAction::make()
                ->visible(fn () => request()->routeIs('*.trash'))",
        ];
    }

    public function getMigrations(): array
    {
        return [
            '$table->softDeletes()',
        ];
    }
}
