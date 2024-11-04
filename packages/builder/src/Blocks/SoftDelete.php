<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class SoftDelete extends AbstractBlock
{
    protected bool $isFeature = true;

    protected bool $isSingleFeature = false;

    protected array $incompatibleWith = [
        Publish::class,
    ];

    public function __construct(
        string $name = 'softDelete',
        string $label = 'Soft Delete',
        string $description = 'Soft delete functionality',
    ) {
        parent::__construct($name, $label, $description);

        $this->useStatements = [
            'resource' => [
                'actions' => [
                    'use Filament\Tables\Actions\DeleteBulkAction;',
                    'use Filament\Tables\Actions\RestoreBulkAction;',
                    'use Filament\Actions\Action;',
                ],
            ],
            'model' => [
                'use Illuminate\Database\Eloquent\SoftDeletes;',
                'use Illuminate\Database\Eloquent\Builder;',
            ],
            'pages' => [
                'edit' => ['use Filament\Actions\Action;'],
                'list' => ['use Illuminate\Database\Eloquent\Builder;'],
                'view' => ['use Filament\Actions\Action;'],
            ],
        ];

        $this->traits['model'] = ['SoftDeletes'];

        $this->methods['resource'] = [
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
        ];

        $this->methods['model'] = [
            'scopes' => [
                'public function scopeOnlyTrashed(Builder $query): Builder {
                    return $query->whereNotNull("deleted_at");
                }',
            ],
        ];

        $this->methods['pages']['list'] = [
            'protected function applyStatusFilter(Builder $query, string $status): Builder {
                return match ($status) {
                    "deleted" => $query->onlyTrashed(),
                    default => $query->whereNull("deleted_at"),
                };
            }',
        ];

        $this->actions['pages']['edit']['header'] = [
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
        ];

        $this->actions['bulk'] = [
            "DeleteBulkAction::make()
                ->hidden(fn () => request()->routeIs('*.trash'))",
            "RestoreBulkAction::make()
                ->visible(fn () => request()->routeIs('*.trash'))",
        ];

        $this->migrations['fields'] = [
            '$table->softDeletes()',
        ];
    }
}
