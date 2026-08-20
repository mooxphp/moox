<?php

declare(strict_types=1);

namespace Moox\MailInbox\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-scope persisted inbox sync cursor (opaque to this package; interpreted by the driver).
 */
class MailInboxSyncState extends Model
{
    protected $table = 'mail_inbox_sync_states';

    protected $primaryKey = 'scope';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scope',
        'driver',
        'delta_link',
        'last_synced_at',
        'cursor_reset_at',
        'updated_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'cursor_reset_at' => 'datetime',
        ];
    }
}
