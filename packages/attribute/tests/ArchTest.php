<?php

arch()
    ->expect('Moox\Attribute')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
