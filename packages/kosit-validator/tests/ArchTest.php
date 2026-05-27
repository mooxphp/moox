<?php

use Illuminate\Database\Eloquent\Model;

arch()
    ->expect('Moox\KositValidator')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);
