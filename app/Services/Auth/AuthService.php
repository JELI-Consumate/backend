<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\AuthResultData;
use App\Data\Auth\RegisterData;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final readonly class AuthService
{
    public function register(RegisterData $data): AuthResultData
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'date_of_birth' => $data->dateOfBirth,
            'password' => $data->password,
        ]);

        return new AuthResultData($user, $user->createToken('auth')->plainTextToken);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function issueToken(User $user, string $password): ?AuthResultData
    {
        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return new AuthResultData($user, $user->createToken('auth')->plainTextToken);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * @return string One of Password::PASSWORD_RESET or an error status constant.
     */
    public function resetPassword(string $email, string $token, string $password): string
    {
        return Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            },
        );
    }
}
