<?php

declare(strict_types=1);

namespace Moox\Tree\Tests\Support;

use Filament\Resources\Pages\CreateRecord;

class TestCreateTreeNodePage extends CreateRecord
{
    protected static string $resource = TestForwardTreeResource::class;
}
