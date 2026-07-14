<?php

declare(strict_types=1);

namespace Moox\Page\Resources\PageResource\Pages;

use Moox\BlockEditor\Filament\Concerns\InteractsWithTemplateForm;
use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Page\Resources\PageResource;

class EditPage extends BaseEditDraft
{
    use InteractsWithTemplateForm;

    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->normalizeTemplateFormData($data);

        return parent::mutateFormDataBeforeSave($data);
    }
}
