<?php

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\Invoice;

interface InvoiceParserInterface
{
    /**
     * Parse raw input (e.g. extracted PDF text) into an Invoice DTO.
     *
     * Implement this interface in your host application and bind it
     * in your ServiceProvider:
     *   $this->app->bind(InvoiceParserInterface::class, YourInvoiceParser::class);
     */
    public function parse(string $rawText): Invoice;
}
