<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Features;

use Moox\Core\Traits\Publish\SinglePublishInModel;
use Moox\Core\Traits\Publish\SinglePublishInResource;
use Moox\Core\Traits\Publish\SinglePublishInListPage;
use Moox\Builder\Blocks\AbstractBlock;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Blocks\Singles\SoftDelete;

class CustomDemo extends AbstractBlock
{
    protected array $containsBlocks = [
        Simple::class,
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
            ],
            'resource' => [
                'forms' => ['use Filament\Forms\Components\DateTimePicker;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\Filter;'],
                'actions' => ['use Filament\Actions\Action;'],
            ],
            'pages' => [
                'list' => [
                    // This is missing in the generated list page, why?
                    'use Illuminate\Database\Eloquent\Builder;',
                    // while this is generated
                    'use Moox\Core\Traits\Publish\SinglePublishInListPage;',
                ],
            ],
        ];

        $this->traits['model'] = [SinglePublishInModel::class];
        $this->traits['resource'] = [SinglePublishInResource::class];
        $this->traits['pages']['list'] = [SinglePublishInListPage::class];

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

        $this->metaFields['resource'] = [
            'static::getFormActions()',
            'static::getPublishAtFormField()',
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

        $this->filters = [];
        /* TODO: Fix this
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
        */

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
            '$table->softDeletes()',
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

    public function getTabs(): array
    {
        return [
            'all' => [
                'label' => 'trans//core::core.all',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'published' => [
                'label' => 'trans//core::core.published',
                'icon' => 'gmdi-check-circle',
                'query' => [
                    [
                        'field' => 'publish_at',
                        'operator' => '<=',
                        'value' => 'now()',
                    ],
                ],
            ],
            'scheduled' => [
                'label' => 'trans//core::core.scheduled',
                'icon' => 'gmdi-schedule',
                'query' => [
                    [
                        'field' => 'publish_at',
                        'operator' => '>',
                        'value' => 'now()',
                    ],
                ],
            ],
            'draft' => [
                'label' => 'trans//core::core.draft',
                'icon' => 'gmdi-text-snippet',
                'query' => [
                    [
                        'field' => 'published_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'deleted' => [
                'label' => 'trans//core::core.deleted',
                'icon' => 'gmdi-delete',
                'query' => [
                    [
                        'field' => 'deleted_at',
                        'operator' => '!=',
                        'value' => null,
                    ],
                ],
            ],
        ];
    }
}
