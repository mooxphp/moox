<?php

declare(strict_types=1);

namespace Moox\Definition\Features\Identity;

use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\Feature;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldRole;

final class Identity extends Feature
{
    public static function key(): string
    {
        return 'identity';
    }

    public function apply(Entity $entity): void
    {
        $entity
            ->addField(
                Field::id('id')
                    ->label('ID')
                    ->section('Identity')
                    ->unique()
                    ->immutable()
                    ->systemManaged()
                    ->generated('primary')
                    ->role(FieldRole::PrimaryIdentifier),
            )
            ->addField(
                Field::ulid('ulid')
                    ->label('ULID')
                    ->section('Identity')
                    ->unique()
                    ->immutable()
                    ->systemManaged()
                    ->generated('ulid')
                    ->role(FieldRole::PublicIdentifier),
            )
            ->addField(
                Field::uuid('uuid')
                    ->label('UUID')
                    ->section('Identity')
                    ->unique()
                    ->immutable()
                    ->systemManaged()
                    ->generated('uuid')
                    ->role(FieldRole::UniversalIdentifier),
            );
    }
}
