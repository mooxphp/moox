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
use Illuminate\Validation\Rule;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\MailTemplate\Actions\SendMailTemplate;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\CreateMailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\EditMailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\ListMailTemplates;
use Moox\MailTemplate\Support\MailSendConfig;
use Override;

class MailTemplateResource extends BaseDraftResource
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
        $layouts = static::layoutOptions();

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
                                    ->unique(table: 'mail_templates', column: 'slug', ignoreRecord: true),
                                Select::make('layout')
                                    ->label(__('mail-template::translations.layout'))
                                    ->helperText(__('mail-template::translations.layout_help'))
                                    ->options($layouts)
                                    ->searchable()
                                    ->required()
                                    ->rule(Rule::in(array_keys($layouts))),
                                TextInput::make('title')
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
                                        static::getCreatedAtTextEntry(),
                                        static::getUpdatedAtTextEntry(),
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
                TextColumn::make('slug')
                    ->label(__('mail-template::translations.slug'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('layout')
                    ->label(__('mail-template::translations.layout'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('mail-template::translations.subject')),
                TranslationColumn::make('translations.locale'),
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
                    ->fillForm(function (MailTemplate $record): array {
                        $locale = static::resolveSendLocale($record);

                        return [
                            'locale' => $locale,
                            'subject' => static::subjectForLocale($record, $locale),
                        ];
                    })
                    ->schema([
                        Select::make('locale')
                            ->label(__('mail-template::translations.send_locale'))
                            ->options(fn (): array => MailSendConfig::localeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state, MailTemplate $record): void {
                                $locale = trim((string) $state);

                                if ($locale === '') {
                                    return;
                                }

                                $subject = static::subjectForLocale($record, $locale);

                                if ($subject === '') {
                                    return;
                                }

                                $set('subject', $subject);
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
     * @return array<string, string>
     */
    public static function layoutOptions(): array
    {
        $layouts = config('mail-template.layouts', []);

        if (! is_array($layouts)) {
            return [];
        }

        $options = [];

        foreach ($layouts as $key => $label) {
            $view = trim((string) $key);
            $name = trim((string) $label);

            if ($view === '' || $name === '') {
                continue;
            }

            $options[$view] = $name;
        }

        return $options;
    }

    private static function resolveSendLocale(MailTemplate $record): string
    {
        $allowed = MailSendConfig::localeOptions();
        $current = trim((string) app()->getLocale());

        if ($current !== '' && isset($allowed[$current]) && $record->hasTranslation($current)) {
            return $current;
        }

        foreach (array_keys($allowed) as $locale) {
            if ($record->hasTranslation($locale)) {
                return $locale;
            }
        }

        return array_key_first($allowed) ?? 'de_DE';
    }

    private static function subjectForLocale(MailTemplate $record, string $locale): string
    {
        $translation = $record->translate($locale, true);

        if ($translation !== null && filled($translation->title)) {
            return (string) $translation->title;
        }

        return (string) $record->slug;
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
