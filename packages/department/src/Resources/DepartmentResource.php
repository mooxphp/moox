<?php

declare(strict_types=1);

namespace Moox\Department\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Department\Models\Department;
use Moox\Department\Resources\Department\Pages\CreateDepartment;
use Moox\Department\Resources\Department\Pages\EditDepartment;
use Moox\Department\Resources\Department\Pages\ListDepartments;
use Moox\Department\Resources\Department\Pages\ViewDepartment;
use Moox\Department\Support\DepartmentRules;

class DepartmentResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-corporate-fare';

    protected static function getEntityType(): string
    {
        return 'department';
    }

    public static function getModelLabel(): string
    {
        return config('department.resources.department.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('department.resources.department.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('department.resources.department.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('department.resources.department.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('department.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();
        $statusOptions = static::configOptions('department.statuses');

        $schema = [
            Grid::make()
                ->schema([
                    Section::make(__('department::fields.identity'))
                        ->schema([
                            Select::make('status')
                                ->label(__('department::fields.status'))
                                ->options($statusOptions)
                                ->required()
                                ->rules(DepartmentRules::for('status'))
                                ->default('draft'),
                            TextInput::make('name')
                                ->label(__('department::fields.name'))
                                ->required()
                                ->rules(DepartmentRules::for('name'))
                                ->maxLength(160),
                            TextInput::make('code')
                                ->label(__('department::fields.code'))
                                ->rules(DepartmentRules::for('code'))
                                ->maxLength(40),
                            Textarea::make('description')
                                ->label(__('department::fields.description'))
                                ->rules(DepartmentRules::for('description'))
                                ->columnSpanFull(),
                            TextInput::make('external_reference')
                                ->label(__('department::fields.external_reference'))
                                ->rules(DepartmentRules::for('external_reference'))
                                ->maxLength(100),
                            Textarea::make('data')
                                ->label(__('department::fields.data'))
                                ->columnSpanFull()
                                ->cols(100)
                                ->rows(10)
                                ->formatStateUsing(function ($state) {
                                    return json_encode((array) $state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    Section::make('')
                                        ->schema([
                                            ...static::getStandardTimestampFields(),
                                        ]),
                                ])
                                ->hidden(fn (?Department $record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];

        return $form->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('department::fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('department::fields.code'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('department::fields.status'))
                    ->badge()
                    ->color(
                        fn (string $state): string => match ($state) {
                            'draft' => 'info',
                            'active' => 'success',
                            'inactive' => 'warning',
                            'approved' => 'success',
                            'archived' => 'danger',
                            default => 'gray',
                        }
                    )
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('name')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                ...static::getDepartmentTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter>
     */
    protected static function getDepartmentTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('department::fields.status'))
                ->options(static::configOptions('department.statuses')),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function configOptions(string $configKey): array
    {
        /** @var mixed $configured */
        $configured = config($configKey, []);
        $values = is_array($configured) ? $configured : [];

        $options = [];
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $options[$value] = $value;
            }
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
            'view' => ViewDepartment::route('/{record}'),
        ];
    }
}
