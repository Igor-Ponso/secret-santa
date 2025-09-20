<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;

it('enables two-factor only after verification', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'disabled',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8))];
    $response = test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('settings.security.2fa.enable'), [
        'password' => 'Passw0rd!',
    ]);
    $response->assertRedirect(route('2fa.challenge'));
    $user->refresh();
    expect($user->two_factor_mode)->toBe('disabled'); // still deferred
    // Make challenge code known
    $challenge = \App\Models\EmailSecondFactorChallenge::latest('id')->first();
    $challenge->update(['code_hash' => hash('sha256', 'ABCDEF')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'ABCDEF']);
    $user->refresh();
    expect($user->two_factor_mode)->toBe('email_on_new_device');
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

it('disables two-factor only after verification', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8))];
    $response = test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->delete(route('settings.security.2fa.disable'), [
        'password' => 'Passw0rd!',
    ]);
    $response->assertRedirect(route('2fa.challenge'));
    $user->refresh();
    expect($user->two_factor_mode)->toBe('email_on_new_device');
    $challenge = \App\Models\EmailSecondFactorChallenge::latest('id')->first();
    $challenge->update(['code_hash' => hash('sha256', 'FEDCBA')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'FEDCBA']);
    $user->refresh();
    expect($user->two_factor_mode)->toBe('disabled');
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
