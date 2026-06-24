<?php

declare(strict_types=1);

namespace Moox\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Staff\Database\Factories\StaffFactory;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo<\Illuminate\Database\Eloquent\Model, $this> user()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo<\Illuminate\Database\Eloquent\Model, $this> contact()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<StaffAssignment, $this> staffAssignments()
 */
class Staff extends BaseRecordModel implements HasMedia
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    use HasModelTaxonomy;
    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'staff';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'status',
        'legacy_id',
        'external_reference',
        'short_code',
        'display_name',
        'first_name',
        'last_name',
        'job_title',
        'department',
        'email',
        'email_account',
        'phone',
        'fax',
        'language_code',
        'user_id',
        'contact_id',
        'sales_unit_guid',
        'sales_unit_id',
        'can_change',
        'is_system_user',
        'is_internal',
        'is_user_for_services',
        'is_active',
        'bcc_on_mail_send',
        'data',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'legacy_id' => 'integer',
            'sales_unit_id' => 'integer',
            'user_id' => 'integer',
            'can_change' => 'boolean',
            'is_system_user' => 'boolean',
            'is_internal' => 'boolean',
            'is_user_for_services' => 'boolean',
            'is_active' => 'boolean',
            'bcc_on_mail_send' => 'boolean',
            'data' => 'array',
        ];
    }

    public static function getResourceName(): string
    {
        return 'staff';
    }

    public static function newFactory(): StaffFactory
    {
        return StaffFactory::new();
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatar')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300);
    }

    public function displayLabel(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        $fullName = trim(implode(' ', array_filter([$this->first_name, $this->last_name])));

        if ($fullName !== '') {
            return $fullName;
        }

        if ($this->short_code) {
            return $this->short_code;
        }

        return (string) $this->getKey();
    }
}
