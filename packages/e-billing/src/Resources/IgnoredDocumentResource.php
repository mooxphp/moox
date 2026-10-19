<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources;

use Closure;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Resources\Concerns\ReadsEBillingResourceConfig;
use Moox\EBilling\Resources\IgnoredDocumentResource\Pages\ListIgnoredDocuments;
use Moox\EBilling\Resources\IgnoredDocumentResource\Tables\IgnoredDocumentsTable;
use Moox\MailInbox\Models\InboxAttachment;

/**
 * Read-only list of documents the gateway deliberately did not turn into an invoice
 * ({@see EBillingAttachmentProcessingStatus::ignoredCases()}). They have no invoice row,
 * so the invoice list cannot show them.
 */
class IgnoredDocumentResource extends BaseItemResource
{
    use ReadsEBillingResourceConfig;

    protected static ?string $model = EbillingDocument::class;

    protected static ?string $slug = 'ignored-documents';

    public static function resourceConfigKey(): string
    {
        return 'ignored_documents';
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resourceConfigDefaults(): array
    {
        return [
            'label' => 'trans//e-billing::ebilling.ignored_document',
            'plural_label' => 'trans//e-billing::ebilling.ignored_documents',
            'navigation_group' => 'trans//e-billing::ebilling.navigation_group',
            'navigation_icon' => Heroicon::OutlinedEyeSlash,
            'navigation_sort' => 3,
        ];
    }

    public static function tabsConfigPath(): string
    {
        return 'e-billing.tabs.'.static::resourceConfigKey();
    }

    private static function constrainToIgnored(Builder $query): Builder
    {
        return $query->whereIn(
            $query->getModel()->qualifyColumn('gateway_status'),
            array_map(
                fn (EBillingAttachmentProcessingStatus $status): string => $status->value,
                EBillingAttachmentProcessingStatus::ignoredCases(),
            ),
        );
    }

    /**
     * Selects only the light columns: a parsed invoice in `bill_data` can be several hundred KB of
     * JSON, and MySQL carries every selected column through the sort, overflowing its sort buffer.
     * The two `bill_data` values the table shows come from subquery selects, so only the extracted
     * strings enter the sort.
     */
    protected static function modifyEloquentQuery(Builder $query): Builder
    {
        return self::constrainToIgnored(parent::modifyEloquentQuery($query))
            ->select(array_map($query->qualifyColumn(...), [
                'id',
                'source_type',
                'source_id',
                'gateway_status',
                'ignored_reason',
                'customer_id',
                'created_at',
            ]))
            ->addSelect([
                'bill_invoice_number' => self::billDataValue($query, 'invoice_number'),
                'bill_customer_name' => self::billDataValue($query, 'customer_name'),
            ])
            ->with([
                'source' => fn ($morphTo) => $morphTo->morphWith([InboxAttachment::class => ['message']]),
                'customer',
                'latestSourcePdfRelayAttempt',
            ]);
    }

    private static function billDataValue(Builder $query, string $key): Builder
    {
        return $query->getModel()->newQueryWithoutScopes()
            ->from($query->getModel()->getTable().' as bill')
            ->select("bill.bill_data->{$key}")
            ->whereColumn('bill.id', $query->qualifyColumn('id'));
    }

    /**
     * Tab badge/filter queries do not go through {@see modifyEloquentQuery()}, so they constrain
     * to ignored documents themselves. `relay_failed` selects documents whose latest source-PDF
     * relay attempt failed.
     *
     * @param  list<array{field: string, operator: string, value: mixed}>  $conditions
     */
    public static function applyListTabConditions(Builder $query, array $conditions): Builder
    {
        $query = self::constrainToIgnored($query);

        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof Closure) {
                $value = $value();
            }

            if ($condition['field'] === 'relay_failed') {
                if ($value) {
                    $query->whereLatestSourcePdfRelayFailed();
                }

                continue;
            }

            match ($condition['operator']) {
                'in' => $query->whereIn($condition['field'], (array) $value),
                'not_in' => $query->whereNotIn($condition['field'], (array) $value),
                default => $query->where($condition['field'], $condition['operator'], $value),
            };
        }

        return $query;
    }

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
        return IgnoredDocumentsTable::configure($table);
    }

    public static function getBulkActions(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIgnoredDocuments::route('/'),
        ];
    }
}
