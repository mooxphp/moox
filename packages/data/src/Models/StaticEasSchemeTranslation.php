<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticEasSchemeTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_eas_scheme_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_eas_scheme_id',
            'common_name',
            'description',
        ];
    }
}
