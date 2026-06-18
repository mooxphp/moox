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
    ];
}
