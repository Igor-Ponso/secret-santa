<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Group;
use App\Policies\GroupPolicy;
use App\Models\Wishlist;
use App\Policies\WishlistPolicy;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Wishlist::class, WishlistPolicy::class);

        // Rate limiting for group invitation related endpoints
        RateLimiter::for('group-invitations', function (Request $request) {
            $userId = optional($request->user())->id ?: 'guest';
            // Allow 20 actions per minute per user (link + create + resend + revoke grouped)
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('invitation:' . $userId);
        });

        // Additional protection: limit 2FA verification attempts independent of per-challenge attempt counter
        RateLimiter::for('2fa-verify', function (Request $request) {
            $userPart = optional($request->user())->id ?: $request->ip();
            // 15 verification submissions per minute (covers both success & failure) per user (fallback IP)
            return Limit::perMinute(15)->by('2fa-verify:' . $userPart);
        });

        \Inertia\Inertia::share('flash', function () {
            return [
                'success' => session('flash.success') ?? session('success'),
                'info' => session('flash.info') ?? session('info'),
                'error' => session('flash.error') ?? session('error'),
            ];
        });

        // Locale & translations no longer shared; handled entirely client-side via vue-i18n.

        // After auth (login/register), process pending invite token if present
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            $this->consumePendingInvite($event->user);
            $this->consumePendingShareLink($event->user);
        });
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Registered::class, function ($event) {
            $this->consumePendingInvite($event->user);
            $this->consumePendingShareLink($event->user);
        });
    }

    protected function consumePendingInvite($user): void
    {
        $token = session('pending_invite_token');
        if (!$token)
            return;
        session()->forget('pending_invite_token');
        $service = app(\App\Services\InvitationService::class);
        $invitation = $service->findByPlainToken($token);
        if (!$invitation)
            return; // invalid token
        if (strcasecmp($invitation->email, $user->email) !== 0) {
            // store mismatch flag for UI feedback maybe
            session(['invite_email_mismatch' => true]);
            // Ensure we correctly detect HTTPS behind a reverse proxy (e.g., Koyeb)
            // If APP_URL is set to https but generated asset URLs are http, it's usually because
            // the framework does not trust the X-Forwarded-Proto header or APP_URL is misconfigured.
            // Trust common proxy headers and force https scheme in production when request indicates it.
            if (app()->environment('production')) {
                // Trust proxy headers (keep it broad - platform sets X-Forwarded-* ).
                // In Laravel 11/12 minimal bootstrap, trusting proxies can be done via setTrustedProxies.
                try {
                    // Use available header constants; fallback to trusting X-Forwarded-Proto only
                    $headerSet = 0;
                    foreach (['HEADER_X_FORWARDED_AWS_ELB', 'HEADER_X_FORWARDED_ALL', 'HEADER_X_FORWARDED_FOR', 'HEADER_X_FORWARDED_HOST', 'HEADER_X_FORWARDED_PORT', 'HEADER_X_FORWARDED_PROTO'] as $c) {
                        if (defined(\Illuminate\Http\Request::class . '::' . $c)) {
                            $headerSet |= constant(\Illuminate\Http\Request::class . '::' . $c);
                        }
                    }
                    if ($headerSet === 0 && defined(\Illuminate\Http\Request::class . '::HEADER_X_FORWARDED_PROTO')) {
                        $headerSet = constant(\Illuminate\Http\Request::class . '::HEADER_X_FORWARDED_PROTO');
                    }
                    if ($headerSet) {
                        \Illuminate\Http\Request::setTrustedProxies(['*'], $headerSet);
                    }
                } catch (\Throwable $e) {
                    // Ignore; not critical
                }

                // Force URL generator to use https so @vite() and asset() links are https.
                if (config('app.url') && str_starts_with(config('app.url'), 'https://')) {
                    \Illuminate\Support\Facades\URL::forceScheme('https');
                }
            }
            return;
        }
        if (!$invitation->accepted_at && !$invitation->declined_at && !$invitation->isExpired()) {
            $service->accept($invitation, $user);
            // Mark a session key so redirect logic in auth controllers can send user to onboarding
            session(['just_accepted_group_id' => $invitation->group_id]);
        }
    }

    protected function consumePendingShareLink($user): void
    {
        $token = session('pending_share_token');
        if (!$token)
            return;
        session()->forget('pending_share_token');
        $shareService = app(\App\Services\ShareLinkService::class);
        $group = $shareService->findGroupByPlainToken($token);
        if (!$group)
            return; // invalid/rotated
        if ($group->owner_id === $user->id)
            return; // owner não cria join request
        if ($group->isParticipant($user))
            return; // já participa
        // Evitar duplicidade
        $existing = \App\Models\GroupJoinRequest::where('group_id', $group->id)->where('user_id', $user->id)->first();
        if ($existing)
            return;
        // Precisamos do id do share link para attribution
        $hashed = hash('sha256', $token);
        $shareLink = $shareService->findLinkByPlainToken($token);
        $jr = \App\Models\GroupJoinRequest::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'share_link_id' => optional($shareLink)->id,
        ]);
        // Armazena para feedback (lista de IDs) e flash
        $pending = session('just_requested_join_groups', []);
        $pending[] = $group->id;
        session(['just_requested_join_groups' => $pending]);
        session(['flash.success' => 'Pedido de entrada enviado para o grupo: ' . $group->name]);
    }
}
