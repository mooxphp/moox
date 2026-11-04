<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Contracts\BuyerMasterDataResolver;
use Moox\EBilling\Data\BuyerLookup;
use Moox\EBilling\Data\BuyerMasterData;
use Moox\EBilling\Data\BuyerMasterDataResult;
use Moox\EBilling\Data\PartyReference;
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

        if ($result->status === BuyerMasterDataStatus::Ambiguous) {
            return BuyerMasterDataResult::noMatch();
        }

        if ($result->buyer instanceof BuyerMasterData && ! $this->exists($result->buyer)) {
            Log::error('[EBilling] Buyer resolver matched a party that does not exist; master data is unavailable', [
                'ebilling_document_id' => $document->getKey(),
                'resolver' => $this->resolver::class,
                'party' => (array) $result->buyer->party,
                'contact' => $result->buyer->contact === null ? null : (array) $result->buyer->contact,
            ]);

            return BuyerMasterDataResult::unavailable();
        }

        return $result;
    }

    /**
     * A morph has no foreign key, so the parties are checked here, before they are attributed.
     */
    private function exists(BuyerMasterData $buyer): bool
    {
        return $this->partyExists($buyer->party)
            && ($buyer->contact === null || $this->partyExists($buyer->contact));
    }

    private function partyExists(PartyReference $party): bool
    {
        $model = Relation::getMorphedModel($party->type) ?? $party->type;

        if (! is_a($model, Model::class, true)) {
            return false;
        }

        return $model::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereKey($party->id)
            ->exists();
    }
}
