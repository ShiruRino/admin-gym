<?php

use App\Models\User;

test('home redirects guest to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

test('home redirects authenticated user to check-in terminal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('check-in.index'));
});
