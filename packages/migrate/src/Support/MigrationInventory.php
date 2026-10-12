<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Str;

final class MigrationInventory
{
    public function __construct(private Migrator $migrator) {}

    /**
     * @return list<PendingMigration>
     */
    public function pending(): array
    {
        $files = $this->migrator->getMigrationFiles($this->paths());
        $ran = array_flip($this->migrator->getRepository()->getRan());
        $pending = [];

        foreach ($files as $name => $path) {
            if (isset($ran[$name])) {
                continue;
            }

            $pending[] = new PendingMigration(
                name: $name,
                path: $path,
                origin: $this->origin($path),
            );
        }

        return $pending;
    }

    public function pendingCount(): int
    {
        return count($this->pending());
    }

    public function origin(string $path): string
    {
        $relative = $this->relativePath($path);

        if (Str::startsWith($relative, 'database/migrations')) {
            return __('migrate::migrate.origin_app');
        }

        if (preg_match('#^packages/([^/]+)/#', $relative, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('#^vendor/([^/]+)/([^/]+)/#', $relative, $matches) === 1) {
            return $matches[1].'/'.$matches[2];
        }

        return $relative === '' ? __('migrate::migrate.origin_app') : dirname($relative);
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return array_values(array_unique([
            ...$this->migrator->paths(),
            database_path('migrations'),
        ]));
    }

    private function relativePath(string $path): string
    {
        $base = str_replace('\\', '/', base_path());
        $normalized = str_replace('\\', '/', $path);

        if (str_starts_with($normalized, $base.'/')) {
            return substr($normalized, strlen($base) + 1);
        }

        return ltrim($normalized, '/');
    }
}
