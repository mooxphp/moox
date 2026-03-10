<?php

/** @phpstan-ignore method.notFound (Pest arch() fluent API) */
arch()
    ->expect('Moox\Media')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

/** @phpstan-ignore method.notFound (Pest arch() fluent API) */
arch()
    ->expect('Moox\Media\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn('Moox\Media');

/** @phpstan-ignore method.notFound (Pest arch() preset API) */
arch()->preset()->php();
/** @phpstan-ignore method.notFound (Pest arch() preset API) */
arch()->preset()->security()->ignoring('md5');
