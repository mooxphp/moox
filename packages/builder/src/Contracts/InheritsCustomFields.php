<?php

declare(strict_types=1);

namespace Moox\Builder\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Optional seam: empty custom-field values fall back to a parent record.
 * Domain packages decide the parent; Builder stays entity-agnostic.
 */
interface InheritsCustomFields
{
    public function customFieldsParent(): ?Model;

    public function customFieldsParentEntity(): string;
}
