<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Invoice\Models\Invoice;
use Moox\EBilling\Resources\InvoiceResource;

final class ListInvoices extends ListRecords
{
    use BaseInListPage;
    use HasListPageTabs;
    use SingleSoftDeleteInListPage;

    protected static string $resource = InvoiceResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('e-billing.tabs.invoices', Invoice::class); // Extend Invoice in your host app if needed
    }

    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof \Closure) {
                $value = $value();
            }

            if ($condition['field'] === 'deleted_at' && in_array(SoftDeletes::class, class_uses_recursive($query->getModel()))) {
                $query = $query->withTrashed();
            }

            if ($condition['field'] === 'review_status' && $condition['operator'] === 'in') {
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('review_status', (array) $value),
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

        return $query;
    }
}
