<?php

declare(strict_types=1);

namespace Moox\Builder\Listeners;

use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Services\CustomFieldsManager;

class PersistCustomFields
{
    public function __construct(
        protected CustomFieldsManager $customFieldsManager,
    ) {}

    /**
     * Filament dispatches resource events with a payload array; Laravel passes
     * each value as a separate listener argument (record, data, page).
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Model $record, array $data, Page $page): void
    {
        $resourceClass = $page::getResource();

        if (! is_subclass_of($resourceClass, Resource::class)) {
            return;
        }

        if (! $this->customFieldsManager->usesCustomFields($resourceClass)) {
            return;
        }

        $this->customFieldsManager->saveFromFormData($resourceClass, $record, $data);
    }
}
