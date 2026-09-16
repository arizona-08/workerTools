<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function registrationPayload(array $overrides = []): array
{
    return [
        'name' => 'Jeanne Dupont',
        'email' => 'jeanne.dupont@example.test',
        'password' => 'mot-de-passe-fiable',
        'password_confirmation' => 'mot-de-passe-fiable',
        ...$overrides,
    ];
}

it('registers a valid user without returning sensitive attributes', function () {
    $response = $this->postJson('/api/auth/register', registrationPayload());

    $response->assertCreated()
        ->assertJsonPath('user.name', 'Jeanne Dupont')
        ->assertJsonPath('user.email', 'jeanne.dupont@example.test')
        ->assertJsonMissing(['password', 'password_confirmation', 'remember_token']);

    $this->assertDatabaseHas('users', ['name' => 'Jeanne Dupont', 'email' => 'jeanne.dupont@example.test']);
    expect(Hash::check('mot-de-passe-fiable', User::query()->sole()->password))->toBeTrue();
});

it('rejects an invalid email without creating a user', function () {
    $this->postJson('/api/auth/register', registrationPayload(['email' => 'email-invalide']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(User::query()->count())->toBe(0);
});

it('rejects an email already used without creating a duplicate', function () {
    User::factory()->create(['email' => 'jeanne.dupont@example.test']);

    $this->postJson('/api/auth/register', registrationPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email')
        ->assertJsonPath('errors.email.0', 'Cette adresse email est déjà utilisée.');

    expect(User::query()->where('email', 'jeanne.dupont@example.test')->count())->toBe(1);
});

it('rejects a password shorter than eight characters', function () {
    $this->postJson('/api/auth/register', registrationPayload([
        'password' => 'court',
        'password_confirmation' => 'court',
    ]))->assertUnprocessable()->assertJsonValidationErrors('password');

    expect(User::query()->count())->toBe(0);
});

it('rejects a password confirmation that does not match', function () {
    $this->postJson('/api/auth/register', registrationPayload(['password_confirmation' => 'autre-mot-de-passe']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    expect(User::query()->count())->toBe(0);
});

it('rejects a payload missing required registration fields', function () {
    $this->postJson('/api/auth/register', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);

    expect(User::query()->count())->toBe(0);
});
