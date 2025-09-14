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
    }
}
