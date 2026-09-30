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

    public const REFUSAL_NOT_CONFIGURED = 'not_configured';

    public const REFUSAL_NOT_AUDITED = 'not_audited';

    public const REFUSAL_UNKNOWN_KEY = 'unknown_key';

    /**
     * JSON sub-keys a correction may add when the parser left them out (ADR 0004 addendum): the parser
     * found nothing and the reviewer supplies what the document says. Any other missing key is refused.
     */
    private const CREATABLE_KEYS = [
        'seller.contact.name', 'seller.contact.phone', 'seller.contact.email',
        'buyer.contact.name', 'buyer.contact.phone', 'buyer.contact.email',
        'delivery.name',
        'delivery.address.line1', 'delivery.address.line2', 'delivery.address.postal_code',
        'delivery.address.city', 'delivery.address.subdivision', 'delivery.address.country_code',
        'payment_means.payment_means_code',
        'payment_means.bank_accounts.0.iban', 'payment_means.bank_accounts.0.bic', 'payment_means.bank_accounts.0.bank_name',
        'preceding_invoices.0.number', 'preceding_invoices.0.date',
    ];

    /**
     * Column nullability per table, read once per process.
     *
     * @var array<string, array<string, bool>>
     */
    private static array $nullableColumns = [];

    /**
     * @param  string  $attribute  attribute of the target; JSON sub-keys with dots, e.g. `buyer.vat_id`
     * @return bool false when the value is unchanged
     */
    public function execute(EbillingDocument $document, Model $target, string $attribute, mixed $value, ?string $note = null): bool
    {
        return $this->executeMany($document, $target, [$attribute => $value], $note) > 0;
    }

    /**
     * Several fields of one record saved together, e.g. the parts of an address. Each changed field is
     * still its own correction; unchanged fields record nothing.
     *
     * @param  array<string, mixed>  $values  attribute => corrected value
     * @return int the number of corrections recorded
     */
    public function executeMany(EbillingDocument $document, Model $target, array $values, ?string $note = null): int
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

        $fields = [];
        foreach ($values as $attribute => $value) {
            $fields[$attribute] = $this->field($invoice, $target, $attribute);

            if ($this->refusal($invoice, $target, $attribute) !== null) {
                throw new InvalidArgumentException("The field {$fields[$attribute]} is not correctable.");
            }

            if (is_string($value)) {
                $values[$attribute] = trim($value) === '' ? null : trim($value);
            }

            if ($values[$attribute] === null && ! $this->isNullable($target, $attribute)) {
                throw new InvalidArgumentException("The field {$fields[$attribute]} cannot be empty.");
            }
        }

        $count = DB::transaction(function () use ($document, $invoice, $target, $values, $fields, $note): int {
            // Re-read under lock: an approval or another correction may have landed since the models were loaded.
            $lockedDocument = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            if ($lockedDocument->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
                throw new InvalidArgumentException('Only a pending document can be corrected.');
            }

            $row = $target->newQuery()->lockForUpdate()->findOrFail($target->getKey());

            $parsedValues = [];
            $previousValues = [];
            foreach (array_keys($values) as $attribute) {
                $parsedValues[$attribute] = ParsedValueHistory::parsedValue($row, $attribute);
                $previousValues[$attribute] = $this->valueOf($row, $attribute);
            }

            $this->assign($row, $values);

            $changes = [];
            foreach ($values as $attribute => $value) {
                $correctedValue = $this->valueOf($row, $attribute);
                $corrected = $this->comparable($row, $attribute, $correctedValue);

                // A JSON value object may drop what it cannot hold, e.g. a consignee without an address.
                if ($this->splitAttribute($attribute)[1] !== null && $corrected !== $this->comparable($row, $attribute, $value)) {
                    throw new InvalidArgumentException("The field {$fields[$attribute]} cannot be set to this value on its own.");
                }

                if ($corrected !== $this->comparable($row, $attribute, $previousValues[$attribute])) {
                    $changes[$attribute] = $correctedValue;
                }
            }

            if ($changes === []) {
                return 0;
            }

            if (! $row->save()) {
                throw new RuntimeException('The corrected value could not be saved.');
            }

            foreach ($changes as $attribute => $correctedValue) {
                $activity = MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
                    'event' => self::ACTIVITY_EVENT,
                    'entry_type' => 'audit',
                    'subject' => $invoice,
                    'properties' => [
                        'action_type' => ReviewerActionType::ValueCorrection->value,
                        'field' => $fields[$attribute],
                        'target_type' => $target->getMorphClass(),
                        'target_id' => $target->getKey(),
                        'position' => $this->position($target),
                        'parsed_value' => $parsedValues[$attribute],
                        'previous_value' => $previousValues[$attribute],
                        'corrected_value' => $correctedValue,
                        'note' => $note,
                    ],
                ]);

                if ($activity === null) {
                    throw new RuntimeException('The value correction could not be recorded.');
                }
            }

            return count($changes);
        });

        if ($count > 0) {
            $target->refresh();
        }

        return $count;
    }

    /**
     * Why a field of this record cannot be corrected, as one of the `REFUSAL_*` codes, or null when it can.
     * The approval state is not part of it: that is checked under lock when saving.
     */
    public function refusal(Invoice $invoice, Model $target, string $attribute): ?string
    {
        if (! $this->isCorrectable($this->field($invoice, $target, $attribute))) {
            return self::REFUSAL_NOT_CONFIGURED;
        }

        if (! ParsedValueHistory::isAudited($target, $attribute)) {
            return self::REFUSAL_NOT_AUDITED;
        }

        return $this->hasAttribute($target, $attribute) ? null : self::REFUSAL_UNKNOWN_KEY;
    }

    /**
     * The normalised field a correction of this record's attribute is recorded under, e.g. `lines.quantity`.
     */
    public function field(Invoice $invoice, Model $target, string $attribute): string
    {
        return $this->fieldPrefix($invoice, $target).$attribute;
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
     * A JSON sub-key must exist in the column's structure, or be one the parser may have left out.
     */
    private function hasAttribute(Model $model, string $attribute): bool
    {
        [$column, $path] = $this->splitAttribute($attribute);

        return $path === null
            || in_array($attribute, self::CREATABLE_KEYS, true)
            || Arr::has($this->asArray($model->getAttribute($column)), $path);
    }

    /**
     * A column-level attribute can be cleared only when its column accepts null; JSON sub-keys are
     * checked by their value object when it is rebuilt.
     */
    private function isNullable(Model $model, string $attribute): bool
    {
        [$column, $path] = $this->splitAttribute($attribute);
        if ($path !== null) {
            return true;
        }

        $table = $model->getTable();
        self::$nullableColumns[$table] ??= collect($model->getConnection()->getSchemaBuilder()->getColumns($table))
            ->mapWithKeys(fn (array $definition): array => [$definition['name'] => (bool) $definition['nullable']])
            ->all();

        return self::$nullableColumns[$table][$column] ?? true;
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

    /**
     * JSON sub-keys of one column are set together, so its value object is rebuilt once from all of them.
     *
     * @param  array<string, mixed>  $values
     */
    private function assign(Model $model, array $values): void
    {
        $columns = [];

        foreach ($values as $attribute => $value) {
            [$column, $path] = $this->splitAttribute($attribute);

            if ($path === null) {
                $model->setAttribute($column, $value);

                continue;
            }

            $columns[$column] ??= $this->asArray($model->getAttribute($column));
            Arr::set($columns[$column], $path, $value);
        }

        foreach ($columns as $column => $data) {
            $model->setAttribute($column, $data);
        }
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
