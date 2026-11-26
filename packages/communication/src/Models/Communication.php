<?php

declare(strict_types=1);

namespace Moox\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;
use Moox\Communication\Enums\CommunicationValidationType;
use Moox\Communication\Support\CommunicationValueValidator;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;

class Communication extends Model
{
    use HasActive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'communications';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'value',
        'label',
        'is_primary',
        'is_verified',
        'verified_at',
        'note',
        'owner_id',
        'owner_type',
        'communication_type_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_primary' => false,
        'is_verified' => false,
    ];

    public static function getResourceName(): string
    {
        return 'communication';
    }

    protected static function booted(): void
    {
        static::saving(function (self $communication): void {
            if ($communication->exists && ! $communication->isDirty(['value', 'communication_type_id', 'owner_id', 'owner_type'])) {
                return;
            }

            $communication->value = $communication->canonicalValue();
        });
    }

    public function canonicalValue(): string
    {
        $validationType = $this->validationType();
        $type = CommunicationValidationType::from($validationType);
        $canonical = app(CommunicationValueValidator::class)->canonicalize(
            $validationType,
            $this->value,
            $type->usesCallingCode() ? $this->ownerCallingCode() : null,
        );

        if ($canonical === null) {
            throw ValidationException::withMessages([
                'value' => __($this->invalidValueKey($type)),
            ]);
        }

        return $canonical;
    }

    private function validationType(): string
    {
        $type = $this->relationLoaded('communicationType')
            ? $this->getRelation('communicationType')
            : $this->communicationType()->first();

        $validationType = $type instanceof CommunicationType ? $type->validation_type : null;
        if (! is_string($validationType) || CommunicationValidationType::tryFrom($validationType) === null) {
            throw ValidationException::withMessages([
                'communication_type_id' => __('communication::fields.communication_type_invalid'),
            ]);
        }

        return $validationType;
    }

    private function ownerCallingCode(): ?string
    {
        if ($this->owner_id === null || $this->owner_type === null) {
            return null;
        }

        $owner = $this->relationLoaded('owner') ? $this->getRelation('owner') : $this->owner()->first();
        if (! is_object($owner) || ! method_exists($owner, 'callingCode')) {
            return null;
        }

        $callingCode = $owner->callingCode();

        return is_string($callingCode) && $callingCode !== '' ? $callingCode : null;
    }

    private function invalidValueKey(CommunicationValidationType $type): string
    {
        return match ($type) {
            CommunicationValidationType::Email => 'communication::fields.value_invalid_email',
            CommunicationValidationType::Phone, CommunicationValidationType::Fax => 'communication::fields.value_invalid_phone',
            CommunicationValidationType::Url => 'communication::fields.value_invalid_url',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'owner_id' => 'integer',
            'communication_type_id' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
