<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\MailOutbox\Enums\MailSendSource;
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
        'source',
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
            'source' => MailSendSource::class,
            'status' => MailSendStatus::class,
        ];
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<MailSendLog>  $query
     * @return Builder<MailSendLog>
     */
    #[Scope]
    protected function matchingIdentifiers(Builder $query, ?string $correlationId, ?string $messageId): Builder
    {
        return $query->where(function (Builder $inner) use ($correlationId, $messageId): void {
            if ($correlationId !== null) {
                $inner->orWhere('correlation_id', $correlationId);
            }

            if ($messageId !== null) {
                $inner->orWhere('message_id', $messageId);
            }
        });
    }
}
