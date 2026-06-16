<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;

final class TestDraftMain extends BaseDraftModel
{
    use SoftDeletes;

    protected $table = 'test_draft_mains';

    public $incrementing = true;

    protected $keyType = 'int';

    public string $translationModel = TestDraftMainTranslation::class;

    public string $translationForeignKey = 'test_draft_main_id';

    public string $localeKey = 'locale';

    public bool $useTranslationFallback = true;

    protected $fillable = [
        'status',
        'scope',
    ];

    /**
     * @return array<int, string>
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
        ];
    }
}
