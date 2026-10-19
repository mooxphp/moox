<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\IgnoredDocumentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\EBilling\Resources\Concerns\HasEBillingDocumentListTabs;
use Moox\EBilling\Resources\IgnoredDocumentResource;

final class ListIgnoredDocuments extends ListRecords
{
    use HasEBillingDocumentListTabs;

    protected static string $resource = IgnoredDocumentResource::class;
}
