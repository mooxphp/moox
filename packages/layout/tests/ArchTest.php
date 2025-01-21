<?php

arch()
    ->expect('Moox\Layout')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Layout\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Layout');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
