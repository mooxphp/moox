<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\HandlesDynamicTaxonomies;

class ViewItem extends ViewRecord
{
    use HandlesDynamicTaxonomies;

    protected static string $resource = ItemResource::class;

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $taxonomyService = $this->getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();

        foreach ($taxonomies as $taxonomy => $settings) {
            $table = $taxonomyService->getTaxonomyTable($taxonomy);
            $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
            $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
            $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);

            $model = app($modelClass);
            $modelTable = $model->getTable();

            $tags = DB::table($table)
                ->join($modelTable, "{$table}.{$relatedKey}", '=', "{$modelTable}.id")
                ->where("{$table}.{$foreignKey}", $this->record->id)
                ->pluck("{$modelTable}.id")
                ->toArray();

            $data[$taxonomy] = $tags;
        }

        return $data;
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->record->toArray();

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title.' - '.__('core::core.deleted');
        }

        return $title;
    }

    private function isRecordTrashed(): bool
    {
        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function resolveRecord($key): Model
    {
        $model = static::getResource()::getModel();

        $record = $model::findOrFail($key);

        return $record;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        $this->handleTaxonomies();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getFormActions(): array
    {
        return [];
    }
}
