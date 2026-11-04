<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Moox\EBilling\Actions\RecordAttributionAction;
use Moox\EBilling\Data\PartyReference;
use Moox\EBilling\Enums\AttributionRole;
use Moox\EBilling\Enums\AttributionSource;

/**
 * One attribution of a document to a party in a role. A re-attribution ends the active row (`ended_at`) and adds a
 * new one, so the history stays reconstructable. At most one row per document and role is active; written only
 * through {@see RecordAttributionAction}.
 *
 * @property int $id
 * @property string $ebilling_document_id
 * @property AttributionRole $role
 * @property string $party_type
 * @property string $party_id
 * @property AttributionSource|null $attribution_source
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property-read string $role_label
 * @property-read string $party_label
 * @property-read string|null $attribution_source_label
 */
class EbillingAttribution extends Model
{
    protected $table = 'ebilling_attributions';

    protected $fillable = [
        'ebilling_document_id',
        'role',
        'party_type',
        'party_id',
        'attribution_source',
        'ended_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AttributionRole::class,
            'attribution_source' => AttributionSource::class,
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EbillingDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(EbillingDocument::class, 'ebilling_document_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function partyReference(): PartyReference
    {
        return new PartyReference($this->party_type, $this->party_id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    protected function roleLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->role->label());
    }

    protected function partyLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $party = $this->party;
            $name = $party?->getAttribute('customer_name')
                ?? $party?->getAttribute('display_name')
                ?? $party?->getAttribute('name');

            return filled($name)
                ? "{$name} (#{$this->party_id})"
                : class_basename($this->party_type).' #'.$this->party_id;
        });
    }

    protected function attributionSourceLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attribution_source === null
            ? null
            : __('e-billing::fields.attribution_source.'.$this->attribution_source->value));
    }
}
