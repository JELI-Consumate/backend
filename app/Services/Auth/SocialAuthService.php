<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\AuthResultData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

final readonly class SocialAuthService
{
    public function loginWithGoogle(string $accessToken): AuthResultData
    {
        $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);

        $user = DB::transaction(function () use ($googleUser): User {
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user === null) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'password' => null,
                ]);

                // `email_verified_at` isn't in User's #[Fillable] on purpose
                // (it must never be settable via mass assignment from
                // request input elsewhere), so passing it through
                // User::create() above silently drops it instead of saving
                // it — markEmailAsVerified() uses forceFill() and bypasses
                // that guard correctly, same as the existing-user branch
                // below.
                $user->markEmailAsVerified();

                return $user;
            }

            if ($user->google_id === null) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            // Google has already confirmed the user controls this mailbox,
            // so an existing local (password-based) account linked here
            // shouldn't stay stuck behind the email-verification gate.
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return $user;
        });

        return new AuthResultData($user, $user->createToken('auth')->plainTextToken);
    }
}
