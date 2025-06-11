<?php

declare(strict_types=1);

namespace App\Builder\Resources\PublishItemResource\Pages;

use App\Builder\Resources\PublishItemResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Publish\SinglePublishInViewPage;

class ViewPublishItem extends ViewRecord
{
    use BaseInViewPage, SinglePublishInViewPage;

    protected static string $resource = PublishItemResource::class;
}
