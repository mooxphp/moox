<?php

declare(strict_types=1);

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $meta_id
 * @property int $site_id
 * @property string $meta_key
 * @property mixed $meta_value
 */
class WpSiteMeta extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['site_id', 'meta_key', 'meta_value'];

    /** @var list<string> */
    protected $searchableFields = ['*'];

    protected ?string $wpPrefix = null;

    protected $table;

    protected $primaryKey = 'meta_id';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'sitemeta';
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(WpSite::class, 'site_id', 'id');
    }
}
