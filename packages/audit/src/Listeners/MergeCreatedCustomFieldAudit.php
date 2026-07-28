<?php

declare(strict_types=1);

namespace Moox\Audit\Listeners;

use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Support\CustomFieldAuditMerger;

final class MergeCreatedCustomFieldAudit
{
    public function __construct(
        private readonly CustomFieldAuditMerger $merger,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Model $record, array $data, Page $page): void
    {
        $resourceClass = $page::getResource();

        if (! is_subclass_of($resourceClass, Resource::class)) {
            return;
        }

        $this->merger->mergeCreated(
            $record,
            $resourceClass,
            $data,
        );
    }
}
