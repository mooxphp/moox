<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\CreatePage;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\EditPage;
use Moox\LoginLink\Resources\LoginLinkProcessResource\Pages\ListPage;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Override;

class LoginLinkProcessResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = LoginLinkProcess::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-settings-suggest-o';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextInput::make('mail_from')
                    ->label(__('login-link::translations.mail_from'))
                    ->email()
                    ->maxLength(255),
                Textarea::make('content')
                    ->label(__('login-link::translations.content'))
                    ->rows(10)
                    ->columnSpanFull(),
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
            ]);
    }

    #[Override]
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
                TextColumn::make('mail_from')
                    ->label(__('login-link::translations.mail_from'))
                    ->toggleable(),
                TextColumn::make('handler_key')
                    ->label(__('login-link::translations.handler_key'))
                    ->sortable(),
                TextColumn::make('expiry_minutes')
                    ->label(__('login-link::translations.expiry_minutes'))
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? __('login-link::translations.expiry_minutes_default', [
                            'default' => (int) config('login-link.expiration_minutes', 60),
                        ])
                        : (string) $state)
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('login-link.resources.process.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('login-link.resources.process.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('login-link.resources.process.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('login-link.resources.process.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('login-link.navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return 10;
    }
}
