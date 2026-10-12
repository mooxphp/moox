<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\EBilling\Actions\ClassifyDocumentTypeAction;
use Moox\EBilling\Actions\CorrectFieldValueAction;
use Moox\EBilling\Actions\SetRecipientEmailAction;

/**
 * How one row of the review workspace is edited (ADR 0004): which record and attributes a save
 * writes and with which input, or why the row cannot be edited.
 */
final readonly class ReviewEditField
{
    /** Parsed values, saved through {@see CorrectFieldValueAction}. */
    public const KIND_VALUE = 'value';

    /** Document type, saved through {@see ClassifyDocumentTypeAction}. */
    public const KIND_CLASSIFICATION = 'classification';

    /** Delivery recipient, saved through {@see SetRecipientEmailAction}. */
    public const KIND_RECIPIENT = 'recipient';

    public const KIND_NONE = 'none';

    /**
     * @param  array<string, string>  $inputs  attribute => input type (text, textarea, date, decimal, integer, email)
     * @param  ?string  $crossChecked  the cross-checked field whose master-data options the inputs offer
     * @param  ?string  $reason  translated reason the row cannot be edited
     */
    public function __construct(
        public string $key,
        public string $kind,
        public string $label,
        public ?Model $target = null,
        public array $inputs = [],
        public ?string $crossChecked = null,
        public ?string $reason = null,
    ) {
    }

    public function isEditable(): bool
    {
        return $this->kind !== self::KIND_NONE;
    }
}
