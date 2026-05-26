<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Address\Database\Factories\AddressFactory;
use Moox\Address\Exceptions\DuplicateAddressException;
use Moox\Address\Support\AddressFingerprint;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

/**
 * @property string|null $label
 * @property string|null $name
 * @property string|null $street
 * @property string|null $street2
 * @property string|null $postal_code
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country_code
 * @property bool $is_primary
 * @property array<string, mixed>|null $data
 */
class Address extends BaseItemModel
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    use HasModelTaxonomy;
    use SoftDeletes;

    protected $fillable = [
        'label',
        'name',
        'street',
        'street2',
        'postal_code',
        'city',
        'state',
        'country_code',
        'is_primary',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'data' => 'array',
        ];
    }

    public static function getResourceName(): string
    {
        return 'address';
    }

    public static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    /**
     * @return HasMany<Addressable, $this>
     */
    public function addressables(): HasMany
    {
        return $this->hasMany(Addressable::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Address $address): void {
            if ($address->country_code !== null) {
                $address->country_code = strtoupper(trim($address->country_code));
            }

            if ($address->findDuplicate()) {
                throw DuplicateAddressException::forAddress();
            }
        });
    }

    public function findDuplicate(): ?self
    {
        $query = static::withFingerprint(AddressFingerprint::fromAddress($this));

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->first();
    }

    /**
     * @param  Builder<Address>  $query
     * @param  array<string, ?string>  $fingerprint
     * @return Builder<Address>
     */
    public function scopeWithFingerprint(Builder $query, array $fingerprint): Builder
    {
        foreach (AddressFingerprint::columns() as $column) {
            $value = $fingerprint[$column] ?? null;

            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    public function formattedLine(): string
    {
        return collect([
            $this->name,
            trim(implode(' ', array_filter([$this->street, $this->street2]))),
            trim(implode(' ', array_filter([$this->postal_code, $this->city]))),
            $this->country_code,
        ])
            ->filter()
            ->implode(', ');
    }
}
