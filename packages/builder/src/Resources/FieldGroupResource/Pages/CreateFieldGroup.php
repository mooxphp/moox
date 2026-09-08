<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\InteractsWithFieldGroupLocale;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\PersistsFieldGroupInAdmin;
use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;

class CreateFieldGroup extends BaseCreateStaticRecord
{
    use InteractsWithFieldGroupLocale;
    use PersistsFieldGroupInAdmin;

    protected static string $resource = FieldGroupResource::class;

    public function mount(): void
    {
        $this->mountInteractsWithFieldGroupLocale();
        $lang = $this->lang;

        parent::mount();

        $this->lang = $lang;
        $this->syncLangToRequest();
        $this->guardFieldGroupAdminLocale();
    }

    public function hydrate(): void
    {
        $this->hydrateInteractsWithFieldGroupLocale();
    }

    public function getHeaderActions(): array
    {
        return [
            $this->getFieldGroupLanguageSelectorAction(),
        ];
    }

    /**
     * Keep Filament create footer actions — BaseCreateStaticRecord clears them
     * for Moox form-sidebar layouts; FieldGroup uses its own form layout.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            ...($this->canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
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
