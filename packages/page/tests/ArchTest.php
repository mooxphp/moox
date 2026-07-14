<?php

arch()
    ->expect('Moox\Page')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Moox\Page')
    ->not->toUse([
        'eval',
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'create_function',
        'unserialize',
        'extract',
        'dl',
        'assert',
        'sha1',
        'uniqid',
        'rand',
        'mt_rand',
        'tempnam',
        'str_shuffle',
        'shuffle',
        'array_rand',
    ])
    ->ignoring('Moox\Page\Support\PageResponseCache');

arch()
    ->expect('Moox\Page')
    ->not->toUse('md5')
    ->ignoring('Moox\Page\Support\PageResponseCache');
