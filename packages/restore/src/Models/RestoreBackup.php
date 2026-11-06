<?php

declare(strict_types=1);

namespace Moox\Restore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\BackupServer\Models\Backup;

/**
 * @property int $id
 * @property string $status
 * @property int $backup_id
 * @property int $restore_destination_id
 * @property string|null $message
 * @property-read Backup|null $backup
 * @property-read RestoreDestination|null $restoreDestination
 */
class RestoreBackup extends Model
{
    public const STATUS_IN_PROGRESS = 'in progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'restore_backups';

    protected $fillable = [
        'status',
        'backup_id',
        'message',
        'restore_destination_id',
    ];

    /**
     * @return BelongsTo<Backup, $this>
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'backup_id');
    }

    /**
     * @return BelongsTo<RestoreDestination, $this>
     */
    public function restoreDestination(): BelongsTo
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
