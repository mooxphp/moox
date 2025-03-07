<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\ThemeLight')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\ThemeLight\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\ThemeLight');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
