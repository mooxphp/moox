<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

final class TestAuditableItemResource extends Resource
{
    protected static ?string $model = TestAuditableItem::class;

    public static function getPages(): array
    {
        return [];
    }

    public static function hasPage(string $page): bool
    {
        return $page === 'edit';
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = true, ?string $configuration = null): string
    {
        $record = $parameters['record'] ?? null;
        $id = $record instanceof Model ? $record->getKey() : $record;

        return '/test-items/'.$id.'/edit';
    }
}
