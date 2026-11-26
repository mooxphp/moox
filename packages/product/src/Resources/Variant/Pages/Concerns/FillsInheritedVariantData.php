<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Variant\Pages\Concerns;

use Moox\Product\Models\Variant;

trait FillsInheritedVariantData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Variant) {
            return $data;
        }

        return $record->mergeInheritedAttributesForForm($data);
    }
}
