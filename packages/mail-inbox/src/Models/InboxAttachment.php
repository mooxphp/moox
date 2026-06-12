<?php

declare(strict_types=1);

namespace Moox\MailInbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;

/**
 * File stored for an {@see InboxMessage} (email attachment).
 *
 * The `processing_status` attribute stores {@see InboxAttachmentProcessingStatus} string values.
 */
class InboxAttachment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attachment_role',
        'checksum',
        'created_at',
        'error_message',
        'extension',
        'external_attachment_id',
        'filesize',
        'filename',
        'inbox_message_id',
        'is_pdf',
        'mime_type',
        'processed_at',
        'processing_status',
        'scope',
        'storage_disk',
        'storage_path',
        'zugferd_storage_disk',
        'zugferd_storage_path',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filesize' => 'integer',
            'is_pdf' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InboxMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(InboxMessage::class, 'inbox_message_id');
    }

    public function isPdf(): bool
    {
        return $this->is_pdf
            || $this->mime_type === 'application/pdf'
            || str_ends_with(strtolower($this->filename), '.pdf');
    }

    public function fullPath(): string
    {
        return Storage::disk($this->storage_disk)->path($this->storage_path);
    }

    public function markAsProcessing(): void
    {
        $this->processing_status = InboxAttachmentProcessingStatus::Processing->value;
        $this->save();
    }

    public function markAsProcessed(): void
    {
        $this->processing_status = InboxAttachmentProcessingStatus::Processed->value;
        $this->processed_at = now();
        $this->save();
    }

    public function markAsFailed(string $error): void
    {
        $this->processing_status = InboxAttachmentProcessingStatus::Failed->value;
        $this->error_message = $error;
        $this->save();
    }

    public function markAsSkipped(): void
    {
        $this->processing_status = InboxAttachmentProcessingStatus::Skipped->value;
        $this->save();
    }
}
