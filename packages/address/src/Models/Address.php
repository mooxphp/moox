<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

class Address extends Model
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'addresses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'street',
        'house_number',
        'address_line_2',
        'po_box',
        'postal_code',
        'city',
        'state',
        'note',
        'owner_id',
        'owner_type',
        'country_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function getResourceName(): string
    {
        return 'address';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_id' => 'integer',
            'country_id' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Titles of the linked address usages, for the contact address table.
     */
    protected function usageTitles(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->trashed()) {
                $this->setRelation('usages', $this->usages()->withTrashed()->with('addressUsageType')->get());
            } else {
                $this->loadMissing('usages.addressUsageType');
            }

            return $this->usages
                ->map(fn (AddressUsage $usage): ?string => $this->usageTypeTitle($usage->addressUsageType))
                ->filter()
                ->implode(', ');
        });
    }

    protected function usageTypeTitle(?AddressUsageType $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $locale = app()->getLocale();
        $shortLocale = strstr($locale, '_', true) ?: $locale;

        foreach ([$locale, $shortLocale] as $candidate) {
            $title = $type->translate($candidate, false)?->title;

            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        $title = $type->title;

        return is_string($title) && $title !== '' ? $title : null;
    }
}
