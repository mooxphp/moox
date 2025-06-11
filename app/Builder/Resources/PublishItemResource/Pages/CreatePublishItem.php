<?php

declare(strict_types=1);

namespace App\Builder\Resources\PublishItemResource\Pages;

use App\Builder\Resources\PublishItemResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Publish\SinglePublishInCreatePage;

class CreatePublishItem extends CreateRecord
{
    use BaseInCreatePage, SinglePublishInCreatePage;

    protected static string $resource = PublishItemResource::class;
}
