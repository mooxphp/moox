<?php

arch()
    ->expect('Moox\Page')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()->preset()->php();
arch()->preset()->security()->ignoring('md5');
