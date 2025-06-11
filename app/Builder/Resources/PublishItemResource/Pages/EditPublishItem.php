<?php

declare(strict_types=1);

namespace App\Builder\Resources\PublishItemResource\Pages;

use App\Builder\Resources\PublishItemResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Publish\SinglePublishInEditPage;

class EditPublishItem extends EditRecord
{
    use BaseInEditPage, SinglePublishInEditPage;

    protected static string $resource = PublishItemResource::class;
}
