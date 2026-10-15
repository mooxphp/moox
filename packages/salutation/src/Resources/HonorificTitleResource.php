<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Salutation\Models\HonorificTitle;
use Moox\Salutation\Resources\HonorificTitle\Pages\CreateHonorificTitle;
use Moox\Salutation\Resources\HonorificTitle\Pages\EditHonorificTitle;
use Moox\Salutation\Resources\HonorificTitle\Pages\ListHonorificTitles;
use Moox\Salutation\Resources\HonorificTitle\Pages\ViewHonorificTitle;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;
use Moox\Support\Title\HasTitleForm;

class HonorificTitleResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasSoftDeleteForm;
    use HasTitleForm;

    protected static ?string $model = HonorificTitle::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-workspace-premium';

    public static function getModelLabel(): string
    {
        return __('salutation::salutation.honorific_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('salutation::salutation.honorific_titles');
    }

    public static function getNavigationLabel(): string
    {
        return __('salutation::salutation.honorific_titles');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::activeSection(),
                            static::titleSection(),
                            static::dataSection(),
                            static::sortOrderSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::relationSection(),
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                            static::archiveSection()->columns(1),
                            static::softDeleteSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function sortOrderSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'sort_order'))
            ->schema([
                TextInput::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->rules(static::featureRules('sort_order')),
            ]);
    }

    protected static function featureTranslation(): string
    {
        return 'salutation::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'is_active' => ['boolean'],
            'salutation_id' => ['nullable', 'integer'],
            'sort_order' => ['required', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                ...static::titleColumns(),
                TextColumn::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHonorificTitles::route('/'),
            'create' => CreateHonorificTitle::route('/create'),
            'view' => ViewHonorificTitle::route('/{record}'),
            'edit' => EditHonorificTitle::route('/{record}/edit'),
        ];
    }
}
