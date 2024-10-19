<?php

arch()
    ->expect('Moox\Tag')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Tag\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Tag');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
