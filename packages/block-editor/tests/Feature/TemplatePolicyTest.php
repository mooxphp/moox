<?php

use App\Models\User;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Policies\TemplatePolicy;

it('allows authenticated users for all template abilities', function (): void {
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
