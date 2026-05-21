<?php

declare(strict_types=1);

namespace Moox\LoginLink\Tests\Support;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TestUser extends Authenticatable implements FilamentUser
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
