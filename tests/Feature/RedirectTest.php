<?php

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Optionally, you can refresh the application routes
    Artisan::call('route:cache');
});

test('Lock WP config is true', function () {
    $lockwp = config('press.lock_wordpress');
    expect($lockwp)->toBeTrue();
});

it('will lock Wp', function () {
    $response = $this->get('/wp');
    $response->assertRedirect('/press/login');
});
