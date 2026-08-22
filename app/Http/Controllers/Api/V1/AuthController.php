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
use OpenApi\Attributes as OA;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly SocialAuthService $socialAuthService,
    ) {}

    #[OA\Post(
        path: '/auth/register',
        summary: 'Registrasi akun baru (email + password)',
        tags: ['Autentikasi'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 200, example: 'Budi Santoso'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'budi@example.com'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 20, example: '081234567890'),
                    new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true, example: '1998-05-10'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'rahasia123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Berhasil registrasi, langsung dapat token',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validasi gagal (mis. email sudah dipakai)'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toData());

        return ApiResponse::success([
            'user' => new UserResource($result->user),
            'token' => $result->token,
        ], status: 201);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login dengan email/telepon + password',
        description: 'BR-17: akun yang dibuat via Google (password null) ditolak dengan pesan jelas (kode `GOOGLE_ONLY_ACCOUNT`), bukan pesan generic.',
        tags: ['Autentikasi'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['identifier', 'password'],
                properties: [
                    new OA\Property(property: 'identifier', type: 'string', description: 'Email atau nomor telepon', example: 'budi@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'rahasia123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil login',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Kredensial salah (`INVALID_CREDENTIALS`)'),
            new OA\Response(response: 422, description: 'Akun ini terdaftar lewat Google (`GOOGLE_ONLY_ACCOUNT`)'),
        ]
    )]
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

    #[OA\Post(
        path: '/auth/google',
        summary: 'Login/register via Google (BR-16)',
        description: 'Client mengirim access_token hasil Google Sign-In (Socialite stateless). Find-or-create-or-link by google_id/email.',
        tags: ['Autentikasi'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['access_token'],
                properties: [
                    new OA\Property(property: 'access_token', type: 'string', example: 'ya29.a0AfH6...'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil login/register via Google',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 422, description: 'access_token tidak valid'),
        ]
    )]
    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->socialAuthService->loginWithGoogle($request->string('access_token')->toString());

        return ApiResponse::success([
            'user' => new UserResource($result->user),
            'token' => $result->token,
        ]);
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout (revoke token saat ini)',
        tags: ['Autentikasi'],
        responses: [
            new OA\Response(response: 200, description: 'Berhasil logout'),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, ['message' => 'Berhasil logout']);
    }

    #[OA\Get(
        path: '/auth/me',
        summary: 'Profil user yang sedang login',
        tags: ['Autentikasi'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    #[OA\Patch(
        path: '/auth/profile',
        summary: 'Update profil (nama, avatar, tanggal lahir)',
        tags: ['Autentikasi'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 200),
                new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
                new OA\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
            ])
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil update',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return ApiResponse::success(new UserResource($user));
    }
}
