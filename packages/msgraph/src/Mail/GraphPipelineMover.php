<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail;

use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\Move\MovePostRequestBody;
use Moox\MailInbox\Enums\ClaimResult;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MsGraph\Exceptions\GraphException;
use Throwable;

/**
 * Claim and settle moves with pipeline-source guards. Folder failures are best-effort.
 */
final class GraphPipelineMover
{
    public function __construct(
        private GraphMailboxClient $mailbox,
        private GraphCall $graphCall,
        private GraphFolderResolver $folders,
        private MailSettings $settings,
    ) {
    }

    public function claim(string $externalId): ClaimResult
    {
        if ($this->settings->processingFolder === null) {
            return ClaimResult::Won;
        }

        try {
            $processingId = $this->folders->getOrCreate($this->settings->processingFolder);
            $currentParentId = $this->getMessageParentFolderId($externalId);

            if ($currentParentId !== null && $currentParentId === $processingId) {
                return ClaimResult::AlreadyHeld;
            }

            $inboxFolderId = $this->folders->inboxFolderId();
            if ($currentParentId === null || $currentParentId !== $inboxFolderId) {
                $this->logUnexpectedParentForMove($externalId, $currentParentId);

                return ClaimResult::MoveFailed;
            }

            $this->postGraphMoveMessageToFolder($externalId, $processingId);

            return ClaimResult::Won;
        } catch (GraphException $e) {
            Log::channel('mail-inbox')->warning('[Msgraph] claim skipped: processing folder move failed', [
                'messageId' => $externalId,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ]);

            return ClaimResult::MoveFailed;
        }
    }

    public function settle(string $externalId, SettlementOutcome $outcome): void
    {
        try {
            if ($outcome === SettlementOutcome::Processed) {
                $this->markMessageAsRead($externalId);
            }

            $destinationId = $this->folders->getOrCreate($this->settings->folderFor($outcome));
            $this->moveMessageToFolder($externalId, $destinationId);
        } catch (GraphException $e) {
            Log::channel('mail-inbox')->warning('[Msgraph] settle skipped: folder move failed', [
                'messageId' => $externalId,
                'outcome' => $outcome->value,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ]);
        }
    }

    private function markMessageAsRead(string $messageId): void
    {
        $this->graphCall->run(function () use ($messageId): void {
            $body = new Message;
            $body->setIsRead(true);

            $this->mailbox->message($messageId)->patch($body)->wait();
        }, 'markMessageAsRead');
    }

    private function moveMessageToFolder(string $messageId, string $destinationFolderId): void
    {
        $currentParentId = $this->getMessageParentFolderId($messageId);

        if ($currentParentId !== null && $currentParentId === $destinationFolderId) {
            Log::channel('mail-inbox')->debug('[Msgraph] Message already in destination folder; skipping move', [
                'messageId' => $messageId,
                'destinationFolderId' => $destinationFolderId,
            ]);

            return;
        }

        try {
            $inboxFolderId = $this->folders->inboxFolderId();
        } catch (GraphException $e) {
            $context = [
                'messageId' => $messageId,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ];
            if ($this->looksLikeGraphFolderResolutionFailure($e)) {
                $context['hint'] = 'Could not resolve the well-known Inbox folder id'
                    .' — verify the mailbox address and Graph permissions.';
            }
            Log::channel('mail-inbox')->warning(
                '[Msgraph] Skipping move: Inbox folder id unavailable for pipeline guard',
                $context,
            );

            return;
        }

        try {
            $acceptable = $this->parentIsAcceptablePipelineSource($currentParentId, $inboxFolderId);
        } catch (GraphException $e) {
            Log::channel('mail-inbox')->warning(
                '[Msgraph] Skipping move: could not resolve folder ids for pipeline guard',
                [
                    'messageId' => $messageId,
                    'parentFolderId' => $currentParentId,
                    'exception_class' => $e::class,
                    'exception_message' => $e->getMessage(),
                ],
            );

            return;
        }

        if (! $acceptable) {
            $this->logUnexpectedParentForMove($messageId, $currentParentId);

            return;
        }

        $this->postGraphMoveMessageToFolder($messageId, $destinationFolderId);
    }

    private function getMessageParentFolderId(string $messageId): ?string
    {
        return $this->graphCall->run(function () use ($messageId) {
            $config = new MessageItemRequestBuilderGetRequestConfiguration;
            $config->queryParameters = new MessageItemRequestBuilderGetQueryParameters(
                select: ['parentFolderId'],
            );

            $message = $this->mailbox->message($messageId)->get($config)->wait();

            return $message?->getParentFolderId();
        }, 'getMessageParentFolderId');
    }

    private function parentIsAcceptablePipelineSource(?string $parentFolderId, string $inboxFolderId): bool
    {
        if ($parentFolderId === null || $parentFolderId === '') {
            return false;
        }

        if ($parentFolderId === $inboxFolderId) {
            return true;
        }

        if ($this->settings->processingFolder === null) {
            return false;
        }

        return $parentFolderId === $this->folders->getOrCreate($this->settings->processingFolder);
    }

    private function logUnexpectedParentForMove(string $messageId, ?string $parentFolderId): void
    {
        $context = [
            'messageId' => $messageId,
            'parentFolderId' => $parentFolderId,
        ];

        try {
            foreach ([
                $this->settings->processedFolder,
                $this->settings->failedFolder,
                $this->settings->ignoredFolder,
            ] as $folderName) {
                $folderId = $this->folders->getOrCreate($folderName);
                if ($parentFolderId !== null && $parentFolderId === $folderId) {
                    Log::channel('mail-inbox')->warning(
                        '[Msgraph] Skipping move: message parent appears to be a terminal mailbox folder',
                        $context,
                    );

                    return;
                }
            }
        } catch (GraphException) {
        }

        Log::channel('mail-inbox')->warning(
            '[Msgraph] Skipping move: message parent is not an acceptable pipeline source (Inbox or Processing)',
            $context,
        );
    }

    private function postGraphMoveMessageToFolder(string $messageId, string $destinationFolderId): void
    {
        $this->graphCall->run(function () use ($messageId, $destinationFolderId): void {
            $body = new MovePostRequestBody;
            $body->setDestinationId($destinationFolderId);

            $this->mailbox->message($messageId)->move()->post($body)->wait();
        }, 'moveMessageToFolder.post');
    }

    private function looksLikeGraphFolderResolutionFailure(Throwable $e): bool
    {
        $current = $e;
        for ($i = 0; $i < 6 && $current !== null; $i++) {
            if ($current instanceof ODataError) {
                $code = $current->getError()?->getCode();
                if (
                    $code !== null
                    && in_array((string) $code, ['ErrorFolderNotFound', 'ErrorInvalidIdMalformed'], true)
                ) {
                    return true;
                }
            }
            $previous = $current->getPrevious();
            if (! $previous instanceof Throwable) {
                break;
            }
            $current = $previous;
        }

        return false;
    }
}
