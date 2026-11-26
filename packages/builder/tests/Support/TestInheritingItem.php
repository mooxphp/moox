<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Contracts\InheritsCustomFields;

class TestInheritingItem extends Model implements InheritsCustomFields
{
    use InteractsWithCustomFields;

    protected $table = 'items';

    protected $guarded = [];

    protected static function customFieldsEntity(): ?string
    {
        return 'item';
    }

    public function customFieldsParent(): ?Model
    {
        $parentId = $this->getAttribute('parent_id');

        if (! is_int($parentId) && ! is_string($parentId)) {
            return null;
        }

        return TestItem::query()->find($parentId);
    }

    public function customFieldsParentEntity(): string
    {
        return 'item';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TestItem::class, 'parent_id');
    }
}
