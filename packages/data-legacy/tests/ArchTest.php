<?php

arch()
    ->expect('Moox\DataLegacy')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\DataLegacy\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\DataLegacy');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
