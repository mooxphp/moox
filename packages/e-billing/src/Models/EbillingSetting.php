<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\EBilling\Enums\RecipientFormatChoice;

/**
 * A recipient's e-billing settings, attached to any model (the portal uses the buyer's contact).
 *
 * @property int $id
 * @property string $owner_type
 * @property string $owner_id
 * @property RecipientFormatChoice|null $preferred_ebilling_format
 * @property list<string>|null $ebilling_recipients Reserved for the portal role model; not read yet.
 */
class EbillingSetting extends Model
{
    protected $table = 'ebilling_settings';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'preferred_ebilling_format',
        'ebilling_recipients',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_ebilling_format' => RecipientFormatChoice::class,
            'ebilling_recipients' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public static function for(Model $owner): ?self
    {
        return self::forReference($owner->getMorphClass(), (string) $owner->getKey());
    }

    public static function forReference(string $ownerType, string $ownerId): ?self
    {
        return self::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->first();
    }
}
