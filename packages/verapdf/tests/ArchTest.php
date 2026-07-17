<?php

declare(strict_types=1);

arch()
    ->expect('Moox\VeraPdf')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);
