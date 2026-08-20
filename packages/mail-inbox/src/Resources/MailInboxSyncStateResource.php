<?php

declare(strict_types=1);

namespace Moox\MailInbox\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\MailInbox\Models\MailInboxSyncState;
use Moox\MailInbox\Resources\MailInboxSyncStateResource\Pages\ListMailInboxSyncStates;
use Moox\MailInbox\Support\InboxProcessingStatusPresenter;

final class MailInboxSyncStateResource extends BaseItemResource
{
    protected static ?string $model = MailInboxSyncState::class;

    protected static ?string $slug = 'mail-inbox-sync-states';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

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
        return false;
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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->defaultSort('scope')
            ->columns([
                TextColumn::make('scope')
                    ->label(__('mail-inbox::fields.scope'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mailbox_address')
                    ->label(__('mail-inbox::fields.mailbox_address'))
                    ->getStateUsing(
                        function (MailInboxSyncState $record): string {
                            return InboxProcessingStatusPresenter::mailboxAddressForScope($record->scope) ?? '—';
                        }
                    )
                    ->toggleable(),
                TextColumn::make('driver')
                    ->label(__('mail-inbox::fields.driver'))
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('catch_up_in_progress')
                    ->label(__('mail-inbox::fields.catch_up'))
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedArrowPath)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (MailInboxSyncState $record): string => $record->catch_up_in_progress
                        ? __('mail-inbox::fields.catch_up_in_progress')
                        : __('mail-inbox::fields.catch_up_idle')),
                TextColumn::make('last_synced_at')
                    ->label(__('mail-inbox::fields.last_synced_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('cursor_reset_at')
                    ->label(__('mail-inbox::fields.cursor_reset_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->color(function (MailInboxSyncState $record): ?string {
                        $warningMinutes = max(1, (int) config('mail-inbox.cursor_reset_warning_minutes', 60));

                        if ($record->cursor_reset_at === null) {
                            return null;
                        }

                        return $record->cursor_reset_at->greaterThan(now()->subMinutes($warningMinutes))
                            ? 'warning'
                            : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('delta_link')
                    ->label(__('mail-inbox::fields.sync_cursor'))
                    ->fontFamily('mono')
                    ->limit(80)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->copyable(fn (?string $state): bool => filled($state))
                    ->copyableState(fn (?string $state): ?string => $state)
                    ->formatStateUsing(
                        fn (?string $state): string => InboxProcessingStatusPresenter::truncateDiagnosticBlob(
                            $state,
                            80
                        ) ?? '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailInboxSyncStates::route('/'),
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

    public static function getModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('mail-inbox.resources.sync-states.single'));
    }

    public static function getPluralModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('mail-inbox.resources.sync-states.plural'));
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
