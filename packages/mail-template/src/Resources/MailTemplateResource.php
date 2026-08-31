<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources;

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
use Moox\MailTemplate\Actions\SendMailTemplate;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\CreateMailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\EditMailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\ListMailTemplates;
use Moox\MailTemplate\Support\MailSendConfig;
use Override;

class MailTemplateResource extends BaseRecordResource
{
    protected static ?string $model = MailTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

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
        $views = config('mail-template.views', []);

        return $form
            ->components([
                Grid::make()
                    ->schema([
                        Section::make(__('mail-template::translations.identity'))
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('mail-template::translations.key'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('locale')
                                    ->label(__('mail-template::translations.locale'))
                                    ->required()
                                    ->maxLength(8)
                                    ->default('de'),
                                Select::make('view')
                                    ->label(__('mail-template::translations.view'))
                                    ->helperText(__('mail-template::translations.view_help'))
                                    ->options($views)
                                    ->searchable()
                                    ->required(),
                                TextInput::make('brand_name')
                                    ->label(__('mail-template::translations.brand_name'))
                                    ->maxLength(255),
                                TextInput::make('subject')
                                    ->label(__('mail-template::translations.subject'))
                                    ->required()
                                    ->maxLength(255),
                                FileUpload::make('logo_path')
                                    ->label(__('mail-template::translations.logo'))
                                    ->helperText(__('mail-template::translations.logo_help'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('mail-templates')
                                    ->visibility('public'),
                                Textarea::make('mail_content')
                                    ->label(__('mail-template::translations.mail_content'))
                                    ->helperText(__('mail-template::translations.mail_content_help'))
                                    ->rows(10),
                                Textarea::make('footer')
                                    ->label(__('mail-template::translations.footer'))
                                    ->helperText(__('mail-template::translations.footer_help'))
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
                    ->label(__('mail-template::translations.key'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->label(__('mail-template::translations.locale'))
                    ->sortable(),
                TextColumn::make('view')
                    ->label(__('mail-template::translations.view'))
                    ->searchable(),
                TextColumn::make('brand_name')
                    ->label(__('mail-template::translations.brand_name')),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('mail-template::translations.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(function (MailTemplate $record): string {
                        $panel = filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel();

                        return $panel->route('mail-templates.preview', [
                            'mailTemplate' => $record,
                        ]);
                    })
                    ->openUrlInNewTab(),
                Action::make('send')
                    ->label(__('mail-template::translations.send'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color(fn (): string => MailSendConfig::recipients() === [] ? 'gray' : 'primary')
                    ->modalHeading(__('mail-template::translations.send_heading'))
                    ->modalDescription(fn (): string => __('mail-template::translations.send_description', [
                        'mailer' => (string) config('mail.default'),
                        'from' => (string) config('mail.from.address'),
                    ]))
                    ->modalSubmitActionLabel(__('mail-template::translations.send_submit'))
                    ->disabled(fn (): bool => MailSendConfig::recipients() === [])
                    ->tooltip(fn (): ?string => MailSendConfig::recipients() === []
                        ? __('mail-template::translations.send_no_recipients')
                        : null)
                    ->fillForm(fn (MailTemplate $record): array => [
                        'locale' => (string) $record->locale,
                        'subject' => filled($record->subject)
                            ? (string) $record->subject
                            : (string) ($record->brand_name ?: $record->key),
                    ])
                    ->schema([
                        Select::make('locale')
                            ->label(__('mail-template::translations.send_locale'))
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
                            ->label(__('mail-template::translations.send_recipients'))
                            ->helperText(__('mail-template::translations.send_recipients_help'))
                            ->options(fn (): array => MailSendConfig::recipientOptions())
                            ->required()
                            ->minItems(1),
                        TextInput::make('subject')
                            ->label(__('mail-template::translations.subject'))
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
                                ->title(__('mail-template::translations.send_nothing_selected'))
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
        return config('mail-template.resources.mail-template.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('mail-template.resources.mail-template.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('mail-template.resources.mail-template.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('mail-template.resources.mail-template.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('mail-template.navigation_group');
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
                ->title(__('mail-template::translations.send_success_title'))
                ->body(__('mail-template::translations.send_success_body', ['count' => $sentCount]))
                ->send();

            return;
        }

        if ($sentCount > 0) {
            Notification::make()
                ->warning()
                ->title(__('mail-template::translations.send_partial_title'))
                ->body(__('mail-template::translations.send_partial_body', [
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'addresses' => $failedAddresses,
                ]))
                ->send();

            return;
        }

        Notification::make()
            ->danger()
            ->title(__('mail-template::translations.send_failed_title'))
            ->body($failedAddresses === ''
                ? __('mail-template::translations.send_nothing_selected')
                : __('mail-template::translations.send_failed_body', ['addresses' => $failedAddresses]))
            ->send();
    }
}
