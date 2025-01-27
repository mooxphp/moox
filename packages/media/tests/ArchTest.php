<?php

arch()
    ->expect('Moox\Media')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Media\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Media');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
