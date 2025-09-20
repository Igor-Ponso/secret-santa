<?php

use App\Models\User;
use App\Models\UserTrustedDevice;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Support\Facades\Hash;
require_once __DIR__ . '/../../Support/TestCredentials.php';

/**
 * Helper: bootstrap a challenge (first access) and override code to known value.
 */
function startChallenge(User $user, array $fp, string $overrideCode = 'ABCDEF'): string
{
    // First request triggers challenge issue
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $challenge = EmailSecondFactorChallenge::latest('id')->first();
    expect($challenge)->not()->toBeNull();
    $challenge->update(['code_hash' => hash('sha256', $overrideCode)]);
    return $overrideCode;
}

it('does not enable 2FA before verification and enables after code', function () {
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
        'two_factor_mode' => 'disabled',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8))];

    // Request enabling -> should redirect to challenge, but NOT update DB yet
    $resp = test()->post(route('settings.security.2fa.enable'), ['password' => TEST_PASSWORD]);
    $resp->assertRedirect();
    $user->refresh();
    expect($user->two_factor_mode)->toBe('disabled');

    // Challenge exists now; force known code and verify
    $code = startChallenge($user, $fp, 'ZZZZZZ'); // ensure we have a fresh challenge context
    // Overwrite again after enabling route may have already created one
    $challenge = EmailSecondFactorChallenge::latest('id')->first();
    $challenge->update(['code_hash' => hash('sha256', $code)]);

    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => $code]);
    $user->refresh();
    expect($user->two_factor_mode)->toBe('email_on_new_device');
});

it('canceling pending enable leaves mode unchanged', function () {
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
        'two_factor_mode' => 'disabled',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8))];
    test()->post(route('settings.security.2fa.enable'), ['password' => TEST_PASSWORD])->assertRedirect();
    $user->refresh();
    expect($user->two_factor_mode)->toBe('disabled');
    // Cancel
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.cancel'))->assertRedirect();
    $user->refresh();
    expect($user->two_factor_mode)->toBe('disabled');
});

it('revoke all executes only after verify', function () {
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    // Trust two devices first
    $fp1 = ['device_id' => bin2hex(random_bytes(8))];
    $fp2 = ['device_id' => bin2hex(random_bytes(8))];
    startChallenge($user, $fp1, 'AAAAAA');
    $c1 = EmailSecondFactorChallenge::latest('id')->first();
    $c1->update(['code_hash' => hash('sha256', 'AAAAAA')]);
    test()->withUnencryptedCookies(['device_id' => $fp1['device_id']])->post(route('2fa.verify'), ['code' => 'AAAAAA', 'trust' => true]);

    startChallenge($user, $fp2, 'BBBBBB');
    $c2 = EmailSecondFactorChallenge::latest('id')->first();
    $c2->update(['code_hash' => hash('sha256', 'BBBBBB')]);
    test()->withUnencryptedCookies(['device_id' => $fp2['device_id']])->post(route('2fa.verify'), ['code' => 'BBBBBB', 'trust' => true]);

    $initialCount = UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count();
    expect($initialCount)->toBeGreaterThanOrEqual(1);

    // Initiate revoke all
    test()->delete(route('settings.security.devices.destroyAll'))->assertRedirect();
    // Should still be 2 (not yet revoked)
    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe($initialCount);

    // Latest challenge -> make code known
    $latest = EmailSecondFactorChallenge::latest('id')->first();
    $latest->update(['code_hash' => hash('sha256', 'CCCCCC')]);
    test()->withUnencryptedCookies(['device_id' => $fp2['device_id']])->post(route('2fa.verify'), ['code' => 'CCCCCC']);

    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(0);
});

it('canceling revoke all leaves devices intact', function () {
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp1 = ['device_id' => bin2hex(random_bytes(8))];
    startChallenge($user, $fp1, 'AAAAAB');
    $ch = EmailSecondFactorChallenge::latest('id')->first();
    $ch->update(['code_hash' => hash('sha256', 'AAAAAB')]);
    test()->withUnencryptedCookies(['device_id' => $fp1['device_id']])->post(route('2fa.verify'), ['code' => 'AAAAAB', 'trust' => true]);

    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(1);

    test()->delete(route('settings.security.devices.destroyAll'))->assertRedirect();
    // Cancel
    test()->withUnencryptedCookies(['device_id' => $fp1['device_id']])->post(route('2fa.cancel'))->assertRedirect();
    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(1);
});

it('rename device deferred until verify', function () {
    $user = User::factory()->create([
        'password' => Hash::make(TEST_PASSWORD),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8))];
    startChallenge($user, $fp, 'DDDDEE');
    $ch = EmailSecondFactorChallenge::latest('id')->first();
    $ch->update(['code_hash' => hash('sha256', 'DDDDEE')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'DDDDEE', 'trust' => true]);

    $device = UserTrustedDevice::where('user_id', $user->id)->first();
    expect($device->device_label)->toBeNull();

    test()->patch(route('settings.security.devices.rename', $device->id), ['name' => 'Notebook'])->assertRedirect();
    $device->refresh();
    expect($device->device_label)->toBeNull(); // still unchanged pre-verify

    $latest = EmailSecondFactorChallenge::latest('id')->first();
    $latest->update(['code_hash' => hash('sha256', 'EEEFFF')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'EEEFFF']);
    $device->refresh();
    expect($device->device_label)->toBe('Notebook');
});
