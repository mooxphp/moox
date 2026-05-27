<?php

arch()
    ->expect('Moox\KositValidator')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);
