<?php

declare(strict_types=1);

namespace App\Builder\Resources\StaticLanguageResource\Pages;

use App\Builder\Resources\StaticLanguageResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateStaticLanguage extends CreateRecord
{
    use BaseInCreatePage;
    use SingleSimpleInCreatePage;
    protected static string $resource = StaticLanguageResource::class;
}
