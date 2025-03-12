<?php

declare(strict_types=1);

namespace App\Builder\Resources\PublishItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Publish\SinglePublishInEditPage;

class EditPublishItem extends EditRecord
{
    use BaseInEditPage, SinglePublishInEditPage;

    protected static string $resource = \App\Builder\Resources\PublishItemResource::class;
}
