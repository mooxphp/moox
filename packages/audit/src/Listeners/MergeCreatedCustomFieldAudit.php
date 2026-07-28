<?php

declare(strict_types=1);

namespace Moox\Audit\Listeners;

use Filament\Resources\Events\RecordCreated;
use Moox\Audit\Support\CustomFieldAuditMerger;

final class MergeCreatedCustomFieldAudit
{
    public function __construct(
        private readonly CustomFieldAuditMerger $merger,
    ) {
    }

    public function handle(RecordCreated $event): void
    {
        $this->merger->mergeCreated(
            $event->getRecord(),
            $event->getPage()::getResource(),
            $event->getData(),
        );
    }
}
