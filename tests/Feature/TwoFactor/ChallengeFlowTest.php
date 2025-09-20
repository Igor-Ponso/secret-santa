<?php

use App\Models\User;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Carbon;
require_once __DIR__ . '/../../Support/TestCredentials.php';

function fakeFingerprint(): array
{
    return [
        'device_id' => bin2hex(random_bytes(8)),
        'ua' => 'Mozilla/5.0 PHPUnit',
        'platform' => 'PHPUnit',
    ];
}

it('issues challenge and redirects to 2fa.challenge for new device (mail may be queued or sent)', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = fakeFingerprint();
    $response = test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $response->assertRedirect(route('2fa.challenge'));
    // In testing we expose plain code in session; ensure at least one challenge row exists
    expect(DB::table('email_second_factor_challenges')->count())->toBe(1);
    // If queue enabled assertQueued else assert conditionally send
    if (config('twofactor.use_queue')) {
        Mail::assertQueued(TwoFactorCodeMail::class);
    } else {
        Mail::assertSent(TwoFactorCodeMail::class);
    }
});

it('verifies valid code and trusts device', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
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
        'password' => Hash::make(TEST_PASSWORD),
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

it('revoking device defers until verification and then applies', function () {
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
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

    // Revoke initiates pending action and redirects to challenge but should NOT revoke yet
    test()->delete(route('settings.security.devices.destroy', $device->id))->assertRedirect(route('2fa.challenge'));
    $device->refresh();
    expect($device->revoked_at)->toBeNull();

    // Complete verification for pending revoke
    $latest = EmailSecondFactorChallenge::latest('id')->first();
    $latest->update(['code_hash' => hash('sha256', 'FEDCBA')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'FEDCBA']);
    $device->refresh();
    expect($device->revoked_at)->not()->toBeNull();
});
