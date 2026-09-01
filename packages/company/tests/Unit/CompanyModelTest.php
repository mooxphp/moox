<?php

declare(strict_types=1);

use Moox\Company\Models\Company;

it('creates a company via factory', function (): void {
    $company = Company::factory()->create();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->exists)->toBeTrue()
        ->and($company->getKey())->toBeString()
        ->and($company->name)->not->toBeEmpty();
});

it('builds a display label from display name or name', function (): void {
    $company = Company::factory()->create([
        'name' => 'Legal Name GmbH',
        'display_name' => 'Muster Display',
    ]);

    expect($company->displayLabel())->toBe('Muster Display');
});

it('falls back to email and never uses the UUID', function (): void {
    $uuid = '019fd24c-72b9-73d1-bba3-67e8170b797f';

    $withEmail = Company::factory()->make([
        'display_name' => $uuid,
        'name' => $uuid,
        'legal_name' => null,
        'email' => 'firma@example.test',
    ]);

    $empty = Company::factory()->create([
        'display_name' => null,
        'name' => null,
        'legal_name' => null,
        'email' => null,
    ]);

    expect($withEmail->displayLabel())->toBe('firma@example.test')
        ->and($empty->displayLabel())->toBe('')
        ->and($empty->displayLabel())->not->toBe((string) $empty->getKey());
});

it('links a child company to a parent', function (): void {
    $parent = Company::factory()->create();
    $child = Company::factory()->withParent($parent)->create();

    expect($child->parent_id)->toBe($parent->getKey())
        ->and($parent->children()->count())->toBe(1);
});

it('normalizes default currency to uppercase', function (): void {
    $company = Company::factory()->create([
        'default_currency_code' => 'eur',
    ]);

    expect($company->fresh()->default_currency_code)->toBe('EUR');
});

it('allows saving when default currency is null', function (): void {
    $company = Company::factory()->create([
        'default_currency_code' => 'EUR',
    ]);

    $company->default_currency_code = null;
    $company->save();

    expect($company->fresh()->default_currency_code)->toBeNull();
});

it('clears parent when set to itself', function (): void {
    $company = Company::factory()->create();

    $company->parent_id = $company->getKey();
    $company->save();

    expect($company->fresh()->parent_id)->toBeNull();
});
