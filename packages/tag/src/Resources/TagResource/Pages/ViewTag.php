<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\Tag\Resources\TagResource;
use Override;

class ViewTag extends BaseViewDraft
{
    protected static string $resource = TagResource::class;



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
