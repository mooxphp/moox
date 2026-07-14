<?php

declare(strict_types=1);

namespace Moox\Page\Resources\PageResource\Pages;

use Moox\BlockEditor\Filament\Concerns\InteractsWithTemplateForm;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Page\Resources\PageResource;

class CreatePage extends BaseCreateDraft
{
    use InteractsWithTemplateForm;

    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeTemplateFormData($data);
    }
}
