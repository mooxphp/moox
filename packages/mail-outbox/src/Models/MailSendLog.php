<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\MailOutbox\Enums\MailSendStatus;

/**
 * Outbound send log row. Status {@see MailSendStatus::Sent} means the provider accepted
 * the message and the send was recorded — not that a recipient mailbox received it.
 *
 * @property list<string>|null $intended_recipients
 * @property list<string>|null $actual_recipients
 */
class MailSendLog extends Model
{
    protected $table = 'mail_send_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'actual_recipients',
        'attempt_count',
        'correlation_id',
        'error',
        'intended_recipients',
        'mailer',
        'message_id',
        'provider_reference',
        'related_id',
        'related_type',
        'status',
        'subject',
        'template_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_recipients' => 'array',
            'attempt_count' => 'integer',
            'intended_recipients' => 'array',
            'status' => MailSendStatus::class,
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
