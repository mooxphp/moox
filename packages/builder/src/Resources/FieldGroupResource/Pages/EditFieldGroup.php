<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Services\FieldGroupPersistence;

class EditFieldGroup extends EditRecord
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var FieldGroup $record */
        $record = $this->getRecord();
        $record->load(['fields.options']);

        $data['location_rules'] = app(FieldGroupPersistence::class)->flattenLocationRulesForForm(
            $record->location_rules ?? [],
        );

        $data['target_entities'] = app(FieldGroupPersistence::class)->entitiesFromLocationRules(
            $record->location_rules ?? [],
        );

        $data['fields'] = app(FieldGroupPersistence::class)->fieldRowsForForm($record);

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): FieldGroup
    {
        app(FieldGroupPersistence::class)->sync($record, $data);

        return $record->fresh(['fields.options']);
    }
}
