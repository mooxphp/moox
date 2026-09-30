<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\ReviewerActionType;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ParsedValueHistory;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;
use RuntimeException;

/**
 * A reviewer restores what the source document says after the parser mis-read one field
 * (mooxphp/e-billing#45). The value is written to the invoice, line or allowance/charge, and the act
 * is recorded as a `value_corrected` moox/audit activity on the invoice: field, parsed value, previous
 * value, corrected value and an optional note; actor and time are the activity's causer and timestamp.
 * The parsed value comes from the row's audit history, so it survives any number of corrections.
 * Corrections are never updated or deleted by this package.
 */
final class CorrectFieldValueAction
{
    public const ACTIVITY_EVENT = 'value_corrected';

    /**
     * @param  string  $attribute  attribute of the target; JSON sub-keys with dots, e.g. `buyer.vat_id`
     * @return bool false when the value is unchanged
     */
    public function execute(EbillingDocument $document, Model $target, string $attribute, mixed $value, ?string $note = null): bool
    {
        if (auth()->user() === null) {
            throw new InvalidArgumentException('An authenticated actor is required to correct a value.');
        }

        if (! ParsedValueHistory::isAvailable()) {
            throw new RuntimeException('Value corrections require moox/audit with auditing enabled.');
        }

        $invoice = $document->invoice;
        if (! $invoice instanceof Invoice) {
            throw new InvalidArgumentException("Document #{$document->id} has no linked invoice.");
        }

        $field = $this->fieldPrefix($invoice, $target).$attribute;

        if (! $this->isCorrectable($field) || ! $this->hasAttribute($target, $attribute) || ! ParsedValueHistory::isAudited($target, $attribute)) {
            throw new InvalidArgumentException("The field {$field} is not correctable.");
        }

        if (is_string($value)) {
            $value = trim($value) === '' ? null : trim($value);
        }

        $corrected = DB::transaction(function () use ($document, $invoice, $target, $attribute, $value, $field, $note): bool {
            // Re-read under lock: an approval or another correction may have landed since the models were loaded.
            $lockedDocument = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            if ($lockedDocument->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
                throw new InvalidArgumentException('Only a pending document can be corrected.');
            }

            $row = $target->newQuery()->lockForUpdate()->findOrFail($target->getKey());
            $parsedValue = ParsedValueHistory::parsedValue($row, $attribute);
            $previousValue = $this->valueOf($row, $attribute);

            $this->assign($row, $attribute, $value);
            $correctedValue = $this->valueOf($row, $attribute);

            if ($this->comparable($row, $attribute, $correctedValue) === $this->comparable($row, $attribute, $previousValue)) {
                return false;
            }

            if (! $row->save()) {
                throw new RuntimeException('The corrected value could not be saved.');
            }

            $activity = MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
                'event' => self::ACTIVITY_EVENT,
                'entry_type' => 'audit',
                'subject' => $invoice,
                'properties' => [
                    'action_type' => ReviewerActionType::ValueCorrection->value,
                    'field' => $field,
                    'target_type' => $target->getMorphClass(),
                    'target_id' => $target->getKey(),
                    'position' => $this->position($target),
                    'parsed_value' => $parsedValue,
                    'previous_value' => $previousValue,
                    'corrected_value' => $correctedValue,
                    'note' => $note,
                ],
            ]);

            if ($activity === null) {
                throw new RuntimeException('The value correction could not be recorded.');
            }

            return true;
        });

        if ($corrected) {
            $target->refresh();
        }

        return $corrected;
    }

    /**
     * Normalised, groupable field prefix; the concrete row is recorded as the target.
     */
    private function fieldPrefix(Invoice $invoice, Model $target): string
    {
        if ($target instanceof Invoice && $target->is($invoice)) {
            return '';
        }

        if ($target instanceof InvoiceLine && (string) $target->invoice_id === (string) $invoice->getKey()) {
            return 'lines.';
        }

        if ($target instanceof InvoiceAllowanceCharge) {
            $owner = $target->chargeable;

            if ($owner instanceof Invoice && $owner->is($invoice)) {
                return 'allowance_charges.';
            }

            if ($owner instanceof InvoiceLine && (string) $owner->invoice_id === (string) $invoice->getKey()) {
                return 'lines.allowance_charges.';
            }
        }

        throw new InvalidArgumentException('The corrected record does not belong to the document\'s invoice.');
    }

    private function isCorrectable(string $field): bool
    {
        /** @var list<string> $patterns */
        $patterns = (array) config('e-billing.value_correction.fields', ['*']);

        return Str::is($patterns, $field);
    }

    private function position(Model $target): ?int
    {
        $line = $target instanceof InvoiceAllowanceCharge ? $target->chargeable : $target;

        return $line instanceof InvoiceLine ? $line->position : null;
    }

    /**
     * A JSON sub-key must exist in the column's structure; a correction never adds keys.
     */
    private function hasAttribute(Model $model, string $attribute): bool
    {
        [$column, $path] = $this->splitAttribute($attribute);

        return $path === null || Arr::has($this->asArray($model->getAttribute($column)), $path);
    }

    private function valueOf(Model $model, string $attribute): mixed
    {
        [$column, $path] = $this->splitAttribute($attribute);
        $value = $model->getAttribute($column);

        if ($path === null) {
            return $value;
        }

        return Arr::get($this->asArray($value), $path);
    }

    private function assign(Model $model, string $attribute, mixed $value): void
    {
        [$column, $path] = $this->splitAttribute($attribute);

        if ($path === null) {
            $model->setAttribute($column, $value);

            return;
        }

        $data = $this->asArray($model->getAttribute($column));
        Arr::set($data, $path, $value);
        $model->setAttribute($column, $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function asArray(mixed $value): array
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return is_array($value) ? $value : [];
    }

    /**
     * @return array{0: string, 1: string|null} the column and the JSON sub-key path, if any
     */
    private function splitAttribute(string $attribute): array
    {
        $parts = explode('.', $attribute, 2);

        return [$parts[0], $parts[1] ?? null];
    }

    /**
     * Unchanged means equal after normalising to the field's type: numeric columns by value,
     * dates by day, text trimmed (so `0123` differs from `123`), and an empty string as no value.
     */
    private function comparable(Model $model, string $attribute, mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null) {
            return null;
        }

        [$column, $path] = $this->splitAttribute($attribute);
        if ($path === null && is_numeric($value) && $model->hasCast($column, ['decimal', 'int', 'integer', 'float', 'double', 'real'])) {
            return (float) $value;
        }

        return is_scalar($value) ? (string) $value : $value;
    }
}
