<?php

declare(strict_types=1);

use Moox\MailTesting\Enums\FillMode;
use Moox\MailTesting\Support\PayloadResolver;
use Moox\MailTesting\Support\VariableBinding;
use Tests\TestCase;

uses(TestCase::class);

it('keeps demo variable values identical for every position', function (): void {
    $resolver = new PayloadResolver;
    $bindings = VariableBinding::collect([
        ['token' => '{invoiceNumber}', 'mode' => 'demo', 'value' => 'RE-2026-001'],
    ]);

    $first = $resolver->resolve(FillMode::Demo, 'fp', 1, $bindings);
    $second = $resolver->resolve(FillMode::Demo, 'fp', 2, $bindings);

    expect($first['invoiceNumber'])->toBe('RE-2026-001')
        ->and($second['invoiceNumber'])->toBe('RE-2026-001')
        ->and($first['firstName'])->toBe('Max')
        ->and($first['lastName'])->toBe('Mustermann')
        ->and($first['anrede'])->toBe('Sehr geehrter Herr Mustermann');
});

it('repeats random values for the same fingerprint and position', function (): void {
    $resolver = new PayloadResolver;
    $bindings = VariableBinding::collect([
        ['token' => 'invoiceNumber', 'mode' => 'random', 'value' => ''],
        ['token' => 'code', 'mode' => 'random', 'value' => 'AB-####'],
    ]);

    $one = $resolver->resolve(FillMode::Random, 'same-fp', 3, $bindings);
    $again = $resolver->resolve(FillMode::Random, 'same-fp', 3, $bindings);
    $otherPosition = $resolver->resolve(FillMode::Random, 'same-fp', 4, $bindings);

    expect($one['invoiceNumber'])->toBe('RE-2026-00003')
        ->and($again['invoiceNumber'])->toBe('RE-2026-00003')
        ->and($otherPosition['invoiceNumber'])->toBe('RE-2026-00004')
        ->and($one['code'])->toBe($again['code'])
        ->and($one['code'])->not->toBe($otherPosition['code'])
        ->and($one['firstName'])->toBe($again['firstName'])
        ->and($one['displayName'])->toBe($again['displayName']);
});

it('lets bindings override contact keys', function (): void {
    $resolver = new PayloadResolver;
    $bindings = VariableBinding::collect([
        ['token' => 'firstName', 'mode' => 'demo', 'value' => 'Anna'],
        ['token' => '', 'mode' => 'demo', 'value' => 'ignored'],
    ]);

    $payload = $resolver->resolve(FillMode::Demo, 'fp', 1, $bindings);

    expect($payload['firstName'])->toBe('Anna')
        ->and($payload['lastName'])->toBe('Mustermann')
        ->and($payload)->not->toHaveKey('');
});
