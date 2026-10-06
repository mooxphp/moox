<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

/**
 * Labels the converter prefixes to the free-text line notes (BT-127) it composes, and how a date is
 * written in them. Bind your own implementation to emit them in the invoice language.
 */
interface ZugferdNoteLabels
{
    /**
     * A date as given on the invoice (Y-m-d) written for the note text.
     */
    public function date(string $value): string;

    public function purchaseOrder(): string;

    public function orderDate(): string;

    public function despatchAdvice(): string;

    public function consignee(): string;
}
