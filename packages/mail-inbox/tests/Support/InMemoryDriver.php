<?php

declare(strict_types=1);

namespace Moox\MailInbox\Tests\Support;

use Moox\MailInbox\Contracts\InboxDriver;
use Moox\MailInbox\Enums\ClaimResult;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\MessagePage;

/**
 * In-memory fake driver for testing the inbox pipeline with no network access.
 *
 * Scripted messages go in, settled outcomes are recorded and inspectable.
 */
class InMemoryDriver implements InboxDriver
{
    /** @var array<string, SettlementOutcome> */
    private array $settled = [];

    /** @var array<string, true> */
    private array $claimed = [];

    /** @var array<string, string> */
    private array $attachments = [];

    /** @var array<string, list<array{id: string|int, name: string, content_type: string, size: int}>> */
    private array $attachmentMetadata = [];

    /** @var array<string, MessagePage> cursor → page */
    private readonly array $cursorIndex;

    private readonly string $firstCursor;

    /**
     * @param  array<int, MessagePage>  $pages  Scripted pages; continuationCursor (else resumeCursor) chains to the next.
     */
    public function __construct(array $pages = [])
    {
        $index = [];
        $first = '__first__';

        foreach ($pages as $i => $page) {
            $key = $i === 0 ? $first : ($pages[$i - 1]->continuationCursor ?? $pages[$i - 1]->resumeCursor ?? $first);
            $index[$key] = $page;
        }

        $this->cursorIndex = $index;
        $this->firstCursor = $first;
    }

    public function fetch(?string $cursor = null): MessagePage
    {
        $key = $cursor ?? $this->firstCursor;

        return $this->cursorIndex[$key] ?? new MessagePage(messages: [], continuationCursor: null, resumeCursor: null);
    }

    public function claim(string $externalId): ClaimResult
    {
        if (isset($this->claimed[$externalId])) {
            return ClaimResult::AlreadyHeld;
        }

        $this->claimed[$externalId] = true;

        return ClaimResult::Won;
    }

    public function settle(string $externalId, SettlementOutcome $outcome): void
    {
        $this->settled[$externalId] = $outcome;
    }

    /**
     * @return list<array{id: string|int, name: string, content_type: string, size: int}>
     */
    public function listAttachments(string $externalId): array
    {
        return $this->attachmentMetadata[$externalId] ?? [];
    }

    public function readAttachment(string $externalId, string|int $attachmentId): string
    {
        $key = $externalId.':'.$attachmentId;

        return $this->attachments[$key] ?? '';
    }

    /**
     * Script attachment metadata and content for listAttachments() / readAttachment().
     */
    public function addFileAttachment(
        string $externalId,
        string|int $attachmentId,
        string $content,
        string $name = 'attachment',
        string $contentType = 'application/octet-stream',
    ): void {
        $this->attachments[$externalId.':'.$attachmentId] = $content;
        $this->attachmentMetadata[$externalId][] = [
            'id' => $attachmentId,
            'name' => $name,
            'content_type' => $contentType,
            'size' => strlen($content),
        ];
    }

    /**
     * @return array<string, SettlementOutcome>
     */
    public function settledOutcomes(): array
    {
        return $this->settled;
    }

    /**
     * @return array<string, true>
     */
    public function claimedIds(): array
    {
        return $this->claimed;
    }

    public function wasSettled(string $externalId): bool
    {
        return isset($this->settled[$externalId]);
    }

    public function outcomeFor(string $externalId): ?SettlementOutcome
    {
        return $this->settled[$externalId] ?? null;
    }
}
