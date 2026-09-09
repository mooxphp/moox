<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

final class TestListOnlyItemResource extends Resource
{
    protected static ?string $model = TestAuditableItem::class;

    public static function getPages(): array
    {
        return [];
    }

    public static function hasPage(string $page): bool
    {
        return false;
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = true, ?string $configuration = null): string
    {
        $query = http_build_query([
            'tableAction' => $parameters['tableAction'] ?? null,
            'tableActionRecord' => $parameters['tableActionRecord'] instanceof Model
                ? $parameters['tableActionRecord']->getKey()
                : ($parameters['tableActionRecord'] ?? null),
        ]);

        return '/test-items?'.$query;
    }
}
