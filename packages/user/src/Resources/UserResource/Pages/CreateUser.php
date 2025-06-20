<?php

namespace Moox\User\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\User\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
