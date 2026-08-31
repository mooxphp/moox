<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
use Moox\MailOutbox\Enums\MailSendSource;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Resources\MailSendLogResource\Pages\ListMailSendLogs;
use Moox\MailOutbox\Resources\MailSendLogResource\Pages\ViewMailSendLog;
use Moox\MailOutbox\Support\MailSendStatusPresenter;
use Moox\MailOutbox\Support\RelatedRecordUrlResolver;

final class MailSendLogResource extends BaseItemResource
{
    protected static ?string $model = MailSendLog::class;

    protected static ?string $slug = 'mail-send-logs';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?int $navigationSort = 21;

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
                            TextEntry::make('status')
                                ->label(__('mail-outbox::fields.status'))
                                ->badge()
                                ->formatStateUsing(
                                    fn (?MailSendStatus $state): string => MailSendStatusPresenter::label($state)
                                )
                                ->color(
                                    fn (?MailSendStatus $state): string => MailSendStatusPresenter::color($state)
                                ),
                            TextEntry::make('mailer')
                                ->label(__('mail-outbox::fields.mailer')),
                            TextEntry::make('source')
                                ->label(__('mail-outbox::fields.source'))
                                ->formatStateUsing(
                                    fn (?MailSendSource $state): string => MailSendStatusPresenter::sourceLabel($state)
                                ),
                            TextEntry::make('created_at')
                                ->label(__('mail-outbox::fields.sent_at'))
                                ->dateTime(),
                            TextEntry::make('subject')
                                ->label(__('mail-outbox::fields.subject'))
                                ->columnSpanFull(),
                            TextEntry::make('redirected_badge')
                                ->label(__('mail-outbox::fields.delivery'))
                                ->badge()
                                ->state(fn (MailSendLog $record): string => $record->isRedirected()
                                    ? __('mail-outbox::fields.redirected')
                                    : __('mail-outbox::fields.direct'))
                                ->color(fn (MailSendLog $record): string => $record->isRedirected() ? 'warning' : 'success')
                                ->visible(fn (MailSendLog $record): bool => $record->status === MailSendStatus::Sent
                                    || $record->status === MailSendStatus::Suppressed),
                        ]),
                    ]),
                Section::make(__('mail-outbox::fields.recipients'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextEntry::make('intended_recipients')
                                ->label(__('mail-outbox::fields.intended_recipients'))
                                ->state(fn (MailSendLog $record): string => self::formatRecipients($record->intended_recipients))
                                ->placeholder('—'),
                            TextEntry::make('actual_recipients')
                                ->label(__('mail-outbox::fields.actual_recipients'))
                                ->state(fn (MailSendLog $record): string => self::formatRecipients($record->actual_recipients))
                                ->placeholder('—'),
                        ]),
                    ]),
                Section::make(__('mail-outbox::fields.identifiers'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextEntry::make('message_id')
                                ->label(__('mail-outbox::fields.message_id'))
                                ->placeholder('—')
                                ->copyable(),
                            TextEntry::make('correlation_id')
                                ->label(__('mail-outbox::fields.correlation_id'))
                                ->placeholder('—')
                                ->copyable(),
                            TextEntry::make('provider_reference')
                                ->label(__('mail-outbox::fields.provider_reference'))
                                ->placeholder('—')
                                ->copyable(),
                        ]),
                    ]),
                Section::make(__('mail-outbox::fields.related_record'))
                    ->schema([
                        TextEntry::make('related')
                            ->label(__('mail-outbox::fields.related_record'))
                            ->formatStateUsing(fn (MailSendLog $record): string => $record->related
                                ? (string) ($record->related->getAttribute('name')
                                    ?? $record->related->getAttribute('title')
                                    ?? class_basename($record->related_type).' #'.$record->related_id)
                                : '—')
                            ->url(fn (MailSendLog $record): ?string => RelatedRecordUrlResolver::forModel($record->related))
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->placeholder('—'),
                    ])
                    ->visible(fn (MailSendLog $record): bool => $record->related_id !== null),
                Section::make(__('mail-outbox::fields.error'))
                    ->schema([
                        TextEntry::make('error')
                            ->hiddenLabel()
                            ->placeholder(__('mail-outbox::fields.no_error'))
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (MailSendLog $record): bool => filled($record->error)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')
                    ->label(__('mail-outbox::fields.status'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(
                        fn (?MailSendStatus $state): string => MailSendStatusPresenter::label($state)
                    )
                    ->color(fn (?MailSendStatus $state): string => MailSendStatusPresenter::color($state)),
                TextColumn::make('mailer')
                    ->label(__('mail-outbox::fields.mailer'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('recipient')
                    ->label(__('mail-outbox::fields.recipient'))
                    ->state(fn (MailSendLog $record): string => $record->primaryRecipientLabel())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $inner) use ($search): void {
                            $inner->where('intended_recipients', 'like', '%'.$search.'%')
                                ->orWhere('actual_recipients', 'like', '%'.$search.'%');
                        });
                    }),
                TextColumn::make('redirected')
                    ->label(__('mail-outbox::fields.redirected'))
                    ->badge()
                    ->state(fn (MailSendLog $record): ?string => $record->isRedirected()
                        ? __('mail-outbox::fields.redirected')
                        : null)
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('subject')
                    ->label(__('mail-outbox::fields.subject'))
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('created_at')
                    ->label(__('mail-outbox::fields.sent_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('error')
                    ->label(__('mail-outbox::fields.error'))
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('mail-outbox::fields.filter_status'))
                    ->options(collect(MailSendStatus::cases())
                        ->mapWithKeys(fn (MailSendStatus $case): array => [
                            $case->value => MailSendStatusPresenter::label($case),
                        ])
                        ->all()),
                SelectFilter::make('mailer')
                    ->label(__('mail-outbox::fields.filter_mailer'))
                    ->options(fn (): array => MailSendLog::query()
                        ->distinct()
                        ->orderBy('mailer')
                        ->pluck('mailer', 'mailer')
                        ->all()),
                Filter::make('sent_at_range')
                    ->label(__('mail-outbox::fields.sent_at'))
                    ->schema([
                        DatePicker::make('from')->label(__('mail-outbox::fields.filter_from'))->native(false),
                        DatePicker::make('until')->label(__('mail-outbox::fields.filter_until'))->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['from'] ?? null)) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }

                        if (filled($data['until'] ?? null)) {
                            $query->whereDate('created_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->recordUrl(fn (MailSendLog $record): string => self::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label(__('mail-outbox::fields.action_view')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailSendLogs::route('/'),
            'view' => ViewMailSendLog::route('/{record}'),
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
        $count = MailSendLog::query()
            ->where('status', MailSendStatus::Failed)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('mail-outbox.resources.send-logs.single'));
    }

    public static function getPluralModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('mail-outbox.resources.send-logs.plural'));
    }

    public static function getNavigationLabel(): string
    {
        return self::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return self::resolveConfigLabel((string) config('mail-outbox.navigation_group'));
    }

    /**
     * @param  list<string>|null|string  $recipients
     */
    private static function formatRecipients(array|string|null $recipients): string
    {
        if (is_string($recipients)) {
            $decoded = json_decode($recipients, true);

            $recipients = is_array($decoded) ? $decoded : null;
        }

        if ($recipients === null || $recipients === []) {
            return '—';
        }

        return implode(', ', $recipients);
    }

    private static function resolveConfigLabel(string $value): string
    {
        if (str_starts_with($value, 'trans//')) {
            return __(substr($value, 7));
        }

        return $value;
    }
}
