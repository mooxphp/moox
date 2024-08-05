<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property string $meta_key
 * @property string $meta_value
 */
class WpUserMeta extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'meta_key', 'meta_value'];

    protected $searchableFields = ['*'];

    protected $wpPrefix;

    protected $table;

    protected $primaryKey = 'umeta_id';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'usermeta';
    }

    public function user()
    {
        return $this->belongsTo(WpUser::class, 'ID');
    }
}
