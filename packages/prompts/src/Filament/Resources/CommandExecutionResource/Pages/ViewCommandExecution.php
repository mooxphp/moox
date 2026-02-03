<?php

namespace Moox\Prompts\Filament\Resources\CommandExecutionResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use Moox\Prompts\Filament\Resources\CommandExecutionResource;

class ViewCommandExecution extends ViewRecord
{
    protected static string $resource = CommandExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_error')
                ->label(__('moox-prompts::prompts.ui.view_error'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->modalHeading(__('moox-prompts::prompts.ui.error_message'))
                ->modalContent(fn () => new HtmlString('<pre style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 0.875rem; overflow-x: auto; max-height: 70vh; overflow-y: auto;">'.e($this->record->error_message ?? __('moox-prompts::prompts.ui.no_error_message')).'</pre>'))
                ->modalWidth('screen')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('moox-prompts::prompts.ui.close'))
                ->visible(fn () => ! empty($this->record->error_message)),
        ];
    }
}
