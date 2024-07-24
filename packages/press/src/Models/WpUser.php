<?php

namespace Moox\Press\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    protected $appends;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'users';
        $this->metatable = $this->wpPrefix.'usermeta';

        $this->appends = [
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

    protected static function newFactory(): Factory
    {
        return WpUserFactory::new();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function userMeta()
    {
        return $this->hasMany(WpUserMeta::class, 'user_id', 'ID');
    }

    public function meta($key)
    {
        if (! Str::startsWith($key, $this->wpPrefix)) {
            $key = "{$this->wpPrefix}{$key}";
        }

        return $this->getMeta($key);
    }

    protected function getMeta($key)
    {
        $meta = $this->userMeta()->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
    }

    protected function addOrUpdateMeta($key, $value)
    {
        $meta = $this->userMeta()->where('meta_key', $key)->first();

        if ($meta) {
            $meta->meta_value = $value;
            $meta->save();
        } else {
            $this->userMeta()->create([
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
    }
}
