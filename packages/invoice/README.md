![Moox Invoice](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox Invoice

Structured e-invoice entity for Laravel (ZUGFeRD/XRechnung-ready), with header/line allowances and charges.

## Features

-   EN 16931-ready structured invoice entity (header, lines, allowances/charges)
-   JSON party snapshots on the invoice (`seller`, `buyer`, `delivery`, `payment_means`) cast to typed value objects
-   Line-level delivery address snapshot (`delivery` JSON on `InvoiceLine`)
-   `InvoiceBuilder` with readonly draft objects for transactional persistence
-   Morph-linked `InvoiceAllowanceCharge` rows on invoice header and individual lines
-   UUID primary keys and soft deletes on `Invoice` and `InvoiceLine`
-   Host apps can swap model classes via config

This package is a pure entity layer — no Filament UI or admin screens. Billing workflows and the Filament resource live in [`moox/e-billing`](../e-billing).

## Requirements

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/invoice
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

## Usage

Build a draft, then persist it with `InvoiceBuilder::build()` in a single database transaction:

```php
use Moox\Invoice\Support\ChargeDraft;
use Moox\Invoice\Support\En16931\Party;
use Moox\Invoice\Support\InvoiceBuilder;
use Moox\Invoice\Support\InvoiceDraft;
use Moox\Invoice\Support\InvoiceLineDraft;

$seller = Party::fromArray([
    'name' => 'Seller GmbH',
    'vat_id' => 'DE123456789',
    'address' => [
        'line1' => 'Hauptstr. 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country_code' => 'DE',
    ],
]);

$draft = new InvoiceDraft(
    invoice_number: 'INV-2026-001',
    invoice_date: '2026-06-01',
    document_type: '380',
    due_date: '2026-06-15',
    currency: 'EUR',
    customer_reference: null,
    order_number: null,
    order_date: null,
    pricing_basis: null,
    net_total: 100.0,
    vat_rate: 19.0,
    vat_amount: 19.0,
    gross_total: 119.0,
    seller: $seller,
    buyer: null,
    delivery: null,
    payment_means: null,
    lines: [
        new InvoiceLineDraft(
            position: 1,
            unit: 'Stück',
            quantity: 1.0,
            description: 'Widget A',
            description_detail: null,
            article_number: 'ART-1',
            customs_tariff_number: null,
            unit_price: 100.0,
            line_total: 100.0,
            delivery_date: null,
            delivery_note_number: null,
            order_number: null,
            order_date: null,
            delivery: null,
            charges: [],
        ),
    ],
    headerCharges: [
        new ChargeDraft(
            is_charge: false,
            amount: 10.0,
            reason_text: 'Header discount',
        ),
    ],
);

$invoice = (new InvoiceBuilder)->build($draft);
```

`InvoiceBuilder` creates the invoice, its lines, and all header/line allowance-charge rows. If the caller already has an active database transaction, it participates in that transaction instead of opening a nested one.

## EN 16931 value objects

Structured party, address, contact, and payment-means data for EN 16931 (ZUGFeRD/XRechnung) lives under `Moox\Invoice\Support\En16931\*` (e.g. `Party`, `Address`, `PaymentMeans`). The sub-namespace keeps short class names while preserving the standard as context; other standards can be added alongside later (e.g. a future `Peppol\` namespace).

All value objects are `readonly` and expose `fromArray(array $data): self` and `toArray(): array` for serialization.

### `Address`

```php
public function __construct(
    public string $line1,
    public ?string $line2,
    public string $city,
    public string $postal_code,
    public ?string $subdivision,
    public string $country_code,
)
```

### `Contact`

```php
public function __construct(
    public string $name,
    public ?string $phone,
    public ?string $email,
)
```

### `Party`

```php
public function __construct(
    public string $name,
    public ?string $vat_id,
    public ?string $tax_number,
    public Address $address,
    public ?Contact $contact,
)
```

### `BankAccount`

```php
public function __construct(
    public string $iban,
    public ?string $bic,
    public ?string $bank_name,
    public ?string $account_holder,
)
```

### `PaymentMeans`

```php
public function __construct(
    public ?string $payment_means_code,
    public array $bank_accounts, // list<BankAccount>
)
```

Also exposes `bankAccounts(): array` returning the bank-account list.

JSON columns on the models are cast via `PartyCast`, `AddressCast`, and `PaymentMeansCast` in `Support\En16931\Casts\`.

## The Invoice Model

The `Invoice` model (`Moox\Invoice\Models\Invoice`) stores the invoice header. It extends `BaseItemModel`, uses UUID primary keys (`HasUuids`), and soft deletes.

### Attributes

-   `id` (uuid) - Primary key
-   `invoice_number` (string, indexed) - Invoice identifier
-   `invoice_date` (string) - Issue date
-   `document_type` (string) - EN 16931 document type code (e.g. `380` for invoice)
-   `due_date` (string, nullable) - Payment due date
-   `currency` (string, default: `EUR`) - ISO 4217 currency code
-   `customer_reference` (string, nullable) - Buyer reference
-   `order_number` (string, nullable) - Associated order number
-   `order_date` (string, nullable) - Associated order date
-   `pricing_basis` (string, nullable) - Incoterms / pricing basis (serialized as note in e-billing / ZUGFeRD layer)
-   `seller` (json, nullable) - Seller party snapshot; cast to `Party` via `PartyCast`
-   `buyer` (json, nullable) - Buyer party snapshot; cast to `Party` via `PartyCast`
-   `delivery` (json, nullable) - Delivery location; cast to `Address` via `AddressCast`
-   `payment_means` (json, nullable) - Payment instructions; cast to `PaymentMeans` via `PaymentMeansCast`
-   `net_total` (decimal 12,2, default: `0`) - Sum of line net amounts
-   `vat_rate` (decimal 5,2, default: `19.00`) - VAT rate percentage
-   `vat_amount` (decimal 12,2, default: `0`) - VAT amount
-   `gross_total` (decimal 12,2, default: `0`) - Gross total including VAT
-   `created_at` (datetime) - Creation timestamp
-   `updated_at` (datetime) - Last update timestamp
-   `deleted_at` (datetime, nullable) - Soft-delete timestamp
-   `scope` (string, nullable, indexed) - Moox scope column from `BaseItemModel`

### Relationships

-   `lines()` - `HasMany` `InvoiceLine`
-   `allowanceCharges()` - `MorphMany` `InvoiceAllowanceCharge` (`chargeable`)

## The InvoiceLine Model

The `InvoiceLine` model (`Moox\Invoice\Models\InvoiceLine`) stores a single invoice line item. It extends `BaseItemModel`, uses UUID primary keys (`HasUuids`), and soft deletes.

### Attributes

-   `id` (uuid) - Primary key
-   `invoice_id` (foreignUuid) - Parent invoice (`cascadeOnDelete`)
-   `position` (integer, default: `0`) - Line position / sequence
-   `unit` (string) - Unit of measure
-   `quantity` (decimal 12,3, default: `0`) - Quantity
-   `description` (text, nullable) - Short line description
-   `description_detail` (text, nullable) - Extended line description
-   `article_number` (string, nullable) - Seller article / SKU
-   `customs_tariff_number` (string, nullable) - Customs tariff number
-   `unit_price` (decimal 12,2, default: `0`) - Net unit price
-   `line_total` (decimal 12,2, default: `0`) - Net line total
-   `delivery` (json, nullable) - Line delivery address; cast to `Address` via `AddressCast`
-   `delivery_date` (string, nullable) - Delivery date
-   `delivery_note_number` (string, nullable) - Delivery note reference
-   `order_number` (string, nullable) - Line order number
-   `order_date` (string, nullable) - Line order date
-   `created_at` (datetime) - Creation timestamp
-   `updated_at` (datetime) - Last update timestamp
-   `deleted_at` (datetime, nullable) - Soft-delete timestamp
-   `scope` (string, nullable, indexed) - Moox scope column from `BaseItemModel`

### Relationships

-   `invoice()` - `BelongsTo` `Invoice`
-   `allowanceCharges()` - `MorphMany` `InvoiceAllowanceCharge` (`chargeable`)

## The InvoiceAllowanceCharge Model

The `InvoiceAllowanceCharge` model (`Moox\Invoice\Models\InvoiceAllowanceCharge`) stores a document-level or line-level allowance or charge. It extends plain `Illuminate\Database\Eloquent\Model` with an auto-incrementing bigInteger primary key — appropriate for a child morph row rather than a top-level entity. No UUIDs, no soft deletes.

### Attributes

-   `id` (bigInteger) - Auto-increment primary key
-   `chargeable_type` (uuid morph) - Parent model class (`Invoice` or `InvoiceLine`)
-   `chargeable_id` (uuid morph) - Parent model primary key
-   `is_charge` (boolean) - `true` for a charge, `false` for an allowance (discount)
-   `amount` (decimal 12,2) - Allowance/charge amount
-   `reason_code` (string, nullable) - EN 16931 reason code
-   `reason_text` (string, nullable) - Human-readable reason
-   `base_amount` (decimal 12,2, nullable) - Base amount for percentage calculation
-   `percentage` (decimal 5,2, nullable) - Percentage rate
-   `created_at` (datetime) - Creation timestamp
-   `updated_at` (datetime) - Last update timestamp
-   `scope` (string, nullable, indexed) - Moox scope column

### Relationships

-   `chargeable()` - `MorphTo` parent (`Invoice` or `InvoiceLine`)

## Configuration

Published config: `config/invoice.php`.

| Key | Default class |
|-----|---------------|
| `models.invoice` | `Moox\Invoice\Models\Invoice` |
| `models.invoice_line` | `Moox\Invoice\Models\InvoiceLine` |
| `models.invoice_allowance_charge` | `Moox\Invoice\Models\InvoiceAllowanceCharge` |

Override these entries to swap in custom model implementations; `InvoiceModels` and `InvoiceBuilder` resolve classes from this map.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
