<?php

declare(strict_types=1);

namespace App\Builder\Resources\TranslateItemResource\Pages;

use App\Builder\Resources\TranslateItemResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditTranslateItem extends EditRecord
{
    use BaseInEditPage;
    use SingleSimpleInEditPage;
    protected static string $resource = TranslateItemResource::class;
}
