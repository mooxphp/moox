<?php

declare(strict_types=1);

namespace Moox\MailInbox\Resources\MailInboxSyncStateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\MailInbox\Resources\MailInboxSyncStateResource;

final class ListMailInboxSyncStates extends ListRecords
{
    protected static string $resource = MailInboxSyncStateResource::class;
}
