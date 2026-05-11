<?php

arch()
    ->expect('Moox\Scopes')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
