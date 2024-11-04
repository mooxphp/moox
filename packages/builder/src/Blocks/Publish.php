<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Publish extends AbstractBlock
{
    protected bool $isFeature = true;

    protected bool $isSingleFeature = true;

    protected array $includes = [
        Author::class,
        SoftDelete::class,
    ];

    public function __construct(
        string $name = 'publish',
        string $label = 'Publish',
        string $description = 'Publication status management',
    ) {
        parent::__construct($name, $label, $description);

        $this->useStatements = [
            'model' => [
                'use Illuminate\Database\Eloquent\Builder;',
                'use Moox\Core\Traits\SinglePublishInModel;',
            ],
            'resource' => [
                'forms' => ['use Filament\Forms\Components\DateTimePicker;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\Filter;'],
                'actions' => ['use Filament\Actions\Action;'],
            ],
            'pages' => [
                'list' => ['use Illuminate\Database\Eloquent\Builder;'],
            ],
        ];

        $this->traits['model'] = ['SinglePublishInModel'];
        $this->traits['resource'] = ['SinglePublishInResource'];

        $this->methods['model'] = [
            'scopes' => [
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
        ];

        $this->methods['pages']['list'] = [
            'protected function applyStatusFilter(Builder $query, string $status): Builder {
                return match ($status) {
                    "published" => $query->published(),
                    "scheduled" => $query->scheduled(),
                    "draft" => $query->draft(),
                    default => $query,
                };
            }',
        ];

        $this->formFields['resource'] = [
            "DateTimePicker::make('publish_at')
                ->label(__('core::core.publish_at'))
                ->nullable()",
        ];

        $this->tableColumns['resource'] = [
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

        $this->filters['resource'] = [
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

        $this->actions['pages']['edit']['header'] = [
            "Action::make('publish')
                ->label(__('core::core.publish'))
                ->color('success')
                ->action(function (\$livewire) {
                    \$data = \$livewire->form->getState();
                    \$data['published_at'] = now();
                    \$livewire->form->fill(\$data);
                    \$livewire->save();
                })
                ->visible(fn (\$record) => ! \$record->published_at)",
        ];

        $this->migrations['fields'] = [
            '$table->timestamp("published_at")->nullable()',
            '$table->timestamp("publish_at")->nullable()',
        ];

        $this->factories['model']['states'] = [
            'published' => [
                'published_at' => 'now()',
                'publish_at' => 'now()',
            ],
            'scheduled' => [
                'publish_at' => 'fake()->dateTimeBetween("tomorrow", "+30 days")',
                'published_at' => 'null',
            ],
            'draft' => [
                'publish_at' => 'null',
                'published_at' => 'null',
            ],
        ];
    }
}
