<?php

declare(strict_types=1);

namespace Moox\Transform\Contracts;

use Illuminate\Database\Eloquent\Model;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Support\Execution\ResolvedTransformRow;

interface BatchDestinationWriter
{
    /**
     * @param  class-string<Model>  $destinationClass
     */
    public function supports(string $destinationClass, TransformDefinition $definition): bool;

    /**
     * @param  list<ResolvedTransformRow>  $rows
     * @return list<string>
     */
    public function write(string $destinationClass, TransformDefinition $definition, array $rows): array;
}
