<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Archive\Models\HasArchive;

class ArchiveModel extends Model
{
    use HasArchive;

    protected $table = 'archive_models';

    protected $guarded = [];
}