<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticAllowanceReasonTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_allowance_reason_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_allowance_reason_id',
            'common_name',
            'description',
        ];
    }
}
