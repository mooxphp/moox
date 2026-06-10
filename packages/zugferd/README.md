![Moox Zugferd](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox Zugferd

Moox Zugferd converts invoice data implementing `ZugferdInvoice` into valid ZUGFeRD / XRechnung XML (EN 16931) and can embed that XML into invoice PDFs as PDF/A-3 ZUGFeRD documents. It builds on [horstoeko/zugferd](https://github.com/horstoeko/zugferd). For a visual check of generated XML, see [horstoeko/zugferdvisualizer](https://github.com/horstoeko/zugferdvisualizer).

## Features

<!--features-->

- `ZugferdConverter::convert()` — XML string from any `ZugferdInvoice` implementor
- `convertToFile()` — writes `{output_path}/{invoiceNumber}.xml`
- `mergePdfWithXml()` — PDF/A-3 binary with embedded XML; optional `qpdf` decrypt/re-encrypt
- Contract interfaces for invoices, lines, addresses, bank accounts, and allowance/charges
- Concrete `AllowanceCharge` DTO for tests and simple consumers
- Configurable profile: MINIMUM, BASIC, EN16931, EXTENDED, XRECHNUNG (default)

<!--/features-->

## Responsibility Boundaries

- `moox/zugferd` owns XML generation and PDF/A-3 embedding from `ZugferdInvoice` contracts.
- `moox/e-billing` orchestrates the pipeline and adapts `moox/invoice` models via `ZugferdInvoiceAdapter` (`GenerateXmlJob`, `MergeZugferdPdfJob`).
- `moox/kosit-validator` validates XML produced here; validation does not live in this package.
- `moox/pdf-parser` extracts PDF text upstream; this package does not parse PDF text.
- **Composer does not require `moox/e-billing`** — only `moox/core` and `horstoeko/zugferd`. E-billing is an optional peer at runtime.

## Requirements

| Package / tool | Purpose |
|----------------|---------|
| `moox/core` | Moox service provider |
| `horstoeko/zugferd` | Document builder and PDF merger |
| `qpdf` (optional) | Decrypt/re-encrypt PDFs when `mail-inbox.zugferd.pdf_password` is set |
| Invoice DTO implementor | e.g. `Moox\EBilling\Adapters\ZugferdInvoiceAdapter` or test fixtures |

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/zugferd
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

Optionally publish configuration:

```bash
php artisan vendor:publish --tag=zugferd-config
```

Set the ZUGFeRD profile:

```env
ZUGFERD_PROFILE=XRECHNUNG
```

## Screenshot

![Moox Zugferd](https://github.com/mooxphp/moox/raw/main/art/screenshots/record.jpg)

## Usage

Resolve `Moox\Zugferd\ZugferdConverter` from the container.

### Generate XML

```php
use Moox\Zugferd\ZugferdConverter;

$xml = app(ZugferdConverter::class)->convert($invoice);
```

`$invoice` must implement `Moox\Zugferd\Contracts\ZugferdInvoice`.

### E-billing adapter

```php
use Moox\EBilling\Adapters\ZugferdInvoiceAdapter;

$xml = app(ZugferdConverter::class)->convert(new ZugferdInvoiceAdapter($invoiceModel));
```

`GenerateXmlJob` calls the e-billing gateway, which delegates to `ZugferdConverter` with this adapter.

### Write XML to disk

```php
$path = app(ZugferdConverter::class)->convertToFile($invoice);
// Default directory: config('zugferd.output_path')
```

### Merge into a ZUGFeRD PDF

```php
$pdfBinary = app(ZugferdConverter::class)->mergePdfWithXml('/path/to/invoice.pdf', $xml);
```

`MergeZugferdPdfJob` uses this after a passed KoSIT validation.

**Password-protected PDFs:** reads `config('mail-inbox.zugferd.pdf_password')`. When set, runs `qpdf --decrypt` before merge and `qpdf --encrypt` afterward (falls back to unencrypted merge/decrypt on failure).

## Contract interfaces

All contracts use PHP 8.4 property hooks (`public type $name { get; }`). Implementors expose the required properties.

### `ZugferdInvoice`

Header, parties, totals, `lines`, `bankAccounts`, and `allowanceCharges`.

**Required for conversion (throws `IncompleteInvoiceException` when missing):**

- Non-null `supplierAddress` and `customerAddress`
- Non-empty trimmed `supplierEmail`
- Non-empty `bankAccounts` with non-empty IBAN on each account

**Other notable fields:** `documentType` (credit note when value contains `gutschrift` → type code `381`, else `380`), `paymentMeansCode` (default `58`), `dueDate` / `paymentTerms`, `vatRate`, `netTotal`, `vatAmount`, `grossTotal`.

### `ZugferdInvoiceLine`

`position`, `description`, `descriptionDetail`, `articleNumber`, `unitPrice`, `quantity`, `unit`, `lineTotal`, `allowanceCharges`.

### `ZugferdAddress`

`street`, `addressLine2`, `addressLine3`, `zip`, `city`, `country` — mapped to horstoeko address lines via `buildAddressLines()`.

### `ZugferdBankAccount`

`iban` (required), `bic`, `bankName` (unused by converter), `accountHolder` (falls back to `supplierName`).

### `ZugferdAllowanceCharge`

`isCharge`, `amount`, `reasonCode`, `reasonText`. `basisAmount` and `percentage` are on the contract but ignored by the converter. Items with `amount <= 0` are skipped.

### `AllowanceCharge` data class

`Moox\Zugferd\Data\AllowanceCharge` is a readonly concrete implementor for tests and simple use cases.

## The ZugferdConverter Service

Class: `Moox\Zugferd\ZugferdConverter` (singleton in `ZugferdServiceProvider`).

| Method | Returns | Description |
|--------|---------|-------------|
| `convert(ZugferdInvoice $invoice): string` | XML string | Builds horstoeko document; `getContentSafely()` mitigates stream-resource warnings |
| `convertToFile(ZugferdInvoice $invoice, ?string $outputPath = null): string` | File path | Writes `{outputPath}/{invoiceNumber}.xml` |
| `mergePdfWithXml(string $pdfPath, string $xml): string` | PDF binary | Optional qpdf decrypt → merge → optional re-encrypt |

### Profile map

| Config value | horstoeko constant |
|--------------|-------------------|
| `MINIMUM` | `PROFILE_MINIMUM` |
| `BASIC` | `PROFILE_BASIC` |
| `EN16931` | `PROFILE_EN16931` |
| `EXTENDED` | `PROFILE_EXTENDED` |
| `XRECHNUNG` | `PROFILE_XRECHNUNG_3` |
| *(unknown)* | Falls back to `PROFILE_EN16931` |

Runtime profile: `config('zugferd.profile')` (default `XRECHNUNG`). This package does **not** read `config/e-billing.php` profile keys.

### Unit codes (`mapUnitCode`)

| Input | UN/ECE |
|-------|--------|
| `Stück` | `C62` |
| `Meter` | `MTR` |
| `Liter` | `LTR` |
| `kg` | `KGM` |
| `Satz` | `SET` |
| `Pauschal` | `LS` |
| default | `C62` |

## Exceptions

| Class | When |
|-------|------|
| `Moox\Zugferd\Exceptions\IncompleteInvoiceException` | Missing supplier/customer address, supplier email, or valid bank accounts |
| `\RuntimeException` | Failed to read merged or re-encrypted temp PDF |

## Configuration

File: `config/zugferd.php`

| Key | Env | Default | Used by |
|-----|-----|---------|---------|
| `profile` | `ZUGFERD_PROFILE` | `XRECHNUNG` | `buildDocument()` |
| `output_path` | — | `storage/app/private/zugferd` | `convertToFile()` only |

**Cross-package config:** `mail-inbox.zugferd.pdf_password` — read in `mergePdfWithXml()` only.

No migrations, routes, or Filament UI.

## Running tests

From the monorepo root:

```bash
php vendor/bin/pest packages/zugferd/tests
```

Feature tests cover allowance/charges, address lines, payment means codes, and `IncompleteInvoiceException` for missing supplier address. `mergePdfWithXml()` and `convertToFile()` are not covered in this package (e-billing tests exercise the adapter path).

## See also

- [Moox EBilling](../e-billing/README.md) — `ZugferdInvoiceAdapter`, `GenerateXmlJob`, `MergeZugferdPdfJob`
- [Moox KositValidator](../kosit-validator/README.md) — validates generated XML
- [Moox PdfParser](../pdf-parser/README.md) — upstream PDF text extraction
- [Moox documentation](https://moox.org/docs/zugferd)
- [Architecture](docs/ARCHITECTURE.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
