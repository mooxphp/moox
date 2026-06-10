<?php

declare(strict_types=1);

namespace Moox\MailInbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;

/**
 * Inbound mailbox message synchronized from Microsoft Graph.
 *
 * The `processing_status` attribute stores {@see InboxMessageProcessingStatus} string values.
 *
 * Note: the `message_id` column stores RFC822 `internetMessageId` (not the Graph REST id); Graph calls use `external_id` (Microsoft Graph **immutable** REST id, requested via Prefer: IdType="ImmutableId" on API traffic).
 */
class InboxMessage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'created_at',
        'error_message',
        'external_id',
        'from_email',
        'from_name',
        'has_attachments',
        'message_id',
        'processed_at',
        'processing_status',
        'raw_body_html',
        'raw_body_text',
        'raw_headers',
        'received_at',
        'scope',
        'subject',
        'to_email',
        'to_name',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_attachments' => 'boolean',
            'processed_at' => 'datetime',
            'raw_headers' => 'array',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<InboxAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(InboxAttachment::class, 'inbox_message_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeFailed(Builder $query): void
    {
        $query->whereIn('processing_status', [
            InboxMessageProcessingStatus::Failed->value,
            InboxMessageProcessingStatus::PartiallyFailed->value,
        ]);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForScope(Builder $query, string $scope): void
    {
        $query->where('scope', $scope);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeNew(Builder $query): void
    {
        $query->where('processing_status', InboxMessageProcessingStatus::New->value);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithChannel(Builder $query, string $channel): void
    {
        $query->where('channel', $channel);
    }

    public function markAsRead(): void
    {
        $this->processing_status = InboxMessageProcessingStatus::Read->value;
        $this->save();
    }

    public function markAsProcessed(): void
    {
        $this->processing_status = InboxMessageProcessingStatus::Processed->value;
        $this->processed_at = now();
        $this->save();
    }

    public function markAsFailed(string $error): void
    {
        $this->processing_status = InboxMessageProcessingStatus::Failed->value;
        $this->error_message = $error;
        $this->save();
    }

    public function markAsPartiallyFailed(string $error): void
    {
        $this->processing_status = InboxMessageProcessingStatus::PartiallyFailed->value;
        $this->error_message = $error;
        $this->save();
    }

    public function hasAttachmentsPendingOrProcessing(): bool
    {
        return $this->attachments()
            ->whereIn('processing_status', [
                InboxAttachmentProcessingStatus::New->value,
                InboxAttachmentProcessingStatus::Processing->value,
            ])
            ->exists();
    }

    /**
     * @return HasMany<InboxAttachment, $this>
     */
    public function pdfAttachments(): HasMany
    {
        return $this->attachments()->where('is_pdf', true);
    }
}
