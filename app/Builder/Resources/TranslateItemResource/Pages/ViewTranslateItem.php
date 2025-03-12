<?php

declare(strict_types=1);

namespace App\Builder\Resources\TranslateItemResource\Pages;

use App\Builder\Resources\TranslateItemResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewTranslateItem extends ViewRecord
{
    use BaseInViewPage;
    use SingleSimpleInViewPage;

    protected static string $resource = TranslateItemResource::class;
}
