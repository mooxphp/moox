<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class RelatedRecordUrlResolver
{
    public static function forModel(?Model $record): ?string
    {
        if (! $record instanceof Model) {
            return null;
        }

        try {
            $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
        } catch (Throwable) {
            return null;
        }

        if ($panel === null) {
            return null;
        }

        try {
            $resourceClass = $panel->getModelResource($record);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($resourceClass) || ! class_exists($resourceClass)) {
            return null;
        }

        foreach (['view', 'edit'] as $page) {
            if (! $resourceClass::hasPage($page)) {
                continue;
            }

            try {
                return $resourceClass::getUrl($page, ['record' => $record], shouldGuessMissingParameters: false);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
