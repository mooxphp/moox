<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources;

use Moox\EBilling\Resources\CreditNoteResource\Pages\ListCreditNotes;
use Moox\EBilling\Resources\CreditNoteResource\Pages\ViewCreditNote;

class CreditNoteResource extends InvoiceResource
{
    protected static ?string $slug = 'credit-notes';

    public static function resourceConfigKey(): string
    {
        return 'credit_notes';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditNotes::route('/'),
            'view' => ViewCreditNote::route('/{record}'),
        ];
    }
}
