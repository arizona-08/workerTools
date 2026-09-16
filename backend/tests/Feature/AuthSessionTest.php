<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only the current authenticated user contract', function () {
    $user = User::factory()->create([
        'name' => 'Jeanne Dupont',
        'email' => 'jeanne.dupont@example.test',
    ]);

    $this->actingAs($user, 'web')->getJson('/api/user')
        ->assertOk()
        ->assertExactJson([
            'id' => $user->id,
            'name' => 'Jeanne Dupont',
            'email' => 'jeanne.dupont@example.test',
        ]);
});

it('refuses the current user endpoint without an active session', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('logs out the authenticated user and invalidates access to protected routes', function () {
    $user = User::factory()->create([
        'email' => 'jeanne.dupont@example.test',
        'password' => 'mot-de-passe-fiable',
    ]);

    $loginResponse = $this->withHeader('Origin', 'http://localhost:4200')->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'mot-de-passe-fiable',
    ]);
    $sessionCookie = $loginResponse->getCookie((string) config('session.cookie'));
    expect($sessionCookie)->not->toBeNull();

    $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
        ->withHeader('Origin', 'http://localhost:4200')
        ->postJson('/api/auth/logout')
        ->assertNoContent();

    $this->assertGuest('web');
    $this->refreshApplication();

    $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
        ->withHeader('Origin', 'http://localhost:4200')
        ->getJson('/api/user')
        ->assertUnauthorized();
});
