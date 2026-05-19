<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreeNode extends Model
{
    protected $table = 'tree_nodes';

    /** @var array<int, string> */
    protected $fillable = [
        'parent_id',
        'label',
        'sort_order',
        'is_visible',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
