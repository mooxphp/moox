<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Override;
use Filament\Resources\Pages\CreateRecord;
use Moox\Tag\Resources\TagResource;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
