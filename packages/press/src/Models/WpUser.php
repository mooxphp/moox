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

class WpUser extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, UserMetaAttributes;

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

    protected $wpPrefix;

    protected $table;

    protected $metatable;

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $tempMetaAttributes = [];

    protected $metaDataToSave = []; // Queue for meta data to save after the user is saved

    protected $appends = [
        'nickname',
        'first_name',
        'last_name',
        'description',
        'capabilities',
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

        static::updated(function ($model) {
            $model->addOrUpdateMeta('updated_at', now()->toDateTimeString());
        });

        static::deleted(function ($model) {
            if ($model->ID) {
                $model->userMeta()->delete();
            }
        });

        static::addGlobalScope('addAttributes', function (Builder $builder) {
            $builder->addSelect([
                'ID',
                'user_login',
                'user_nicename',
                'user_email',
                'user_url',
                'user_registered',
                'user_activation_key',
                'user_status',
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

    public function meta($key)
    {
        if (! Str::startsWith($key, $this->wpPrefix)) {
            $key = "{$this->wpPrefix}{$key}";
        }

        $meta = $this->userMeta()->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
    }

    public function getFirstNameAttribute()
    {
        return $this->meta('first_name');
    }

    public function getLastNameAttribute()
    {
        return $this->meta('last_name');
    }

    public function addOrUpdateMeta($key, $value)
    {
        if (! Str::startsWith($key, $this->wpPrefix)) {
            $key = "{$this->wpPrefix}{$key}";
        }

        WpUserMeta::updateOrCreate(
            ['user_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    public function getCapabilitiesAttribute()
    {
        $key = "{$this->wpPrefix}_capabilities";

        return $this->meta($key);
    }

    public function setCapabilitiesAttribute($value)
    {
        $key = "{$this->wpPrefix}_capabilities";
        $this->addOrUpdateMeta($key, $value);
    }

    public function addDefaultMetaFields()
    {
        $defaultMeta = config('press.default_user_meta');

        foreach ($defaultMeta as $key => $value) {
            if (is_array(unserialize($value))) {
                $this->addOrUpdateMeta($key, serialize(unserialize($value)));
            } else {
                $this->addOrUpdateMeta($key, $value);
            }
        }
    }
}
