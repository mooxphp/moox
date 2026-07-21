<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Session;
use Moox\Company\Models\Company;
use Moox\Company\Resources\Company\Pages\CreateCompany;
use Moox\Company\Resources\Company\Pages\EditCompany;
use Moox\Company\Resources\Company\Pages\ListCompanies;
use Moox\DevTools\Models\TestUser;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Session::start();
    $this->actingAs(TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]));
});

it('can render the company list page', function (): void {
    livewire(ListCompanies::class)->assertSuccessful();
});

it('can render table columns for companies', function (): void {
    livewire(ListCompanies::class)
        ->assertTableColumnExists('display_name')
        ->assertTableColumnExists('status');
});

it('create form contains expected company fields', function (): void {
    livewire(CreateCompany::class)
        ->assertFormExists('form')
        ->assertFormFieldExists('name', 'form')
        ->assertFormFieldExists('status', 'form')
        ->assertFormFieldExists('default_currency_code', 'form');
});

it('can create a company via filament', function (): void {
    livewire(CreateCompany::class)
        ->fillForm([
            'name' => 'Filament GmbH',
            'display_name' => 'Filament GmbH',
            'status' => 'draft',
            'default_currency_code' => 'EUR',
        ], 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Company::query()->where('name', 'Filament GmbH')->exists())->toBeTrue();
});

it('can edit an existing company via filament', function (): void {
    $company = Company::factory()->create([
        'name' => 'Filament GmbH',
        'display_name' => 'Filament GmbH',
        'status' => 'draft',
        'default_currency_code' => 'EUR',
    ]);

    livewire(EditCompany::class, ['record' => $company->getKey()])
        ->fillForm([
            'display_name' => 'New Name',
        ], 'form')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($company->fresh()->display_name)->toBe('New Name');
});
