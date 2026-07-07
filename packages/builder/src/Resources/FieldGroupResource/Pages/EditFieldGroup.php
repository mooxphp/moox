<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Filament\Actions\FieldGroupDefinitionActions;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\InteractsWithFieldGroupLocale;
use Moox\Builder\Resources\FieldGroupResource\Pages\Concerns\PersistsFieldGroupInAdmin;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\FieldGroupPlacement;

class EditFieldGroup extends EditRecord
{
    use InteractsWithFieldGroupLocale;
    use PersistsFieldGroupInAdmin;

    protected static string $resource = FieldGroupResource::class;

    public function mount(int|string $record): void
    {
        $this->mountInteractsWithFieldGroupLocale();

        parent::mount($record);

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
            FieldGroupDefinitionActions::export($this->getRecord()),
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->syncLangToRequest();

        /** @var FieldGroup $record */
        $record = $this->getRecord();
        $record->load(['fields.options', 'translations']);

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
        $this->syncLangToRequest();
        $this->applyFieldGroupDefaultLocale($record);

        return $this->persistFieldGroup($record, $data);
    }
}
