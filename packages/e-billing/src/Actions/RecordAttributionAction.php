<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Data\PartyReference;
use Moox\EBilling\Enums\AttributionRole;
use Moox\EBilling\Enums\AttributionSource;
use Moox\EBilling\Models\EbillingAttribution;
use Moox\EBilling\Models\EbillingDocument;

/**
 * The one writer of `ebilling_attributions`: ends the document's active row for the role and adds the new party.
 * The same party with the same source is left alone; a null party only ends the active row.
 */
final class RecordAttributionAction
{
    /**
     * The buyer and its contact, recorded together with the same source.
     *
     * @return bool whether either attribution changed
     */
    public function executeBuyer(
        EbillingDocument $document,
        ?PartyReference $buyer,
        ?PartyReference $contact,
        ?AttributionSource $source,
    ): bool {
        $buyerChanged = $this->execute($document, AttributionRole::Buyer, $buyer, $source);
        $contactChanged = $this->execute($document, AttributionRole::BuyerContact, $contact, $source);

        return $buyerChanged || $contactChanged;
    }

    /**
     * @return bool whether the attribution changed
     */
    public function execute(
        EbillingDocument $document,
        AttributionRole $role,
        ?PartyReference $party,
        ?AttributionSource $source,
    ): bool {
        return $document->getConnection()->transaction(function () use ($document, $role, $party, $source): bool {
            // The document row serialises writers: with no active row yet there is nothing else to lock.
            $document->newQuery()->whereKey($document->getKey())->lockForUpdate()->first();

            $active = $document->attributions()
                ->where('role', $role->value)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($active instanceof EbillingAttribution
                && $party?->equals($active->partyReference())
                && $active->attribution_source === $source
            ) {
                return false;
            }

            if ($active === null && $party === null) {
                return false;
            }

            $active?->forceFill(['ended_at' => now()])->save();

            if ($party !== null) {
                $document->attributions()->create([
                    'role' => $role,
                    'party_type' => $party->type,
                    'party_id' => $party->id,
                    'attribution_source' => $source,
                ]);
            }

            $document->unsetRelation('attributions');

            return true;
        });
    }
}
