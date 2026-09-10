<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Resources\MailLayoutResource\Pages\CreateMailLayout;
use Moox\MailTemplate\Resources\MailLayoutResource\Pages\EditMailLayout;
use Moox\MailTemplate\Resources\MailLayoutResource\Pages\ListMailLayouts;
use Override;

class MailLayoutResource extends BaseDraftResource
{
    protected static ?string $model = MailLayout::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    #[Override]
    protected static function getEntityType(): string
    {
        return 'mail-template';
    }

    #[Override]
    public static function enableView(): bool
    {
        return false;
    }

    #[Override]
    public static function enablePublish(): bool
    {
        return false;
    }

    #[Override]
    public static function getCancelAction(): Action
    {
        return parent::getCancelAction()
            ->url(fn (): string => static::getUrl('index'));
    }

    #[Override]
    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Grid::make()
                    ->schema([
                        Section::make(__('mail-template::translations.identity'))
                            ->schema([
                                TextInput::make('slug')
                                    ->label(__('mail-template::translations.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(table: 'mail_layouts', column: 'slug', ignoreRecord: true),
                                TextInput::make('title')
                                    ->label(__('mail-template::translations.layout_title'))
                                    ->required()
                                    ->maxLength(255),
                                FileUpload::make('logo')
                                    ->label(__('mail-template::translations.logo'))
                                    ->helperText(__('mail-template::translations.layout_logo_help'))
                                    ->image()
                                    ->imagePreviewHeight('80')
                                    ->disk('public')
                                    ->directory('mail-layouts')
                                    ->visibility('public'),
                                Grid::make(3)
                                    ->schema([
                                        ColorPicker::make('background_color')
                                            ->label(__('mail-template::translations.background_color'))
                                            ->hex()
                                            ->required()
                                            ->default('#ECF2F6'),
                                        ColorPicker::make('button_color')
                                            ->label(__('mail-template::translations.button_color'))
                                            ->hex()
                                            ->required()
                                            ->default('#005CA3'),
                                        ColorPicker::make('text_color')
                                            ->label(__('mail-template::translations.text_color'))
                                            ->hex()
                                            ->required()
                                            ->default('#000000'),
                                    ]),
                                Textarea::make('footer')
                                    ->label(__('mail-template::translations.footer'))
                                    ->helperText(__('mail-template::translations.layout_footer_help'))
                                    ->rows(8),
                            ])
                            ->columns(1)
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        static::getCreatedAtTextEntry(),
                                        static::getUpdatedAtTextEntry(),
                                    ])
                                    ->hidden(fn (?MailLayout $record) => $record === null),
                            ])
                            ->columnSpan(1)
                            ->columns(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label(__('mail-template::translations.slug'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('mail-template::translations.layout_title')),
                TranslationColumn::make('translations.locale'),
            ])
            ->recordActions([
                ...static::getTableActions(),
            ])
            ->toolbarActions([
                ...static::getBulkActions(),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListMailLayouts::route('/'),
            'create' => CreateMailLayout::route('/create'),
            'edit' => EditMailLayout::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('mail-template.resources.mail-layout.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('mail-template.resources.mail-layout.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('mail-template.resources.mail-layout.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('mail-template.resources.mail-layout.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('mail-template.navigation_group');
    }
}
