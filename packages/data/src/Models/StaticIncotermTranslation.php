<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticIncotermTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_incoterm_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_incoterm_id',
            'common_name',
            'description',
        ];
    }
}
