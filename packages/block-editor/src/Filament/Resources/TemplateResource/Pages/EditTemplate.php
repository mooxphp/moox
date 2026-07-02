<?php

namespace Moox\BlockEditor\Filament\Resources\TemplateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\BlockEditor\Filament\Concerns\InteractsWithTemplateForm;
use Moox\BlockEditor\Filament\Resources\TemplateResource;

class EditTemplate extends EditRecord
{
    use InteractsWithTemplateForm;

    protected static string $resource = TemplateResource::class;

    protected static ?string $title = 'Template bearbeiten';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeTemplateFormData($data);
    }
}
