<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticPaymentMeanTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_payment_mean_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_payment_mean_id',
            'common_name',
            'description',
        ];
    }
}
