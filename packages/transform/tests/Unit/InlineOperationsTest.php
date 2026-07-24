<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\Customer\Models\CustomerAssignment;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 1).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it applies case integer and truthy inline operations', function (): void {
    createTestTables();

    $definition = createDefinition([
        'destination_match' => [
            'price_label' => 'legacy.flag|truthy',
        ],
        'field_map' => [
            'title' => 'legacy.code|upper',
            'stock' => 'legacy.amount|int',
            'price_label' => 'legacy.flag|truthy',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'code' => 'abc',
                'amount' => '12',
                'flag' => 'yes',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    $saved = TransformDummyModel::query()->first();

    expect($record->status)->toBe('processed')
        ->and($saved?->title)->toBe('ABC')
        ->and($saved?->stock)->toBe(12)
        ->and((bool) $saved?->price_label)->toBeTrue();
});

test('it applies coalesce and any_truthy inline operations', function (): void {
    createTestTables();

    $definition = createDefinition([
        'destination_match' => [
            'price_label' => 'any_truthy:legacy.deleted,legacy.inactive|status_from_deleted',
        ],
        'field_map' => [
            'title' => 'coalesce:legacy.primary,legacy.fallback',
            'stock' => 'legacy.amount|int',
            'price_label' => 'any_truthy:legacy.deleted,legacy.inactive|status_from_deleted',
        ],
        'validation_rules' => [
            'stock' => ['nullable', 'integer'],
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'primary' => '',
                'fallback' => 'Fallback Title',
                'deleted' => '0',
                'inactive' => '1',
                'amount' => '3',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    $saved = TransformDummyModel::query()->first();

    expect($record->status)->toBe('processed')
        ->and($saved?->title)->toBe('Fallback Title')
        ->and($saved?->stock)->toBe(3)
        ->and($saved?->price_label)->toBe('inactive');
});

test('it transforms customer definition fields', function (): void {
    createTestTables();

    if (! Schema::hasTable('customers')) {
        test()->markTestSkipped('customers table is not available.');
    }

    Customer::query()->where('external_reference', 'K-100')->forceDelete();

    $definition = createDefinition([
        'name' => 'test-customer-definition',
        'destination_model' => Customer::class,
        'destination_match' => [
            'external_reference' => 'legacy.ID_Kunde',
        ],
        'source_references' => [],
        'field_map' => [
            'external_reference' => 'legacy.ID_Kunde',
            'customer_number' => 'coalesce:legacy.Debitorennummer,legacy.ID_Kunde',
            'search_terms' => 'legacy.Firma',
            'note' => 'legacy.Notiz',
            'price_type' => 'legacy.ID_Preisart|map:1=standard,2=dealer,*=null',
            'customer_group' => 'legacy.ID_Kundengruppe',
            'is_active' => 'any_truthy:legacy.is_inactive,legacy.gelöscht|not_truthy',
            'status' => 'any_truthy:legacy.is_inactive,legacy.gelöscht|status_from_deleted',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'legacy' => [
                'ID_Kunde' => 'K-100',
                'Debitorennummer' => 'DEB-100',
                'Firma' => 'Acme GmbH',
                'Notiz' => 'VIP',
                'ID_Preisart' => '2',
                'ID_Kundengruppe' => 'A',
                'is_inactive' => '0',
                'gelöscht' => '0',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    $customer = Customer::query()->where('external_reference', 'K-100')->first();

    expect($record->status)->toBe('processed')
        ->and($customer?->customer_number)->toBe('DEB-100')
        ->and($customer?->search_terms)->toBe('Acme GmbH')
        ->and($customer?->price_type)->toBe('dealer')
        ->and($customer?->is_active)->toBeTrue()
        ->and($customer?->status)->toBe('active');
});

test('it transforms customer assignment with runtime source references', function (): void {
    createTestTables();

    if (! Schema::hasTable('companies') || ! Schema::hasTable('customers') || ! Schema::hasTable('customer_assignments')) {
        test()->markTestSkipped('company/customer assignment tables are not available.');
    }

    CustomerAssignment::query()->delete();

    $companyId = (string) Str::uuid();
    $customerId = (string) Str::uuid();

    DB::table('companies')->updateOrInsert(
        ['external_reference' => 'F-100'],
        [
            'id' => $companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    );

    DB::table('customers')->updateOrInsert(
        ['external_reference' => 'K-100'],
        [
            'id' => $customerId,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    );

    $definition = createDefinition([
        'name' => 'test-customer-assignment-definition',
        'destination_model' => CustomerAssignment::class,
        'destination_match' => [
            'assignable_type' => 'meta.assignable_type',
            'assignable_id' => 'company.id',
            'customer_id' => 'customer.id',
        ],
        'source_references' => [],
        'field_map' => [
            'assignable_type' => 'meta.assignable_type',
            'assignable_id' => 'company.id',
            'customer_id' => 'customer.id',
            'is_primary' => 'legacy.is_company_default_customer|truthy',
            'role' => 'meta.role',
        ],
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'meta' => [
                'assignable_type' => Company::class,
                'role' => 'general',
            ],
            'legacy' => [
                'is_company_default_customer' => '1',
            ],
        ],
        'source_references' => [
            [
                'source_type' => 'db_table',
                'table' => 'companies',
                'key_column' => 'external_reference',
                'row_key' => 'F-100',
                'alias' => 'company',
            ],
            [
                'source_type' => 'db_table',
                'table' => 'customers',
                'key_column' => 'external_reference',
                'row_key' => 'K-100',
                'alias' => 'customer',
            ],
        ],
    ]);

    makeRunner()->run($record);
    $record->refresh();

    expect($record->status)->toBe('processed')
        ->and(CustomerAssignment::query()->count())->toBe(1)
        ->and(CustomerAssignment::query()->first()?->is_primary)->toBeTrue()
        ->and(CustomerAssignment::query()->first()?->role)->toBe('general');
});
