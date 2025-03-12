<?php

declare(strict_types=1);

namespace App\Builder\Resources\StaticLanguageResource\Pages;

use App\Builder\Resources\StaticLanguageResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditStaticLanguage extends EditRecord
{
    use BaseInEditPage;
    use SingleSimpleInEditPage;

    protected static string $resource = StaticLanguageResource::class;
}
