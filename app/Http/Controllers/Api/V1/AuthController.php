<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\SocialAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly SocialAuthService $socialAuthService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toData());

        return ApiResponse::success([
            'user' => new UserResource($result->user),
            'token' => $result->token,
        ], status: 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->findByIdentifier($request->string('identifier')->toString());

        if ($user === null || $user->password === null) {
            return ApiResponse::error(
                'Akun ini terdaftar lewat Google. Silakan masuk dengan Google.',
                422,
                code: 'GOOGLE_ONLY_ACCOUNT',
            );
        }

        $result = $this->authService->issueToken($user, $request->string('password')->toString());

        if ($result === null) {
            return ApiResponse::error('Email/telepon atau kata sandi salah.', 401, code: 'INVALID_CREDENTIALS');
        }

        return ApiResponse::success([
            'user' => new UserResource($result->user),
            'token' => $result->token,
        ]);
    }

    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->socialAuthService->loginWithGoogle($request->string('access_token')->toString());

        return ApiResponse::success([
            'user' => new UserResource($result->user),
            'token' => $result->token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, ['message' => 'Berhasil logout']);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return ApiResponse::success(new UserResource($user));
    }
}
