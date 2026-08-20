<?php

declare(strict_types=1);

namespace Moox\Msgraph\Mail;

use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\Delta\DeltaRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilder;
use Microsoft\Graph\GraphServiceClient;

/**
 * Hides the Graph SDK user/mailbox request walk behind one object.
 */
final class GraphMailboxClient
{
    public function __construct(
        private GraphServiceClient $client,
        private string $mailboxAddress,
    ) {}

    public function inboxDelta(): DeltaRequestBuilder
    {
        return $this->client
            ->users()
            ->byUserId($this->mailboxAddress)
            ->mailFolders()
            ->byMailFolderId('inbox')
            ->messages()
            ->delta();
    }

    public function message(string $messageId): MessageItemRequestBuilder
    {
        return $this->client
            ->users()
            ->byUserId($this->mailboxAddress)
            ->messages()
            ->byMessageId($messageId);
    }

    public function mailFolders(): MailFoldersRequestBuilder
    {
        return $this->client
            ->users()
            ->byUserId($this->mailboxAddress)
            ->mailFolders();
    }
}
