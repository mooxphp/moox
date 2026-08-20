<?php

declare(strict_types=1);

namespace Moox\MailInbox\Resources;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Resources\InboxMessageResource\Pages\ListInboxMessages;
use Moox\MailInbox\Resources\InboxMessageResource\Pages\ViewInboxMessage;
use Moox\MailInbox\Support\InboxProcessingStatusPresenter;

final class InboxMessageResource extends BaseItemResource
{
    protected static ?string $model = InboxMessage::class;

    protected static ?string $slug = 'inbox-messages';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static ?int $navigationSort = 20;

    public static function enableCreate(): bool
    {
        return false;
    }

    public static function enableEdit(): bool
    {
        return false;
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    protected static function modifyEloquentQuery(Builder $query): Builder
    {
        return parent::modifyEloquentQuery($query)->withCount('attachments');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                            TextEntry::make('processing_status')
                                ->label(__('mail-inbox::fields.processing_status'))
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string => InboxProcessingStatusPresenter::messageLabel($state))
                                ->color(fn (?string $state): string => InboxProcessingStatusPresenter::messageColor($state)),
                            TextEntry::make('scope')
                                ->label(__('mail-inbox::fields.scope')),
                            TextEntry::make('received_at')
                                ->label(__('mail-inbox::fields.received_at'))
                                ->dateTime(),
                            TextEntry::make('from_email')
                                ->label(__('mail-inbox::fields.from'))
                                ->formatStateUsing(fn (InboxMessage $record): string => filled($record->from_name)
                                    ? "{$record->from_name} <{$record->from_email}>"
                                    : (string) ($record->from_email ?? '—')),
                            TextEntry::make('to_email')
                                ->label(__('mail-inbox::fields.to'))
                                ->placeholder('—'),
                            TextEntry::make('subject')
                                ->label(__('mail-inbox::fields.subject'))
                                ->columnSpanFull(),
                        ]),
                    ]),
                Section::make(__('mail-inbox::fields.error_message'))
                    ->schema([
                        TextEntry::make('error_message')
                            ->hiddenLabel()
                            ->placeholder(__('mail-inbox::fields.no_error'))
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (InboxMessage $record): bool => filled($record->error_message)),
                Section::make(__('mail-inbox::fields.attachments'))
                    ->schema([
                        TextEntry::make('no_attachments')
                            ->hiddenLabel()
                            ->state(__('mail-inbox::fields.no_attachments'))
                            ->color('gray')
                            ->visible(fn (InboxMessage $record): bool => $record->attachments->isEmpty()),
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->state(fn (InboxMessage $record): array => $record->attachments->all())
                            ->visible(fn (InboxMessage $record): bool => $record->attachments->isNotEmpty())
                            ->table([
                                TableColumn::make(__('mail-inbox::fields.filename')),
                                TableColumn::make(__('mail-inbox::fields.attachment_status')),
                                TableColumn::make(__('mail-inbox::fields.filesize')),
                                TableColumn::make(__('mail-inbox::fields.error_message')),
                                TableColumn::make(__('mail-inbox::fields.processed_at')),
                            ])
                            ->schema([
                                TextEntry::make('filename')
                                    ->weight('medium'),
                                TextEntry::make('processing_status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => InboxProcessingStatusPresenter::attachmentLabel($state))
                                    ->color(fn (?string $state): string => InboxProcessingStatusPresenter::attachmentColor($state)),
                                TextEntry::make('filesize')
                                    ->formatStateUsing(fn (?int $state): string => $state !== null
                                        ? number_format($state / 1024, 1).' KB'
                                        : '—'),
                                TextEntry::make('error_message')
                                    ->placeholder('—')
                                    ->color('danger'),
                                TextEntry::make('processed_at')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ]),
                Section::make(__('mail-inbox::fields.body_preview'))
                    ->collapsed()
                    ->schema([
                        TextEntry::make('body_preview')
                            ->hiddenLabel()
                            ->state(fn (InboxMessage $record): ?string => self::bodyPreviewFor($record))
                            ->placeholder('—')
                            ->wrap()
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => 'whitespace-pre-wrap max-h-96 overflow-y-auto font-mono text-sm',
                            ]),
                    ]),
            ]);
    }

    /**
     * Prefer plain text; many provider payloads only store HTML — strip tags for a safe preview.
     */
    private static function bodyPreviewFor(InboxMessage $record): ?string
    {
        if (filled($record->raw_body_text)) {
            return $record->raw_body_text;
        }

        if (! filled($record->raw_body_html)) {
            return null;
        }

        $plain = html_entity_decode(
            strip_tags($record->raw_body_html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $plain = preg_replace("/[ \t]+/u", ' ', $plain) ?? $plain;
        $plain = preg_replace("/\n{3,}/u", "\n\n", $plain) ?? $plain;
        $plain = trim($plain);

        return $plain !== '' ? $plain : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('processing_status')
                    ->label(__('mail-inbox::fields.processing_status'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => InboxProcessingStatusPresenter::messageLabel($state))
                    ->color(fn (?string $state): string => InboxProcessingStatusPresenter::messageColor($state)),
                TextColumn::make('scope')
                    ->label(__('mail-inbox::fields.scope'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label(__('mail-inbox::fields.subject'))
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('from_email')
                    ->label(__('mail-inbox::fields.from'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('received_at')
                    ->label(__('mail-inbox::fields.received_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('attachments_count')
                    ->label(__('mail-inbox::fields.attachments'))
                    ->sortable()
                    ->alignment('end'),
                TextColumn::make('error_message')
                    ->label(__('mail-inbox::fields.error_message'))
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label(__('mail-inbox::fields.filter_scope'))
                    ->options(function (): array {
                        $mailboxes = config('mail-inbox.mailboxes', []);

                        if (! is_array($mailboxes)) {
                            return [];
                        }

                        return collect(array_keys($mailboxes))
                            ->mapWithKeys(fn (string $scope): array => [$scope => $scope])
                            ->all();
                    }),
                SelectFilter::make('processing_status')
                    ->label(__('mail-inbox::fields.filter_status'))
                    ->options(collect(InboxMessageProcessingStatus::cases())
                        ->mapWithKeys(fn (InboxMessageProcessingStatus $case): array => [
                            $case->value => InboxProcessingStatusPresenter::messageLabel($case->value),
                        ])
                        ->all()),
                Filter::make('received_at_range')
                    ->label(__('mail-inbox::fields.received_at'))
                    ->schema([
                        DatePicker::make('from')->label(__('mail-inbox::fields.filter_from'))->native(false),
                        DatePicker::make('until')->label(__('mail-inbox::fields.filter_until'))->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['from'] ?? null)) {
                            $query->whereDate('received_at', '>=', $data['from']);
                        }

                        if (filled($data['until'] ?? null)) {
                            $query->whereDate('received_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->recordUrl(fn (InboxMessage $record): string => self::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label(__('mail-inbox::fields.action_view')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInboxMessages::route('/'),
            'view' => ViewInboxMessage::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationSort(): ?int
    {
        return self::$navigationSort;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = InboxMessage::query()->failed()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('mail-inbox.resources.inbox-messages.single'));
    }

    public static function getPluralModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('mail-inbox.resources.inbox-messages.plural'));
    }

    public static function getNavigationLabel(): string
    {
        return self::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return self::resolveConfigLabel((string) config('mail-inbox.navigation_group'));
    }

    private static function resolveConfigLabel(string $value): string
    {
        if (str_starts_with($value, 'trans//')) {
            return __(substr($value, 7));
        }

        return $value;
    }
}
