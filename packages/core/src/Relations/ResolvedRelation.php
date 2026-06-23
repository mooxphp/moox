<?php

declare(strict_types=1);

namespace Moox\Core\Relations;

use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Enums\RelationPresentation;

final class ResolvedRelation
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $pivotAttributes
     * @param  list<string>  $displayColumns
     * @param  array<class-string, array{label: string, title_attribute?: string|null}>  $ownerTypes
     */
    public function __construct(
        public readonly string $key,
        public readonly RelationKind $kind,
        public readonly RelationPerspective $perspective,
        public readonly RelationPresentation $presentation,
        public readonly string $relationship,
        public readonly ?string $relatedModel,
        public readonly ?string $relatedResource,
        public readonly ?string $pivotModel,
        public readonly ?string $pivotTable,
        public readonly ?string $morphType,
        public readonly ?string $foreignKey,
        public readonly ?string $relatedKey,
        public readonly ?string $inverseRelationship,
        public readonly array $pivotAttributes,
        public readonly array $displayColumns,
        public readonly array $ownerTypes,
        public readonly ?string $label,
        public readonly ?string $translationPrefix,
        public readonly array $config,
    ) {}

    public function label(): string
    {
        return RelationLabel::resolve($this->label, ucfirst($this->key));
    }
}
