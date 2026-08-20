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
    public function map(Message $message): ?InboxMessageDto
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

        $from = $message->getFrom()?->getEmailAddress();
        $toRecipients = $message->getToRecipients() ?? [];
        $firstTo = ($toRecipients[0] ?? null)?->getEmailAddress();

        return new InboxMessageDto(
            externalId: $externalId,
            subject: $message->getSubject() ?? '',
            from: $from?->getAddress() ?? '',
            receivedAt: self::receivedAt($message),
            bodyHtml: $bodyHtml,
            bodyText: $bodyText,
            attachments: [],
            messageId: $message->getInternetMessageId(),
            fromName: $from?->getName(),
            toEmail: $firstTo?->getAddress(),
            toName: $firstTo?->getName(),
            hasAttachments: $message->getHasAttachments() ?? false,
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
