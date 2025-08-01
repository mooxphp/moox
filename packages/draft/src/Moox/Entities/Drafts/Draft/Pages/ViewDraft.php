<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft\Pages;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Override;

class ViewDraft extends BaseViewDraft
{
    #[Override]
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
}
