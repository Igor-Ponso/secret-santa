<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAccountService;
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{
    public function redirect(string $provider, SocialAccountService $service): RedirectResponse
    {
        return $service->redirectToProvider($provider);
    }

    public function callback(string $provider, SocialAccountService $service): RedirectResponse
    {
        return $service->handleProviderCallback($provider);
    }
}
