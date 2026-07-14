<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;
use Moox\Page\Resources\PageResource\Pages\ListPages;
use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    $user = new class extends User
    {
        protected $table = 'users';
    };

    $user->forceFill([
        'name' => 'Test User',
        'email' => 'page-test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ])->save();

    $this->actingAs($user);
});

it('can list pages', function (): void {
    $pages = collect([
        createPackageTestPage(layout: 'default', slug: 'list-page-1'),
        createPackageTestPage(layout: 'default', slug: 'list-page-2'),
        createPackageTestPage(layout: 'default', slug: 'list-page-3'),
    ]);

    Livewire::test(ListPages::class)
        ->assertOk()
        ->assertCanSeeTableRecords($pages);
});

it('can sort pages by title', function (): void {
    $pages = collect([
        createPackageTestPage(layout: 'default', slug: 'sort-page-1'),
        createPackageTestPage(layout: 'default', slug: 'sort-page-2'),
    ]);

    Livewire::test(ListPages::class, ['lang' => 'en'])
        ->assertCanSeeTableRecords($pages)
        ->sortTable('title')
        ->assertCanSeeTableRecords($pages);
});
