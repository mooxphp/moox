<?php

declare(strict_types=1);

namespace Moox\Static\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

class StaticEntryTranslation extends BaseStaticTranslationModel
{
    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_entry_id',
            'common_name',
            'description',
        ];
    }
}
