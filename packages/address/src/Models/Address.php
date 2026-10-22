<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class Address extends Model
{
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use SoftDeletes;

    protected $table = 'addresses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'name',
        'street',
        'house_number',
        'address_line_2',
        'po_box',
        'postal_code',
        'city',
        'state',
        'note',
        'external_reference',
        'external_info',
        'external_status',
        'owner_id',
        'owner_type',
        'country_id',
        'data',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'archived_at',
        'archived_by_id',
        'archived_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
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
            'is_active' => 'boolean',
            'data' => 'array',
            'owner_id' => 'integer',
            'country_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'archived_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'archived_at' => 'datetime',
            'restored_at' => 'datetime',
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
