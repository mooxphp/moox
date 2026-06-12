<?php

declare(strict_types=1);

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property int $id
 * @property string $domain
 * @property string $path
 */
class WpSite extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['domain', 'path'];

    /** @var list<string> */
    protected $searchableFields = ['*'];

    protected ?string $wpPrefix = null;

    protected $table;

    protected ?string $metatable = null;

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected bool $metaFieldsInitialized = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'site';
        $this->metatable = $this->wpPrefix.'sitemeta';
        $this->metaFieldsInitialized = true;
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::deleted(function (WpSite $model): void {
            $model->siteMeta()->delete();
        });
    }

    public function siteMeta(): HasMany
    {
        return $this->hasMany(WpSiteMeta::class, 'site_id', 'id');
    }

    #[Override]
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (is_null($value) && $this->metaFieldsInitialized && $this->isMetaField($key)) {
            return $this->getMeta($key);
        }

        return $value;
    }

    #[Override]
    public function setAttribute($key, $value)
    {
        if ($this->metaFieldsInitialized && $this->isMetaField($key)) {
            $this->addOrUpdateMeta($key, $value);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getMeta(string $key): mixed
    {
        if (! $this->relationLoaded('siteMeta')) {
            $this->load('siteMeta');
        }

        /** @var Collection<int, WpSiteMeta> $siteMeta */
        $siteMeta = $this->siteMeta;

        $meta = $siteMeta->where('meta_key', $key)->first();

        return $meta instanceof WpSiteMeta ? $meta->meta_value : null;
    }

    public function addOrUpdateMeta(string $key, mixed $value): void
    {
        WpSiteMeta::updateOrCreate(
            ['site_id' => $this->id, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllMetaAttributes(): array
    {
        $metaFields = config('press.default_site_meta', []);
        $attributes = [];

        foreach (array_keys($metaFields) as $key) {
            $attributes[$key] = $this->getMeta($key);
        }

        return $attributes;
    }

    protected function isMetaField(string $key): bool
    {
        return array_key_exists($key, config('press.default_site_meta', []));
    }
}
