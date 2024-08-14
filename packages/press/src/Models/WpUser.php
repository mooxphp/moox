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

    protected $wpPrefix;

    protected $table;

    protected $metatable;

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $metaFieldsInitialized = false;

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
                // 'your_custom_meta_field', // Uncomment if needed
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
}
