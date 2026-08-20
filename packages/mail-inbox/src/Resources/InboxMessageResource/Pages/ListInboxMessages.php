<?php

declare(strict_types=1);

namespace Moox\MailInbox\Resources\InboxMessageResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Resources\InboxMessageResource;

final class ListInboxMessages extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = InboxMessageResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('mail-inbox.resources.inbox-messages.tabs', InboxMessage::class);
    }

    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof \Closure) {
                $value = $value();
            }

            if ($condition['operator'] === 'in') {
                $query->whereIn($condition['field'], (array) $value);
            } elseif ($condition['operator'] === 'not_in') {
                $query->whereNotIn($condition['field'], (array) $value);
            } else {
                $query->where($condition['field'], $condition['operator'], $value);
            }
        }

        return $query;
    }
}
