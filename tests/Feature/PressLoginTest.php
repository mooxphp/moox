<?php

test('healthy response', function () {

    $response = $this->get('/');

    $response->assertStatus(200);
});

it('redirects to login', function () {
    $response = $this->get('/press');

    $response->assertRedirect('press/login');
});

it('contains Sign in', function () {
    $response = $this->get('/press/login');

    $response->assertSee('Sign in');
});
