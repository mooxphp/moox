<?php

namespace Moox\Core\Forms;

use Moox\Slug\Forms\Components\TitleWithSlugInput;

class TaxonomyCreateForm
{
    public static function getSchema(): array
    {
        return [
            TitleWithSlugInput::make(
                fieldTitle: 'title',
                fieldSlug: 'slug',
            ),
        ];
    }
}
