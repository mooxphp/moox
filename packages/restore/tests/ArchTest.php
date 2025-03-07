<?php

arch()
    ->expect('Moox\Restore')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Restore\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Restore');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
