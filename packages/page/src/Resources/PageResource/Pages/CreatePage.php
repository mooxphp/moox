<?php

namespace Moox\Page\Resources\PageResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Page\Resources\Concerns\InteractsWithBlockEditorContent;
use Moox\Page\Resources\PageResource;

class CreatePage extends BaseCreateDraft
{
    use InteractsWithBlockEditorContent;

    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeBlockEditorFormData(parent::mutateFormDataBeforeCreate($data));
    }
}
