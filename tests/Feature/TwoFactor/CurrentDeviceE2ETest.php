<?php

namespace Tests\Feature\TwoFactor;

use App\Models\User;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentDeviceE2ETest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_trusts_device_and_marks_current_after_verification()
    {
        $user = User::factory()->create([
            'two_factor_mode' => 'email_on_new_device',
            'email_verified_at' => now(),
        ]);
        $ua = 'E2E Test UA';
        $platform = 'TestOS';
        $this->actingAs($user);

        // First access should redirect to challenge because not trusted yet
        $resp = $this->get(route('security.index'), [
            'User-Agent' => $ua,
            'Sec-CH-UA-Platform' => $platform,
        ]);
        $resp->assertRedirect(route('2fa.challenge'));

        // Ensure challenge row was created
        $fingerprint = session('2fa.fingerprint');
        $this->assertNotNull($fingerprint, 'Fingerprint not stored in session');
        $challenge = EmailSecondFactorChallenge::where('user_id', $user->id)
            ->where('fingerprint_hash', $fingerprint)
            ->latest('id')->first();
        $this->assertNotNull($challenge, 'Challenge not created');

        // We don't know real code (it was hashed); bypass by directly marking consumed and trusting device via service
        // Simulate verify route effect
        $challenge->update(['consumed_at' => now()]);
        $device = app(\App\Services\TwoFactorService::class)->trustDevice($user, $fingerprint, null, [
            'ip' => '127.0.0.1',
            'user_agent' => $ua,
        ]);
        // Queue cookies manually (Laravel TestResponse cookieQueue won't persist across manual operations), so set unencrypted
        $this->withUnencryptedCookies([
            'trusted_device_token' => $device->plain_token,
        ]);
        session()->forget('2fa.fingerprint');

        // Second access should now succeed and mark current device
        $resp2 = $this->get(route('security.index'), [
            'User-Agent' => $ua,
            'Sec-CH-UA-Platform' => $platform,
        ]);
        $resp2->assertOk();
        $page = $resp2->inertiaPage();
        $currentId = $page['props']['current_device_id'] ?? null;
        $this->assertNotNull($currentId, 'Current device id not set after trusting');
        $devices = collect($page['props']['devices'] ?? []);
        $found = $devices->firstWhere('id', $currentId);
        $this->assertNotNull($found, 'Trusted device not present in list');
        $this->assertTrue((bool) ($found['current'] ?? false), 'Trusted device not flagged current');
    }
}
