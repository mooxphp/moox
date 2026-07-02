<?php

namespace Moox\BlockEditor\Filament\Resources\TemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\BlockEditor\Filament\Concerns\InteractsWithTemplateForm;
use Moox\BlockEditor\Filament\Resources\TemplateResource;

class CreateTemplate extends CreateRecord
{
    use InteractsWithTemplateForm;

    protected static string $resource = TemplateResource::class;

    protected static ?string $title = 'Template anlegen';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeTemplateFormData($data);
    }
}
