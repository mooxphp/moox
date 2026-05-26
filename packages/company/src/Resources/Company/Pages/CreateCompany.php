<?php

declare(strict_types=1);

namespace Moox\Company\Resources\Company\Pages;

use Moox\Company\Resources\CompanyResource;
use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;

class CreateCompany extends BaseCreateRecord
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        parent::mount();

        $parentId = request()->query('parent_id');

        if (filled($parentId)) {
            $this->form->fill([
                'parent_id' => $parentId,
            ]);
        }
    }
}
