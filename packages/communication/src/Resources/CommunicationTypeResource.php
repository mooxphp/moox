<?php

declare(strict_types=1);

namespace Moox\Communication\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Moox\Communication\Enums\CommunicationValidationType;
use Moox\Communication\Models\CommunicationType;
use Moox\Communication\Resources\CommunicationType\Pages\CreateCommunicationType;
use Moox\Communication\Resources\CommunicationType\Pages\EditCommunicationType;
use Moox\Communication\Resources\CommunicationType\Pages\ListCommunicationTypes;
use Moox\Communication\Resources\CommunicationType\Pages\ViewCommunicationType;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\SoftDelete\HasSoftDeleteForm;
use Moox\Support\Title\HasTitleForm;

class CommunicationTypeResource extends Resource
{
    use HasAuditForm;
    use HasSoftDeleteForm;
    use HasTitleForm;

    protected static ?string $model = CommunicationType::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-category';

    public static function getModelLabel(): string
    {
        return __('communication::communication.communication_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication::communication.communication_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('communication::communication.communication_types');
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
                            static::titleSection(),
                            static::domainSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::auditSection()->columns(1),
                            static::softDeleteSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function domainSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'domain'))
            ->schema([
                Toggle::make('mandatory')
                    ->label(FeatureFields::label($translation, 'mandatory'))
                    ->rules(static::featureRules('mandatory')),
                Select::make('validation_type')
                    ->label(FeatureFields::label($translation, 'validation_type'))
                    ->options(array_combine(CommunicationValidationType::values(), CommunicationValidationType::values()))
                    ->required()
                    ->rules(static::featureRules('validation_type')),
            ])
            ->columns(2);
    }

    protected static function featureTranslation(): string
    {
        return 'communication::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'title', 'slug' => ['required', 'string', 'max:255'],
            'validation_type' => ['required', 'string', Rule::in(CommunicationValidationType::values())],
            'mandatory' => ['boolean'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                ...static::titleColumns(),
                IconColumn::make('mandatory')
                    ->label(FeatureFields::label($translation, 'mandatory'))
                    ->boolean(),
                TextColumn::make('validation_type')
                    ->label(FeatureFields::label($translation, 'validation_type'))
                    ->searchable()
                    ->sortable(),
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
            'index' => ListCommunicationTypes::route('/'),
            'create' => CreateCommunicationType::route('/create'),
            'view' => ViewCommunicationType::route('/{record}'),
            'edit' => EditCommunicationType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return config('communication-type.navigationgroup.label');
    }
}
