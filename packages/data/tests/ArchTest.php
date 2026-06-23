<?php

arch()
    ->expect('Moox\Data')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Data\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Data');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
