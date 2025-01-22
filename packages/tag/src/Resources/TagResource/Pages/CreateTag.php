<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Tag\Resources\TagResource;
use Override;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
