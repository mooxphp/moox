<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

final class TestDraftMainTranslation extends BaseDraftTranslationModel
{
    protected $table = 'test_draft_main_translations';

    /**
     * @return array<int, string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'test_draft_main_id',
            'title',
        ];
    }
}
