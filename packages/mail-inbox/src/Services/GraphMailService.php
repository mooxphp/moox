<?php

declare(strict_types=1);

namespace Moox\MailInbox\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\Delta\DeltaRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\Delta\DeltaRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\Move\MovePostRequestBody;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Moox\MailInbox\DeltaPage;
use Moox\MailInbox\Exceptions\GraphAuthenticationException;
use Moox\MailInbox\Exceptions\GraphConnectionException;
use Moox\MailInbox\Exceptions\GraphException;
use Moox\MailInbox\Exceptions\GraphItemNotFoundException;
use Moox\MailInbox\Exceptions\GraphMailboxNotFoundException;
use Moox\MailInbox\Exceptions\GraphRateLimitException;
use Moox\MailInbox\Exceptions\GraphSyncStateNotFoundException;
use Moox\MailInbox\Support\DeltaMessageInspector;
use Moox\MailInbox\Support\MailInboxGraphServiceClientFactory;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * Microsoft Graph client for mailbox operations.
 */
class GraphMailService
{
    private GraphServiceClient $client;

    /**
     * @param  GraphServiceClient|null  $client  Explicit client (e.g. tests). When null, credentials are read from config — intended for tests without the container; production should resolve via {@see MailInboxServiceProvider}.
     */
    public function __construct(?GraphServiceClient $client = null)
    {
        $this->client = $client ?? $this->buildDefaultClient();
    }

    /**
     * Fetches one page of inbox mail via Graph delta (`messages/delta` or a persisted `@odata.deltaLink` / `@odata.nextLink` URL).
     */
    public function fetchInboxMessagesViaDelta(?string $deltaLink): DeltaPage
    {
        return $this->wrapGraphCall(function () use ($deltaLink): DeltaPage {
            $deltaBuilder = ($deltaLink !== null && $deltaLink !== '')
                ? $this->client->users()
                    ->byUserId($this->mailbox())
                    ->mailFolders()
                    ->byMailFolderId('inbox')
                    ->messages()
                    ->delta()
                    ->withUrl($deltaLink)
                : $this->client->users()
                    ->byUserId($this->mailbox())
                    ->mailFolders()
                    ->byMailFolderId('inbox')
                    ->messages()
                    ->delta();

            $requestConfiguration = null;
            if ($deltaLink === null || $deltaLink === '') {
                $query = new DeltaRequestBuilderGetQueryParameters(
                    select: $this->deltaSelectFields(),
                    top: 50,
                );
                $requestConfiguration = new DeltaRequestBuilderGetRequestConfiguration(null, null, $query);
            }

            $result = $deltaBuilder->get($requestConfiguration)->wait();
            if ($result === null) {
                throw new GraphException('Graph delta returned null response.');
            }

            $removedFiltered = 0;
            $messages = [];
            foreach ($result->getValue() ?? [] as $item) {
                if (! $item instanceof Message) {
                    continue;
                }
                if (DeltaMessageInspector::isRemovedPlaceholder($item)) {
                    $removedFiltered++;

                    continue;
                }
                $messages[] = $item;
            }

            $next = $result->getOdataNextLink();
            $finalDelta = $result->getOdataDeltaLink();
            $hasNext = $next !== null && $next !== '';
            $hasFinal = $finalDelta !== null && $finalDelta !== '';

            if (($hasNext && $hasFinal) || (! $hasNext && ! $hasFinal)) {
                throw new GraphException('Graph delta page must expose exactly one of @odata.nextLink or @odata.deltaLink.');
            }

            return new DeltaPage(
                messages: $messages,
                nextLink: $hasNext ? $next : null,
                deltaLink: $hasFinal ? $finalDelta : null,
                removedFiltered: $removedFiltered,
            );
        }, 'fetchInboxMessagesViaDelta');
    }

    public function fetchAttachments(string $messageId): Collection
    {
        return $this->wrapGraphCall(function () use ($messageId) {
            $result = $this->client
                ->users()
                ->byUserId($this->mailbox())
                ->messages()
                ->byMessageId($messageId)
                ->attachments()
                ->get()
                ->wait();

            if ($result === null || $result->getValue() === null) {
                return collect();
            }

            return collect($result->getValue())->filter(
                fn ($attachment) => $attachment instanceof FileAttachment
            );
        }, 'fetchAttachments');
    }

    /**
     * @return array{name: string|null, contentType: string|null, size: int|null, contentBytes: string}
     */
    public function downloadAttachmentContent(string $messageId, string $attachmentId): array
    {
        return $this->wrapGraphCall(function () use ($messageId, $attachmentId) {
            $attachment = $this->client
                ->users()
                ->byUserId($this->mailbox())
                ->messages()
                ->byMessageId($messageId)
                ->attachments()
                ->byAttachmentId($attachmentId)
                ->get()
                ->wait();

            if (! $attachment instanceof FileAttachment) {
                throw new InvalidArgumentException("Attachment {$attachmentId} is not a file attachment");
            }

            return [
                'name' => $attachment->getName(),
                'contentType' => $attachment->getContentType(),
                'size' => $attachment->getSize(),
                'contentBytes' => $this->binaryContentFromFileAttachment($attachment),
            ];
        }, 'downloadAttachmentContent');
    }

    public function markMessageAsRead(string $messageId): void
    {
        $this->wrapGraphCall(function () use ($messageId) {
            $body = new Message;
            $body->setIsRead(true);

            $this->client
                ->users()
                ->byUserId($this->mailbox())
                ->messages()
                ->byMessageId($messageId)
                ->patch($body)
                ->wait();
        }, 'markMessageAsRead');
    }

    /**
     * Moves a message to a folder by Graph folder id. Enforces an acceptable pipeline parent (well-known Inbox or
     * optional Processing folder) before calling Graph, so terminal folders and arbitrary locations are not pulled
     * back into the pipeline. {@see InboxMessagePipelineFinalizer} calls this method directly and inherits the guard.
     *
     * Idempotent: when the message already sits in {@code $destinationFolderId}, logs at **debug** and returns before
     * any source check (re-finalizing into Processed must not warn).
     *
     * @param  ?string  $scope  Mail-ingest scope (delta / {@see InboxMessage::scope}); defaults to {@code default}.
     */
    public function moveMessageToFolder(string $messageId, string $destinationFolderId, ?string $scope = null): void
    {
        $scope = $scope ?? 'default';

        $currentParentId = $this->getMessageParentFolderId($messageId);

        if ($currentParentId !== null && $currentParentId === $destinationFolderId) {
            Log::channel('mail-inbox')->debug('[MailInbox] Message already in destination folder; skipping move', [
                'messageId' => $messageId,
                'destinationFolderId' => $destinationFolderId,
                'scope' => $scope,
            ]);

            return;
        }

        try {
            $inboxFolderId = $this->cachedInboxFolderId($scope);
        } catch (Throwable $e) {
            $context = [
                'messageId' => $messageId,
                'scope' => $scope,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ];
            if ($this->looksLikeGraphFolderResolutionFailure($e)) {
                $context['hint'] = 'Could not resolve the well-known Inbox folder id — verify MAIL_INBOX_MAILBOX and Graph permissions for this scope/mailbox.';
            }
            Log::channel('mail-inbox')->warning('[MailInbox] Skipping move: Inbox folder id unavailable for pipeline guard', $context);

            return;
        }

        try {
            $acceptable = $this->parentIsAcceptablePipelineSource($currentParentId, $inboxFolderId);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->warning('[MailInbox] Skipping move: could not resolve folder ids for pipeline guard', [
                'messageId' => $messageId,
                'scope' => $scope,
                'parentFolderId' => $currentParentId,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'hint' => $this->processingFolderMisconfigurationHint($e),
            ]);

            return;
        }

        if (! $acceptable) {
            $this->logUnexpectedParentForMove($messageId, $currentParentId, $scope);

            return;
        }

        $this->postGraphMoveMessageToFolder($messageId, $destinationFolderId);
    }

    /**
     * Best-effort move into the optional {@see config('mail-inbox.processing_folder')} folder after delta persist.
     */
    public function moveGraphMessageToProcessingFolder(string $messageId, string $scope): void
    {
        $folderName = config('mail-inbox.processing_folder');
        if ($folderName === null || $folderName === '') {
            Log::channel('mail-inbox')->debug('[MailInbox] processing folder not configured, skipping move', [
                'messageId' => $messageId,
                'scope' => $scope,
            ]);

            return;
        }

        try {
            $destinationId = $this->getOrCreateFolder((string) $folderName);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->warning('[MailInbox] move to Processing folder failed (folder resolution)', [
                'messageId' => $messageId,
                'scope' => $scope,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'hint' => $this->processingFolderMisconfigurationHint($e),
            ]);

            return;
        }

        try {
            $this->moveMessageToFolder($messageId, $destinationId, $scope);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->warning('[MailInbox] move to Processing folder failed', [
                'messageId' => $messageId,
                'scope' => $scope,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'hint' => $this->processingFolderMisconfigurationHint($e),
            ]);
        }
    }

    /**
     * Moves a message into {@see config('mail-inbox.processed_folder')} or {@see config('mail-inbox.failed_folder')}.
     *
     * @param  ?string  $scope  Defaults to {@code default} when omitted (e.g. legacy callers).
     */
    public function moveGraphMessageToProcessedOrFailedFolder(string $messageId, bool $success, ?string $scope = null): void
    {
        $folderConfig = $success ? 'mail-inbox.processed_folder' : 'mail-inbox.failed_folder';
        $destinationId = $this->getOrCreateFolder((string) config($folderConfig));
        $this->moveMessageToFolder($messageId, $destinationId, $scope);
    }

    /**
     * Moves a message into an Ignored (or equivalent) folder by display name.
     *
     * @param  ?string  $scope  Defaults to {@code default} when omitted.
     */
    public function moveGraphMessageToIgnoredFolder(string $messageId, string $ignoredFolderDisplayName, ?string $scope = null): void
    {
        $destinationId = $this->getOrCreateFolder($ignoredFolderDisplayName);
        $this->moveMessageToFolder($messageId, $destinationId, $scope);
    }

    /**
     * Current parent folder id for idempotent moves (see {@see moveMessageToFolderByName()}).
     */
    public function getMessageParentFolderId(string $messageId): ?string
    {
        return $this->wrapGraphCall(function () use ($messageId) {
            $config = new MessageItemRequestBuilderGetRequestConfiguration;
            $config->queryParameters = new MessageItemRequestBuilderGetQueryParameters(
                select: ['parentFolderId'],
            );

            $message = $this->client
                ->users()
                ->byUserId($this->mailbox())
                ->messages()
                ->byMessageId($messageId)
                ->get($config)
                ->wait();

            return $message?->getParentFolderId();
        }, 'getMessageParentFolderId');
    }

    /**
     * Resolves a mail folder by display name (optionally creating it), then moves the message there.
     *
     * Before calling Graph move, {@see moveMessageToFolder()} compares the message's current parent to the
     * destination id (debug no-op when equal) and enforces an acceptable pipeline source (Inbox or optional Processing).
     */
    public function moveMessageToFolderByName(string $messageId, string $folderName, bool $createIfMissing = true, ?string $scope = null): void
    {
        if ($createIfMissing) {
            $folderId = $this->getOrCreateFolder($folderName);
            $this->moveMessageToFolder($messageId, $folderId, $scope);

            return;
        }

        $escapedName = $this->escapeODataSingleQuotedString($folderName);

        $listConfig = new MailFoldersRequestBuilderGetRequestConfiguration;
        $listConfig->queryParameters = new MailFoldersRequestBuilderGetQueryParameters(
            filter: "displayName eq '{$escapedName}'",
        );

        $result = $this->wrapGraphCall(
            fn () => $this->client
                ->users()
                ->byUserId($this->mailbox())
                ->mailFolders()
                ->get($listConfig)
                ->wait(),
            'moveMessageToFolderByName.list'
        );

        $folders = $result?->getValue() ?? [];
        if ($folders === []) {
            throw new GraphException("Mail folder \"{$folderName}\" not found.");
        }

        $id = $folders[0]->getId();
        if ($id === null) {
            throw new GraphException("Mail folder \"{$folderName}\" has no id.");
        }

        $this->moveMessageToFolder($messageId, $id, $scope);
    }

    public function getOrCreateFolder(string $folderName): string
    {
        $cacheKey = 'mail-inbox:folder:'.$this->mailbox().':'.$folderName;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($folderName) {
            $escapedName = $this->escapeODataSingleQuotedString($folderName);

            $listConfig = new MailFoldersRequestBuilderGetRequestConfiguration;
            $listConfig->queryParameters = new MailFoldersRequestBuilderGetQueryParameters(
                filter: "displayName eq '{$escapedName}'",
            );

            $result = $this->wrapGraphCall(
                fn () => $this->client
                    ->users()
                    ->byUserId($this->mailbox())
                    ->mailFolders()
                    ->get($listConfig)
                    ->wait(),
                'getOrCreateFolder.list'
            );

            $folders = $result?->getValue() ?? [];
            if ($folders !== []) {
                $id = $folders[0]->getId();
                if ($id !== null) {
                    return $id;
                }
            }

            $newFolder = new MailFolder;
            $newFolder->setDisplayName($folderName);

            $created = $this->wrapGraphCall(
                fn () => $this->client
                    ->users()
                    ->byUserId($this->mailbox())
                    ->mailFolders()
                    ->post($newFolder)
                    ->wait(),
                'getOrCreateFolder.create'
            );

            $id = $created?->getId();
            if ($id === null) {
                throw new GraphException('Graph API did not return a folder id after create.');
            }

            return $id;
        });
    }

    private function buildDefaultClient(): GraphServiceClient
    {
        return MailInboxGraphServiceClientFactory::make(new ClientCredentialContext(
            (string) config('mail-inbox.graph.tenant_id'),
            (string) config('mail-inbox.graph.client_id'),
            (string) config('mail-inbox.graph.client_secret'),
        ));
    }

    private function mailbox(): string
    {
        return (string) config('mail-inbox.mailbox');
    }

    /**
     * @param  callable(): mixed  $call
     */
    private function wrapGraphCall(callable $call, string $operation): mixed
    {
        Log::channel('mail-inbox')->debug('[MailInbox] Graph API: '.$operation);

        try {
            return $call();
        } catch (ApiException $e) {
            $statusCode = $e->getResponseStatusCode() ?? $e->getCode();

            Log::channel('mail-inbox')->error('[MailInbox] Graph API error: '.$operation, [
                'exception' => $e,
                'status' => $statusCode,
            ]);

            // Extract actual Graph API error details
            $errorMessage = $e->getMessage();
            $odataCode = null;
            if ($e instanceof ODataError) {
                $mainError = $e->getError();
                if ($mainError) {
                    $odataCode = $mainError->getCode();
                    $errorMessage = $odataCode.': '.$mainError->getMessage();
                }
            }
            Log::channel('mail-inbox')->error('[MailInbox] Graph API error detail: '.$errorMessage);

            throw match (true) {
                $statusCode === 401 || $statusCode === 403 => new GraphAuthenticationException(
                    'Graph API auth failed. Check MAIL_INBOX_TENANT_ID, MAIL_INBOX_CLIENT_ID, MAIL_INBOX_CLIENT_SECRET in .env',
                    $statusCode,
                    $e
                ),
                $odataCode !== null
                    && strcasecmp((string) $odataCode, 'syncStateNotFound') === 0
                    && ($statusCode === 410 || $statusCode === 400) => new GraphSyncStateNotFoundException(
                        'Graph delta sync state expired or invalid: '.$errorMessage,
                        $statusCode,
                        $e
                    ),
                $statusCode === 404 && $odataCode === 'ErrorItemNotFound' => new GraphItemNotFoundException(
                    'Graph item not found (likely deleted or purged from the store): '.$errorMessage,
                    $statusCode,
                    $e
                ),
                $statusCode === 404 => new GraphMailboxNotFoundException(
                    "Mailbox '{$this->mailbox()}' not found. Check MAIL_INBOX_MAILBOX in .env",
                    $statusCode,
                    $e
                ),
                $statusCode === 429 => new GraphRateLimitException(
                    'Graph API rate limit reached. Try again later.',
                    $statusCode,
                    $e
                ),
                default => new GraphException(
                    'Graph API error: '.$errorMessage,
                    $statusCode,
                    $e
                ),
            };
        } catch (InvalidArgumentException $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Graph API invalid argument: '.$operation, ['exception' => $e]);

            throw $e;
        } catch (\Exception $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Graph API connection failed: '.$operation, ['exception' => $e]);

            throw new GraphConnectionException(
                'Graph API connection failed: '.$e->getMessage(),
                0,
                $e
            );
        }
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

            return base64_decode($contentBytes->getContents());
        }

        throw new InvalidArgumentException('Unexpected contentBytes type on file attachment.');
    }

    private function escapeODataSingleQuotedString(string $value): string
    {
        return str_replace("'", "''", $value);
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

    private function postGraphMoveMessageToFolder(string $messageId, string $destinationFolderId): void
    {
        $this->wrapGraphCall(function () use ($messageId, $destinationFolderId): void {
            $body = new MovePostRequestBody;
            $body->setDestinationId($destinationFolderId);

            $this->client
                ->users()
                ->byUserId($this->mailbox())
                ->messages()
                ->byMessageId($messageId)
                ->move()
                ->post($body)
                ->wait();
        }, 'moveMessageToFolder.post');
    }

    private function cachedInboxFolderId(string $scope): string
    {
        $cacheKey = 'mail-inbox:inbox-folder-id:'.((string) $scope).':'.$this->mailbox();

        return Cache::remember($cacheKey, now()->addHours(24), function (): string {
            $folder = $this->wrapGraphCall(
                fn () => $this->client
                    ->users()
                    ->byUserId($this->mailbox())
                    ->mailFolders()
                    ->byMailFolderId('inbox')
                    ->get()
                    ->wait(),
                'getInboxMailFolder'
            );

            $id = $folder?->getId();
            if ($id === null || $id === '') {
                throw new GraphException('Graph API did not return an id for the Inbox folder.');
            }

            return $id;
        });
    }

    private function optionalProcessingFolderId(): ?string
    {
        $name = config('mail-inbox.processing_folder');
        if ($name === null || $name === '') {
            return null;
        }

        return $this->getOrCreateFolder((string) $name);
    }

    /**
     * @param  ?string  $inboxFolderId  Resolved well-known Inbox folder id (must not trigger extra Graph calls here).
     */
    private function parentIsAcceptablePipelineSource(?string $parentFolderId, string $inboxFolderId): bool
    {
        if ($parentFolderId === null || $parentFolderId === '') {
            return false;
        }

        if ($parentFolderId === $inboxFolderId) {
            return true;
        }

        $processingId = $this->optionalProcessingFolderId();

        return $processingId !== null && $parentFolderId === $processingId;
    }

    private function logUnexpectedParentForMove(string $messageId, ?string $parentFolderId, string $scope): void
    {
        $context = [
            'messageId' => $messageId,
            'parentFolderId' => $parentFolderId,
            'scope' => $scope,
        ];

        try {
            $processedId = $this->getOrCreateFolder((string) config('mail-inbox.processed_folder'));
            if ($parentFolderId !== null && $parentFolderId === $processedId) {
                Log::channel('mail-inbox')->warning('[MailInbox] Skipping move: message parent appears to be a terminal mailbox folder (Processed/Failed)', $context);

                return;
            }
            $failedId = $this->getOrCreateFolder((string) config('mail-inbox.failed_folder'));
            if ($parentFolderId !== null && $parentFolderId === $failedId) {
                Log::channel('mail-inbox')->warning('[MailInbox] Skipping move: message parent appears to be a terminal mailbox folder (Processed/Failed)', $context);

                return;
            }
        } catch (Throwable) {
        }

        Log::channel('mail-inbox')->warning('[MailInbox] Skipping move: message parent is not an acceptable pipeline source (Inbox or Processing)', $context);
    }

    private function looksLikeGraphFolderResolutionFailure(Throwable $e): bool
    {
        $current = $e;
        for ($i = 0; $i < 6 && $current !== null; $i++) {
            if ($current instanceof ODataError) {
                $code = $current->getError()?->getCode();
                if ($code !== null && in_array((string) $code, ['ErrorFolderNotFound', 'ErrorInvalidIdMalformed'], true)) {
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

    private function processingFolderMisconfigurationHint(Throwable $e): ?string
    {
        if ($this->looksLikeGraphFolderResolutionFailure($e)) {
            return 'Processing folder may be misconfigured — verify MAIL_INBOX_PROCESSING_FOLDER names an existing mailbox folder for this scope/mailbox.';
        }

        return null;
    }
}
