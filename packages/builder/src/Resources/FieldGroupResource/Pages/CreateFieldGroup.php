<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\InteractsWithFieldGroupLocale;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\PersistsFieldGroupInAdmin;

class CreateFieldGroup extends CreateRecord
{
    use InteractsWithFieldGroupLocale;
    use PersistsFieldGroupInAdmin;

    protected static string $resource = FieldGroupResource::class;

    public function mount(): void
    {
        $this->mountInteractsWithFieldGroupLocale();

        parent::mount();

        $this->guardFieldGroupAdminLocale();
    }

    public function hydrate(): void
    {
        $this->hydrateInteractsWithFieldGroupLocale();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getFieldGroupLanguageSelectorAction(),
        ];
    }

    protected function handleRecordCreation(array $data): FieldGroup
    {
        $this->ensureAllowedBuilderAdminLocale();
        $this->syncLangToRequest();

        $group = new FieldGroup;
        $this->applyFieldGroupDefaultLocale($group);

        return $this->persistFieldGroup($group, $data);
    }
}
