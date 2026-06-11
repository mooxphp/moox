<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Contact\Database\Factories\ContactFactory;
use Moox\Contact\Support\CompanyContactRelation;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

class Contact extends BaseRecordModel
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasModelTaxonomy;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'contacts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'status',
        'gender',
        'salutation_code',
        'academic_title',
        'first_name',
        'last_name',
        'display_name',
        'job_title',
        'department',
        'email',
        'phone',
        'mobile',
        'language_id',
        'user_id',
        'contact_type',
        'is_active',
        'is_system_user',
        'note',
        'external_reference',
        'data',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system_user' => 'boolean',
            'data' => 'array',
            'language_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public static function getResourceName(): string
    {
        return 'contact';
    }

    public static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    /** @return BelongsToMany<Model, Contact, Pivot> */
    public function companies(): BelongsToMany
    {
        return CompanyContactRelation::forContact($this);
    }

    /** @return MorphToMany<Model, $this> */
    public function addresses(): MorphToMany
    {
        return $this->relation('addressables');
    }

    /** @return MorphToMany<Model, $this> */
    public function address(): MorphToMany
    {
        return $this->primaryRelation('addressables');
    }

    public function displayLabel(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        $name = trim(implode(' ', array_filter([$this->first_name, $this->last_name])));

        return $name !== '' ? $name : (string) $this->getKey();
    }

    protected static function booted(): void
    {
        static::saving(function (Contact $contact): void {
            if ($contact->display_name === null || $contact->display_name === '') {
                $generated = trim(implode(' ', array_filter([$contact->first_name, $contact->last_name])));

                if ($generated !== '') {
                    $contact->display_name = $generated;
                }
            }
        });
    }
}
