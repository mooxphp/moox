<?php

declare(strict_types=1);

namespace Moox\Devlink\Support;

use RuntimeException;

final class DevlinkPackageNotRegisteredException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $missing  slug => packages that require it
     */
    public function __construct(
        public readonly array $missing,
    ) {
        parent::__construct(self::formatMessage($missing));
    }

    /**
     * @param  array<string, list<string>>  $missing
     */
    private static function formatMessage(array $missing): string
    {
        ksort($missing);

        $lines = [
            'Moox package(s) are required by your active bundle(s) but missing from config/devlink.php:',
            '',
        ];

        foreach ($missing as $slug => $requiredBy) {
            $requiredBy = array_values(array_unique($requiredBy));
            sort($requiredBy);

            $lines[] = sprintf(
                '  - moox/%s (required by %s)',
                $slug,
                implode(', ', array_map(fn (string $name): string => 'moox/'.$name, $requiredBy))
            );
        }

        $lines[] = '';
        $lines[] = 'Add each package under packages => [\'slug\' => [\'path\' => ..., \'type\' => \'public\', ...]] in config/devlink.php.';

        return implode("\n", $lines);
    }
}
