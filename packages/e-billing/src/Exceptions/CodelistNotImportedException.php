<?php

declare(strict_types=1);

namespace Moox\EBilling\Exceptions;

use RuntimeException;

final class CodelistNotImportedException extends RuntimeException
{
    public function __construct(
        public readonly string $table,
        public readonly string $importCommand = 'moox:data:import-codelists',
    ) {
        parent::__construct(
            "Codelist table [{$table}] is empty. Run `php artisan {$importCommand}` first."
        );
    }
}
