<?php

use App\Models\User;
use App\Models\UserTrustedDevice;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Support\Facades\Hash;

function issueAndTrust(User $user, array $fp): UserTrustedDevice
{
    // Trigger challenge
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->get('/dashboard');
    $challenge = EmailSecondFactorChallenge::first();
    $challenge->update(['code_hash' => hash('sha256', 'ABCDEF')]);
    test()->withUnencryptedCookies(['device_id' => $fp['device_id']])->post(route('2fa.verify'), [
        'code' => 'ABCDEF',
        'trust' => true,
    ]);
    return UserTrustedDevice::first();
}

it('renames a trusted device', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Passw0rd!'),
        'two_factor_mode' => 'email_on_new_device',
    ]);
    actingAsUser($user);
    $fp = ['device_id' => bin2hex(random_bytes(8)), 'ua' => 'Mozilla', 'platform' => 'Test'];
    $device = issueAndTrust($user, $fp);
    expect($device)->not()->toBeNull();
    test()->patch(route('settings.security.devices.rename', $device->id), ['name' => 'Notebook Casa'])
        ->assertRedirect();
    test()->assertDatabaseHas('user_trusted_devices', ['id' => $device->id, 'device_label' => 'Notebook Casa']);
});

it('prevents renaming device of another user', function () {
    $userA = User::factory()->create(['password' => Hash::make('Passw0rd!'), 'two_factor_mode' => 'email_on_new_device']);
    $userB = User::factory()->create(['password' => Hash::make('Passw0rd!'), 'two_factor_mode' => 'email_on_new_device']);
    actingAsUser($userA);
    $fp = ['device_id' => bin2hex(random_bytes(8)), 'ua' => 'Mozilla', 'platform' => 'Test'];
    $device = issueAndTrust($userA, $fp);
    actingAsUser($userB);
    test()->patch(route('settings.security.devices.rename', $device->id), ['name' => 'Hack'])->assertStatus(403);
});
