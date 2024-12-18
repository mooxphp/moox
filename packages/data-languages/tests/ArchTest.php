<?php

arch()
    ->expect('Moox\DataLanguages')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\DataLanguages\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\DataLanguages');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
