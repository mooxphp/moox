<?php

declare(strict_types=1);

namespace Moox\Msgraph\Mail;

use Illuminate\Contracts\Cache\Repository;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilderGetRequestConfiguration;
use Moox\Msgraph\Exceptions\GraphException;

/**
 * Resolves mailbox folders by display name, creating them when missing, with a 24h cache.
 */
final class GraphFolderResolver
{
    public function __construct(
        private GraphMailboxClient $mailbox,
        private GraphCall $graphCall,
        private Repository $cache,
        private string $mailboxAddress,
    ) {}

    public function getOrCreate(string $folderName): string
    {
        $cacheKey = 'msgraph:folder:'.$this->mailboxAddress.':'.$folderName;

        return $this->cache->remember($cacheKey, now()->addHours(24), function () use ($folderName): string {
            $escapedName = str_replace("'", "''", $folderName);

            $listConfig = new MailFoldersRequestBuilderGetRequestConfiguration;
            $listConfig->queryParameters = new MailFoldersRequestBuilderGetQueryParameters(
                filter: "displayName eq '{$escapedName}'",
            );

            $result = $this->graphCall->run(
                fn () => $this->mailbox->mailFolders()->get($listConfig)->wait(),
                'getOrCreateFolder.list',
            );

            $folders = $result?->getValue() ?? [];
            if ($folders !== []) {
                $id = $folders[0]->getId();
                if ($id !== null && $id !== '') {
                    return $id;
                }
            }

            $newFolder = new MailFolder;
            $newFolder->setDisplayName($folderName);

            $created = $this->graphCall->run(
                fn () => $this->mailbox->mailFolders()->post($newFolder)->wait(),
                'getOrCreateFolder.create',
            );

            $id = $created?->getId();
            if ($id === null || $id === '') {
                throw new GraphException('Graph API did not return a folder id after create.');
            }

            return $id;
        });
    }

    public function inboxFolderId(): string
    {
        $cacheKey = 'msgraph:inbox-folder-id:'.$this->mailboxAddress;

        return $this->cache->remember($cacheKey, now()->addHours(24), function (): string {
            $folder = $this->graphCall->run(
                fn () => $this->mailbox->mailFolders()->byMailFolderId('inbox')->get()->wait(),
                'getInboxMailFolder',
            );

            $id = $folder?->getId();
            if ($id === null || $id === '') {
                throw new GraphException('Graph API did not return an id for the Inbox folder.');
            }

            return $id;
        });
    }
}
