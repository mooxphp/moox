<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Firewall')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Firewall\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Firewall');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
