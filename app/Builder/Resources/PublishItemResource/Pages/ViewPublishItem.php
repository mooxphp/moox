<?php

declare(strict_types=1);

namespace App\Builder\Resources\PublishItemResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Publish\SinglePublishInViewPage;

class ViewPublishItem extends ViewRecord
{
    use BaseInViewPage, SinglePublishInViewPage;

    protected static string $resource = \App\Builder\Resources\PublishItemResource::class;
}
