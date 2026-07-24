<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Moox\Contact\Database\Factories\ContactFactory;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Data\Models\StaticLanguage;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany<Model, $this> companies()
 * @method \Illuminate\Database\Eloquent\Relations\MorphToMany<Model, $this> addresses()
 * @method \Illuminate\Database\Eloquent\Relations\MorphToMany<Model, $this> address()
 */
class Contact extends BaseRecordModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, FilamentUser, HasName
{
    /** @use HasFactory<ContactFactory> */
    use Authenticatable;

    use Authorizable;
    use CanResetPassword;
    use HasFactory;
    use HasModelTaxonomy;
    use HasUuids;
    use Notifiable;

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
        'email',
        'username',
        'email_verified_at',
        'password',
        'phone',
        'mobile',
        'language_id',
        'contact_type',
        'note',
        'external_reference',
        'data',
        // Needed so transform field_map can persist soft-deletes via mass assignment.
        'deleted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'language_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    /**
     * @return BelongsTo<StaticLanguage, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(StaticLanguage::class, 'language_id');
    }

    public function displayLabel(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        $name = trim(implode(' ', array_filter([$this->first_name, $this->last_name])));

        return $name !== '' ? $name : (string) $this->getKey();
    }

    public function canAuthenticate(): bool
    {
        return filled($this->username) && filled($this->getAuthPassword());
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->canAuthenticate() || $this->status !== 'active') {
            return false;
        }

        // Contacts authenticate for portal panels, not the admin panel.
        return $panel->getId() !== 'admin';
    }

    public function getFilamentName(): string
    {
        return $this->displayLabel();
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
