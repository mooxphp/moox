<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCertificateKindResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticCertificateKindResource;

class CreateStaticCertificateKind extends BaseCreateStaticRecord
{
    protected static string $resource = StaticCertificateKindResource::class;
}
