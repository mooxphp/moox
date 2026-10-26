<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\LegalForm\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Organization\Resources\LegalFormResource;

class CreateLegalForm extends CreateRecord
{
    protected static string $resource = LegalFormResource::class;
}
