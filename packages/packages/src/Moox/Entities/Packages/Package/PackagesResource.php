<?php

namespace Moox\Packages\Moox\Entities\Packages\Package;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Moox\Packages\Models\Package;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use Moox\Core\Forms\Components\CopyableField;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Packages\Moox\Entities\Packages\Package\Pages\ViewPackage;
use Moox\Packages\Moox\Entities\Packages\Package\Pages\ListPackages;
use Moox\Packages\Moox\Entities\Packages\Package\Pages\CreatePackage;

class PackagesResource extends BaseItemResource
{

    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('packages.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('packages.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('packages.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('packages.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('packages.navigation_group');
    }

    public static function enableEdit(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {

        $schema = [
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')
                                        ->label(__('packages::fields.title')),
                                    TextInput::make('name')
                                        ->label(__('packages::fields.name')),
                                    TextInput::make('vendor')
                                        ->label(__('packages::fields.vendor')),
                                    Select::make('package_type')
                                        ->label(__('packages::fields.package_type'))
                                        ->options([
                                            'moox_package' => 'Moox Package',
                                            'moox_compatible' => 'Moox Compatible',
                                            'moox_dependency' => 'Moox Dependency',
                                            'filament_plugin' => 'Filament Plugin',
                                            'laravel_package' => 'Laravel Package',
                                            'php_package' => 'PHP Package',
                                        ]),
                                    Toggle::make('is_theme')
                                        ->label(__('packages::fields.is_theme')),
                                    TextInput::make('version_installed')
                                        ->label(__('packages::fields.version_installed')),
                                    Select::make('install_status')
                                        ->label(__('packages::fields.install_status'))
                                        ->options([
                                            'available' => 'Available',
                                            'installed' => 'Installed',
                                            'active' => 'Active',
                                        ]),
                                    Select::make('update_status')
                                        ->label(__('packages::fields.update_status'))
                                        ->options([
                                            'up-to-date' => 'Up to Date',
                                            'update-available' => 'Update Available',
                                            'update-scheduled' => 'Update Scheduled',
                                            'update-failed' => 'Update Failed',
                                        ]),
                                    Toggle::make('auto_update')
                                        ->label(__('packages::fields.auto_update')),
                                    DateTimePicker::make('update_scheduled_at')
                                        ->label(__('packages::fields.update_scheduled_at')),
                                    TextInput::make('installed_by_type')
                                        ->label(__('packages::fields.installed_by_type')),
                                    KeyValue::make('activation_steps')
                                        ->label(__('packages::fields.activation_steps'))
                                        ->keyLabel('Step')
                                        ->valueLabel('Description')
                                        ->addActionLabel('Add Step')
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    // TODO: exactly same as getFormActions(), why?
                                    /** @phpstan-ignore-next-line */
                                    static::getFooterActions()->columnSpan(1),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    CopyableField::make('id')
                                        ->label(__('packages::fields.id'))
                                        ->defaultValue(fn($record): string => $record->id ?? ''),


                                    Section::make('')
                                        ->schema([
                                            Placeholder::make('created_at')
                                                ->label(__('packages::fields.created_at'))
                                                ->content(fn($record): string => $record->created_at ?
                                                    $record->created_at . ' - ' . $record->created_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('installed_at')
                                                ->label(__('packages::fields.installed_at'))
                                                ->content(fn($record): string => $record->installed_at ?
                                                    $record->installed_at . ' - ' . $record->installed_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('installed_by')
                                                ->label(__('packages::fields.installed_by'))
                                                ->content(fn($record): string => $record->installed_by ?
                                                    $record->installed_by->name : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('updated_at')
                                                ->label(__('packages::fields.updated_at'))
                                                ->content(fn($record): string => $record->updated_at ?
                                                    $record->updated_at . ' - ' . $record->updated_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('updated_by')
                                                ->label(__('packages::fields.updated_by'))
                                                ->content(fn($record): string => $record->updated_by ?
                                                    $record->updated_by->name : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                        ]),
                                ])
                                ->hidden(fn($record) => $record === null),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ];

        return $form
            ->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('packages::fields.title'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state): string => ucfirst($state)),
                TextColumn::make('name')
                    ->label(__('packages::fields.packagist'))
                    ->formatStateUsing(fn($record) => "{$record->vendor}/{$record->name}")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('package_type')
                    ->label(__('packages::fields.package_type'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state): string => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('version_installed')
                    ->label(__('packages::fields.version_installed'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('install_status')
                    ->label(__('packages::fields.install_status'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state): string => ucfirst($state)),
                TextColumn::make('update_status')
                    ->label(__('packages::fields.update_status'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state): string => ucwords(str_replace('-', ' ', $state))),
                ToggleColumn::make('auto_update')
                    ->label(__('packages::fields.auto_update'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('packages::fields.updated_at'))
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('title', 'desc')
            ->actions([
                ViewAction::make(),
            ])
            ->filters([
                SelectFilter::make('install_status')
                    ->label(__('packages::fields.install_status'))
                    ->placeholder(__('core::core.filter') . ' Install Status')
                    ->options(['available' => 'Available', 'installed' => 'Installed', 'active' => 'Active']),
                SelectFilter::make('package_type')
                    ->label(__('packages::fields.package_type'))
                    ->placeholder(__('core::core.filter') . ' Package Type')
                    ->options(['moox_package' => 'Moox Package', 'moox_compatible' => 'Moox Compatible', 'moox_dependency' => 'Moox Dependency', 'filament_plugin' => 'Filament Plugin', 'laravel_package' => 'Laravel Package', 'php_package' => 'PHP Package']),
                SelectFilter::make('update_status')
                    ->label(__('packages::fields.update_status'))
                    ->placeholder(__('core::core.filter') . ' Update Status')
                    ->options(['up-to-date' => 'Up to Date', 'update-available' => 'Update Available', 'update-scheduled' => 'Update Scheduled', 'update-failed' => 'Update Failed']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'view' => ViewPackage::route('/{record}'),
        ];
    }
}
