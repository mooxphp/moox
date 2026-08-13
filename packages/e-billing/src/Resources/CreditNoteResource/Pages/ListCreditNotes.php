<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\CreditNoteResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage;
use Moox\EBilling\Resources\Concerns\HasEBillingDocumentListTabs;
use Moox\EBilling\Resources\CreditNoteResource;

final class ListCreditNotes extends ListRecords
{
    use BaseInListPage;
    use HasEBillingDocumentListTabs;
    use SingleSoftDeleteInListPage;

    protected static string $resource = CreditNoteResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return array_values(array_filter([
            CreditNoteResource::getManualUploadAction(),
        ]));
    }
}
