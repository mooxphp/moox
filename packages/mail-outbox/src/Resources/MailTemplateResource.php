<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\MailOutbox\Models\MailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource\Pages\CreateMailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource\Pages\EditMailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource\Pages\ListMailTemplates;
use Override;

class MailTemplateResource extends BaseRecordResource
{
    protected static ?string $model = MailTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    #[Override]
    protected static function getEntityType(): string
    {
        return 'mail-outbox';
    }

    #[Override]
    public static function enableView(): bool
    {
        return false;
    }

    #[Override]
    public static function enableRestore(): bool
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
        $views = config('mail-outbox.views', []);

        return $form
            ->components([
                Grid::make()
                    ->schema([
                        Section::make(__('mail-outbox::translations.identity'))
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('mail-outbox::translations.key'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('locale')
                                    ->label(__('mail-outbox::translations.locale'))
                                    ->required()
                                    ->maxLength(8)
                                    ->default('de'),
                                Select::make('view')
                                    ->label(__('mail-outbox::translations.view'))
                                    ->helperText(__('mail-outbox::translations.view_help'))
                                    ->options($views)
                                    ->searchable()
                                    ->required(),
                                TextInput::make('brand_name')
                                    ->label(__('mail-outbox::translations.brand_name'))
                                    ->maxLength(255),
                                FileUpload::make('logo_path')
                                    ->label(__('mail-outbox::translations.logo'))
                                    ->helperText(__('mail-outbox::translations.logo_help'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('mail-templates')
                                    ->visibility('public'),
                                Textarea::make('mail_content')
                                    ->label(__('mail-outbox::translations.mail_content'))
                                    ->helperText(__('mail-outbox::translations.mail_content_help'))
                                    ->rows(10),
                                Textarea::make('footer')
                                    ->label(__('mail-outbox::translations.footer'))
                                    ->helperText(__('mail-outbox::translations.footer_help'))
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
                                        Section::make('')
                                            ->schema([
                                                ...static::getStandardTimestampFields(),
                                            ]),
                                    ])
                                    ->hidden(fn (?MailTemplate $record) => $record === null),
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
                TextColumn::make('key')
                    ->label(__('mail-outbox::translations.key'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->label(__('mail-outbox::translations.locale'))
                    ->sortable(),
                TextColumn::make('view')
                    ->label(__('mail-outbox::translations.view'))
                    ->searchable(),
                TextColumn::make('brand_name')
                    ->label(__('mail-outbox::translations.brand_name')),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('mail-outbox::translations.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(function (MailTemplate $record): string {
                        $panel = filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel();

                        return $panel->route('mail-templates.preview', [
                            'mailTemplate' => $record,
                        ]);
                    })
                    ->openUrlInNewTab(),
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
            'index' => ListMailTemplates::route('/'),
            'create' => CreateMailTemplate::route('/create'),
            'edit' => EditMailTemplate::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('mail-outbox.resources.mail-template.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('mail-outbox.resources.mail-template.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('mail-outbox.resources.mail-template.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('mail-outbox.resources.mail-template.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('mail-outbox.navigation_group');
    }
}
