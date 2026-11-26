<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Audit\Models\HasAudit;

class AuditModel extends Model
{
    use HasAudit;

    protected $table = 'audit_models';

    protected $guarded = [];
    protected $fillable = ['name'];
}