<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\InvoiceNotes;

/**
 * Reads and writes notes in the {text, subject_code} shape, whatever shape the row was written in.
 * Texts are kept as they are, so a note keeps its index.
 *
 * @phpstan-import-type InvoiceNote from InvoiceNotes
 *
 * @implements CastsAttributes<list<InvoiceNote>|null, mixed>
 */
class InvoiceNotesCast implements CastsAttributes
{
    /**
     * @return list<InvoiceNote>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        $notes = InvoiceNotes::fromStored(is_string($value) ? json_decode($value, true) : $value);

        return $notes !== [] ? $notes : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $notes = InvoiceNotes::fromStored($value);

        return $notes !== [] ? json_encode($notes, JSON_THROW_ON_ERROR) : null;
    }
}
