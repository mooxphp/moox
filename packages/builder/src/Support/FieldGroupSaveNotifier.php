<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

final class FieldGroupSaveNotifier
{
    public static function notify(ValidationException $exception): void
    {
        $message = collect($exception->errors())->flatten()->first();

        if (! is_string($message) || $message === '') {
            return;
        }

        Notification::make()
            ->title(__('builder::builder.field_group.save_failed'))
            ->body($message)
            ->danger()
            ->persistent()
            ->send();
    }
}
