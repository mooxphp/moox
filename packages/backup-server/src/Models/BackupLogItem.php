<?php

namespace Moox\BackupServerUi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\BackupServer\Models\Backup;
use Spatie\BackupServer\Models\Destination;
use Spatie\BackupServer\Models\Source;

class BackupLogItem extends Model
{
    use HasFactory;

    protected $table = 'backup_server_backup_log';

    protected $guarded = [];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
