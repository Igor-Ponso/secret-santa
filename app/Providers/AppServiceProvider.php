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
