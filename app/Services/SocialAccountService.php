<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;

/**
 * Handle user authentication via OAuth providers.
 */
class SocialAccountService
{
    /**
     * Redirect the user to the given provider.
     *
     * @param string $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the callback from the provider and log in the user.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback(string $provider)
    {
        try {
            $driver = Socialite::driver($provider); // base driver instance
            // Some providers need stateless for SPA / Inertia setups; call if available
            if (method_exists($driver, 'stateless')) { /** @phpstan-ignore-line */ $driver = $driver->stateless(); }
            /** @var ProviderUser $socialUser */
            $socialUser = $driver->user();
        } catch (\Throwable $e) {
            Log::warning('Social auth failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('login')->withErrors([
                'oauth' => 'Failed to login with ' . ucfirst($provider) . '.',
            ]);
        }

        $user = $this->findOrCreateUser($provider, $socialUser);

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    /**
     * Find existing user or create a new one based on provider data.
     *
     * @param ProviderUser $providerUser
     * @return \App\Models\User
     */
    protected function findOrCreateUser(string $provider, ProviderUser $providerUser): User
    {
        // Normalize email (some providers may not return one depending on scopes)
        $email = $providerUser->getEmail();
        if (!$email) {
            $email = $provider . '+' . $providerUser->getId() . '@oauth.local';
        }

        // If user already linked by provider id
        if ($providerUser->getId()) {
            $existing = User::where('provider_name', $provider)
                ->where('provider_id', $providerUser->getId())
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        // Fallback: match by email
        $user = User::where('email', $email)->first();

        if ($user) {
            // Attach provider details if missing
            if (!$user->provider_name || !$user->provider_id) {
                $user->forceFill([
                    'provider_name' => $provider,
                    'provider_id' => $providerUser->getId(),
                ])->save();
            }
            return $user;
        }

        // Create new
        return User::create([
            'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'Anonymous',
            'email' => $email,
            'password' => bcrypt(Str::random(32)),
            'email_verified_at' => now(),
            'provider_name' => $provider,
            'provider_id' => $providerUser->getId(),
        ]);
    }
}
