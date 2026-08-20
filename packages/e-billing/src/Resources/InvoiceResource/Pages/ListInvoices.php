<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage;
use Moox\EBilling\Resources\Concerns\HasEBillingDocumentListTabs;
use Moox\EBilling\Resources\InvoiceResource;

final class ListInvoices extends ListRecords
{
    use BaseInListPage;
    use HasEBillingDocumentListTabs;
    use SingleSoftDeleteInListPage;

    protected static string $resource = InvoiceResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return array_values(array_filter([
            InvoiceResource::getManualUploadAction(),
        ]));
    }
}
