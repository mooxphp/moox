<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Moox\MailTemplate\Support\MailMedia;

final class LogoField
{
    public static function make(string $helpKey, string $directory): FileUpload
    {
        $label = __('mail-template::translations.logo');
        $help = __($helpKey);
        $pickerClass = MailMedia::pickerClass();

        if (is_string($pickerClass)) {
            return $pickerClass::make('logo')
                ->label($label)
                ->helperText($help)
                ->multiple(false)
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'image/svg+xml',
                ])
                ->loadStateFromRelationshipsUsing(function (): void {});
        }

        return FileUpload::make('logo')
            ->label($label)
            ->helperText($help)
            ->image()
            ->imagePreviewHeight('80')
            ->disk('public')
            ->directory($directory)
            ->visibility('public');
    }
}
