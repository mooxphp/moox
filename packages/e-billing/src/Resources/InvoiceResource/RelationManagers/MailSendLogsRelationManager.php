<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Models\Invoice;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Support\MailSendStatusPresenter;
use Moox\MailOutbox\Support\RelatedRecordUrlResolver;
use Override;

/**
 * Read-only outbound mail send logs related to the invoice (or its ebilling document).
 *
 * Optional: only registered when moox/mail-outbox is installed. No composer hard-dep.
 *
 * Must return an Eloquent {@see Relation} (not a bare Builder): Filament calls
 * `$relationship->getQuery()`, and on a Builder that yields Query\Builder → TypeError.
 */
class MailSendLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'mailSendLogs';

    protected static bool $shouldSkipAuthorization = true;

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('e-billing::fields.section_mail_send_logs');
    }

    #[Override]
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return class_exists(MailSendLog::class);
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    /**
     * @return MorphMany<MailSendLog, Invoice>
     */
    #[Override]
    public function getRelationship(): Relation|Builder
    {
        $owner = $this->getOwnerRecord();
        assert($owner instanceof Invoice);

        /** @var MorphMany<MailSendLog, Invoice> $relation */
        $relation = $owner->mailSendLogs();

        return $relation;
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->modifyQueryUsing(function (Builder $query): void {
                $owner = $this->getOwnerRecord();
                assert($owner instanceof Invoice);

                $document = $owner->ebillingDocument;

                if (! $document instanceof EbillingDocument) {
                    return;
                }

                // morphMany already scopes to the invoice; OR in document-linked logs.
                $query->orWhere(function (Builder $inner) use ($document): void {
                    $inner->where('related_type', $document->getMorphClass())
                        ->where('related_id', (string) $document->getKey());
                });
            })
            ->columns([
                TextColumn::make('status')
                    ->label(__('mail-outbox::fields.status'))
                    ->badge()
                    ->formatStateUsing(
                        fn (?MailSendStatus $state): string => MailSendStatusPresenter::label($state)
                    )
                    ->color(fn (?MailSendStatus $state): string => MailSendStatusPresenter::color($state))
                    ->sortable(),
                TextColumn::make('recipient')
                    ->label(__('mail-outbox::fields.recipient'))
                    ->state(fn (MailSendLog $record): string => $record->primaryRecipientLabel()),
                TextColumn::make('subject')
                    ->label(__('mail-outbox::fields.subject'))
                    ->limit(60)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('created_at')
                    ->label(__('mail-outbox::fields.sent_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (MailSendLog $record): ?string => RelatedRecordUrlResolver::forModel($record))
                    ->visible(fn (MailSendLog $record): bool => RelatedRecordUrlResolver::forModel($record) !== null)
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('e-billing::fields.mail_send_logs_empty'));
    }
}
