<?php

declare(strict_types=1);

namespace Moox\Msgraph\Mail;

use DateTimeImmutable;
use DateTimeInterface;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\Message;
use Moox\MailInbox\InboxMessageDto;

/**
 * Maps a Graph Message to the inbox-driver DTO. Does not leak Graph types through the contract.
 */
final class GraphMessageMapper
{
    /**
     * @param  array<int, array{id: string|int, name: string, content_type: string, size: int}>  $attachments
     */
    public function map(Message $message, array $attachments = []): ?InboxMessageDto
    {
        $externalId = $message->getId();
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $body = $message->getBody();
        $contentType = $body?->getContentType();
        $content = $body?->getContent();

        $bodyHtml = ($contentType !== null && $contentType->value() === BodyType::HTML) ? $content : null;
        $bodyText = ($contentType !== null && $contentType->value() === BodyType::TEXT) ? $content : null;

        return new InboxMessageDto(
            externalId: $externalId,
            subject: $message->getSubject() ?? '',
            from: $message->getFrom()?->getEmailAddress()?->getAddress() ?? '',
            receivedAt: self::receivedAt($message),
            bodyHtml: $bodyHtml,
            bodyText: $bodyText,
            attachments: $attachments,
        );
    }

    private static function receivedAt(Message $message): DateTimeImmutable
    {
        $received = $message->getReceivedDateTime();
        if ($received instanceof DateTimeImmutable) {
            return $received;
        }
        if ($received instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($received);
        }

        return new DateTimeImmutable('@0');
    }
}
