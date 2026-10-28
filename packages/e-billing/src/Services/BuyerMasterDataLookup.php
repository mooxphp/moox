<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Illuminate\Support\Facades\Log;
use Moox\EBilling\Contracts\BuyerMasterDataResolver;
use Moox\EBilling\Data\BuyerLookup;
use Moox\EBilling\Data\BuyerMasterDataResult;
use Moox\EBilling\Enums\BuyerMasterDataStatus;
use Moox\EBilling\Models\EbillingDocument;
use Throwable;

/**
 * The one place the bound {@see BuyerMasterDataResolver} is called: a failing resolver never fails the document,
 * it is logged and becomes {@see BuyerMasterDataStatus::Unavailable} (ADR 0020).
 */
final class BuyerMasterDataLookup
{
    public function __construct(private BuyerMasterDataResolver $resolver)
    {
    }

    public function resolve(BuyerLookup $lookup, EbillingDocument $document): BuyerMasterDataResult
    {
        try {
            $result = $this->resolver->resolve($lookup);
        } catch (Throwable $exception) {
            Log::error('[EBilling] Buyer master-data resolver failed; treating master data as unavailable', [
                'ebilling_document_id' => $document->getKey(),
                'resolver' => $this->resolver::class,
                'exception' => $exception,
            ]);

            return BuyerMasterDataResult::unavailable();
        }

        return $result->status === BuyerMasterDataStatus::Ambiguous ? BuyerMasterDataResult::noMatch() : $result;
    }
}
