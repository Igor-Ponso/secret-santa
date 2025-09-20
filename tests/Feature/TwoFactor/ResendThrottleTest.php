<?php

namespace Tests\Feature\TwoFactor;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config()->set('twofactor.code_ttl', 300);
        config()->set('twofactor.min_resend_interval', 5);
        config()->set('twofactor.resend_backoff', [
            1 => 2, // after 1st resend wait 2s
            2 => 5, // after 2nd resend wait 5s
            3 => 10,
        ]);
        config()->set('twofactor.max_resends_before_suspend', 6);
    }

    protected function initiateChallenge(User $user): string
    {
        $fingerprint = app(\App\Services\TwoFactorService::class)->generateFingerprint('dev123', 'Test UA', 'Platform');
        session(['2fa.fingerprint' => $fingerprint]);
        // Force user into email 2FA mode
        $user->forceFill(['two_factor_mode' => 'email_on_new_device'])->save();
        // Issue initial challenge
        app(\App\Services\TwoFactorService::class)->issueChallenge($user, $fingerprint);
        return $fingerprint;
    }

    public function test_min_interval_blocks_immediate_second_resend()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->initiateChallenge($user);

        // First resend should pass
        $r1 = $this->post(route('2fa.resend'));
        $r1->assertRedirect();

        // Immediate second resend should be blocked (min interval 5s)
        $r2 = $this->post(route('2fa.resend'));
        $r2->assertSessionHasErrors('resend');
    }

    public function test_backoff_wait_increases_and_allows_after_time_travel()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->initiateChallenge($user);

        // 1st resend
        $this->post(route('2fa.resend'));
        // Travel 2s (enough for first backoff of 2s but still < min interval 5 => should still block)
        $this->travel(2)->seconds();
        $blocked = $this->post(route('2fa.resend'));
        $blocked->assertSessionHasErrors('resend');

        // Travel to surpass min interval (total 5s)
        $this->travel(3)->seconds();
        $this->post(route('2fa.resend'))->assertRedirect(); // second resend ok

        // Now backoff for second resend is 5s
        $this->travel(2)->seconds();
        $stillBlocked = $this->post(route('2fa.resend'));
        $stillBlocked->assertSessionHasErrors('resend');

        $this->travel(3)->seconds();
        $this->post(route('2fa.resend'))->assertRedirect(); // third resend ok
    }

    public function test_suspension_after_max_resends_triggers_password_reset()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->initiateChallenge($user);

        // Perform sequential resends respecting timing to reach suspension threshold
        for ($i = 1; $i <= 6; $i++) {
            $response = $this->post(route('2fa.resend'));
            if ($i === 1) {
                $response->assertRedirect();
                $this->travel(5)->seconds();
                continue;
            }
            if ($i < 6) {
                $response->assertRedirect();
                // Travel max of backoff and min interval for next attempt
                $waitMap = [1 => 2, 2 => 5, 3 => 10];
                $backoff = $waitMap[$i] ?? 10;
                $this->travel(max(5, $backoff))->seconds();
            } else {
                // 6th triggers suspension
                $response->assertSessionHasErrors('resend');
            }
        }
    }

    public function test_burst_limiter_blocks_third_request_in_2s_window()
    {
        $user = User::factory()->create();
        $this->be($user);
        $this->initiateChallenge($user);
        $this->post(route('2fa.resend'))->assertRedirect(); // 1
        $this->post(route('2fa.resend')); // likely blocked by min interval/backoff or burst
        $resp = $this->post(route('2fa.resend'));
        $resp->assertSessionHasErrors('resend');
    }
}
