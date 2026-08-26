<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\MailOutbox\Actions\SendMailTemplate;
use Moox\MailOutbox\Models\MailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource\Pages\CreateMailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource\Pages\EditMailTemplate;
use Moox\MailOutbox\Resources\MailTemplateResource\Pages\ListMailTemplates;
use Moox\MailOutbox\Support\MailSendConfig;
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
                                TextInput::make('subject')
                                    ->label(__('mail-outbox::translations.subject'))
                                    ->required()
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
                Action::make('send')
                    ->label(__('mail-outbox::translations.send'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color(fn (): string => MailSendConfig::recipients() === [] ? 'gray' : 'primary')
                    ->modalHeading(__('mail-outbox::translations.send_heading'))
                    ->modalDescription(fn (): string => __('mail-outbox::translations.send_description', [
                        'mailer' => (string) config('mail.default'),
                        'from' => (string) config('mail.from.address'),
                    ]))
                    ->modalSubmitActionLabel(__('mail-outbox::translations.send_submit'))
                    ->disabled(fn (): bool => MailSendConfig::recipients() === [])
                    ->tooltip(fn (): ?string => MailSendConfig::recipients() === []
                        ? __('mail-outbox::translations.send_no_recipients')
                        : null)
                    ->fillForm(fn (MailTemplate $record): array => [
                        'locale' => (string) $record->locale,
                        'subject' => filled($record->subject)
                            ? (string) $record->subject
                            : (string) ($record->brand_name ?: $record->key),
                    ])
                    ->schema([
                        Select::make('locale')
                            ->label(__('mail-outbox::translations.send_locale'))
                            ->options(fn (): array => MailSendConfig::localeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state, MailTemplate $record): void {
                                $locale = strtolower(trim((string) $state));

                                if ($locale === '') {
                                    return;
                                }

                                $sibling = MailTemplate::query()
                                    ->where('key', $record->key)
                                    ->where('locale', $locale)
                                    ->first();

                                if ($sibling === null || ! filled($sibling->subject)) {
                                    return;
                                }

                                $set('subject', (string) $sibling->subject);
                            }),
                        CheckboxList::make('emails')
                            ->label(__('mail-outbox::translations.send_recipients'))
                            ->helperText(__('mail-outbox::translations.send_recipients_help'))
                            ->options(fn (): array => MailSendConfig::recipientOptions())
                            ->required()
                            ->minItems(1),
                        TextInput::make('subject')
                            ->label(__('mail-outbox::translations.subject'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (MailTemplate $record, array $data): void {
                        $emails = $data['emails'] ?? [];
                        $subject = trim((string) ($data['subject'] ?? ''));
                        $locale = isset($data['locale']) ? (string) $data['locale'] : null;

                        if (! is_array($emails) || $emails === [] || $subject === '') {
                            Notification::make()
                                ->danger()
                                ->title(__('mail-outbox::translations.send_nothing_selected'))
                                ->send();

                            return;
                        }

                        /** @var list<string> $emails */
                        $result = app(SendMailTemplate::class)->handle($record, $emails, $subject, $locale);

                        static::notifySendResult($result);
                    }),
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

    /**
     * @param  array{sent: list<string>, failed: array<string, string>}  $result
     */
    private static function notifySendResult(array $result): void
    {
        $sentCount = count($result['sent']);
        $failedCount = count($result['failed']);
        $failedAddresses = implode(', ', array_keys($result['failed']));

        if ($sentCount > 0 && $failedCount === 0) {
            Notification::make()
                ->success()
                ->title(__('mail-outbox::translations.send_success_title'))
                ->body(__('mail-outbox::translations.send_success_body', ['count' => $sentCount]))
                ->send();

            return;
        }

        if ($sentCount > 0) {
            Notification::make()
                ->warning()
                ->title(__('mail-outbox::translations.send_partial_title'))
                ->body(__('mail-outbox::translations.send_partial_body', [
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'addresses' => $failedAddresses,
                ]))
                ->send();

            return;
        }

        Notification::make()
            ->danger()
            ->title(__('mail-outbox::translations.send_failed_title'))
            ->body($failedAddresses === ''
                ? __('mail-outbox::translations.send_nothing_selected')
                : __('mail-outbox::translations.send_failed_body', ['addresses' => $failedAddresses]))
            ->send();
    }
}
