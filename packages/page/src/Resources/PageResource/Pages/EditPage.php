<?php

namespace Moox\Page\Resources\PageResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Page\Resources\Concerns\InteractsWithBlockEditorContent;
use Moox\Page\Resources\PageResource;

class EditPage extends BaseEditDraft
{
    use InteractsWithBlockEditorContent;

    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeSave(array $data): array
    {
        return parent::mutateFormDataBeforeSave($this->normalizeBlockEditorFormData($data));
    }
}
