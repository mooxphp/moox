<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Traits\InteractsWithAuditResourceRelations;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\EBilling\Enums\DispatchStatus;
use Moox\EBilling\Resources\Concerns\ReadsEBillingResourceConfig;
use Moox\EBilling\Resources\InvoiceResource\Actions\ManualUploadActions;
use Moox\EBilling\Resources\InvoiceResource\Pages\ListInvoices;
use Moox\EBilling\Resources\InvoiceResource\Pages\ViewInvoice;
use Moox\EBilling\Resources\InvoiceResource\RelationManagers\MailSendLogsRelationManager;
use Moox\EBilling\Resources\InvoiceResource\Tables\InvoicesTable;
use Moox\Invoice\Support\InvoiceModels;
use Moox\MailOutbox\Models\MailSendLog;

class InvoiceResource extends BaseItemResource
{
    use HasResourceRelations;
    use InteractsWithAuditResourceRelations {
        HasResourceRelations::getRelations insteadof InteractsWithAuditResourceRelations;
        HasResourceRelations::getRelations as protected getConfiguredResourceRelations;
        InteractsWithAuditResourceRelations::getRelations as protected getAuditAwareRelations;
    }
    use ReadsEBillingResourceConfig;
    use SingleSoftDeleteInResource;

    protected static ?string $slug = 'invoices';

    /**
     * Resolve the model from config so a host can swap in a subclass
     * via `invoice.models.invoice`; defaults to the generic Moox\Invoice\Models\Invoice.
     * Replaces the static `$model` property so subclass casts/relations apply on the
     * Filament read/edit path.
     */
    public static function getModel(): string
    {
        return InvoiceModels::invoice();
    }

    /**
     * Matches {@see HasListPageTabs::getTableQuery()} which passes the
     * Livewire `activeTab` value. Filament also syncs that property with the `?tab=` query string.
     */
    public static function getTableQuery(?string $activeTab = null): Builder
    {
        $query = parent::getTableQuery();

        $deletedTabKey = (string) config('e-billing.resources.'.static::resourceConfigKey().'.soft_delete_tab_key', 'deleted');

        // "all" / needs-review / deleted: every Fassung. Other tabs stay on the current one.
        $showAllVersions = in_array($activeTab, [null, 'all', 'needs_review', $deletedTabKey], true);

        if (! $showAllVersions) {
            $query->where('is_current', true);
        }

        return $query;
    }

    protected static function applySoftDeleteQuery(Builder $query): Builder
    {
        $model = self::getModel();

        // TODO: Moox Core vendor trait hardcodes 'deleted'/'trash' for bulk action visibility.
        // When Core makes this configurable, remove the dual-check here and use the trait's mechanism.
        $deletedTabKey = (string) config('e-billing.resources.'.static::resourceConfigKey().'.soft_delete_tab_key', 'deleted');
        $tab = request()->query('tab');
        $activeTabQuery = request()->query('activeTab');

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && ($tab === $deletedTabKey || $activeTabQuery === $deletedTabKey)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
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
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
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
        return true;
    }

    protected static function modifyEloquentQuery(Builder $query): Builder
    {
        $query = parent::modifyEloquentQuery($query);
        $query = static::constrainToDocumentTypes($query);

        return $query->with([
            'ebillingDocument',
            'ebillingDocument.deliveryAttempts',
            'ebillingDocument.kositValidations' => fn ($query) => $query
                ->orderByDesc('validated_at')
                ->orderByDesc('id'),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table, static::class);
    }

    public static function getDeleteBulkAction(): BulkAction
    {
        return parent::getDeleteBulkAction()
            ->hidden(fn ($livewire): bool => isset($livewire->activeTab)
                && in_array($livewire->activeTab, ['trash', 'deleted'], true));
    }

    public static function getBulkActions(): array
    {
        return [
            ...(self::enableRestore() ? [self::getRestoreBulkAction()] : []),
            ...(self::enableDelete() ? [self::getDeleteBulkAction()] : []),
            ...(self::enableHardDelete() ? [self::getHardDeleteBulkAction()] : []),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    /**
     * Optional mail-outbox RM (OR invoice∥document morphs) via
     * {@see getDeclaredRelations()}. Delivery / KoSIT / veraPDF tabs come from
     * config('e-billing.invoice_relations') merged into invoice.relations
     * ({@see HasResourceRelations}). Audit Activities merge separately.
     *
     * @return array<int, mixed>
     */
    protected static function getDeclaredRelations(): array
    {
        if (! class_exists(MailSendLog::class)) {
            return [];
        }

        return [MailSendLogsRelationManager::class];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getRelations(): array
    {
        return array_values(array_unique([
            ...static::getConfiguredResourceRelations(),
            ...static::getAuditAwareRelations(),
        ], SORT_REGULAR));
    }

    public static function resourceConfigKey(): string
    {
        return 'invoices';
    }

    /**
     * @return list<string>
     */
    public static function documentTypes(): array
    {
        $types = config('e-billing.resources.'.static::resourceConfigKey().'.document_types', []);

        if (! is_array($types)) {
            return [];
        }

        return array_values(array_filter($types, is_string(...)));
    }

    public static function tabsConfigPath(): string
    {
        $resourcePath = 'e-billing.tabs.'.static::resourceConfigKey();

        return is_array(config($resourcePath)) ? $resourcePath : 'e-billing.tabs.invoices';
    }

    public static function constrainToDocumentTypes(Builder $query): Builder
    {
        $documentTypes = static::documentTypes();

        if ($documentTypes !== []) {
            $query->whereIn('document_type', $documentTypes);
        }

        return $query;
    }

    /**
     * Tab badge/filter queries do not go through {@see modifyEloquentQuery()};
     * they must apply the same document-type scope as the table.
     *
     * @param  list<array{field: string, operator: string, value: mixed}>  $conditions
     */
    public static function applyListTabConditions(Builder $query, array $conditions): Builder
    {
        $query = static::constrainToDocumentTypes($query);

        $isNeedsReviewTab = false;
        $isDeletedTab = false;
        $isAllTab = true;

        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof Closure) {
                $value = $value();
            }

            if ($condition['field'] === 'deleted_at' && in_array(SoftDeletes::class, class_uses_recursive($query->getModel()))) {
                $query = $query->withTrashed();
            }

            if ($condition['field'] === 'deleted_at' && $condition['operator'] === '!=') {
                $isDeletedTab = true;
            }

            if (in_array($condition['field'], ['review_status', 'gateway_status', 'approval_status', 'delivered'], true)) {
                $isAllTab = false;
            }

            if ($condition['field'] === 'approval_status' && $condition['operator'] === 'in') {
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('approval_status', (array) $value),
                );

                continue;
            }

            if ($condition['field'] === 'delivered') {
                $query->whereHas('ebillingDocument', fn ($documentQuery) => DispatchStatus::Sent->constrain($documentQuery));

                continue;
            }

            if ($condition['field'] === 'review_status' && $condition['operator'] === 'in') {
                $isNeedsReviewTab = true;
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('review_status', (array) $value),
                );

                continue;
            }

            if ($condition['field'] === 'gateway_status' && $condition['operator'] === 'in') {
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('gateway_status', (array) $value),
                );

                continue;
            }

            if ($condition['operator'] === 'in') {
                $query->whereIn($condition['field'], (array) $value);
            } elseif ($condition['operator'] === 'not_in') {
                $query->whereNotIn($condition['field'], (array) $value);
            } else {
                $query->where($condition['field'], $condition['operator'], $value);
            }
        }

        // "Alle" and needs-review keep every Fassung; other status tabs stay on current only.
        if (! $isAllTab && ! $isNeedsReviewTab && ! $isDeletedTab) {
            $query->where('is_current', true);
        }

        return $query;
    }

    public static function getManualUploadAction(): ?Action
    {
        return ManualUploadActions::make(static::resourceConfigKey());
    }

    /**
     * Bulk upload (`manual_upload.bulk_enabled`); see {@see ManualUploadActions::makeBulk()}.
     */
    public static function getBulkManualUploadAction(): ?Action
    {
        return ManualUploadActions::makeBulk(static::resourceConfigKey());
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resourceConfigDefaults(): array
    {
        return [
            'label' => 'trans//e-billing::ebilling.invoice',
            'plural_label' => 'trans//e-billing::ebilling.invoices',
            'navigation_icon' => Heroicon::OutlinedDocumentText,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('e-billing.resources.'.static::resourceConfigKey().'.navigation_count_badge', false)) {
            return null;
        }

        return (string) static::constrainToDocumentTypes(self::getModel()::query())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
