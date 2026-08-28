<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
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

    /**
     * No token in the response on purpose — the app navigates straight to
     * the OTP-entry screen after this, and only gets a session from
     * verifyEmail() once the code is confirmed.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->toData());

        return ApiResponse::success([
            'user' => new UserResource($user),
        ], ['message' => 'Kode verifikasi telah dikirim ke email kamu.'], status: 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->findByEmail($request->string('email')->toString());

        if ($user === null || $user->password === null) {
            return ApiResponse::error(
                'Akun ini terdaftar lewat Google. Silakan masuk dengan Google.',
                422,
                code: 'GOOGLE_ONLY_ACCOUNT',
            );
        }

        $result = $this->authService->issueToken($user, $request->string('password')->toString());

        if ($result === AuthService::INVALID_CREDENTIALS) {
            return ApiResponse::error('Email atau kata sandi salah.', 401, code: 'INVALID_CREDENTIALS');
        }

        if ($result === AuthService::EMAIL_NOT_VERIFIED) {
            return ApiResponse::error(
                'Email kamu belum diverifikasi. Silakan cek inbox atau minta kirim ulang email verifikasi.',
                403,
                code: 'EMAIL_NOT_VERIFIED',
            );
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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetOtp($request->string('email')->toString());

        return ApiResponse::success(null, ['message' => 'Jika email terdaftar, kode reset kata sandi telah dikirim.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword(
            $request->string('email')->toString(),
            $request->string('otp')->toString(),
            $request->string('password')->toString(),
        );

        if ($result === AuthService::INVALID_RESET_OTP) {
            return ApiResponse::error('Kode reset kata sandi tidak valid atau sudah kedaluwarsa.', 422, code: 'INVALID_RESET_OTP');
        }

        return ApiResponse::success(null, ['message' => 'Kata sandi berhasil direset.']);
    }

    /**
     * No auth guard here on purpose — the user has no session/token yet at
     * this point, only the email+otp they just typed in the app.
     */
    public function verifyEmail(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            $request->string('email')->toString(),
            $request->string('otp')->toString(),
        );

        if ($result === AuthService::INVALID_OTP) {
            return ApiResponse::error('Kode OTP tidak valid atau sudah kedaluwarsa.', 422, code: 'INVALID_OTP');
        }

        return ApiResponse::success([
            'user' => new UserResource($result->user),
            'token' => $result->token,
        ]);
    }

    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $this->authService->resendOtp($request->string('email')->toString());

        return ApiResponse::success(null, ['message' => 'Jika email terdaftar dan belum diverifikasi, kode OTP baru telah dikirim.']);
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
