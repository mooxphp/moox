<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticUnitTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_unit_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_unit_id',
            'common_name',
            'description',
        ];
    }
}
