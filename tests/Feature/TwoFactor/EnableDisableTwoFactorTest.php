<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;

it('enables two-factor with correct password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'disabled',
    ]);
    actingAsUser($user);
    $response = test()->post(route('settings.security.2fa.enable'), [
        'password' => 'Passw0rd!',
    ]);
    $response->assertRedirect();
    test()->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_mode' => 'email_on_new_device'
    ]);
});

it('rejects enable with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'disabled',
    ]);

    actingAsUser($user);
    $response = test()->post(route('settings.security.2fa.enable'), [
        'password' => 'wrong',
    ]);
    $response->assertSessionHasErrors('password');
    test()->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_mode' => 'disabled'
    ]);
});

it('disables two-factor with correct password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);

    actingAsUser($user);
    $response = test()->delete(route('settings.security.2fa.disable'), [
        'password' => 'Passw0rd!',
    ]);
    $response->assertRedirect();
    test()->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_mode' => 'disabled'
    ]);
});

it('rejects disable with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);

    actingAsUser($user);
    $response = test()->delete(route('settings.security.2fa.disable'), [
        'password' => 'WRONG',
    ]);
    $response->assertSessionHasErrors('password');
    test()->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_mode' => 'email_on_new_device'
    ]);
});
