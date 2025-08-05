<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Model;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Override;

class ViewCategory extends BaseViewDraft
{
    protected static string $resource = CategoryResource::class;


    #[Override]
    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title . ' - ' . __('core::core.deleted');
        }

        return $title;
    }

    private function isRecordTrashed(): bool
    {
        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }
}
