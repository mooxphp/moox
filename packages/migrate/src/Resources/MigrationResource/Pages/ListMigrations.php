<?php

declare(strict_types=1);

namespace Moox\Migrate\Resources\MigrationResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Migrate\Models\Migration;
use Moox\Migrate\Resources\MigrationResource;
use Moox\Migrate\Support\MigrationInventory;
use Moox\Migrate\Support\MigrateRunner;
use Moox\Migrate\Support\PendingMigrationSchema;
use Override;

class ListMigrations extends BaseListItems
{
    use HasListPageTabs;

    protected static string $resource = MigrationResource::class;

    #[Override]
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                PendingMigrationSchema::callout(),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    #[Override]
    public function getHeaderActions(): array
    {
        return [
            Action::make('migrate')
                ->label(__('migrate::migrate.migrate'))
                ->requiresConfirmation()
                ->modalHeading(__('migrate::migrate.migrate_modal_heading'))
                ->modalDescription(function (): string {
                    if (app(MigrationInventory::class)->pendingCount() === 0) {
                        return __('migrate::migrate.pending_empty');
                    }

                    return __('migrate::migrate.migrate_modal_description');
                })
                ->schema(fn (): array => app(MigrationInventory::class)->pendingCount() > 0
                    ? [PendingMigrationSchema::list()]
                    : [])
                ->formWrapper(false)
                ->action(function (MigrateRunner $runner): void {
                    $result = $runner->run();

                    $notification = Notification::make()
                        ->title($result['title'])
                        ->body($result['body']);

                    if ($result['ok']) {
                        $notification->success()->send();
                    } elseif ($result['busy']) {
                        $notification->warning()->send();
                    } else {
                        $notification->danger()->send();
                    }

                    $this->resetTable();
                }),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('migrate.resources.migration.tabs', Migration::class);
    }
}
