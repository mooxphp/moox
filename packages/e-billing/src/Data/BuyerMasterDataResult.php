<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Enums\BuyerMasterDataStatus;

final readonly class BuyerMasterDataResult
{
    private function __construct(
        public BuyerMasterDataStatus $status,
        public ?BuyerMasterData $buyer = null,
    ) {
    }

    public static function matched(BuyerMasterData $buyer): self
    {
        return new self(BuyerMasterDataStatus::Matched, $buyer);
    }

    public static function noMatch(): self
    {
        return new self(BuyerMasterDataStatus::NoMatch);
    }

    public static function ambiguous(): self
    {
        return new self(BuyerMasterDataStatus::Ambiguous);
    }

    public static function unavailable(): self
    {
        return new self(BuyerMasterDataStatus::Unavailable);
    }

    public function isUnavailable(): bool
    {
        return $this->status === BuyerMasterDataStatus::Unavailable;
    }
}
