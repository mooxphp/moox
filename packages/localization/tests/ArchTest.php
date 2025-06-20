<?php

arch()
    ->expect('Moox\Localization')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Localization\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Localization');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
