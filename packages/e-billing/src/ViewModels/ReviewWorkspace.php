<?php

declare(strict_types=1);

namespace Moox\EBilling\ViewModels;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\CrossCheckedFields;
use Moox\EBilling\Support\ParsedValueHistory;
use Moox\EBilling\Support\ReviewEditField;
use Moox\EBilling\Support\ReviewFieldCatalog;
use Moox\Invoice\Models\Invoice;

/**
 * Per-row state of the mixed review workspace (ADR 0004): how a row is edited, whether it was
 * corrected and what the parser produced, and whether a cross-checked value is unknown to the
 * attributed customer's master data.
 */
final class ReviewWorkspace
{
    /** @var array<string, ReviewEditField> */
    private array $edits = [];

    /** @var array<string, mixed>|null */
    private ?array $correctedFields = null;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly ?EbillingDocument $document,
        private readonly ReviewFieldCatalog $catalog,
    ) {
    }

    /**
     * Editing is open while the document awaits approval and corrections can be recorded.
     */
    public function isAvailable(): bool
    {
        return $this->document?->resolveApprovalStatusEnum() === DocumentApprovalStatus::Pending
            && ParsedValueHistory::isAvailable();
    }

    public function edit(string $key): ReviewEditField
    {
        return $this->edits[$key] ??= $this->catalog->resolve($this->invoice, $key);
    }

    /**
     * "Corrected, parsed: …" for a row with at least one correction, else null.
     */
    public function correctionNote(string $key): ?string
    {
        $edit = $this->edit($key);
        $parsed = $this->catalog->parsedValuesOfCorrections(
            $this->invoice,
            $edit,
            $this->correctedFields ??= ParsedValueHistory::correctedFields($this->invoice),
        );

        if ($parsed === []) {
            return null;
        }

        $values = [];
        foreach ($parsed as $attribute => $value) {
            $display = self::display($value);
            $values[] = count($edit->inputs) > 1 ? $this->inputLabel($edit, $attribute).': '.$display : $display;
        }

        return __('e-billing::fields.review_corrected_parsed', ['value' => implode(' · ', $values)]);
    }

    /**
     * A cross-checked value the attributed customer's master data does not contain.
     */
    public function isFlagged(string $key): bool
    {
        $edit = $this->edit($key);
        if ($edit->crossChecked === null || $edit->target === null) {
            return false;
        }

        $value = CrossCheckedFields::isAddressField($edit->crossChecked)
            ? $this->addressParts($edit)
            : self::currentValue($edit->target, (string) array_key_first($edit->inputs));

        return CrossCheckedFields::isKnown($edit->crossChecked, $value, $this->document) === false;
    }

    public function inputLabel(ReviewEditField $edit, string $attribute): string
    {
        if (count($edit->inputs) === 1) {
            return $edit->label;
        }

        $segments = explode('.', $attribute);
        $label = __('e-billing::fields.review_input.'.end($segments));
        $index = collect($segments)->first(fn (string $segment): bool => ctype_digit($segment));

        return $index !== null && str_contains($attribute, 'bank_accounts') ? $label.' ('.((int) $index + 1).')' : $label;
    }

    public function customerLabel(): ?string
    {
        $customer = CrossCheckedFields::findCustomer($this->document?->customer_id);

        return $customer instanceof Customer ? self::customerOptionLabel($customer) : null;
    }

    public static function customerOptionLabel(Customer $customer): string
    {
        return $customer->displayLabel().(filled($customer->customer_number) ? " ({$customer->customer_number})" : '');
    }

    /**
     * The stored value of an attribute, JSON sub-keys with dots; dates as `Y-m-d`.
     */
    public static function currentValue(Model $target, string $attribute): mixed
    {
        [$column, $path] = array_pad(explode('.', $attribute, 2), 2, null);
        $value = $target->getAttribute($column);

        if ($path !== null) {
            $value = Arr::get(is_object($value) && method_exists($value, 'toArray') ? $value->toArray() : (array) $value, $path);
        }

        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    /**
     * @return array<string, ?string>
     */
    private function addressParts(ReviewEditField $edit): array
    {
        $parts = [];
        foreach (array_keys($edit->inputs) as $attribute) {
            if (str_contains($attribute, '.address.')) {
                $value = self::currentValue($edit->target, $attribute);
                $parts[Str::afterLast($attribute, '.')] = is_scalar($value) ? (string) $value : null;
            }
        }

        return $parts;
    }

    private static function display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('e-billing::fields.review_parsed_empty');
        }

        return is_scalar($value) ? (string) $value : (string) json_encode($value);
    }
}
