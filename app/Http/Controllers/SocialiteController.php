<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to the OAuth provider.
     */
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth provider callback.
     */
    public function callback(string $provider)
    {
        //i am aware that this method will break if the user has the same email for two diferent providers, but right now there is only one provider.
        $this->validateProvider($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['error' => 'Failed to authenticate with ' . ucfirst($provider)]);
        }

        // Find or create user
        $user = User::firstOrCreate(
            [
                'oauth_provider' => $provider,
                'oauth_id' => $socialiteUser->getId()
            ],
            [
                'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname(),
                'email' => $socialiteUser->getEmail(),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Authenticate a local bypass user (development/testing only).
     */
    public function bypass(Request $request)
    {
        if (! $this->canUseBypassLogin()) {
            abort(404);
        }

        $email = (string) config('services.bypass_user.email');
        $name = (string) config('services.bypass_user.name', 'Bypass User');

        if (blank($email)) {
            return redirect()->route('login')->withErrors([
                'error' => 'Bypass user email is missing. Set BYPASS_USER_EMAIL in your .env file.',
            ]);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
            ]
        );

        if (is_null($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Validate the OAuth provider.
     */
    protected function validateProvider(string $provider): void
    {
        $allowedProviders = ['google'];

        if (!in_array($provider, $allowedProviders)) {
            abort(404, 'Invalid OAuth provider');
        }
    }

    protected function canUseBypassLogin(): bool
    {
        return app()->environment(['local', 'testing'])
            && (bool) config('services.bypass_user.enabled', false);
    }
}
