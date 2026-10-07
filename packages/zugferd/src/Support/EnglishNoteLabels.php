<?php

declare(strict_types=1);

namespace Moox\Zugferd\Support;

use Moox\Zugferd\Contracts\ZugferdNoteLabels;

final class EnglishNoteLabels implements ZugferdNoteLabels
{
    public function date(string $value): string
    {
        return $value;
    }

    public function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    public function purchaseOrder(): string
    {
        return 'Purchase order';
    }

    public function orderDate(): string
    {
        return 'Order date';
    }

    public function despatchAdvice(): string
    {
        return 'Despatch advice';
    }

    public function consignee(): string
    {
        return 'Consignee';
    }
}
