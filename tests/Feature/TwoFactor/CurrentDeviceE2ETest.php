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

        // Override code hash so we can call the real verify route (ensuring session cleanup & passed_at set)
        $challenge->update(['code_hash' => hash('sha256', 'ABCDEF')]);
        // Retrieve device_id cookie from initial redirect response (middleware queued it)
        $deviceId = $resp->headers->getCookies()[0]->getValue() ?? null; // first cookie should be device_id
        $this->assertNotNull($deviceId, 'device_id cookie not set');
        $verify = $this->withUnencryptedCookies(['device_id' => $deviceId])->post(route('2fa.verify'), [
            'code' => 'ABCDEF',
            'trust' => true,
        ]);
        $verify->assertRedirect();
        // Grab trusted device token issued (from cookie jar on TestResponse)
        $trustedToken = collect($verify->headers->getCookies())
            ->firstWhere(fn($c) => $c->getName() === 'trusted_device_token')?->getValue();
        $this->assertNotNull($trustedToken, 'trusted_device_token cookie missing after verification');

        // Second access should now succeed and mark current device
        $resp2 = $this->withUnencryptedCookies([
            'device_id' => $deviceId,
            'trusted_device_token' => $trustedToken,
        ])->get(route('security.index'), [
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
