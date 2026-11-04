<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * A party of any model, as a morph type (class or morph alias) and key. Keys are strings so big integer and UUID
 * keys fit alike.
 */
final readonly class PartyReference
{
    public function __construct(
        public string $type,
        public string $id,
    ) {
    }

    public static function of(Model $party): self
    {
        return new self($party->getMorphClass(), (string) $party->getKey());
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function isOf(string $model): bool
    {
        return $this->type === (new $model)->getMorphClass();
    }

    public function equals(?self $other): bool
    {
        return $other !== null && $other->type === $this->type && $other->id === $this->id;
    }
}
