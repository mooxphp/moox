<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticCertificateKindTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_certificate_kind_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_certificate_kind_id',
            'common_name',
            'description',
        ];
    }
}
