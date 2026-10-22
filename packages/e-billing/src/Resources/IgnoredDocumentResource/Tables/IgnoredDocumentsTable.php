<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\IgnoredDocumentResource\Tables;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Http\Middleware\SetFilamentPanelFromRequest;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Support\DeliveryFailureReasonLabels;
use Moox\EBilling\Support\IgnoredReasonLabels;
use Moox\MailInbox\Models\InboxAttachment;

final class IgnoredDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(self::filters())
            ->recordActions(self::recordActions());
    }

    /**
     * @return array<int, mixed>
     */
    private static function columns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label(__('e-billing::fields.ignored_received_at'))
                ->dateTime('d.m.Y H:i')
                ->sortable(),
            TextColumn::make('filename')
                ->label(__('e-billing::fields.ignored_source_file'))
                ->getStateUsing(fn (EbillingDocument $record): string => $record->sourceOriginalFilename())
                ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                    'source',
                    [InboxAttachment::class, UploadedPdfSource::class],
                    fn (Builder $sourceQuery, string $type): Builder => $sourceQuery->where(
                        $type === InboxAttachment::class ? 'filename' : 'original_filename',
                        'like',
                        '%'.$search.'%',
                    ),
                )),
            TextColumn::make('gateway_status')
                ->label(__('e-billing::fields.ignored_reason'))
                ->badge()
                ->formatStateUsing(fn (EBillingAttachmentProcessingStatus $state): string => $state->label())
                ->color(fn (EBillingAttachmentProcessingStatus $state): string => $state->color()),
            TextColumn::make('reason_detail')
                ->label(__('e-billing::fields.ignored_reason_detail'))
                ->getStateUsing(fn (EbillingDocument $record): ?string => IgnoredReasonLabels::detail($record))
                ->url(fn (EbillingDocument $record): ?string => EbillingDocument::filamentViewUrlFor(
                    IgnoredReasonLabels::matchedInvoice($record),
                ))
                ->placeholder('—'),
            TextColumn::make('invoice_number')
                ->label(__('e-billing::fields.invoice_number_short'))
                ->getStateUsing(
                    fn (EbillingDocument $record): ?string => self::filledString(
                        $record->getAttribute('bill_invoice_number'),
                    ),
                )
                ->searchable(query: fn (Builder $query, string $search): Builder => $query
                    ->where('bill_data->invoice_number', 'like', '%'.$search.'%'))
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('customer')
                ->label(__('e-billing::fields.ignored_customer'))
                ->getStateUsing(fn (EbillingDocument $record): ?string => $record->customer->customer_name
                    ?? self::filledString($record->getAttribute('bill_customer_name')))
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('relay')
                ->label(__('e-billing::fields.ignored_relay'))
                ->badge()
                ->getStateUsing(function (EbillingDocument $record): ?string {
                    $attempt = $record->latestSourcePdfRelayAttempt;

                    if ($attempt === null) {
                        return null;
                    }

                    return __(
                        $attempt->success
                            ? 'e-billing::fields.ignored_relay_success'
                            : 'e-billing::fields.ignored_relay_failure',
                        ['recipient' => $attempt->recipient],
                    );
                })
                ->color(fn (EbillingDocument $record): string => match (
                    $record->latestSourcePdfRelayAttempt?->success
                ) {
                    true => 'success',
                    false => 'danger',
                    null => 'gray',
                })
                ->tooltip(fn (EbillingDocument $record): ?string => DeliveryFailureReasonLabels::label(
                    $record->latestSourcePdfRelayAttempt?->failure_reason,
                ))
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function filters(): array
    {
        return [
            Filter::make('received_range')
                ->label(__('e-billing::fields.filter_received_at'))
                ->schema([
                    DatePicker::make('from')->label(__('e-billing::fields.filter_from'))->native(false),
                    DatePicker::make('until')->label(__('e-billing::fields.filter_until'))->native(false),
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
            Filter::make('relay_failed')
                ->label(__('e-billing::fields.filter_relay_failed'))
                ->toggle()
                ->query(fn (Builder $query): Builder => $query->whereLatestSourcePdfRelayFailed()),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function recordActions(): array
    {
        return [
            Action::make('preview_pdf')
                ->label(__('e-billing::fields.action_preview_source_pdf'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(fn (EbillingDocument $record): string => route('ebilling.pdf.preview', [
                    'document' => $record,
                    SetFilamentPanelFromRequest::QUERY_KEY => Filament::getCurrentPanel()?->getId()
                        ?? Filament::getDefaultPanel()->getId(),
                ]))
                ->openUrlInNewTab()
                ->visible(fn (EbillingDocument $record): bool => filled($record->sourceStoragePath())),
            Action::make('open_inbox_message')
                ->label(__('e-billing::fields.action_open_inbox_message'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(fn (EbillingDocument $record): ?string => EbillingDocument::filamentViewUrlFor(
                    $record->inboxAttachment()?->message,
                ))
                ->visible(fn (EbillingDocument $record): bool => $record->inboxAttachment() !== null),
        ];
    }

    private static function filledString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
