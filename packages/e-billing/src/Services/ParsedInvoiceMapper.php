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
use Moox\Invoice\Support\En16931\Address as En16931Address;
use Moox\Invoice\Support\En16931\BankAccount as En16931BankAccount;
use Moox\Invoice\Support\En16931\Contact;
use Moox\Invoice\Support\En16931\Party;
use Moox\Invoice\Support\En16931\PaymentMeans;
use Moox\Invoice\Support\InvoiceBuilder;
use Moox\Invoice\Support\InvoiceDraft;
use Moox\Invoice\Support\InvoiceLineDraft;
use Moox\MailInbox\Models\InboxAttachment;
use RuntimeException;

class ParsedInvoiceMapper
{
    public function __construct(
        private readonly InvoiceBuilder $invoiceBuilder = new InvoiceBuilder,
        private readonly ?DocumentTypeCodeResolver $documentTypeCodeResolver = null,
    ) {
    }

    private function documentTypeCodeResolver(): DocumentTypeCodeResolver
    {
        return $this->documentTypeCodeResolver ??= app(DocumentTypeCodeResolver::class);
    }

    // Extend Invoice in your host app if needed
    public function createFromDto(InvoiceDto $dto, InboxAttachment $attachment): Invoice
    {
        // Extend Invoice in your host app if needed
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

                // Extend InvoiceLine in your host app if needed
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

        $this->linkDocumentToInvoice($attachment, $invoice);

        event(new InvoiceCreated($invoice));

        return $invoice;
    }

    // Extend Invoice in your host app if needed
    private function linkDocumentToInvoice(InboxAttachment $attachment, Invoice $invoice): void
    {
        EbillingDocument::query()
            ->where('source_type', $attachment->getMorphClass())
            ->where('source_id', (string) $attachment->getKey())
            ->update(['invoice_id' => $invoice->id]);
    }

    // Extend Invoice in your host app if needed
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

        return Invoice::query()->find($document->invoice_id);
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
            delivery: $this->mapEn16931Address($dto->deliveryAddress),
            payment_means: $this->mapPaymentMeans($dto),
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

        foreach ($dto->allowanceCharges as $item) {
            if (! $this->isNonZeroAmount($item->amount)) {
                continue;
            }

            $charges[] = new ChargeDraft(
                is_charge: $item->isCharge,
                amount: $item->amount,
                reason_code: $item->reasonCode,
                reason_text: $item->reasonText,
                percentage: $item->percentage,
            );
        }

        return $charges;
    }

    private function buildLineDraftFromDto(InvoiceLineDto $dto): InvoiceLineDraft
    {
        $charges = [];

        foreach ($dto->allowanceCharges as $item) {
            if (! $this->isNonZeroAmount($item->amount)) {
                continue;
            }

            $charges[] = new ChargeDraft(
                is_charge: $item->isCharge,
                amount: $item->amount,
                reason_code: $item->reasonCode,
                reason_text: $item->reasonText,
                percentage: $item->percentage,
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
            delivery: $this->mapEn16931Address($dto->deliveryAddress),
            charges: $charges,
            extra: [
                'material' => $dto->material,
                'material_test_certificate' => $dto->materialTestCertificate,
                'weight_kg_total' => $dto->weightKgTotal !== null ? (string) $dto->weightKgTotal : null,
                'weight_kg_net' => $dto->weightKgNet !== null ? (string) $dto->weightKgNet : null,
            ],
        );
    }

    private function mapSeller(InvoiceDto $dto): ?Party
    {
        return $this->mapEn16931Party(
            name: $dto->supplierName,
            vatId: $dto->supplierVatId,
            taxNumber: $dto->supplierTaxNumber,
            address: $dto->supplierAddress,
            contact: $this->mapSellerContact($dto),
        );
    }

    private function mapBuyer(InvoiceDto $dto): ?Party
    {
        return $this->mapEn16931Party(
            name: $dto->customerName,
            vatId: $dto->customerVatId,
            taxNumber: null,
            address: $dto->customerAddress,
            contact: null,
        );
    }

    private function mapPaymentMeans(InvoiceDto $dto): ?PaymentMeans
    {
        if ($dto->bankAccounts === []) {
            return null;
        }

        $bankAccounts = [];

        foreach ($dto->bankAccounts as $account) {
            $bankAccounts[] = new En16931BankAccount(
                iban: $account->iban,
                bic: $account->bic,
                bank_name: $account->bankName,
                account_holder: $account->accountHolder,
            );
        }

        return new PaymentMeans(
            payment_means_code: '58',
            bank_accounts: $bankAccounts,
        );
    }

    private function mapSellerContact(InvoiceDto $dto): ?Contact
    {
        $hasAgent = $dto->agent !== null && trim($dto->agent) !== '';
        $hasPhone = $dto->supplierPhone !== null && trim($dto->supplierPhone) !== '';
        $hasEmail = $dto->supplierEmail !== null && trim($dto->supplierEmail) !== '';

        if (! $hasAgent && ! $hasPhone && ! $hasEmail) {
            return null;
        }

        return new Contact(
            name: $hasAgent ? trim($dto->agent) : '',
            phone: $dto->supplierPhone,
            email: $dto->supplierEmail,
        );
    }

    private function mapEn16931Party(
        string $name,
        ?string $vatId,
        ?string $taxNumber,
        ?Address $address,
        ?Contact $contact,
    ): ?Party {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            return null;
        }

        $en16931Address = $this->mapEn16931Address($address);
        if ($en16931Address === null) {
            return null;
        }

        return new Party(
            name: $trimmedName,
            vat_id: $vatId,
            tax_number: $taxNumber,
            address: $en16931Address,
            contact: $contact,
        );
    }

    private function mapEn16931Address(?Address $address): ?En16931Address
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

        return new En16931Address(
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
