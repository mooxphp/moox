<?php

declare(strict_types=1);

test('auto-install stub selects CLI pack and excludes GUI for 1.30+', function (): void {
    $stub = dirname(__DIR__, 2).'/resources/install/auto-install.xml.stub';

    expect(is_file($stub))->toBeTrue();

    $contents = (string) file_get_contents($stub);

    expect($contents)
        ->toContain('name="veraPDF CLI" selected="true"')
        ->toContain('name="veraPDF GUI" selected="false"')
        ->not->toContain('Mac and *nix Scripts')
        ->not->toContain('Validation model');
});
