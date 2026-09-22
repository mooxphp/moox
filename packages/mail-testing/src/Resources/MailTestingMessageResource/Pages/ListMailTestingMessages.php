<?php

declare(strict_types=1);

namespace Moox\MailTesting\Resources\MailTestingMessageResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Resources\MailTestingMessageResource;
use Override;

class ListMailTestingMessages extends ListRecords
{
    public static string $resource = MailTestingMessageResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * @return array<Action>
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteAll')
                ->label(__('mail-testing::translations.delete_all'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('mail-testing::translations.delete_all_heading'))
                ->modalDescription(__('mail-testing::translations.delete_all_description'))
                ->visible(fn (): bool => MailTestingRun::query()->exists() || MailTestingMessage::query()->exists())
                ->action(function (): void {
                    MailTestingRun::purgeAll();

                    Notification::make()
                        ->title(__('mail-testing::translations.deleted_all'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
