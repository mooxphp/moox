<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Illuminate\Support\Facades\DB;
use Moox\EBilling\Data\Address;
use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\EBilling\Data\InvoiceLine as InvoiceLineDto;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Events\InvoiceCreated;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\ChargeDraft;
use Moox\Invoice\Support\InvoiceAddress;
use Moox\Invoice\Support\InvoiceBuilder;
use Moox\Invoice\Support\InvoiceContact;
use Moox\Invoice\Support\InvoiceDraft;
use Moox\Invoice\Support\InvoiceLineDraft;
use Moox\Invoice\Support\InvoiceModels;
use Moox\Invoice\Support\InvoiceParty;
use Moox\MailInbox\Models\InboxAttachment;
use RuntimeException;

class InvoiceFactory
{
    public function __construct(
        private readonly InvoiceBuilder $invoiceBuilder = new InvoiceBuilder,
        private readonly ?DocumentTypeCodeResolver $documentTypeCodeResolver = null,
    ) {}

    private function documentTypeCodeResolver(): DocumentTypeCodeResolver
    {
        return $this->documentTypeCodeResolver ??= app(DocumentTypeCodeResolver::class);
    }

    public function createFromDto(InvoiceDto $dto, InboxAttachment $attachment): Invoice
    {
        $invoice = DB::transaction(function () use ($dto, $attachment): Invoice {
            $existingInvoice = $this->findExistingInvoiceForAttachment($attachment);

            if ($existingInvoice !== null) {
                $document = EbillingDocument::query()
                    ->where('source_type', $attachment->getMorphClass())
                    ->where('source_id', (string) $attachment->getKey())
                    ->first();

                $reviewStatus = $document?->review_status;
                $isReviewed = $reviewStatus === InvoiceProcessingStatus::HumanConfirmed
                    || $reviewStatus === InvoiceProcessingStatus::Validated;
                if ($isReviewed) {
                    throw new RuntimeException(
                        "Cannot re-create Invoice #{$existingInvoice->id} for attachment #{$attachment->id}: "
                        ."document review status is '{$reviewStatus->value}'. "
                        .'Manual intervention required.'
                    );
                }

                $existingInvoice->lines()->each(function (InvoiceLine $line): void {
                    $line->allowanceCharges()->delete();
                    $line->delete();
                });
                $existingInvoice->allowanceCharges()->delete();
                $existingInvoice->delete();
            }

            $draft = $this->buildDraftFromDto($dto);

            return $this->invoiceBuilder->build($draft);
        });

        event(new InvoiceCreated($invoice));

        return $invoice;
    }

    private function findExistingInvoiceForAttachment(InboxAttachment $attachment): ?Invoice
    {
        $document = EbillingDocument::query()
            ->where('source_type', $attachment->getMorphClass())
            ->where('source_id', (string) $attachment->getKey())
            ->whereNotNull('invoice_id')
            ->first();

        if ($document === null || $document->invoice_id === null) {
            return null;
        }

        return InvoiceModels::invoice()::query()->find($document->invoice_id);
    }

    private function buildDraftFromDto(InvoiceDto $dto): InvoiceDraft
    {
        $documentType = $this->documentTypeCodeResolver()
            ->resolveFromCodeOrLabel($dto->documentTypeCode, $dto->documentType);

        return new InvoiceDraft(
            invoice_number: $dto->invoiceNumber,
            invoice_date: $dto->invoiceDate,
            document_type: $documentType,
            due_date: $dto->dueDate,
            currency: $dto->currency,
            customer_reference: $dto->customerReference,
            order_number: $dto->orderNumber,
            order_date: $dto->orderDate,
            pricing_basis: $dto->pricingBasis,
            net_total: $dto->netTotal,
            vat_rate: $dto->vatRate,
            vat_amount: $dto->vatAmount,
            gross_total: $dto->grossTotal,
            seller: $this->mapSeller($dto),
            buyer: $this->mapBuyer($dto),
            delivery: $this->mapInvoiceAddress($dto->deliveryAddress),
            payment_means: null,
            lines: array_map(
                fn (InvoiceLineDto $lineDto): InvoiceLineDraft => $this->buildLineDraftFromDto($lineDto),
                $dto->lines,
            ),
            headerCharges: $this->buildHeaderChargeDraftsFromDto($dto),
        );
    }

    /**
     * @return list<ChargeDraft>
     */
    private function buildHeaderChargeDraftsFromDto(InvoiceDto $dto): array
    {
        $charges = [];

        if ($this->isNonZeroAmount($dto->discountPercent)) {
            $charges[] = new ChargeDraft(
                is_charge: false,
                amount: $this->isNonZeroAmount($dto->discountAmount) ? $dto->discountAmount : 0,
                reason_code: '95', // UNCL 5189 Discount
                percentage: $dto->discountPercent,
            );
        }

        if ($this->isNonZeroAmount($dto->discountAmount) && ! $this->isNonZeroAmount($dto->discountPercent)) {
            $charges[] = new ChargeDraft(
                is_charge: false,
                amount: $dto->discountAmount,
                reason_code: '95', // UNCL 5189 Discount
            );
        }

        if ($this->isNonZeroAmount($dto->shippingCost)) {
            $charges[] = new ChargeDraft(
                is_charge: true,
                amount: $dto->shippingCost,
                reason_code: 'FC', // UNCL 7161 Freight service
                reason_text: 'Versandkosten',
            );
        }

        if ($this->isNonZeroAmount($dto->freightFlatRate)) {
            $charges[] = new ChargeDraft(
                is_charge: true,
                amount: $dto->freightFlatRate,
                reason_code: 'FC', // UNCL 7161 Freight service
                reason_text: 'Frachtpauschale',
            );
        }

        if ($this->isNonZeroAmount($dto->packagingCost)) {
            $charges[] = new ChargeDraft(
                is_charge: true,
                amount: $dto->packagingCost,
                reason_code: 'PC', // UNCL 7161 Packing
                reason_text: 'Verpackung',
            );
        }

        if ($this->isNonZeroAmount($dto->minimumQuantitySurcharge)) {
            $charges[] = new ChargeDraft(
                is_charge: true,
                amount: $dto->minimumQuantitySurcharge,
                reason_text: 'Mindermengenzuschlag',
            );
        }

        return $charges;
    }

    private function buildLineDraftFromDto(InvoiceLineDto $dto): InvoiceLineDraft
    {
        $charges = [];

        if ($this->isNonZeroAmount($dto->surchargeAmount)) {
            $charges[] = new ChargeDraft(
                is_charge: true,
                amount: $dto->surchargeAmount,
                reason_text: $dto->surchargeDescription,
            );
        }

        if ($this->isNonZeroAmount($dto->materialTestCertificatePrice)) {
            $charges[] = new ChargeDraft(
                is_charge: true,
                amount: $dto->materialTestCertificatePrice,
                reason_text: 'Werkszeugnis',
            );
        }

        return new InvoiceLineDraft(
            position: $dto->position,
            unit: $dto->unit,
            quantity: $dto->quantity,
            description: $dto->description,
            description_detail: $dto->descriptionDetail,
            article_number: $dto->articleNumber,
            customs_tariff_number: $dto->customsTariffNumber,
            unit_price: $dto->unitPrice,
            line_total: $dto->lineTotal,
            delivery_date: $dto->deliveryDate,
            delivery_note_number: $dto->deliveryNoteNumber,
            order_number: $dto->orderNumber,
            order_date: $dto->orderDate,
            delivery: $this->mapInvoiceAddress($dto->deliveryAddress),
            charges: $charges,
            extra: [
                'material' => $dto->material,
                'material_test_certificate' => $dto->materialTestCertificate,
                'weight_kg_total' => $dto->weightKgTotal !== null ? (string) $dto->weightKgTotal : null,
                'weight_kg_net' => $dto->weightKgNet !== null ? (string) $dto->weightKgNet : null,
            ],
        );
    }

    private function mapSeller(InvoiceDto $dto): ?InvoiceParty
    {
        return $this->mapInvoiceParty(
            name: $dto->supplierName,
            vatId: $dto->supplierVatId,
            taxNumber: $dto->supplierTaxNumber,
            address: $dto->supplierAddress,
            contact: $this->mapSellerContact($dto),
        );
    }

    private function mapBuyer(InvoiceDto $dto): ?InvoiceParty
    {
        return $this->mapInvoiceParty(
            name: $dto->customerName,
            vatId: $dto->customerVatId,
            taxNumber: null,
            address: $dto->customerAddress,
            contact: null,
        );
    }

    private function mapSellerContact(InvoiceDto $dto): ?InvoiceContact
    {
        $hasAgent = $dto->agent !== null && trim($dto->agent) !== '';
        $hasPhone = $dto->supplierPhone !== null && trim($dto->supplierPhone) !== '';
        $hasEmail = $dto->supplierEmail !== null && trim($dto->supplierEmail) !== '';

        if (! $hasAgent && ! $hasPhone && ! $hasEmail) {
            return null;
        }

        return new InvoiceContact(
            name: $hasAgent ? trim($dto->agent) : '',
            phone: $dto->supplierPhone,
            email: $dto->supplierEmail,
        );
    }

    private function mapInvoiceParty(
        string $name,
        ?string $vatId,
        ?string $taxNumber,
        ?Address $address,
        ?InvoiceContact $contact,
    ): ?InvoiceParty {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            return null;
        }

        $invoiceAddress = $this->mapInvoiceAddress($address);
        if ($invoiceAddress === null) {
            return null;
        }

        return new InvoiceParty(
            name: $trimmedName,
            vat_id: $vatId,
            tax_number: $taxNumber,
            address: $invoiceAddress,
            contact: $contact,
        );
    }

    private function mapInvoiceAddress(?Address $address): ?InvoiceAddress
    {
        if ($address === null) {
            return null;
        }

        $countryCode = $address->country !== null ? strtoupper(trim($address->country)) : '';
        if ($countryCode === '') {
            return null;
        }

        $line1 = trim((string) ($address->street ?? ''));
        if ($line1 === '' && $address->company !== null) {
            $line1 = trim($address->company);
        }

        $line2 = $address->addressLine2;
        if ($address->addressLine3 !== null && trim($address->addressLine3) !== '') {
            $line3 = trim($address->addressLine3);
            $line2 = $line2 !== null && trim($line2) !== ''
                ? trim($line2)."\n".$line3
                : $line3;
        }

        return new InvoiceAddress(
            line1: $line1,
            line2: $line2 !== null && trim($line2) !== '' ? trim($line2) : null,
            city: trim((string) ($address->city ?? '')),
            postal_code: trim((string) ($address->zip ?? '')),
            subdivision: null,
            country_code: $countryCode,
        );
    }

    private function isNonZeroAmount(?float $amount): bool
    {
        return $amount !== null && (float) $amount !== 0.0;
    }
}
