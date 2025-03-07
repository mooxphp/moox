<?php

declare(strict_types=1);

namespace Moox\Restore\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\BackupServer\Models\Backup;

class RestoreBackup extends Model
{

    public const STATUS_IN_PROGRESS = 'in progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';


    // The table name, in case it's not the default plural of the model name
    protected $table = 'restore_backups';

    // Mass assignable fields
    protected $fillable = [
        'status',
        'backup_id',
        'message',
        'restore_destination_id',
    ];

    /**
     * Each deploy backup belongs to one backup.
     */
    public function backup()
    {
        return $this->belongsTo(Backup::class, 'backup_id');
    }

    /**
     * Each deploy backup is linked to one destination.
     */
    public function restoreDestination()
    {
        return $this->belongsTo(RestoreDestination::class, 'restore_destination_id');
    }

    public function markAsInProgress(): self
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);

        return $this;
    }

    public function markAsCompleted(): self
    {

        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);

        return $this;
    }

    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'message' => $errorMessage,
            'status' => self::STATUS_FAILED,
        ]);

        return $this;
    }
}
