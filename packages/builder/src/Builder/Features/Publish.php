<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Features;

class Publish extends Feature
{
    protected static array $useStatements = [
        'resource' => [
            'actions' => [
                'use Filament\Actions\Action;',
            ],
            'columns' => [
                'use Filament\Tables\Columns\TextColumn;',
            ],
            'filters' => [
                'use Filament\Tables\Filters\Filter;',
            ],
            'forms' => [
                'use Filament\Forms\Components\DateTimePicker;',
            ],
        ],
        'model' => [
            'use Illuminate\Database\Eloquent\Builder;',
            'use Moox\Core\Traits\SinglePublishInModel;',
        ],
        'migration' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
            'list' => [
                'use Illuminate\Database\Eloquent\Builder;',
            ],
            'view' => [],
        ],
    ];

    protected static array $traits = [
        'resource' => ['SinglePublishInResource'],
        'model' => ['SinglePublishInModel'],
    ];

    protected static array $methods = [
        'resource' => [],
        'model' => [
            'public function scopePublished(Builder $query): Builder {
                return $query->whereNotNull("published_at");
            }',
            'public function scopeScheduled(Builder $query): Builder {
                return $query->whereNotNull("publish_at")
                    ->whereNull("published_at");
            }',
            'public function scopeDraft(Builder $query): Builder {
                return $query->whereNull("published_at")
                    ->whereNull("publish_at");
            }',
        ],
        'pages' => [
            'list' => [
                'protected function applyStatusFilter(Builder $query, string $status): Builder {
                    return match ($status) {
                        "published" => $query->published(),
                        "scheduled" => $query->scheduled(),
                        "draft" => $query->draft(),
                        default => $query,
                    };
                }',
            ],
        ],
    ];

    public function getFormFields(): array
    {
        return [
            "DateTimePicker::make('publish_at')
                ->label(__('core::core.publish_at'))
                ->nullable()",
        ];
    }

    public function getTableColumns(): array
    {
        return [
            "TextColumn::make('publish_at')
                ->label(__('core::core.publish_at'))
                ->dateTime()
                ->sortable()
                ->toggleable()",
            "TextColumn::make('published_at')
                ->label(__('core::core.published_at'))
                ->dateTime()
                ->sortable()
                ->toggleable()",
        ];
    }

    public function getTableFilters(): array
    {
        return [
            "Filter::make('published')
                ->label(__('core::core.published'))
                ->query(fn (Builder \$query): Builder => \$query->published())",
            "Filter::make('scheduled')
                ->label(__('core::core.scheduled'))
                ->query(fn (Builder \$query): Builder => \$query->scheduled())",
            "Filter::make('draft')
                ->label(__('core::core.draft'))
                ->query(fn (Builder \$query): Builder => \$query->draft())",
        ];
    }

    public function getActions(): array
    {
        return [
            "Action::make('publish')
                ->label(__('core::core.publish'))
                ->color('success')
                ->button()
                ->extraAttributes(['class' => 'w-full'])
                ->action(function (\$livewire) {
                    \$data = \$livewire->form->getState();
                    \$data['published_at'] = now();
                    \$livewire->form->fill(\$data);
                    \$livewire->save();
                })
                ->visible(fn (\$record) => ! \$record->published_at)",
        ];
    }

    public function getMigrations(): array
    {
        return [
            '$table->timestamp("published_at")->nullable()',
            '$table->timestamp("publish_at")->nullable()',
        ];
    }
}
