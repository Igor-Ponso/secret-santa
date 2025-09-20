<?php

use App\Models\User;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Carbon;

function fakeFingerprint(): array
{
    return [
        'device_id' => bin2hex(random_bytes(8)),
        'ua' => 'Mozilla/5.0 PHPUnit',
        'platform' => 'PHPUnit',
    ];
}

it('issues challenge and redirects to 2fa.challenge for new device', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);

    actingAsUser($user);

    $fp = fakeFingerprint();

    $response = test()->withUnencryptedCookies([
        'device_id' => $fp['device_id'],
    ])->get('/dashboard');

    $response->assertRedirect(route('2fa.challenge'));
    Mail::assertQueued(TwoFactorCodeMail::class); // or sent depending on config
    expect(DB::table('email_second_factor_challenges')->count())->toBe(1);
});

it('verifies valid code and trusts device', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);

    $fp = fakeFingerprint();

    // Trigger challenge
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])
        ->get('/dashboard');

    $challenge = EmailSecondFactorChallenge::first();
    expect($challenge)->not()->toBeNull();

    // We don't know plain code (stored hashed). Recreate deterministic code by issuing again with stub? Instead patch record to known hash.
    $known = 'ABCDEF';
    $challenge->update(['code_hash' => hash('sha256', $known)]);

    $verify = test()->withUnencryptedCookies(['device_id' => $fp['device_id']])
        ->post(route('2fa.verify'), [
            'code' => $known,
            'trust' => true,
        ]);

    $verify->assertRedirect();
    // Trusted device created
    expect(DB::table('user_trusted_devices')->count())->toBe(1);
});

it('resend regenerates challenge', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = fakeFingerprint();

    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $firstId = EmailSecondFactorChallenge::first()->id;

    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.resend'));

    $latest = EmailSecondFactorChallenge::latest('id')->first();
    expect($latest->id)->toBeGreaterThan($firstId);
});

it('revoking device forces new challenge', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = fakeFingerprint();

    // initial challenge and verify
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $challenge = EmailSecondFactorChallenge::first();
    $challenge->update(['code_hash' => hash('sha256', 'ABCDEF')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'ABCDEF', 'trust' => true]);
    $device = \App\Models\UserTrustedDevice::first();
    expect($device)->not()->toBeNull();

    // Revoke
    test()->delete(route('settings.security.devices.destroy', $device->id));

    // Should trigger challenge again
    $again = test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $again->assertRedirect(route('2fa.challenge'));
});
