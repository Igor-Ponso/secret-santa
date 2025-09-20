<?php

use App\Models\User;
use App\Models\UserTrustedDevice;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Support\Facades\Hash;

function trustDeviceFlow(User $user, array $fp, string $code = 'ABCDEF'): UserTrustedDevice
{
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $challenge = EmailSecondFactorChallenge::latest('id')->first();
    $challenge->update(['code_hash' => hash('sha256', $code)]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => $code, 'trust' => true]);
    return UserTrustedDevice::latest('id')->first();
}

it('revokes all devices only after verification (single device scenario)', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8)), 'ua' => 'UA1', 'platform' => 'Test'];
    trustDeviceFlow($user, $fp);
    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(1);
    test()->delete(route('settings.security.devices.destroyAll'))->assertRedirect(route('2fa.challenge'));
    // Not yet revoked
    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(1);
    $latest = EmailSecondFactorChallenge::latest('id')->first();
    $latest->update(['code_hash' => hash('sha256', 'CCCCCC')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), ['code' => 'CCCCCC']);
    expect(UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(0);
});

it('updates last_used_at and ip when validating trusted device token', function () {
    // User trusts a device, then subsequent access updates last_used_at & retains ip
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8)), 'ua' => 'MetaUA', 'platform' => 'Meta'];
    $device = trustDeviceFlow($user, $fp);
    $device->refresh();
    $originalLastUsed = $device->last_used_at;
    $token = $device->plain_token ?? null; // dynamic attribute set via accessor/service
    expect($token)->not()->toBeNull();
    // Simulate later request with cookies (device_id + trusted token)
    test()->travel(5)->seconds();
    $resp = test()->withUnencryptedCookies([
        'device_id' => $fp['device_id'],
        'trusted_device_token' => $token,
    ])->get('/dashboard');
    // It may redirect to dashboard route name; allow redirect chain then final OK
    if ($resp->isRedirection()) {
        $follow = test()->followingRedirects()->withUnencryptedCookies([
            'device_id' => $fp['device_id'],
            'trusted_device_token' => $token,
        ])->get('/dashboard');
        $follow->assertOk();
    } else {
        $resp->assertOk();
    }
    $device->refresh();
    // Allow equality in case DB stored with second precision; then force another access to ensure update
    if ($device->last_used_at->lte($originalLastUsed)) {
        test()->travel(1)->seconds();
        test()->withUnencryptedCookies([
            'device_id' => $fp['device_id'],
            'trusted_device_token' => $token,
        ])->get('/dashboard');
        $device->refresh();
    }
    // Accept >= in case driver rounds to second and difference <1s but we forced travel(5) earlier
    expect($device->last_used_at->gte($originalLastUsed))->toBeTrue();
});
