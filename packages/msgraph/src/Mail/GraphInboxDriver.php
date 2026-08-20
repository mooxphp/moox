<?php

declare(strict_types=1);

namespace Moox\Msgraph\Mail;

use Closure;
use Illuminate\Support\Facades\Cache;
use Microsoft\Graph\GraphServiceClient;
use Moox\MailInbox\Contracts\InboxDriver;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\MessagePage;

/**
 * Graph implementation of {@see InboxDriver}. Folder names come from this package's config only.
 */
final class GraphInboxDriver implements InboxDriver
{
    public function __construct(
        private GraphDeltaFetcher $deltaFetcher,
        private GraphPipelineMover $pipelineMover,
        private GraphAttachmentReader $attachmentReader,
    ) {}

    /**
     * @param  Closure(int): void|null  $sleeper
     */
    public static function make(
        GraphServiceClient $client,
        string $mailboxAddress,
        MailSettings $settings,
        ?Closure $sleeper = null,
    ): self {
        $sleeper ??= static function (int $seconds): void {
            if ($seconds > 0) {
                sleep($seconds);
            }
        };

        $graphCall = new GraphCall($sleeper);
        $mailbox = new GraphMailboxClient($client, $mailboxAddress);
        $folders = new GraphFolderResolver($mailbox, $graphCall, Cache::store(), $mailboxAddress);
        $attachments = new GraphAttachmentReader($mailbox, $graphCall);

        return new self(
            new GraphDeltaFetcher(
                $mailbox,
                $graphCall,
                $settings,
                new CursorHostGuard($settings->allowedDeltaHosts),
                new GraphMessageMapper,
                $attachments,
            ),
            new GraphPipelineMover($mailbox, $graphCall, $folders, $settings),
            $attachments,
        );
    }

    public function fetch(?string $cursor = null): MessagePage
    {
        return $this->deltaFetcher->fetch($cursor);
    }

    public function claim(string $externalId): bool
    {
        return $this->pipelineMover->claim($externalId);
    }

    public function settle(string $externalId, SettlementOutcome $outcome): void
    {
        $this->pipelineMover->settle($externalId, $outcome);
    }

    public function readAttachment(string $externalId, string|int $attachmentId): string
    {
        return $this->attachmentReader->content($externalId, (string) $attachmentId);
    }
}
