<?php

namespace Moox\Jobs\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $connection
 * @property string $queue
 * @property Carbon $failed_at
 */
class FailedJob extends Model
{
    public $timestamps = false;
}
