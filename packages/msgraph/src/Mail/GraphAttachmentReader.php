<?php

declare(strict_types=1);

namespace Moox\Msgraph\Mail;

use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Microsoft\Graph\Generated\Models\Message;
use Moox\MailInbox\InboxMessageDto;
use Psr\Http\Message\StreamInterface;

/**
 * Lists file-attachment metadata and downloads attachment bytes.
 */
final class GraphAttachmentReader
{
    public function __construct(
        private GraphMailboxClient $mailbox,
        private GraphCall $graphCall,
    ) {}

    /**
     * @return array<int, array{id: string|int, name: string, content_type: string, size: int}>
     */
    public function metadataFor(Message $message): array
    {
        if (! ($message->getHasAttachments() ?? false)) {
            return [];
        }

        $messageId = $message->getId();
        if ($messageId === null || $messageId === '') {
            return [];
        }

        $attachments = $this->graphCall->run(
            fn () => $this->mailbox->message($messageId)->attachments()->get()->wait()?->getValue() ?? [],
            'listAttachments',
        );

        $metadata = [];
        foreach ($attachments as $attachment) {
            if (! $attachment instanceof FileAttachment) {
                continue;
            }

            $id = $attachment->getId();
            if ($id === null || $id === '') {
                continue;
            }

            $metadata[] = [
                'id' => $id,
                'name' => $attachment->getName() ?? '',
                'content_type' => $attachment->getContentType() ?? 'application/octet-stream',
                'size' => $attachment->getSize() ?? 0,
            ];
        }

        return $metadata;
    }

    public function content(string $externalId, string $attachmentId): string
    {
        return $this->graphCall->run(function () use ($externalId, $attachmentId): string {
            $attachment = $this->mailbox
                ->message($externalId)
                ->attachments()
                ->byAttachmentId($attachmentId)
                ->get()
                ->wait();

            if (! $attachment instanceof FileAttachment) {
                throw new InvalidArgumentException("Attachment {$attachmentId} is not a file attachment");
            }

            return $this->binaryContentFromFileAttachment($attachment);
        }, 'readAttachment');
    }

    public function mapMessage(Message $message, GraphMessageMapper $mapper): ?InboxMessageDto
    {
        return $mapper->map($message, $this->metadataFor($message));
    }

    private function binaryContentFromFileAttachment(FileAttachment $attachment): string
    {
        $contentBytes = $attachment->getContentBytes();
        if ($contentBytes === null) {
            throw new InvalidArgumentException('File attachment has no content bytes.');
        }

        if ($contentBytes instanceof StreamInterface) {
            if ($contentBytes->isSeekable()) {
                $contentBytes->rewind();
            }

            return (string) base64_decode($contentBytes->getContents());
        }

        throw new InvalidArgumentException('Unexpected contentBytes type on file attachment.');
    }
}
