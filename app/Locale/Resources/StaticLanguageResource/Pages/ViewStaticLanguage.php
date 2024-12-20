<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticLanguageResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewStaticLanguage extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = \App\Locale\Resources\StaticLanguageResource::class;
}
