<?php

use App\Models\User;
use App\Models\EmailSecondFactorChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

use function Pest\Laravel\{actingAs, get, post, assertDatabaseHas};

it('shows remaining seconds on challenge and reaches zero after expiry', function () {
    $user = User::factory()->create(['two_factor_mode' => 'email_on_new_device']);

    // Simulate middleware storing fingerprint and issuing challenge
    $fingerprint = app(\App\Services\TwoFactorService::class)->generateFingerprint('dev123', 'Test UA', 'Platform');
    actingAs($user)->withCookie('device_id', 'dev123');
    session(['2fa.fingerprint' => $fingerprint]);
    $challenge = EmailSecondFactorChallenge::create([
        'user_id' => $user->id,
        'fingerprint_hash' => $fingerprint,
        'code_hash' => hash('sha256', 'ABCDEF'),
        'attempts_remaining' => 5,
        'expires_at' => now()->addSeconds(5),
    ]);

    $resp = get(route('2fa.challenge'));
    $resp->assertStatus(200);
    $page = $resp->inertia();
    expect($page['props']['remaining_seconds'])->toBeGreaterThan(0);

    // Fast-forward past expiry
    Carbon::setTestNow(now()->addSeconds(10));
    $resp2 = get(route('2fa.challenge'));
    $page2 = $resp2->inertia();
    expect($page2['props']['remaining_seconds'])->toBe(0);
    Carbon::setTestNow();
});

it('allows cancel to set skip window preventing immediate re-prompt', function () {
    $user = User::factory()->create(['two_factor_mode' => 'email_on_new_device']);
    $fingerprint = app(\App\Services\TwoFactorService::class)->generateFingerprint('dev123', 'Test UA', 'Platform');
    actingAs($user)->withCookie('device_id', 'dev123');
    session(['2fa.fingerprint' => $fingerprint]);
    EmailSecondFactorChallenge::create([
        'user_id' => $user->id,
        'fingerprint_hash' => $fingerprint,
        'code_hash' => hash('sha256', 'ABCDEF'),
        'attempts_remaining' => 5,
        'expires_at' => now()->addMinutes(5),
    ]);

    // Cancel
    post(route('2fa.cancel'));
    expect(session()->has('2fa.fingerprint'))->toBeFalse();
    $skipUntil = session('2fa.skip_until');
    expect($skipUntil)->not->toBeNull();

    // Visiting security page should not redirect back to challenge (status 200)
    $resp = get(route('security.index'));
    $resp->assertStatus(200);

    // Advance time beyond skip window -> visiting a protected route should redirect to challenge
    $future = \Carbon\Carbon::parse($skipUntil)->addSecond();
    \Carbon\Carbon::setTestNow($future);
    $resp2 = get(route('security.index'));
    $resp2->assertRedirect(route('2fa.challenge'));
    \Carbon\Carbon::setTestNow();
});

it('verifies code and trusts device when trust checked', function () {
    $user = User::factory()->create(['two_factor_mode' => 'email_on_new_device']);
    $fingerprint = app(\App\Services\TwoFactorService::class)->generateFingerprint('dev123', 'UA', 'Plat');
    actingAs($user)->withCookie('device_id', 'dev123');
    session(['2fa.fingerprint' => $fingerprint]);
    $code = 'ABCDEF';
    EmailSecondFactorChallenge::where('user_id', $user->id)->delete();
    EmailSecondFactorChallenge::create([
        'user_id' => $user->id,
        'fingerprint_hash' => $fingerprint,
        'code_hash' => hash('sha256', $code),
        'attempts_remaining' => 5,
        'expires_at' => now()->addMinutes(5),
    ]);

    $resp = post(route('2fa.verify'), ['code' => $code, 'trust' => true]);
    $resp->assertRedirect();
    expect(session()->has('2fa.fingerprint'))->toBeFalse();
    // Device record created
    expect(\DB::table('user_trusted_devices')->count())->toBe(1);
});
