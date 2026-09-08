<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Actions\DeleteAction;
use Moox\Builder\Filament\Actions\FieldGroupDefinitionActions;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\InteractsWithFieldGroupLocale;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\PersistsFieldGroupInAdmin;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\FieldGroupPlacement;
use Moox\Core\Entities\Items\Static\Pages\BaseEditStaticRecord;

class EditFieldGroup extends BaseEditStaticRecord
{
    use InteractsWithFieldGroupLocale;
    use PersistsFieldGroupInAdmin;

    protected static string $resource = FieldGroupResource::class;

    public function mount($record): void
    {
        $this->mountInteractsWithFieldGroupLocale();
        $lang = $this->lang;

        parent::mount($record);

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
            FieldGroupDefinitionActions::export($this->getRecord()),
            DeleteAction::make(),
        ];
    }

    /**
     * Keep Filament edit footer actions — BaseEditStaticRecord clears them
     * for Moox form-sidebar layouts; FieldGroup uses its own form layout.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeFill(array $data): array
    {
        $this->ensureAllowedBuilderAdminLocale();
        $this->syncLangToRequest();

        /** @var FieldGroup $record */
        $record = $this->getRecord();

        $persistence = app(FieldGroupPersistence::class);

        $data['name'] = $persistence->localizedGroupName($record, $this->lang);
        $data['placement'] = FieldGroupPlacement::normalize($record->placement);
        $data['location_rules'] = $persistence->flattenLocationRulesForForm(
            $record->location_rules ?? [],
        );

        $data['target_entities'] = $persistence->entitiesFromLocationRules(
            $record->location_rules ?? [],
        );

        $data['location_constraints'] = $persistence->constraintsFromLocationRules(
            $record->location_rules ?? [],
        );

        $data['fields'] = $persistence->fieldRowsForForm($record, $this->lang);

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): FieldGroup
    {
        $this->ensureAllowedBuilderAdminLocale();
        $this->syncLangToRequest();
        $this->applyFieldGroupDefaultLocale($record);

        return $this->persistFieldGroup($record, $data);
    }
}
