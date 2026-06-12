<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Moox\EBilling\Data\Address;
use Moox\EBilling\Data\Invoice;
use Moox\EBilling\Parsers\HecoInvoiceParser;
use Moox\PdfParser\PdfParser;
use Moox\Zugferd\ZugferdConverter;

class EBilling
{
    public function __construct(
        private PdfParser $pdfParser,
        private ZugferdConverter $zugferdConverter,
        private HecoInvoiceParser $parser,
    ) {}

    /**
     * PDF → Text → Invoice with supplier snapshot from config (no XML).
     */
    public function parseInvoiceFromPdf(string $pdfPath): Invoice
    {
        $parsed = $this->pdfParser->parseWithLayout($pdfPath);
        $invoice = $this->parser->parse($parsed->text);
        $supplier = config('e-billing.supplier');
        $invoice->supplierName = $supplier['name'] ?? '';
        $invoice->supplierVatId = $supplier['vat_id'] ?? null;
        $invoice->supplierTaxNumber = $supplier['tax_number'] ?? null;
        $invoice->supplierAddress = Address::fromMixedWithParty($supplier['address'] ?? null, $supplier['name'] ?? '');
        $invoice->supplierPhone = $supplier['phone'] ?? null;
        $invoice->supplierEmail = $supplier['email'] ?? null;
        $invoice->supplierBankAccounts = $supplier['bank_accounts'] ?? [];

        return $invoice;
    }

    /**
     * PDF → Text → Invoice (with supplier from config) → ZUGFeRD XML string. Does not merge into PDF.
     *
     * @return array{invoice: Invoice, xml: string}
     */
    public function generateInvoiceAndXmlFromPdf(string $pdfPath): array
    {
        $invoice = $this->parseInvoiceFromPdf($pdfPath);
        $xml = $this->zugferdConverter->convert($invoice);

        return [
            'invoice' => $invoice,
            'xml' => $xml,
        ];
    }

    /**
     * Full pipeline: PDF → Text → Invoice → ZUGFeRD XML → PDF/A-3 with embedded XML.
     *
     * @return array{invoice: Invoice, xml: string, zugferd_pdf: string}
     */
    public function processFile(string $pdfPath): array
    {
        $generated = $this->generateInvoiceAndXmlFromPdf($pdfPath);
        $invoice = $generated['invoice'];
        $xml = $generated['xml'];
        $zugferdPdfContent = $this->zugferdConverter->mergePdfWithXml($pdfPath, $xml);

        return [
            'invoice' => $invoice,
            'xml' => $xml,
            'zugferd_pdf' => $zugferdPdfContent,
        ];
    }

    /**
     * Parse text content into an Invoice.
     */
    public function parseContent(string $content): Invoice
    {
        return $this->parser->parse($content);
    }

    /**
     * Convert an Invoice to ZUGFeRD XML.
     */
    public function convertToXml(Invoice $invoice): string
    {
        return $this->zugferdConverter->convert($invoice);
    }
}
