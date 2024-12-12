<?php

arch()
    ->expect('Moox\Skeleton')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Skeleton\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Skeleton');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
