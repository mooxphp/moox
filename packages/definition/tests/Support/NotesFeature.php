<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Support;

use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\Feature;
use Moox\Definition\Definitions\Field;

final class NotesFeature extends Feature
{
    public static function key(): string
    {
        return 'notes';
    }

    public function apply(Entity $entity): void
    {
        $entity->addField(
            Field::textarea('notes')->nullable()->section('Content'),
        );
    }
}
