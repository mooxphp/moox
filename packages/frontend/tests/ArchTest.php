<?php

arch()
    ->expect('Moox\Frontend')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Frontend\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Frontend');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
