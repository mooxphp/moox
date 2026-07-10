<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Static')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Static\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Static');
