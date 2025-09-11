<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        /** @var ProviderUser $socialUser */
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->findOrCreateUser($socialUser);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Find existing user or create a new one based on provider data.
     *
     * @param ProviderUser $providerUser
     * @return \App\Models\User
     */
    protected function findOrCreateUser(ProviderUser $providerUser): User
    {
        return User::firstOrCreate(
            ['email' => $providerUser->getEmail()],
            [
                'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'Anonymous',
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]
        );
    }
}
