<?php

namespace Moox\Press\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Moox\Press\Database\Factories\WpUserFactory;
use Moox\Press\QueryBuilder\UserQueryBuilder;

/**
 * @property int $ID
 * @property string $user_login
 * @property string $user_nicename
 * @property string $user_email
 * @property \Illuminate\Database\Eloquent\Collection $userMeta
 */
class WpUser extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

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

    protected $searchableFields = ['*'];

    protected $wpPrefix;

    protected $table;

    protected $metatable;

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $tempMetaAttributes = [];

    /* Append aditional attributes to the model, especially meta keys */
    protected $appends = [
        'nickname',
        'first_name',
        'last_name',
        'description',
        'created_at',
        'updated_at',
        'session_tokens',
        'remember_token',
        'email_verified_at',
        'mm_sua_attachment_id',
        'moox_user_attachment_id',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'users';
        $this->metatable = $this->wpPrefix.'usermeta';
    }

    protected static function boot()
    {
        parent::boot();

        /* Provide ALL meta attributes that should be created, from config */
        static::created(function ($model) {
            $model->addOrUpdateMeta('created_at', now()->toDateTimeString());
        });

        /* Provide ALL meta attributes that should be updated, from config */
        static::updated(function ($model) {
            $model->addOrUpdateMeta('updated_at', now()->toDateTimeString());
        });

        static::deleted(function ($model) {
            $model->userMeta()->delete();
        });

        /* Provide ALL fields that should be queryable by the model */
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
            ]);
        });
    }

    /* Provide a custom query builder for the model */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        $grammar = $connection->getQueryGrammar();
        $processor = $connection->getPostProcessor();

        return new UserQueryBuilder($connection, $grammar, $processor);
    }

    protected $casts = [
        'user_registered' => 'datetime',
        'spam' => 'boolean',
        'deleted' => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return WpUserFactory::new();
    }

    /* FilamentUser implementation */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /* Default userMeta relationship */
    public function userMeta(): HasMany
    {
        return $this->hasMany(WpUserMeta::class, 'user_id', 'ID');
    }

    /* Convenient access to all meta attributes */
    public function getAllMetaAttributes()
    {
        $metas = $this->userMeta->pluck('meta_value', 'meta_key')->toArray();
        foreach ($metas as $key => $value) {
            $metas[$key] = $this->getMeta($key);
        }

        return $metas;
    }

    /* Default meta attribute access */
    public function meta($key)
    {
        if (! Str::startsWith($key, $this->wpPrefix)) {
            $key = "{$this->wpPrefix}{$key}";
        }

        return $this->getMeta($key);
    }

    /* This is probably a duplicate to the static created event */
    public function fill(array $attributes)
    {
        $userAttributes = [];
        $this->tempMetaAttributes = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $userAttributes[$key] = $value;
            } else {
                $this->tempMetaAttributes[$key] = $value;
            }
        }

        parent::fill($userAttributes);

        return $this;
    }

    /* This is probably a duplicate to the static created event */
    public function save(array $options = [])
    {
        $saved = parent::save($options);

        foreach ($this->tempMetaAttributes as $key => $value) {
            $this->addOrUpdateMeta($key, $value);
        }

        return $saved;
    }

    /* This is probably a duplicate to the static updated event */
    public function addOrUpdateMeta($key, $value)
    {
        /** @disregard Intelephense P1036 Non static method 'pluck' should not be called statically. */
        WpUserMeta::updateOrCreate(
            ['user_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    protected function getMeta($key)
    {
        $meta = $this->userMeta()->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
    }

    /* All getters and setters for meta attributes */
    public function getEmailAttribute()
    {
        return $this->attributes['user_email'] ?? null;
    }

    public function setEmailAttribute($value)
    {
        $this->addOrUpdateMeta('user_email', $value);
    }

    public function getNameAttribute()
    {
        return $this->attributes['user_login'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->addOrUpdateMeta('user_login', $value);
    }

    public function getPasswordAttribute()
    {
        return $this->attributes['user_pass'] ?? null;
    }

    public function setPasswordAttribute($value)
    {
        $this->addOrUpdateMeta('user_pass', $value);
    }

    public function getDisplayNameAttribute()
    {
        return $this->attributes['display_name'] ?? null;
    }

    public function setDisplayNameAttribute($value)
    {
        $this->addOrUpdateMeta('display_name', $value);
    }

    public function getNicknameAttribute()
    {
        return $this->getMeta('nickname') ?? null;
    }

    public function setNicknameAttribute($value)
    {
        $this->addOrUpdateMeta('nickname', $value);
    }

    public function getFirstNameAttribute()
    {
        return $this->getMeta('first_name') ?? null;
    }

    public function setFirstNameAttribute($value)
    {
        $this->addOrUpdateMeta('first_name', $value);
    }

    public function getLastNameAttribute()
    {
        return $this->getMeta('last_name') ?? null;
    }

    public function setLastNameAttribute($value)
    {
        $this->addOrUpdateMeta('last_name', $value);
    }

    public function getDescriptionAttribute()
    {
        return $this->getMeta('description') ?? null;
    }

    public function setDescriptionAttribute($value)
    {
        $this->addOrUpdateMeta('description', $value);
    }

    public function getSessionTokensAttribute()
    {
        return $this->getMeta('session_tokens') ?? null;
    }

    public function setSessionTokenAttribute($value)
    {
        $this->addOrUpdateMeta('session_tokens', $value);
    }

    public function getRememberTokenAttribute()
    {
        return $this->getMeta('remember_token') ?? null;
    }

    public function setRememberTokenAttribute($value)
    {
        $this->addOrUpdateMeta('remember_token', $value);
    }

    public function getEmailVerifiedAtAttribute()
    {
        return $this->getMeta('email_verified_at') ?? null;
    }

    public function setEmailVerifiedAtAttribute($value)
    {
        $this->addOrUpdateMeta('email_verified_at', $value);
    }

    public function getCreatedAtAttribute()
    {
        return $this->getMeta('created_at') ?? null;
    }

    public function setCreatedAtAttribute($value)
    {
        $this->addOrUpdateMeta('created_at', $value);
    }

    public function getUpdatedAtAttribute()
    {
        return $this->getMeta('updated_at') ?? null;
    }

    public function setUpdatedAtAttribute($value)
    {
        $this->addOrUpdateMeta('updated_at', $value);
    }

    public function getMmSuaAttachmentIdAttribute()
    {
        return $this->getMeta('mm_sua_attachment_id') ?? null;
    }

    public function setMmSuaAttachmentIdAttribute($value)
    {
        $this->addOrUpdateMeta('mm_sua_attachment_id', $value);
    }

    public function getMooxUserAttachmentIdAttribute()
    {
        return $this->getMeta('moox_user_attachment_id') ?? null;
    }

    public function setMooxUserAttachmentIdAttribute($value)
    {
        $this->addOrUpdateMeta('moox_user_attachment_id', $value);
    }
}
