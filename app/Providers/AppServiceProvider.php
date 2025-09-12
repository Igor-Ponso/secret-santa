<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Group;
use App\Policies\GroupPolicy;
use App\Models\Wishlist;
use App\Policies\WishlistPolicy;

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
