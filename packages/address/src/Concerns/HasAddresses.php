<?php

declare(strict_types=1);

namespace Moox\Address\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Moox\Address\Models\Address;
use Moox\Address\Models\Addressable;
use Moox\Address\Support\AddressRelationConfig;

trait HasAddresses
{
    /**
     * @return MorphToMany<Address, $this>
     */
    public function addresses(): MorphToMany
    {
        $morphName = (string) (AddressRelationConfig::addressables()['morph_name'] ?? 'addressable');
        $pivotTable = AddressRelationConfig::pivotTable();

        return $this->morphToMany(
            Address::class,
            $morphName,
            $pivotTable,
            "{$morphName}_id",
            'address_id',
            'id',
            'id',
        )
            ->using(Addressable::class)
            ->withPivot(array_keys(AddressRelationConfig::pivotColumns()))
            ->withTimestamps();
    }
}
