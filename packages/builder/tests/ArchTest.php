<?php

arch()
    ->expect('Moox\Builder')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Builder\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Builder');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
