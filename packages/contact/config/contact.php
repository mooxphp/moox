<?php

use Moox\Customer\Models\Customer;
use Moox\Customer\Resources\CustomerResource;
use Moox\Department\Models\Department;
use Moox\Department\Resources\DepartmentResource;
use Moox\Person\Models\Person;
use Moox\Person\Resources\PersonResource;
use Moox\Supplier\Models\Supplier;
use Moox\Supplier\Resources\SupplierResource;

$relations = [
    'language' => [
        'kind' => 'belongs_to',
        'presentation' => 'hidden',
        'model' => 'Moox\\Data\\Models\\StaticLanguage',
        'foreign_key' => 'language_id',
        'title_attribute' => 'common_name',
    ],
    'country' => [
        'kind' => 'belongs_to',
        'presentation' => 'hidden',
        'model' => 'Moox\\Data\\Models\\StaticCountry',
        'foreign_key' => 'country_id',
        'title_attribute' => 'common_name',
    ],
    'organizationType' => [
        'kind' => 'belongs_to',
        'presentation' => 'hidden',
        'model' => 'Moox\\Organization\\Models\\OrganizationType',
        'foreign_key' => 'organization_type_id',
        'title_attribute' => 'title',
    ],
    'legalForm' => [
        'kind' => 'belongs_to',
        'presentation' => 'hidden',
        'model' => 'Moox\\Organization\\Models\\LegalForm',
        'foreign_key' => 'legal_form_id',
        'title_attribute' => 'title',
    ],
    'identifiers' => [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//contact::contact.contact_identifiers',
        'relationship' => 'identifiers',
        'inverse_relationship' => 'contact',
        'model' => 'Moox\\Contact\\Models\\ContactIdentifier',
        'related_resource' => 'Moox\\Contact\\Resources\\ContactIdentifierResource',
        'foreign_key' => 'contact_id',
        'translation_prefix' => 'contact::fields',
        'display_columns' => ['value'],
        'record_select_search_columns' => ['value'],
        'create_prefill' => [
            'contact_id' => 'owner.id',
        ],
    ],
    'relationships' => [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//contact::contact.contact_relationships',
        'relationship' => 'relationships',
        'inverse_relationship' => 'contact',
        'model' => 'Moox\\Contact\\Models\\ContactRelationship',
        'related_resource' => 'Moox\\Contact\\Resources\\ContactRelationshipResource',
        'foreign_key' => 'contact_id',
        'translation_prefix' => 'contact::fields',
        'display_columns' => ['note', 'external_reference', 'related_contact_id'],
        'record_select_search_columns' => ['note', 'external_reference'],
        'create_prefill' => [
            'contact_id' => 'owner.id',
        ],
        'actions' => [
            'header' => ['associate', 'create'],
            'record' => ['view', 'edit'],
        ],
    ],
    'incomingRelationships' => [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//contact::contact.incoming_contact_relationships',
        'relationship' => 'incomingRelationships',
        'inverse_relationship' => 'relatedContact',
        'model' => 'Moox\\Contact\\Models\\ContactRelationship',
        'related_resource' => 'Moox\\Contact\\Resources\\ContactRelationshipResource',
        'foreign_key' => 'related_contact_id',
        'translation_prefix' => 'contact::fields',
        'display_columns' => ['note', 'external_reference', 'contact_id'],
        'record_select_search_columns' => ['note', 'external_reference'],
        'create_prefill' => [
            'related_contact_id' => 'owner.id',
        ],
        'actions' => [
            'header' => ['associate', 'create'],
            'record' => ['view', 'edit'],
        ],
    ],
];

if (class_exists(Person::class) && class_exists(PersonResource::class)) {
    $relations['persons'] = [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//person::person.persons',
        'relationship' => 'persons',
        'inverse_relationship' => 'contact',
        'model' => Person::class,
        'related_resource' => PersonResource::class,
        'foreign_key' => 'contact_id',
        'translation_prefix' => 'person::fields',
        'display_columns' => ['first_name', 'last_name'],
        'record_select_search_columns' => ['first_name', 'last_name'],
        'create_prefill' => [
            'contact_id' => 'owner.id',
        ],
        'actions' => [
            'header' => ['associate', 'create'],
            'record' => ['view', 'edit'],
        ],
    ];
}

if (class_exists(Department::class) && class_exists(DepartmentResource::class)) {
    $relations['departments'] = [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//department::department.departments',
        'relationship' => 'departments',
        'inverse_relationship' => 'contact',
        'model' => Department::class,
        'related_resource' => DepartmentResource::class,
        'foreign_key' => 'contact_id',
        'translation_prefix' => 'department::fields',
        'display_columns' => ['title'],
        'record_select_search_columns' => ['title'],
        'create_prefill' => [
            'contact_id' => 'owner.id',
        ],
        'actions' => [
            'header' => ['associate', 'create'],
            'record' => ['view', 'edit'],
        ],
    ];
}

if (class_exists(Customer::class) && class_exists(CustomerResource::class)) {
    $relations['customers'] = [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//customer::customer.customers',
        'relationship' => 'customers',
        'inverse_relationship' => 'contact',
        'model' => Customer::class,
        'related_resource' => CustomerResource::class,
        'foreign_key' => 'contact_id',
        'translation_prefix' => 'customer::fields',
        'display_columns' => ['customer_number', 'customer_name'],
        'record_select_search_columns' => ['customer_number', 'customer_name'],
        'create_prefill' => [
            'contact_id' => 'owner.id',
        ],
        'associate' => [
            'multiple' => false,
        ],
        'actions' => [
            'header' => ['associate', 'create'],
            'record' => ['view', 'edit'],
        ],
    ];
}

if (class_exists(Supplier::class) && class_exists(SupplierResource::class)) {
    $relations['suppliers'] = [
        'kind' => 'has_many',
        'presentation' => 'tab',
        'label' => 'trans//supplier::supplier.suppliers',
        'relationship' => 'suppliers',
        'inverse_relationship' => 'contact',
        'model' => Supplier::class,
        'related_resource' => SupplierResource::class,
        'foreign_key' => 'contact_id',
        'translation_prefix' => 'supplier::fields',
        'display_columns' => ['supplier_number', 'supplier_name'],
        'record_select_search_columns' => ['supplier_number', 'supplier_name'],
        'create_prefill' => [
            'contact_id' => 'owner.id',
        ],
        'associate' => [
            'multiple' => false,
        ],
        'actions' => [
            'header' => ['associate', 'create'],
            'record' => ['view', 'edit'],
        ],
    ];
}

return [
    'relations' => $relations,
];
