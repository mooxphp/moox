<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Unit;

it('resolves a definition without loading Laravel or Filament', function (): void {
    $command = [PHP_BINARY, dirname(__DIR__).'/Support/independence.php'];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

    expect($process)->not->toBeFalse();

    if (! is_resource($process)) {
        return;
    }

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);

    expect([
        'exit' => $exit,
        'error' => $error,
        'output' => $output,
    ])->toBe([
        'exit' => 0,
        'error' => '',
        'output' => 'ok',
    ]);
});
