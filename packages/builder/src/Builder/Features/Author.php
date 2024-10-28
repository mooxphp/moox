<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Features;

class Author extends Feature
{
    protected static array $useStatements = [
        'resource' => [
            'forms' => [
                'use Filament\Forms\Components\Select;',
            ],
            'columns' => [
                'use Filament\Tables\Columns\TextColumn;',
            ],
            'filters' => [
                'use Filament\Tables\Filters\SelectFilter;',
            ],
            'actions' => [],
        ],
        'model' => [
            'use Illuminate\Database\Eloquent\Relations\BelongsTo;',
            'use App\Models\User;',
            'use Moox\Core\Traits\AuthorInModel;',
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
        'resource' => ['AuthorInResource'],
        'model' => ['AuthorInModel'],
    ];

    protected static array $methods = [
        'resource' => [
            'protected static function initAuthorModel(): void {
                static::$model = config("builder.models.author");
            }',
            'public static function getAuthorModel(): string {
                return config("builder.models.author");
            }',
        ],
        'model' => [
            'public function author(): BelongsTo {
                return $this->belongsTo(User::class, "author_id");
            }',
            'public function scopeWhereAuthor(Builder $query, $author): Builder {
                return $query->where("author_id", $author);
            }',
            'public function scopeWhereAuthorIn(Builder $query, array $authors): Builder {
                return $query->whereIn("author_id", $authors);
            }',
        ],
        'pages' => [
            'list' => [
                'protected function applySearchToTableQuery(Builder $query): Builder {
                    if ($this->getTableSearch()) {
                        $query->where(function (Builder $query) {
                            $query->orWhereHas("author", function (Builder $query) {
                                $query->where("name", "like", "%{$this->getTableSearch()}%");
                            });
                        });
                    }
                    return $query;
                }',
            ],
        ],
    ];

    public function getFormFields(): array
    {
        return [
            "Select::make('author_id')
                ->relationship('author', 'name')
                ->searchable()
                ->preload()
                ->required()",
        ];
    }

    public function getTableColumns(): array
    {
        return [
            "TextColumn::make('author.name')
                ->label(__('core::core.author'))
                ->sortable()
                ->searchable()",
        ];
    }

    public function getTableFilters(): array
    {
        return [
            "SelectFilter::make('author')
                ->relationship('author', 'name')
                ->label(__('core::core.author'))
                ->searchable()
                ->preload()
                ->multiple()",
        ];
    }

    public function getActions(): array
    {
        return [];
    }

    public function getMigrations(): array
    {
        return [
            '$table->foreignId("author_id")->constrained("users")->cascadeOnDelete()',
        ];
    }
}
