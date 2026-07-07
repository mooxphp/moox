<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Moox\Transform\Contracts\BatchDestinationWriter;
use Moox\Transform\Models\TransformDefinition;

final class BatchDestinationWriterRegistry
{
    /**
     * @param  iterable<BatchDestinationWriter>  $writers
     */
    public function __construct(
        private readonly iterable $writers,
    ) {}

    public function resolve(string $destinationClass, TransformDefinition $definition): ?BatchDestinationWriter
    {
        foreach ($this->writers as $writer) {
            if ($writer->supports($destinationClass, $definition)) {
                return $writer;
            }
        }

        return null;
    }
}
