<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\FieldValuePurger;

class EntityModelDeletionRegistrar
{
    /** @var list<class-string<Model>> */
    protected static array $registeredModels = [];

    public function __construct(
        protected EntityRegistry $entityRegistry,
        protected FieldValuePurger $purger,
    ) {}

    public function register(): void
    {
        foreach ($this->entityRegistry->all() as $entity => $definition) {
            $modelClass = $this->entityRegistry->modelFor($entity);

            if ($modelClass === null || in_array($modelClass, self::$registeredModels, true)) {
                continue;
            }

            $modelClass::deleted(function (Model $record) use ($entity): void {
                app(FieldValuePurger::class)->purgeForRecord($entity, $record->getKey());
            });

            self::$registeredModels[] = $modelClass;
        }
    }
}
