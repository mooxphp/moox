<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Closure;
use Moox\EBilling\Models\EbillingDocument;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\DTOs\KositResult;

final class ArtifactValidationPersister
{
    /**
     * Persist KOSIT verdict and attach optional supplemental verdicts (e.g. veraPDF via closure).
     *
     * Supplemental persisters stay outside this class so e-billing does not type-hint optional
     * validator packages in the shared persistence seam.
     *
     * @param  list<Closure(EbillingDocument): void>  $supplementalPersisters
     */
    public function persist(
        ?EbillingDocument $document,
        KositResult $kositResult,
        RecordKositValidation $recordKositValidation,
        array $supplementalPersisters = [],
    ): void {
        $kositValidation = $recordKositValidation($kositResult);

        if ($document === null) {
            return;
        }

        $document->kositValidations()->attach($kositValidation->id);

        foreach ($supplementalPersisters as $persistSupplemental) {
            $persistSupplemental($document);
        }
    }
}
