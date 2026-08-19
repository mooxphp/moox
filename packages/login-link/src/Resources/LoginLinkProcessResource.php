<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\CreateLoginLinkProcess;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\EditLoginLinkProcess;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\ListLoginLinkProcesses;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\ViewLoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class LoginLinkProcessResource extends BaseRecordResource
{
    protected static ?string $model = LoginLinkProcess::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-settings-suggest-o';

    protected static function getEntityType(): string
    {
        return 'login-link';
    }

    public static function getModelLabel(): string
    {
        return config('login-link.resources.process.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('login-link.resources.process.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('login-link.resources.process.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('login-link.resources.process.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('login-link.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TitleWithSlugInput::make(
                                    fieldTitle: 'title',
                                    fieldSlug: 'slug',
                                    urlHostVisible: false,
                                    urlVisitLinkVisible: false,
                                    slugRuleUniqueParameters: [
                                        'table' => 'login_link_processes',
                                        'column' => 'slug',
                                        'ignoreRecord' => true,
                                    ],
                                ),
                                Select::make('context')
                                    ->label(__('login-link::translations.context'))
                                    ->options([
                                        LinkProcessContext::AUTH => __('login-link::translations.context_auth'),
                                        LinkProcessContext::PUBLIC => __('login-link::translations.context_public'),
                                    ])
                                    ->default(LinkProcessContext::AUTH)
                                    ->required()
                                    ->native(false)
                                    ->helperText(__('login-link::translations.context_help')),
                                TextInput::make('mail_from')
                                    ->label(__('login-link::translations.mail_from'))
                                    ->email()
                                    ->maxLength(255),
                                Select::make('template_key')
                                    ->label(__('login-link::translations.template_key'))
                                    ->options(fn (): array => collect(config('login-link.templates', []))
                                        ->mapWithKeys(fn (string $view, string $key): array => [$key => $key])
                                        ->all())
                                    ->required()
                                    ->native(false)
                                    ->helperText(__('login-link::translations.template_key_help')),
                                Textarea::make('content')
                                    ->label(__('login-link::translations.content'))
                                    ->rows(6)
                                    ->columnSpanFull()
                                    ->helperText(__('login-link::translations.content_help')),
                                Select::make('handler_key')
                                    ->label(__('login-link::translations.handler_key'))
                                    ->options(fn (): array => collect(app(RedemptionHandlerRegistry::class)->all())
                                        ->mapWithKeys(fn (string $class, string $key): array => [$key => $key])
                                        ->all())
                                    ->required()
                                    ->native(false),
                                TextInput::make('expiry_minutes')
                                    ->label(__('login-link::translations.expiry_minutes'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText(__('login-link::translations.expiry_minutes_help', [
                                        'default' => (int) config('login-link.expiration_minutes', 60),
                                    ])),
                                Toggle::make('invalidate_prior')
                                    ->label(__('login-link::translations.invalidate_prior'))
                                    ->default(true)
                                    ->helperText(__('login-link::translations.invalidate_prior_help')),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        ...static::getStandardTimestampFields(),
                                    ])
                                    ->hidden(fn (?LoginLinkProcess $record): bool => $record === null),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('login-link::translations.process_title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('login-link::translations.slug'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('context')
                    ->label(__('login-link::translations.context'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('template_key')
                    ->label(__('login-link::translations.template_key'))
                    ->sortable(),
                TextColumn::make('mail_from')
                    ->label(__('login-link::translations.mail_from'))
                    ->toggleable(),
                TextColumn::make('handler_key')
                    ->label(__('login-link::translations.handler_key'))
                    ->sortable(),
                IconColumn::make('invalidate_prior')
                    ->label(__('login-link::translations.invalidate_prior'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('expiry_minutes')
                    ->label(__('login-link::translations.expiry_minutes'))
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? __('login-link::translations.expiry_minutes_default', [
                            'default' => (int) config('login-link.expiration_minutes', 60),
                        ])
                        : (string) $state)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('core::core.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoginLinkProcesses::route('/'),
            'create' => CreateLoginLinkProcess::route('/create'),
            'view' => ViewLoginLinkProcess::route('/{record}'),
            'edit' => EditLoginLinkProcess::route('/{record}/edit'),
        ];
    }
}
