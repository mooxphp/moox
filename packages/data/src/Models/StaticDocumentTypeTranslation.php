<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticTranslationModel;

final class StaticDocumentTypeTranslation extends BaseStaticTranslationModel
{
    protected $table = 'static_document_type_translations';

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'static_document_type_id',
            'common_name',
            'description',
        ];
    }
}
