<?php

arch()
    ->expect('Moox\Slug')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Slug\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Slug');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
