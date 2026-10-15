<?php

declare(strict_types=1);

namespace Moox\Department\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Department\Models\DepartmentPerson;
use Moox\Department\Resources\DepartmentPerson\Pages\CreateDepartmentPerson;
use Moox\Department\Resources\DepartmentPerson\Pages\EditDepartmentPerson;
use Moox\Department\Resources\DepartmentPerson\Pages\ListDepartmentPersons;
use Moox\Department\Resources\DepartmentPerson\Pages\ViewDepartmentPerson;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Relation\HasRelationForm;

class DepartmentPersonResource extends Resource
{
    use HasRelationForm;

    protected static ?string $model = DepartmentPerson::class;

    protected static ?string $recordTitleAttribute = 'position';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-badge';

    public static function getModelLabel(): string
    {
        return __('department::department.department_person');
    }

    public static function getPluralModelLabel(): string
    {
        return __('department::department.department_persons');
    }

    public static function getNavigationLabel(): string
    {
        return __('department::department.department_persons');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::domainSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::relationSection()->columns(1),
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
                TextInput::make('position')
                    ->label(FeatureFields::label($translation, 'position'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('position')),
            ]);
    }

    protected static function featureTranslation(): string
    {
        return 'department::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'position' => ['required', 'string', 'max:255'],
            'department_id', 'person_id' => ['required', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('position')
                    ->label(FeatureFields::label($translation, 'position'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department_id')
                    ->label(FeatureFields::label($translation, 'department_id'))
                    ->sortable(),
                TextColumn::make('person_id')
                    ->label(FeatureFields::label($translation, 'person_id'))
                    ->sortable(),
            ])
            ->defaultSort('position')
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
            'index' => ListDepartmentPersons::route('/'),
            'create' => CreateDepartmentPerson::route('/create'),
            'view' => ViewDepartmentPerson::route('/{record}'),
            'edit' => EditDepartmentPerson::route('/{record}/edit'),
        ];
    }
}
