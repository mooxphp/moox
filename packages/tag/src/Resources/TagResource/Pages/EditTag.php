<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Override;
use Filament\Resources\Pages\EditRecord;
use Moox\Tag\Resources\TagResource;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
