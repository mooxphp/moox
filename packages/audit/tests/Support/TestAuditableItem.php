<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TestAuditableItem extends Model
{
    use SoftDeletes;

    protected $table = 'test_auditable_items';

    protected $fillable = [
        'title',
        'status',
        'scope',
        'builder_payload',
    ];

    protected function casts(): array
    {
        return [
            'builder_payload' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customFields(bool $fresh = false): array
    {
        return is_array($this->builder_payload) ? $this->builder_payload : [];
    }

    /**
     * @return list<string>
     */
    public function customFieldNames(): array
    {
        return array_values(array_filter(array_keys($this->customFields()), is_string(...)));
    }
}
