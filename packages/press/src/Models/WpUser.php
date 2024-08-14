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

class WpUser extends Authenticatable implements FilamentUser
{
    use HasFactory, Mutable, Notifiable;

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

    /*
    protected $appends = [
        'nickname',
        'first_name',
        'last_name',
        //'jku8u_capabilities',
        //'jku8u_user_level'
    ];
    */

    protected $wpPrefix;

    protected $table;

    protected $metatable;

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $metaFieldsInitialized = false;

    protected $accessors = [];

    protected $mutators = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'users';
        $this->metatable = $this->wpPrefix.'usermeta';

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
                //'jku8u_capabilities',
                //'jku8u_capabilities as capabilities',
            ]);
        });
    }

    protected function initializeMetaFields()
    {
        if ($this->metaFieldsInitialized) {
            return;
        }

        $metaConfig = config('press.default_user_meta', []);

        foreach ($metaConfig as $key => $defaultValue) {
            $this->fillable[] = $key;

            $this->accessors[$key] = function () use ($key, $defaultValue) {
                return $this->getMeta($key) ?? $defaultValue;
            };

            $this->mutators[$key] = function ($value) use ($key) {
                $this->addOrUpdateMeta($key, $value);

                return $value;
            };
        }

        $this->metaFieldsInitialized = true;
    }

    public function getMeta($key)
    {
        if (! $this->relationLoaded('userMeta')) {
            $this->load('userMeta');
        }

        $meta = $this->userMeta->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
    }

    public function userMeta(): HasMany
    {
        return $this->hasMany(WpUserMeta::class, 'user_id', 'ID');
    }

    public function addOrUpdateMeta($key, $value)
    {
        /** @disregard Intelephense P1036 Non static method 'pluck' should not be called statically. */
        WpUserMeta::updateOrCreate(
            ['user_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (is_null($value) && $this->metaFieldsInitialized) {
            return $this->getMeta($key);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if ($this->metaFieldsInitialized && array_key_exists($key, config('press.default_user_meta', []))) {
            $this->addOrUpdateMeta($key, $value);
        } else {
            parent::setAttribute($key, $value);
        }

        return $this;
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
}
