<?php

declare(strict_types=1);

namespace Moox\Audit\Listeners;

use Filament\Resources\Events\RecordUpdated;
use Moox\Audit\Support\CustomFieldAuditMerger;

final class MergeUpdatedCustomFieldAudit
{
    public function __construct(
        private readonly CustomFieldAuditMerger $merger,
    ) {
    }

    public function handle(RecordUpdated $event): void
    {
        $this->merger->mergeUpdated(
            $event->getRecord(),
            $event->getPage()::getResource(),
            $event->getData(),
        );
    }
}
