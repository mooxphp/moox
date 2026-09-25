<?php

declare(strict_types=1);

namespace Moox\Migrate\Resources\MigrationResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Migrate\Models\Migration;
use Moox\Migrate\Resources\MigrationResource;
use Override;
use Throwable;

class ListMigrations extends BaseListItems
{
    use HasListPageTabs;

    protected static string $resource = MigrationResource::class;

    #[Override]
    public function getHeaderActions(): array
    {
        return [
            Action::make('migrate')
                ->label(__('migrate::migrate.migrate'))
                ->requiresConfirmation()
                ->modalHeading(__('migrate::migrate.migrate_modal_heading'))
                ->modalDescription(__('migrate::migrate.migrate_modal_description'))
                ->action(function (): void {
                    try {
                        $exitCode = Artisan::call('migrate', ['--force' => true]);
                        $output = trim(Artisan::output());

                        if ($exitCode !== 0) {
                            Notification::make()
                                ->title(__('migrate::migrate.migrate_failed'))
                                ->body($output !== '' ? $output : null)
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('migrate::migrate.migrate_done'))
                                ->body($output !== '' ? $output : null)
                                ->success()
                                ->send();
                        }
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title(__('migrate::migrate.migrate_failed'))
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
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
