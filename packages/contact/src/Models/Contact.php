<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Moox\Contact\Database\Factories\ContactFactory;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'contacts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'name_1',
        'name_2',
        'name_3',
        'note',
        'external_reference',
        'external_info',
        'external_status',
        'language_id',
        'country_id',
        'organization_type_id',
        'legal_form_id',
        'data_json',
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
        'data_json' => '{}',
    ];

    public static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'data_json' => 'array',
            'language_id' => 'integer',
            'country_id' => 'integer',
            'organization_type_id' => 'integer',
            'legal_form_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'archived_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'archived_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Contact $contact): void {
            if (! filled($contact->ulid)) {
                $contact->ulid = (string) Str::ulid();
            }

            if (! filled($contact->uuid)) {
                $contact->uuid = (string) Str::uuid();
            }

            $user = Auth::user();

            if ($contact->created_by_id === null && $user !== null) {
                $contact->created_by_id = (int) $user->getAuthIdentifier();
                $contact->created_by_type = $user::class;
            }

            if ($contact->updated_by_id === null) {
                $contact->updated_by_id = $contact->created_by_id;
                $contact->updated_by_type = $contact->created_by_type;
            }
        });

        static::updating(function (Contact $contact): void {
            foreach (['ulid', 'uuid', 'created_at', 'created_by_id', 'created_by_type'] as $attribute) {
                if ($contact->isDirty($attribute)) {
                    $contact->setAttribute($attribute, $contact->getOriginal($attribute));
                }
            }

            $user = Auth::user();

            if ($user !== null) {
                $contact->updated_by_id = (int) $user->getAuthIdentifier();
                $contact->updated_by_type = $user::class;
            }
        });

        static::saved(function (Contact $contact): void {
            $displayName = $contact->newQuery()->whereKey($contact->getKey())->value('display_name');
            $contact->display_name = is_string($displayName) ? $displayName : null;
            $contact->syncOriginalAttribute('display_name');
        });
    }
}
