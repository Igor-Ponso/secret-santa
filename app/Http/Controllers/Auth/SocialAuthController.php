<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAccountService;
use Illuminate\Http\RedirectResponse;

/**
 * OAuth redirect + callback endpoints for supported social providers.
 */
class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the provider authorization screen.
     */
    public function redirect(string $provider, SocialAccountService $service): RedirectResponse
    {
        return $service->redirectToProvider($provider);
    }

    /**
     * Handle the OAuth provider callback.
     */
    public function callback(string $provider, SocialAccountService $service): RedirectResponse
    {
        return $service->handleProviderCallback($provider);
    }
}
