<?php

declare(strict_types=1);

namespace App\Builder\Resources\TranslateItemResource\Pages;

use App\Builder\Resources\TranslateItemResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateTranslateItem extends CreateRecord
{
    use BaseInCreatePage;
    use SingleSimpleInCreatePage;

    protected static string $resource = TranslateItemResource::class;
}
