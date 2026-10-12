<?php

declare(strict_types=1);

namespace Moox\Migrate\Resources\MigrationResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Migrate\Models\Migration;
use Moox\Migrate\Resources\MigrationResource;
use Moox\Migrate\Support\MigrateRunner;
use Moox\Migrate\Support\MigrateRunStatus;
use Moox\Migrate\Support\MigrationInventory;
use Moox\Migrate\Support\PendingMigrationSchema;
use Override;

class ListMigrations extends BaseListItems
{
    use HasListPageTabs;

    protected static string $resource = MigrationResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->consumeMigrateRunNotification();
    }

    public function hydrate(): void
    {
        $this->consumeMigrateRunNotification();
    }

    #[Override]
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                $this->runningCallout(),
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
                ->disabled(fn (): bool => app(MigrationInventory::class)->pendingCount() === 0
                    || MigrateRunStatus::isActive())
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
                    $causer = auth()->user();
                    $result = $runner->queue($causer instanceof Model ? $causer : null);

                    if ($result['terminal']) {
                        MigrateRunStatus::clear();
                    }

                    $notification = Notification::make()
                        ->title($result['title'])
                        ->body($result['body']);

                    if ($result['queued']) {
                        $notification->info()->send();
                    } elseif ($result['ok']) {
                        $notification->success()->send();
                        $this->resetTable();
                    } elseif ($result['busy']) {
                        $notification->warning()->send();
                    } else {
                        $notification->danger()->send();
                        $this->resetTable();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('migrate.resources.migration.tabs', Migration::class);
    }

    private function consumeMigrateRunNotification(): void
    {
        $status = MigrateRunStatus::consumeTerminal();

        if ($status === null) {
            return;
        }

        $notification = Notification::make()
            ->title($status['title'])
            ->body($status['body'] ?? null);

        match ($status['state']) {
            MigrateRunStatus::STATE_DONE => $notification->success()->send(),
            MigrateRunStatus::STATE_BUSY => $notification->warning()->send(),
            default => $notification->danger()->send(),
        };

        $this->resetTable();
    }

    private function runningCallout(): Callout
    {
        return Callout::make(__('migrate::migrate.migrate_running'))
            ->info()
            ->color(null)
            ->icon(Heroicon::OutlinedArrowPath)
            ->description(__('migrate::migrate.migrate_running_hint'))
            ->hidden(fn (): bool => ! MigrateRunStatus::isActive())
            ->columnSpanFull();
    }
}
