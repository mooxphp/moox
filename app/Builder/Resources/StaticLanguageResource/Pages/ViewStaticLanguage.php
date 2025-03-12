<?php

declare(strict_types=1);

namespace App\Builder\Resources\StaticLanguageResource\Pages;

use App\Builder\Resources\StaticLanguageResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewStaticLanguage extends ViewRecord
{
    use BaseInViewPage;
    use SingleSimpleInViewPage;
    protected static string $resource = StaticLanguageResource::class;
}
