<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Illuminate\Database\Eloquent\Model;

final class TestNonSoftDeleteAuditableItem extends Model
{
    protected $table = 'test_non_soft_delete_auditable_items';

    protected $fillable = [
        'title',
        'status',
    ];
}
