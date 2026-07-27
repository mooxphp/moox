<?php

declare(strict_types=1);

namespace Moox\Static\Filament\Resources\Concerns;

use Moox\Core\Entities\Items\Static\Filament\Concerns\HasStaticCodelistResource as CoreHasStaticCodelistResource;

/**
 * @deprecated Use {@see CoreHasStaticCodelistResource}
 */
trait HasStaticCodelistResource
{
    use CoreHasStaticCodelistResource;
}
