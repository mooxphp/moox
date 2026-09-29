<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Policies\TemplatePolicy;

uses(RefreshDatabase::class);

it('allows authenticated users when permission system is unavailable or permissions are not seeded', function (): void {
    $policy = new TemplatePolicy;
    $user = User::factory()->make();
    $template = new Template([
        'name' => 'Policy Template',
        'slug' => 'policy-template',
        'content' => [],
    ]);

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $template))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $template))->toBeTrue()
        ->and($policy->delete($user, $template))->toBeTrue();
});

it('denies users without matching template permissions when permissions exist', function (): void {
    if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
        $this->markTestSkipped('Spatie Permission is not installed.');
    }

    if (! \Illuminate\Support\Facades\Schema::hasTable('permissions')) {
        $this->markTestSkipped('permissions table is not available.');
    }

    \Spatie\Permission\Models\Permission::findOrCreate('ViewAny:Template');
    \Spatie\Permission\Models\Permission::findOrCreate('View:Template');
    \Spatie\Permission\Models\Permission::findOrCreate('Create:Template');
    \Spatie\Permission\Models\Permission::findOrCreate('Update:Template');
    \Spatie\Permission\Models\Permission::findOrCreate('Delete:Template');

    $policy = new TemplatePolicy;
    $user = User::factory()->create();
    $template = new Template([
        'name' => 'Policy Template',
        'slug' => 'policy-template',
        'content' => [],
    ]);

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, $template))->toBeFalse()
        ->and($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $template))->toBeFalse()
        ->and($policy->delete($user, $template))->toBeFalse();
});
