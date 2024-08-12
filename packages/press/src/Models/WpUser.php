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
use Moox\Press\Traits\UserMetaAttributes;

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
    use UserMetaAttributes;

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

    protected static function newFactory(): Factory
    {
        return WpUserFactory::new();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function userMeta(): HasMany
    {
        return $this->hasMany(WpUserMeta::class, 'user_id', 'ID');
    }

    public function getAllMetaAttributes()
    {
        $metas = $this->userMeta->pluck('meta_value', 'meta_key')->toArray();
        foreach ($metas as $key => $value) {
            $metas[$key] = $this->getMeta($key);
        }

        return $metas;
    }

    public function meta($key)
    {
        if (! Str::startsWith($key, $this->wpPrefix)) {
            $key = "{$this->wpPrefix}{$key}";
        }

        return $this->getMeta($key);
    }

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

    public function save(array $options = [])
    {
        $saved = parent::save($options);

        foreach ($this->tempMetaAttributes as $key => $value) {
            $this->addOrUpdateMeta($key, $value);
        }

        return $saved;
    }

    public function addOrUpdateMeta($key, $value)
    {
        /** @disregard Intelephense P1036 Non static method 'pluck' should not be called statically. */
        WpUserMeta::updateOrCreate(
            ['user_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }
}
