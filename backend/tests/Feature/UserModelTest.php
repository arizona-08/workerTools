<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('persists a valid user with the required fields', function () {
    $user = User::factory()->create([
        'name' => 'Jeanne Dupont',
        'email' => 'jeanne.dupont@example.test',
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Jeanne Dupont',
        'email' => 'jeanne.dupont@example.test',
    ]);
    expect($user->created_at)->not->toBeNull()
        ->and($user->updated_at)->not->toBeNull();
});

it('enforces the unique email constraint at database level', function () {
    User::factory()->create(['email' => 'unique@example.test']);

    expect(fn () => User::factory()->create(['email' => 'unique@example.test']))
        ->toThrow(QueryException::class);
});

it('hashes a password before persisting it', function () {
    $plainPassword = 'un-mot-de-passe-de-test';
    $user = User::query()->create([
        'name' => 'Jean Martin',
        'email' => 'jean.martin@example.test',
        'password' => $plainPassword,
    ]);

    $storedPassword = $user->fresh()->password;

    expect($storedPassword)->not->toBe($plainPassword)
        ->and(Hash::check($plainPassword, $storedPassword))->toBeTrue();
});

it('hides password and remember token from serialized users', function () {
    $user = User::factory()->create(['remember_token' => 'secret-token']);
    $serialized = $user->toArray();

    expect($serialized)->toHaveKeys(['id', 'name', 'email', 'created_at', 'updated_at'])
        ->not->toHaveKeys(['password', 'remember_token']);
});

it('keeps the standard users migration schema available on a clean test database', function () {
    expect(Schema::hasColumns('users', [
        'id',
        'name',
        'email',
        'password',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
