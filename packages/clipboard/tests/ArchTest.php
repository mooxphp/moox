<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\Clipboard')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Clipboard\Models')
    ->toBeClasses()
    ->toExtend(Model::class)
    ->toOnlyBeUsedIn('Moox\Clipboard');

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
