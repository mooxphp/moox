<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticIcdSchemeTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_icd_scheme_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_icd_scheme_id',
            'common_name',
            'description',
        ];
    }
}
