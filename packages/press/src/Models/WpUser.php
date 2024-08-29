<?php

namespace Moox\Press\Models;

use Awobaz\Mutator\Mutable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Moox\Press\QueryBuilder\UserQueryBuilder;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $user_email
 */
class WpUser extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Mutable, Notifiable;

    protected $fillable = [
        'user_login',
        'user_pass',
        'user_nicename',
        'user_email',
        'user_url',
        'user_registered',
        'user_activation_key',
        'user_status',
        'display_name',
    ];

    protected $appends = [
        'name',
        'email',
        'password',
    ];

    protected $wpPrefix;

    protected $table;

    protected $metatable;

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $metaFieldsInitialized = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $defaultUserMeta = config('press.default_user_meta');

        $this->fillable = array_keys($defaultUserMeta);

        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix . 'users';
        $this->metatable = $this->wpPrefix . 'usermeta';

        $this->initializeMetaFields();
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->addOrUpdateMeta('created_at', now()->toDateTimeString());
        });

        static::updated(function ($model) {
            $model->addOrUpdateMeta('updated_at', now()->toDateTimeString());
        });

        static::deleted(function ($model) {
            $model->userMeta()->delete();
        });

        static::addGlobalScope('addAttributes', function (Builder $builder) {
            $builder->addSelect([
                'ID',
                'ID as id',
                'user_login',
                'user_login as name',
                'user_email',
                'user_email as email',
                'user_pass',
                'user_pass as password',
                'display_name',
                'user_nicename',
                'user_url',
                'user_registered',
                'user_activation_key',
                'user_status',
            ]);
        });
    }

    protected function initializeMetaFields()
    {
        if ($this->metaFieldsInitialized) {
            return;
        }

        $this->metaFieldsInitialized = true;
    }

    public function getAttribute($key)
    {
        // First, check if the key exists as a native attribute or relationship
        $value = parent::getAttribute($key);

        // If the native attribute is not found, look for the meta field
        if (is_null($value) && $this->metaFieldsInitialized && $this->isMetaField($key)) {
            return $this->getMeta($key);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        // Check if the key is a meta field first
        if ($this->metaFieldsInitialized && $this->isMetaField($key)) {
            $this->addOrUpdateMeta($key, $value);
        } else {
            parent::setAttribute($key, $value);
        }

        return $this;
    }

    public function toArray()
    {
        $attributes = parent::toArray();

        // Include meta fields in the array representation
        $metaFields = config('press.default_user_meta', []);
        foreach ($metaFields as $key => $defaultValue) {
            $attributes[$key] = $this->getMeta($key) ?? $defaultValue;
        }

        return $attributes;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function getMeta($key)
    {
        if (! $this->relationLoaded('userMeta')) {
            $this->load('userMeta');
        }

        $meta = $this->userMeta->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
    }

    public function addOrUpdateMeta($key, $value)
    {
        WpUserMeta::updateOrCreate(
            ['user_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    public function userMeta(): HasMany
    {
        return $this->hasMany(WpUserMeta::class, 'user_id', 'ID');
    }

    public function attachment()
    {
        return $this->belongsTo(WpPost::class, 'mm_sua_attachment_id', 'ID');
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        $grammar = $connection->getQueryGrammar();
        $processor = $connection->getPostProcessor();

        return new UserQueryBuilder($connection, $grammar, $processor);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected function isMetaField($key)
    {
        return array_key_exists($key, config('press.default_user_meta', []));
    }

    public function getNameAttribute()
    {
        return $this->getAttribute('user_login');
    }

    public function setNameAttribute($value)
    {
        return $this->setAttribute('user_login', $value);
    }

    public function getEmailAttribute()
    {
        return $this->getAttribute('user_email');
    }

    public function setEmailAttribute($value)
    {
        $this->setAttribute('user_email', $value);
    }

    public function getPasswordAttribute()
    {
        return $this->getAttribute('user_pass');
    }

    public function setPasswordAttribute($value)
    {
        $this->setAttribute('user_pass', $value);
    }
}
