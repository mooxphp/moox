<?php

declare(strict_types=1);

namespace Moox\Transform\Contracts;

use Moox\Transform\Models\TransformRecord;

interface PreparesTransformSource
{
    public function prepare(TransformRecord $record): void;
}
