<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function loginPayload(array $overrides = []): array
{
    return [
        'email' => 'jeanne.dupont@example.test',
        'password' => 'mot-de-passe-fiable',
        ...$overrides,
    ];
}

it('logs in a user with valid credentials and starts an authenticated session', function () {
    $user = User::factory()->create([
        'name' => 'Jeanne Dupont',
        'email' => 'jeanne.dupont@example.test',
        'password' => Hash::make('mot-de-passe-fiable'),
    ]);

    $response = $this->withHeader('Origin', 'http://localhost:4200')->postJson('/api/auth/login', loginPayload());

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.name', 'Jeanne Dupont')
        ->assertJsonPath('user.email', 'jeanne.dupont@example.test')
        ->assertJsonMissing(['password', 'remember_token']);
    $this->assertAuthenticatedAs($user, 'web');
});

it('returns the same generic error for an incorrect password and an unknown email', function () {
    User::factory()->create([
        'email' => 'jeanne.dupont@example.test',
        'password' => Hash::make('mot-de-passe-fiable'),
    ]);

    foreach ([loginPayload(['password' => 'mauvais-mot-de-passe']), loginPayload(['email' => 'inconnu@example.test'])] as $payload) {
        $this->withHeader('Origin', 'http://localhost:4200')->postJson('/api/auth/login', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Email ou mot de passe incorrect.');
        $this->assertGuest('web');
    }
});

it('validates required and malformed login fields', function () {
    $this->withHeader('Origin', 'http://localhost:4200')->postJson('/api/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);

    $this->withHeader('Origin', 'http://localhost:4200')->postJson('/api/auth/login', loginPayload(['email' => 'invalide']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('protects the existing user endpoint through sanctum', function () {
    $this->getJson('/api/user')->assertUnauthorized();

    $user = User::factory()->create();
    $this->actingAs($user, 'web')->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});
