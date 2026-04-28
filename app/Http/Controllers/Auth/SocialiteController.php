<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to the provider's authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider's callback.
     *
     * @return RedirectResponse
     */
    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['error' => 'Failed to authenticate with '.$provider]);
        }

        $user = User::where('social_id', $socialUser->getId())
            ->where('social_type', $provider)
            ->first();

        if (! $user) {
            // Try to find by email if social_id doesn't match (for existing users linking their account)
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link account
                $user->update([
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                    'social_avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? $socialUser->getEmail(),
                    'email' => $socialUser->getEmail(),
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                    'social_avatar' => $socialUser->getAvatar(),
                    'password' => null,
                ]);
            }
        } else {
            // Update avatar if it changed
            $user->update([
                'social_avatar' => $socialUser->getAvatar(),
            ]);
        }

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}
