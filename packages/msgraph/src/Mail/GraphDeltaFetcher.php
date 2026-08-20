<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail;

use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\Delta\DeltaRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\Delta\DeltaRequestBuilderGetRequestConfiguration;
use Moox\MailInbox\InboxMessageDto;
use Moox\MailInbox\MessagePage;
use Moox\MsGraph\Exceptions\GraphException;

/**
 * Single-page Graph delta fetch. The domain job owns the per-run page budget.
 */
final class GraphDeltaFetcher
{
    public function __construct(
        private GraphMailboxClient $mailbox,
        private GraphCall $graphCall,
        private MailSettings $settings,
        private CursorHostGuard $cursorGuard,
        private GraphMessageMapper $mapper,
    ) {}

    public function fetch(?string $cursor): MessagePage
    {
        $this->cursorGuard->assertAllowed($cursor);

        $page = $this->fetchDeltaPage($cursor);

        $messages = [];
        foreach ($page['messages'] as $mapped) {
            $messages[] = $mapped;
        }

        if ($page['nextLink'] !== null) {
            return new MessagePage(
                messages: $messages,
                continuationCursor: $page['nextLink'],
                resumeCursor: null,
            );
        }

        return new MessagePage(
            messages: $messages,
            continuationCursor: null,
            resumeCursor: $page['deltaLink'],
        );
    }

    /**
     * @return array{messages: list<InboxMessageDto>, nextLink: string|null, deltaLink: string|null}
     */
    private function fetchDeltaPage(?string $cursor): array
    {
        return $this->graphCall->run(function () use ($cursor): array {
            $deltaBuilder = $this->mailbox->inboxDelta();
            $requestConfiguration = null;

            if ($cursor !== null && $cursor !== '') {
                $deltaBuilder = $deltaBuilder->withUrl($cursor);
            } else {
                $query = new DeltaRequestBuilderGetQueryParameters(
                    select: $this->deltaSelectFields(),
                    top: $this->settings->pageSize,
                );
                $requestConfiguration = new DeltaRequestBuilderGetRequestConfiguration(null, null, $query);
            }

            $result = $deltaBuilder->get($requestConfiguration)->wait();
            if ($result === null) {
                throw new GraphException('Graph delta returned null response.');
            }

            $messages = [];
            foreach ($result->getValue() ?? [] as $item) {
                if (! $item instanceof Message) {
                    continue;
                }
                if (RemovedDeltaInspector::isRemoved($item)) {
                    continue;
                }

                $mapped = $this->mapper->map($item);
                if ($mapped instanceof InboxMessageDto) {
                    $messages[] = $mapped;
                }
            }

            $next = $result->getOdataNextLink();
            $finalDelta = $result->getOdataDeltaLink();
            $hasNext = $next !== null && $next !== '';
            $hasFinal = $finalDelta !== null && $finalDelta !== '';

            if (($hasNext && $hasFinal) || (! $hasNext && ! $hasFinal)) {
                throw new GraphException('Graph delta page must expose exactly one of @odata.nextLink or @odata.deltaLink.');
            }

            return [
                'messages' => $messages,
                'nextLink' => $hasNext ? $next : null,
                'deltaLink' => $hasFinal ? $finalDelta : null,
            ];
        }, 'fetch');
    }

    /**
     * @return list<string>
     */
    private function deltaSelectFields(): array
    {
        return [
            'id', 'internetMessageId', 'subject', 'from', 'toRecipients',
            'ccRecipients', 'receivedDateTime', 'bodyPreview', 'body',
            'hasAttachments',
        ];
    }
}
