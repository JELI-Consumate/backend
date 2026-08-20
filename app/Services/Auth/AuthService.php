<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\AuthResultData;
use App\Data\Auth\RegisterData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

    public function findByIdentifier(string $identifier): ?User
    {
        return User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();
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
}
