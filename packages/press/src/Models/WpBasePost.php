<?php

namespace Moox\Press\Models;

use Awobaz\Mutator\Mutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Override;

/**
 * @property int $ID
 * @property string $post_title
 * @property string $post_name
 * @property string $post_author
 */
class WpBasePost extends Model
{
    use HasFactory;
    use Mutable;

    protected $fillable = [
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count',
    ];

    protected $appends = [
        'verantwortlicher',
        'gultig_bis',
        'turnus',
        'fruhwarnung',
    ];

    protected $wpPrefix;

    protected $table;

    protected string $metatable;

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $searchableFields = ['*'];

    protected $metaFieldsInitialized = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'posts';
        $this->metatable = $this->wpPrefix.'postmeta';
        $this->initializeMetaField();
    }

    #[Override]
    protected static function boot()
    {
        parent::boot();
    }

    protected $casts = [
        'post_date' => 'datetime',
        'post_date_gmt' => 'datetime',
        'post_modified' => 'datetime',
        'post_modified_gmt' => 'datetime',
    ];

    protected function initializeMetaField()
    {
        if ($this->metaFieldsInitialized) {
            return;
        }

        $this->metaFieldsInitialized = true;
    }

    public function metaKey($key)
    {
        if (! Str::startsWith($key, $this->wpPrefix)) {
            $key = $this->wpPrefix.$key;
        }

        return $this->getMeta($key);
    }

    protected function getMeta($key)
    {
        if (! $this->relationLoaded('postMeta')) {
            $this->load('postMeta');
        }

        $meta = $this->postMeta->where('meta_key', $key)->first();

        return $meta ? $meta->meta_value : null;
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

    #[Override]
    public function toArray()
    {
        $attributes = parent::toArray();
        // Include meta fields in the array representation
        $metaFields = config('press.default_post_meta', []);
        foreach ($metaFields as $key => $defaultValue) {
            $attributes[$key] = $this->getMeta($key) ?? $defaultValue;
        }

        return $attributes;
    }

    #[Override]
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    protected function addOrUpdateMeta($key, $value)
    {
        WpPostMeta::updateOrCreate(
            ['post_id' => $this->ID, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    protected function isMetaField($key): bool
    {
        return array_key_exists($key, config('press.default_post_meta', []));
    }

    /*
     * Relations
     *
     */
    public function postMeta(): HasMany
    {
        return $this->hasMany(WpPostMeta::class, 'post_id', 'ID');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(WpUser::class, 'post_author', 'ID');
    }

    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(WpTermTaxonomy::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->taxonomies()->where('taxonomy', 'category');
    }

    public function tags(): BelongsToMany
    {
        return $this->taxonomies()->where('taxonomy', 'post_tag');
    }

    public function comment(): HasMany
    {
        return $this->hasMany(WpComment::class, 'comment_post_ID');
    }

    /*
     * ACF- Fields Getter and Setter
     */
    public function getVerantwortlicherAttribute()
    {
        return $this->getMeta('verantwortlicher') ?? null;
    }

    public function setVerantwortlicherAttribute($value): void
    {
        $this->addOrUpdateMeta('verantwortlicher', $value);
    }

    public function getGultigBisAttribute()
    {
        return $this->getMeta('gultig_bis') ?? null;
    }

    public function setGultigBisAttribute($value): void
    {
        $this->addOrUpdateMeta('gultig_bis', $value);
    }

    public function getTurnusAttribute()
    {
        return $this->getMeta('turnus') ?? null;
    }

    public function setTurnusAttribute($value): void
    {
        $this->addOrUpdateMeta('turnus', $value);
    }

    public function getFruhwarnungAttribute()
    {
        return $this->getMeta('fruhwarnung') ?? null;
    }

    public function setFruhwarnungAttribute($value): void
    {
        $this->addOrUpdateMeta('fruhwarnung', $value);
    }
}
