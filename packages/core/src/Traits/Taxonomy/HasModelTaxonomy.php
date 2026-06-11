<?php

namespace Moox\Core\Traits\Taxonomy;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Moox\Core\Traits\Relations\HasRelations;

trait HasModelTaxonomy
{
    use HasRelations;

    public function taxonomy(string $taxonomy): MorphToMany
    {
        /** @var MorphToMany */
        return $this->relation($taxonomy);
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function syncTaxonomy(string $taxonomy, array $ids): void
    {
        $this->syncRelation($taxonomy, $ids);
    }
}
