<?php

arch()
    ->expect('Moox\Draft')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
